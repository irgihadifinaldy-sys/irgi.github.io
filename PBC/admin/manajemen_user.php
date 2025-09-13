<?php
// admin/manajemen_user.php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
include '../koneksi.php';
// Proses tambah user
if (isset($_POST['tambah'])) {
    $nis = $_POST['nis'];
    $nama = $_POST['nama'];
    $password = md5($_POST['password']);
    $role = $_POST['role'];
    $kelas_id = ($role == 'siswa') ? $_POST['kelas_id'] : null;
    
    // Buat username dari NIS
    $username = $nis;
    
    // Cek apakah username sudah ada
    $cek_username = "SELECT * FROM users WHERE username = ?";
    $stmt = mysqli_prepare($koneksi, $cek_username);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        header("Location: manajemen_user.php?error=3");
        exit();
    }
    
    $query = "INSERT INTO users (username, nis, nama, password, role, kelas_id) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "sssssi", $username, $nis, $nama, $password, $role, $kelas_id);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: manajemen_user.php?success=1");
    } else {
        header("Location: manajemen_user.php?error=1");
    }
    exit();
}
// Proses hapus user
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // Cek apakah user yang akan dihapus adalah admin yang sedang login
    if ($id == $_SESSION['user_id']) {
        header("Location: manajemen_user.php?error=4");
        exit();
    }
    
    $query = "DELETE FROM users WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Reset auto increment setelah penghapusan
        mysqli_query($koneksi, "ALTER TABLE users AUTO_INCREMENT = 1");
        header("Location: manajemen_user.php?success=2");
    } else {
        header("Location: manajemen_user.php?error=2");
    }
    exit();
}
// Ambil data user
$query = "SELECT u.id, u.nis, u.nama, u.role, k.kelas 
          FROM users u 
          LEFT JOIN kelas k ON u.kelas_id = k.id 
          ORDER BY u.role, u.nama";
$result = mysqli_query($koneksi, $query);
// Ambil data kelas
$query_kelas = "SELECT * FROM kelas ORDER BY kelas";
$kelas_result = mysqli_query($koneksi, $query_kelas);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User</title>
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
        .kelas-field {
            display: none;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .no-wrap {
            white-space: nowrap;
        }
        .card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .btn {
            border-radius: 4px;
        }
        /* Custom Toast Styles */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
        .toast {
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }
        .toast.show {
            opacity: 1;
        }
        .toast-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0,0,0,.1);
        }
        .toast-success .toast-header {
            color: #0f5132;
            background-color: #d1e7dd;
            border-color: #badbcc;
        }
        .toast-error .toast-header {
            color: #842029;
            background-color: #f8d7da;
            border-color: #f5c2c7;
        }
        .toast-success .toast-body {
            background-color: #d1e7dd;
        }
        .toast-error .toast-body {
            background-color: #f8d7da;
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
                            <a href="manajemen_user.php" class="nav-link active">
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
                    <h2>Manajemen User</h2>
                    <div class="text-muted">
                        <i class="bi bi-person-circle me-1"></i> 
                        <?php echo $_SESSION['nama']; ?> | 
                        <span class="badge bg-primary"><?php echo ucfirst($_SESSION['role']); ?></span>
                    </div>
                </div>
                
                <!-- Form Tambah User -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Tambah User Baru</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nama" class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" id="nama" name="nama" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="nis" class="form-label">NIS</label>
                                    <input type="text" class="form-control" id="nis" name="nis" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="">Pilih Role</option>
                                        <option value="admin">Admin</option>
                                        <option value="guru">Guru</option>
                                        <option value="siswa">Siswa</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3 kelas-field" id="kelas-field">
                                    <label for="kelas_id" class="form-label">Kelas</label>
                                    <select class="form-select" id="kelas_id" name="kelas_id">
                                        <option value="">Pilih Kelas</option>
                                        <?php 
                                        // Reset pointer hasil query kelas
                                        mysqli_data_seek($kelas_result, 0);
                                        while ($kelas = mysqli_fetch_assoc($kelas_result)): ?>
                                            <option value="<?php echo $kelas['id']; ?>"><?php echo $kelas['kelas']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="tambah" class="btn btn-primary">
                                        <i class="bi bi-plus-circle me-1"></i> Tambah User
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Tabel User -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Daftar User</h5>
                        <div>
                            <span class="badge bg-primary"><?php echo mysqli_num_rows($result); ?> User</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th class="no-wrap">No</th>
                                        <th class="no-wrap">NIS</th>
                                        <th>Nama</th>
                                        <th>Role</th>
                                        <th>Kelas</th>
                                        <th class="no-wrap">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    while ($user = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td class="no-wrap"><?php echo $no++; ?></td>
                                            <td class="no-wrap"><?php echo $user['nis']; ?></td>
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
                                            <td class="no-wrap">
                                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="manajemen_user.php?hapus=<?php echo $user['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Yakin ingin menghapus user ini?')" 
                                                   title="Hapus">
                                                    <i class="bi bi-trash"></i>
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

    <!-- Toast Container -->
    <div class="toast-container"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.getElementById('role');
            const kelasField = document.getElementById('kelas-field');
            const kelasSelect = document.getElementById('kelas_id');
            const toastContainer = document.querySelector('.toast-container');
            
            // Fungsi untuk menampilkan toast
            function showToast(message, type = 'success') {
                const toastId = 'toast-' + Date.now();
                const toastClass = type === 'success' ? 'toast-success' : 'toast-error';
                const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
                
                const toastHTML = `
                    <div id="${toastId}" class="toast ${toastClass}" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header">
                            <i class="bi ${icon} me-2"></i>
                            <strong class="me-auto">Notifikasi</strong>
                            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            ${message}
                        </div>
                    </div>
                `;
                
                toastContainer.insertAdjacentHTML('beforeend', toastHTML);
                
                const toastElement = document.getElementById(toastId);
                const toast = new bootstrap.Toast(toastElement, {
                    autohide: true,
                    delay: 5000
                });
                
                toast.show();
                
                // Hapus elemen toast setelah disembunyikan
                toastElement.addEventListener('hidden.bs.toast', function() {
                    toastElement.remove();
                });
            }
            
            // Tampilkan toast jika ada parameter success atau error
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.has('success')) {
                const successCode = urlParams.get('success');
                let message = '';
                
                if (successCode === '1') {
                    message = 'User berhasil ditambahkan!';
                } else if (successCode === '2') {
                    message = 'User berhasil dihapus!';
                }
                
                if (message) {
                    // Tampilkan toast setelah halaman dimuat
                    setTimeout(() => {
                        showToast(message, 'success');
                        // Hapus parameter URL setelah toast ditampilkan
                        const newUrl = window.location.pathname;
                        window.history.replaceState({}, document.title, newUrl);
                    }, 500);
                }
            }
            
            if (urlParams.has('error')) {
                const errorCode = urlParams.get('error');
                let message = '';
                
                if (errorCode === '1') {
                    message = 'Gagal menambahkan user!';
                } else if (errorCode === '2') {
                    message = 'Gagal menghapus user!';
                } else if (errorCode === '3') {
                    message = 'NIS sudah digunakan, silakan gunakan NIS lain!';
                } else if (errorCode === '4') {
                    message = 'Tidak dapat menghapus akun Anda sendiri!';
                }
                
                if (message) {
                    // Tampilkan toast setelah halaman dimuat
                    setTimeout(() => {
                        showToast(message, 'error');
                        // Hapus parameter URL setelah toast ditampilkan
                        const newUrl = window.location.pathname;
                        window.history.replaceState({}, document.title, newUrl);
                    }, 500);
                }
            }
            
            // Fungsi untuk menampilkan/menyembunyikan field kelas
            function toggleKelasField() {
                if (roleSelect.value === 'siswa') {
                    kelasField.style.display = 'block';
                    kelasSelect.setAttribute('required', '');
                } else {
                    kelasField.style.display = 'none';
                    kelasSelect.removeAttribute('required');
                    kelasSelect.value = ''; // Reset nilai kelas saat role bukan siswa
                }
            }
            
            // Panggil fungsi saat halaman dimuat
            toggleKelasField();
            
            // Panggil fungsi saat role berubah
            roleSelect.addEventListener('change', toggleKelasField);
        });
    </script>
</body>
</html>