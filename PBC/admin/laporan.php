<?php
// admin/laporan.php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
include '../koneksi.php';

// Ambil data untuk laporan
$query_users = "SELECT u.id, u.nis, u.nama, u.role, k.kelas 
               FROM users u 
               LEFT JOIN kelas k ON u.kelas_id = k.id 
               ORDER BY u.role, u.nama";
$result_users = mysqli_query($koneksi, $query_users);

$query_kelas = "SELECT k.id, k.kelas, COUNT(u.id) AS jumlah_siswa 
               FROM kelas k 
               LEFT JOIN users u ON k.id = u.kelas_id AND u.role = 'siswa'
               GROUP BY k.id, k.kelas
               ORDER BY k.kelas";
$result_kelas = mysqli_query($koneksi, $query_kelas);

// Ambil data rekap kehadiran
$query_rekap = "SELECT u.id, u.nama, u.nis, k.kelas,
                SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) AS hadir,
                SUM(CASE WHEN a.status = 'izin' THEN 1 ELSE 0 END) AS izin,
                SUM(CASE WHEN a.status = 'sakit' THEN 1 ELSE 0 END) AS sakit,
                SUM(CASE WHEN a.status = 'alpha' THEN 1 ELSE 0 END) AS alpha
                FROM users u
                LEFT JOIN kelas k ON u.kelas_id = k.id
                LEFT JOIN absensi a ON u.id = a.user_id
                WHERE u.role = 'siswa'
                GROUP BY u.id, u.nama, u.nis, k.kelas
                ORDER BY k.kelas, u.nama";
$result_rekap = mysqli_query($koneksi, $query_rekap);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan</title>
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
                            <a href="absensi.php" class="nav-link">
                                <i class="bi bi-calendar-check me-2"></i> Absensi
                            </a>
                        </li>
                        <li>
                            <a href="laporan.php" class="nav-link active">
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
                    <h2>Laporan</h2>
                    <div class="text-muted">
                        <i class="bi bi-person-circle me-1"></i> 
                        <?php echo $_SESSION['nama']; ?> | 
                        <span class="badge bg-primary"><?php echo ucfirst($_SESSION['role']); ?></span>
                    </div>
                </div>
                
                <!-- Tabs -->
                <ul class="nav nav-tabs mb-4" id="laporanTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pengguna-tab" data-bs-toggle="tab" data-bs-target="#pengguna" type="button" role="tab" aria-controls="pengguna" aria-selected="true">Data Pengguna</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="kelas-tab" data-bs-toggle="tab" data-bs-target="#kelas" type="button" role="tab" aria-controls="kelas" aria-selected="false">Data Kelas</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="rekap-tab" data-bs-toggle="tab" data-bs-target="#rekap" type="button" role="tab" aria-controls="rekap" aria-selected="false">Rekap Kehadiran</button>
                    </li>
                </ul>
                
                <div class="tab-content" id="laporanTabsContent">
                    <!-- Tab Data Pengguna -->
                    <div class="tab-pane fade show active" id="pengguna" role="tabpanel" aria-labelledby="pengguna-tab">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5>Laporan Pengguna</h5>
                                <div class="no-print">
                                    <a href="export_excel.php?type=user" class="btn btn-sm btn-success me-2">
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
                                                <th>nis</th>
                                                <th>Nama</th>
                                                <th>Role</th>
                                                <th>Kelas</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($user = mysqli_fetch_assoc($result_users)): ?>
                                                <tr>
                                                    <td><?php echo $user['id']; ?></td>
                                                    <td><?php echo $user['nis']; ?></td>
                                                    <td><?php echo $user['nama']; ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $user['role'] == 'admin' ? 'danger' : 
                                                                 ($user['role'] == 'guru' ? 'warning' : 'info'); 
                                                        ?>">
                                                            <?php echo ucfirst($user['role']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $user['kelas'] ? $user['kelas'] : '-'; ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab Data Kelas -->
                    <div class="tab-pane fade" id="kelas" role="tabpanel" aria-labelledby="kelas-tab">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5>Laporan Kelas</h5>
                                <div class="no-print">
                                    <a href="export_excel.php?type=kelas" class="btn btn-sm btn-success me-2">
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
                                                <th>Nama Kelas</th>
                                                <th>Jumlah Siswa</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($kelas = mysqli_fetch_assoc($result_kelas)): ?>
                                                <tr>
                                                    <td><?php echo $kelas['id']; ?></td>
                                                    <td><?php echo $kelas['kelas']; ?></td>
                                                    <td>
                                                        <span class="badge bg-primary"><?php echo $kelas['jumlah_siswa']; ?></span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
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
                                    <a href="export_excel.php?type=rekap" class="btn btn-sm btn-success me-2">
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
                                                <th class="no-print">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($rekap = mysqli_fetch_assoc($result_rekap)): ?>
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
                                                    <td class="no-print">
                                                        <a href="detail_absensi.php?id=<?php echo $rekap['id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="bi bi-eye"></i> Detail
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>