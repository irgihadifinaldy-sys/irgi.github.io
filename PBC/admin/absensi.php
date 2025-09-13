<?php
// admin/absensi.php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
include '../koneksi.php';

// Proses tambah absensi
if (isset($_POST['tambah_absensi'])) {
    $user_id = $_POST['user_id'];
    $tanggal = $_POST['tanggal'];
    $status = $_POST['status'];
    $keterangan = $_POST['keterangan'];
    
    // Cek apakah absensi sudah ada untuk user dan tanggal yang sama
    $cek_absensi = "SELECT id FROM absensi WHERE user_id = ? AND tanggal = ?";
    $stmt = mysqli_prepare($koneksi, $cek_absensi);
    mysqli_stmt_bind_param($stmt, "is", $user_id, $tanggal);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        header("Location: absensi.php?error=4");
        exit();
    }
    
    $query = "INSERT INTO absensi (user_id, tanggal, status, keterangan) 
              VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "isss", $user_id, $tanggal, $status, $keterangan);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: absensi.php?success=1");
    } else {
        header("Location: absensi.php?error=1");
    }
    exit();
}

// Proses hapus absensi
if (isset($_GET['hapus_absensi'])) {
    $id = $_GET['hapus_absensi'];
    $query = "DELETE FROM absensi WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: absensi.php?success=2");
    } else {
        header("Location: absensi.php?error=2");
    }
    exit();
}

// Ambil data untuk filter
$query_users = "SELECT u.id, u.nis, u.nama, u.role, k.kelas 
               FROM users u 
               LEFT JOIN kelas k ON u.kelas_id = k.id 
               WHERE u.role = 'siswa'
               ORDER BY k.kelas, u.nama";
$result_users = mysqli_query($koneksi, $query_users);

$query_kelas = "SELECT k.id, k.kelas 
               FROM kelas k 
               ORDER BY k.kelas";
$result_kelas = mysqli_query($koneksi, $query_kelas);

// Filter data
$filter_kelas = isset($_GET['kelas_id']) ? $_GET['kelas_id'] : '';
$filter_tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01');
$filter_tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Query untuk data absensi dengan filter
$query_absensi = "SELECT a.id, a.tanggal, a.status, a.keterangan, u.nama, u.nis, k.kelas 
                 FROM absensi a 
                 JOIN users u ON a.user_id = u.id 
                 LEFT JOIN kelas k ON u.kelas_id = k.id 
                 WHERE u.role = 'siswa'";
                 
if (!empty($filter_kelas)) {
    $query_absensi .= " AND k.id = $filter_kelas";
}

if (!empty($filter_tanggal_awal) && !empty($filter_tanggal_akhir)) {
    $query_absensi .= " AND a.tanggal BETWEEN '$filter_tanggal_awal' AND '$filter_tanggal_akhir'";
}

if (!empty($filter_status)) {
    $query_absensi .= " AND a.status = '$filter_status'";
}

$query_absensi .= " ORDER BY a.tanggal DESC, k.kelas, u.nama";
$result_absensi = mysqli_query($koneksi, $query_absensi);

// Ambil data rekap kehadiran
$query_rekap = "SELECT u.id, u.nama, u.nis, k.kelas,
                SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) AS hadir,
                SUM(CASE WHEN a.status = 'izin' THEN 1 ELSE 0 END) AS izin,
                SUM(CASE WHEN a.status = 'sakit' THEN 1 ELSE 0 END) AS sakit,
                SUM(CASE WHEN a.status = 'alpha' THEN 1 ELSE 0 END) AS alpha,
                COUNT(a.id) AS total
                FROM users u
                LEFT JOIN kelas k ON u.kelas_id = k.id
                LEFT JOIN absensi a ON u.id = a.user_id
                WHERE u.role = 'siswa'";
                
if (!empty($filter_kelas)) {
    $query_rekap .= " AND k.id = $filter_kelas";
}

if (!empty($filter_tanggal_awal) && !empty($filter_tanggal_akhir)) {
    $query_rekap .= " AND (a.tanggal BETWEEN '$filter_tanggal_awal' AND '$filter_tanggal_akhir' OR a.tanggal IS NULL)";
}

$query_rekap .= " GROUP BY u.id, u.nama, u.nis, k.kelas
                ORDER BY k.kelas, u.nama";
$result_rekap = mysqli_query($koneksi, $query_rekap);

// Statistik kehadiran
$query_statistik = "SELECT 
                    SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) AS hadir,
                    SUM(CASE WHEN a.status = 'izin' THEN 1 ELSE 0 END) AS izin,
                    SUM(CASE WHEN a.status = 'sakit' THEN 1 ELSE 0 END) AS sakit,
                    SUM(CASE WHEN a.status = 'alpha' THEN 1 ELSE 0 END) AS alpha,
                    COUNT(a.id) AS total
                    FROM absensi a
                    JOIN users u ON a.user_id = u.id
                    WHERE u.role = 'siswa'";
                    
if (!empty($filter_kelas)) {
    $query_statistik .= " AND u.kelas_id = $filter_kelas";
}

if (!empty($filter_tanggal_awal) && !empty($filter_tanggal_akhir)) {
    $query_statistik .= " AND a.tanggal BETWEEN '$filter_tanggal_awal' AND '$filter_tanggal_akhir'";
}

$result_statistik = mysqli_query($koneksi, $query_statistik);
$statistik = mysqli_fetch_assoc($result_statistik);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
        }
        .sidebar .nav-link {
            color: #adb5bd;
        }
        .sidebar .nav-link:hover {
            color: #fff;
        }
        .sidebar .nav-link.active {
            color: #fff;
            background-color: #007bff;
        }
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
        }
        .nav-tabs .nav-link {
            color: #495057;
        }
        .nav-tabs .nav-link.active {
            font-weight: bold;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .table-responsive {
                overflow: visible !important;
                max-height: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="d-flex flex-column p-3 text-white">
                    <h4 class="mb-4">Admin Panel</h4>
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link">
                                <i class="bi bi-speedometer2 me-2"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="manajemen_user.php" class="nav-link">
                                <i class="bi bi-people me-2"></i> Manajemen User
                            </a>
                        </li>
                        <li>
                            <a href="manajemen_kelas.php" class="nav-link">
                                <i class="bi bi-building me-2"></i> Manajemen Kelas
                            </a>
                        </li>
                        <li>
                            <a href="absensi.php" class="nav-link active">
                                <i class="bi bi-calendar-check me-2"></i> Absensi
                            </a>
                        </li>
                        <li>
                            <a href="laporan.php" class="nav-link">
                                <i class="bi bi-file-earmark-text me-2"></i> Laporan
                            </a>
                        </li>
                        <li>
                            <a href="../logout.php" class="nav-link">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Absensi</h2>
                    <div class="text-muted">
                        <i class="bi bi-person-circle me-1"></i> 
                        <?php echo $_SESSION['nama']; ?> | 
                        <span class="badge bg-primary"><?php echo ucfirst($_SESSION['role']); ?></span>
                    </div>
                </div>
                
                <!-- Alert Success -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        if ($_GET['success'] == '1') echo 'Absensi berhasil ditambahkan!';
                        elseif ($_GET['success'] == '2') echo 'Absensi berhasil dihapus!';
                        elseif ($_GET['success'] == '3') {
                            $imported = isset($_GET['imported']) ? $_GET['imported'] : 0;
                            $errors = isset($_GET['errors']) ? $_GET['errors'] : 0;
                            echo "Data berhasil diimpor! Berhasil: {$imported}, Gagal: {$errors}";
                        }
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Alert Error -->
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        if ($_GET['error'] == '1') echo 'Gagal menambahkan absensi!';
                        elseif ($_GET['error'] == '2') echo 'Gagal menghapus absensi!';
                        elseif ($_GET['error'] == '3') echo 'Gagal mengimpor data! Pastikan format file sudah benar.';
                        elseif ($_GET['error'] == '4') echo 'Absensi untuk siswa dan tanggal tersebut sudah ada!';
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Filter -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Filter Data</h5>
                    </div>
                    <div class="card-body">
                        <form method="get" action="">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="kelas_id" class="form-label">Kelas</label>
                                    <select class="form-select" id="kelas_id" name="kelas_id">
                                        <option value="">Semua Kelas</option>
                                        <?php 
                                        // Reset pointer hasil query kelas
                                        mysqli_data_seek($result_kelas, 0);
                                        while ($kelas = mysqli_fetch_assoc($result_kelas)): ?>
                                            <option value="<?php echo $kelas['id']; ?>" 
                                                    <?php echo $filter_kelas == $kelas['id'] ? 'selected' : ''; ?>>
                                                <?php echo $kelas['kelas']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="tanggal_awal" class="form-label">Tanggal Awal</label>
                                    <input type="date" class="form-control" id="tanggal_awal" name="tanggal_awal" 
                                           value="<?php echo $filter_tanggal_awal; ?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                                    <input type="date" class="form-control" id="tanggal_akhir" name="tanggal_akhir" 
                                           value="<?php echo $filter_tanggal_akhir; ?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">Semua Status</option>
                                        <option value="hadir" <?php echo $filter_status == 'hadir' ? 'selected' : ''; ?>>Hadir</option>
                                        <option value="izin" <?php echo $filter_status == 'izin' ? 'selected' : ''; ?>>Izin</option>
                                        <option value="sakit" <?php echo $filter_status == 'sakit' ? 'selected' : ''; ?>>Sakit</option>
                                        <option value="alpha" <?php echo $filter_status == 'alpha' ? 'selected' : ''; ?>>Alpha</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-filter me-1"></i> Filter
                                    </button>
                                    <a href="absensi.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-clockwise me-1"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Statistik Kehadiran -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Hadir</h6>
                                        <h2><?php echo $statistik['hadir']; ?></h2>
                                        <small>
                                            <?php 
                                            $persen_hadir = $statistik['total'] > 0 ? 
                                                round(($statistik['hadir'] / $statistik['total']) * 100, 1) : 0;
                                            echo $persen_hadir . '%';
                                            ?>
                                        </small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-check-circle-fill fs-1"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Izin</h6>
                                        <h2><?php echo $statistik['izin']; ?></h2>
                                        <small>
                                            <?php 
                                            $persen_izin = $statistik['total'] > 0 ? 
                                                round(($statistik['izin'] / $statistik['total']) * 100, 1) : 0;
                                            echo $persen_izin . '%';
                                            ?>
                                        </small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-file-text-fill fs-1"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Sakit</h6>
                                        <h2><?php echo $statistik['sakit']; ?></h2>
                                        <small>
                                            <?php 
                                            $persen_sakit = $statistik['total'] > 0 ? 
                                                round(($statistik['sakit'] / $statistik['total']) * 100, 1) : 0;
                                            echo $persen_sakit . '%';
                                            ?>
                                        </small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-thermometer-half fs-1"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card bg-danger text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Alpha</h6>
                                        <h2><?php echo $statistik['alpha']; ?></h2>
                                        <small>
                                            <?php 
                                            $persen_alpha = $statistik['total'] > 0 ? 
                                                round(($statistik['alpha'] / $statistik['total']) * 100, 1) : 0;
                                            echo $persen_alpha . '%';
                                            ?>
                                        </small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-x-circle-fill fs-1"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabs -->
                <ul class="nav nav-tabs mb-4" id="absensiTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="data-absensi-tab" data-bs-toggle="tab" data-bs-target="#data-absensi" type="button" role="tab" aria-controls="data-absensi" aria-selected="true">Data Absensi</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="rekap-tab" data-bs-toggle="tab" data-bs-target="#rekap" type="button" role="tab" aria-controls="rekap" aria-selected="false">Rekap Kehadiran</button>
                    </li>
                </ul>
                
                <div class="tab-content" id="absensiTabsContent">
                    <!-- Tab Data Absensi -->
                    <div class="tab-pane fade show active" id="data-absensi" role="tabpanel" aria-labelledby="data-absensi-tab">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5>Data Absensi</h5>
                                <div class="no-print">
                                    <button type="button" class="btn btn-sm btn-primary me-2" data-bs-toggle="modal" data-bs-target="#tambahAbsensiModal">
                                        <i class="bi bi-plus-circle me-1"></i> Tambah Absensi
                                    </button>
                                    <button type="button" class="btn btn-sm btn-info me-2" data-bs-toggle="modal" data-bs-target="#importModal">
                                        <i class="bi bi-file-earmark-arrow-up me-1"></i> Import Excel
                                    </button>
                                    <a href="export_excel.php?type=absensi&kelas_id=<?php echo $filter_kelas; ?>&tanggal_awal=<?php echo $filter_tanggal_awal; ?>&tanggal_akhir=<?php echo $filter_tanggal_akhir; ?>&status=<?php echo $filter_status; ?>" class="btn btn-sm btn-success me-2">
                                        <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                                    </a>
                                    <button class="btn btn-sm btn-primary" onclick="window.print()">
                                        <i class="bi bi-printer me-1"></i> Cetak
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Tanggal</th>
                                                <th>nis</th>
                                                <th>Nama</th>
                                                <th>Kelas</th>
                                                <th>Status</th>
                                                <th>Keterangan</th>
                                                <th class="no-print">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (mysqli_num_rows($result_absensi) > 0): ?>
                                                <?php while ($absensi = mysqli_fetch_assoc($result_absensi)): ?>
                                                    <tr>
                                                        <td><?php echo $absensi['id']; ?></td>
                                                        <td><?php echo date('d-m-Y', strtotime($absensi['tanggal'])); ?></td>
                                                        <td><?php echo $absensi['nis']; ?></td>
                                                        <td><?php echo $absensi['nama']; ?></td>
                                                        <td><?php echo $absensi['kelas'] ? $absensi['kelas'] : '-'; ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo $absensi['status'] == 'hadir' ? 'success' : 
                                                                     ($absensi['status'] == 'izin' ? 'warning' : 
                                                                     ($absensi['status'] == 'sakit' ? 'info' : 'danger')); 
                                                            ?>">
                                                                <?php echo ucfirst($absensi['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo $absensi['keterangan'] ? $absensi['keterangan'] : '-'; ?></td>
                                                        <td class="no-print">
                                                            <a href="edit_absensi.php?id=<?php echo $absensi['id']; ?>" class="btn btn-sm btn-warning">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <a href="absensi.php?hapus_absensi=<?php echo $absensi['id']; ?>" 
                                                               class="btn btn-sm btn-danger" 
                                                               onclick="return confirm('Yakin ingin menghapus absensi ini?')">
                                                                <i class="bi bi-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="8" class="text-center">Tidak ada data absensi</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab Rekap Kehadiran -->
                    <div class="tab-pane fade" id="rekap" role="tabpanel" aria-labelledby="rekap-tab">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5>Rekap Kehadiran Siswa</h5>
                                <div class="no-print">
                                    <a href="export_excel.php?type=rekap&kelas_id=<?php echo $filter_kelas; ?>&tanggal_awal=<?php echo $filter_tanggal_awal; ?>&tanggal_akhir=<?php echo $filter_tanggal_akhir; ?>" class="btn btn-sm btn-success me-2">
                                        <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                                    </a>
                                    <button class="btn btn-sm btn-primary" onclick="window.print()">
                                        <i class="bi bi-printer me-1"></i> Cetak
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>nis</th>
                                                <th>Nama</th>
                                                <th>Kelas</th>
                                                <th>Hadir</th>
                                                <th>Izin</th>
                                                <th>Sakit</th>
                                                <th>Alpha</th>
                                                <th>Total</th>
                                                <th>Presentase Hadir</th>
                                                <th class="no-print">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (mysqli_num_rows($result_rekap) > 0): ?>
                                                <?php while ($rekap = mysqli_fetch_assoc($result_rekap)): 
                                                    $presentase = $rekap['total'] > 0 ? round(($rekap['hadir'] / $rekap['total']) * 100, 1) : 0;
                                                ?>
                                                    <tr>
                                                        <td><?php echo $rekap['nis']; ?></td>
                                                        <td><?php echo $rekap['nama']; ?></td>
                                                        <td><?php echo $rekap['kelas'] ? $rekap['kelas'] : '-'; ?></td>
                                                        <td>
                                                            <span class="badge bg-success"><?php echo $rekap['hadir']; ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-warning"><?php echo $rekap['izin']; ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-info"><?php echo $rekap['sakit']; ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-danger"><?php echo $rekap['alpha']; ?></span>
                                                        </td>
                                                        <td><?php echo $rekap['total']; ?></td>
                                                        <td>
                                                            <div class="progress" style="height: 20px;">
                                                                <div class="progress-bar bg-success" role="progressbar" 
                                                                    style="width: <?php echo $presentase; ?>%;" 
                                                                    aria-valuenow="<?php echo $presentase; ?>" 
                                                                    aria-valuemin="0" aria-valuemax="100">
                                                                    <?php echo $presentase; ?>%
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="no-print">
                                                            <a href="detail_absensi.php?id=<?php echo $rekap['id']; ?>" class="btn btn-sm btn-info">
                                                                <i class="bi bi-eye"></i> Detail
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="10" class="text-center">Tidak ada data rekap kehadiran</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Absensi -->
    <div class="modal fade" id="tambahAbsensiModal" tabindex="-1" aria-labelledby="tambahAbsensiModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tambahAbsensiModalLabel">Tambah Absensi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Siswa</label>
                            <select class="form-select" id="user_id" name="user_id" required>
                                <option value="">Pilih Siswa</option>
                                <?php 
                                // Reset pointer hasil query users
                                mysqli_data_seek($result_users, 0);
                                while ($user = mysqli_fetch_assoc($result_users)): ?>
                                    <option value="<?php echo $user['id']; ?>"><?php echo $user['nama']; ?> (<?php echo $user['nis']; ?>) - <?php echo $user['kelas']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="tanggal" class="form-label">Tanggal</label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" required>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="">Pilih Status</option>
                                <option value="hadir">Hadir</option>
                                <option value="izin">Izin</option>
                                <option value="sakit">Sakit</option>
                                <option value="alpha">Alpha</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_absensi" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Import Excel -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">Import Data Absensi dari Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="import_excel.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="file_excel" class="form-label">File Excel</label>
                            <input type="file" class="form-control" id="file_excel" name="file_excel" accept=".xlsx, .xls" required>
                            <div class="form-text">Format file: .xlsx atau .xls</div>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-1"></i>
                            Pastikan file Excel memiliki format: nis, Tanggal, Status, Keterangan
                        </div>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Status harus berisi: hadir, izin, sakit, atau alpha
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set tanggal hari ini sebagai default
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('tanggal').value = today;
        });
    </script>
</body>
</html>