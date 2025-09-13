<?php
// admin/edit_kelas.php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
include '../koneksi.php';

// Cek apakah parameter ID ada
if (!isset($_GET['id'])) {
    header("Location: manajemen_kelas.php");
    exit();
}

$id = $_GET['id'];

// Ambil data kelas berdasarkan ID
$query = "SELECT * FROM kelas WHERE id = ?";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Cek apakah kelas ditemukan
if (mysqli_num_rows($result) == 0) {
    header("Location: manajemen_kelas.php?error=1");
    exit();
}

$kelas = mysqli_fetch_assoc($result);

// Proses update kelas
if (isset($_POST['update'])) {
    $nama_kelas = $_POST['kelas'];
    
    // Validasi input
    if (empty($nama_kelas)) {
        header("Location: edit_kelas.php?id=$id&error=1");
        exit();
    }
    
    // Update data kelas
    $query_update = "UPDATE kelas SET kelas = ? WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $query_update);
    mysqli_stmt_bind_param($stmt, "si", $nama_kelas, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: manajemen_kelas.php?success=2");
    } else {
        header("Location: edit_kelas.php?id=$id&error=2");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kelas</title>
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
                            <a href="manajemen_kelas.php" class="nav-link active">
                                <i class="bi bi-building me-2"></i> Manajemen Kelas
                            </a>
                        </li>
                        <li>
                            <a href="absensi.php" class="nav-link">
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
                    <h2>Edit Kelas</h2>
                    <div class="text-muted">
                        <i class="bi bi-person-circle me-1"></i> 
                        <?php echo $_SESSION['nama']; ?> | 
                        <span class="badge bg-primary"><?php echo ucfirst($_SESSION['role']); ?></span>
                    </div>
                </div>
                
                <!-- Alert Error -->
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        if ($_GET['error'] == '1') {
                            echo 'Nama kelas tidak boleh kosong!';
                        } elseif ($_GET['error'] == '2') {
                            echo 'Gagal mengupdate data kelas!';
                        }
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Form Edit Kelas -->
                <div class="card">
                    <div class="card-header">
                        <h5>Edit Data Kelas</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="kelas" class="form-label">Nama Kelas</label>
                                <input type="text" class="form-control" id="kelas" name="kelas" 
                                       value="<?php echo htmlspecialchars($kelas['kelas']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="confirmUpdate" required>
                                    <label class="form-check-label" for="confirmUpdate">
                                        Saya yakin ingin mengubah data kelas ini
                                    </label>
                                </div>
                                <div class="form-text">
                                    Perubahan nama kelas akan mempengaruhi data siswa yang terkait.
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="manajemen_kelas.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-1"></i> Kembali
                                </a>
                                <button type="submit" name="update" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-1"></i> Update Kelas
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Info Siswa Terkait -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Siswa dalam Kelas Ini</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Ambil data siswa dalam kelas ini
                        $query_siswa = "SELECT id, nis, nama FROM users WHERE kelas_id = ? AND role = 'siswa' ORDER BY nama";
                        $stmt = mysqli_prepare($koneksi, $query_siswa);
                        mysqli_stmt_bind_param($stmt, "i", $id);
                        mysqli_stmt_execute($stmt);
                        $result_siswa = mysqli_stmt_get_result($stmt);
                        
                        if (mysqli_num_rows($result_siswa) > 0):
                        ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>NIS</th>
                                            <th>Nama</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($siswa = mysqli_fetch_assoc($result_siswa)): ?>
                                            <tr>
                                                <td><?php echo $siswa['nis']; ?></td>
                                                <td><?php echo $siswa['nama']; ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-1"></i> Tidak ada siswa dalam kelas ini.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>