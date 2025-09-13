<?php
// admin/manajemen_kelas.php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
include '../koneksi.php';

// Mapping role
$roleDisplay = [
    'admin' => 'Admin',
    'guru' => 'Guru',
    'siswa' => 'Siswa'
];
$roleBadge = $roleDisplay[$_SESSION['role'] ?? ''] ?? 'User';

// Proses tambah kelas
if (isset($_POST['tambah'])) {
    $nama_kelas = $_POST['kelas'];
    if (empty($nama_kelas)) { header("Location: manajemen_kelas.php?error=1"); exit(); }

    $cek_kelas = "SELECT * FROM kelas WHERE kelas = ?";
    $stmt = mysqli_prepare($koneksi, $cek_kelas);
    mysqli_stmt_bind_param($stmt, "s", $nama_kelas);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) { header("Location: manajemen_kelas.php?error=2"); exit(); }

    $query = "INSERT INTO kelas (kelas) VALUES (?)";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "s", $nama_kelas);
    
    if (mysqli_stmt_execute($stmt)) { header("Location: manajemen_kelas.php?success=1"); }
    else { header("Location: manajemen_kelas.php?error=3"); }
    exit();
}

// Proses hapus kelas
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $cek_siswa = "SELECT COUNT(*) as total FROM users WHERE kelas_id = ? AND role = 'siswa'";
    $stmt = mysqli_prepare($koneksi, $cek_siswa);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    
    if ($data['total'] > 0) { header("Location: manajemen_kelas.php?error=4"); exit(); }

    $query = "DELETE FROM kelas WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) { header("Location: manajemen_kelas.php?success=2"); }
    else { header("Location: manajemen_kelas.php?error=5"); }
    exit();
}

// Ambil data kelas
$query = "SELECT k.id, k.kelas, COUNT(u.id) AS jumlah_siswa 
          FROM kelas k 
          LEFT JOIN users u ON k.id = u.kelas_id AND u.role = 'siswa'
          GROUP BY k.id, k.kelas
          ORDER BY k.kelas";
$result = mysqli_query($koneksi, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manajemen Kelas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
body { background-color: #f8f9fa; }

/* Sidebar */
.sidebar { min-height: 100vh; background-color: #343a40; }
.sidebar .nav-link { color: #adb5bd; }
.sidebar .nav-link:hover { color: #fff; }
.sidebar .nav-link.active { color: #fff; background-color: #0d6efd; }

/* Animasi ngetik judul */
#judul { border-right: 2px solid #000; white-space: nowrap; overflow: hidden; font-weight: bold; font-size: 2rem; margin-bottom: 20px; }

/* Badge role */
.role-badge { padding: 5px 10px; font-weight: 500; border-radius: 12px; background-color: #0d6efd; color: #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.15); }

/* Animasi form tambah kelas */
@keyframes fadeInDown { 0% { opacity: 0; transform: translateY(-20px); } 100% { opacity: 1; transform: translateY(0); } }
#form-tambah-kelas { opacity: 0; transform: translateY(-20px); }
#form-tambah-kelas.show { animation: fadeInDown 0.5s forwards; }

/* Animasi baris tabel */
@keyframes fadeInUp { 0% { opacity: 0; transform: translateY(20px); } 100% { opacity: 1; transform: translateY(0); } }
.table tbody tr { opacity: 0; transform: translateY(20px); }
.table tbody tr.show { animation: fadeInUp 0.5s forwards; }

/* Animasi tombol aksi */
.action-btn { opacity: 0; transform: translateX(20px); transition: all 0.3s ease; }
tr.show .action-btn { opacity: 1; transform: translateX(0); transition-delay: 0.3s; }

/* Badge jumlah siswa */
.badge { opacity: 0; transform: scale(0.8); transition: all 0.3s ease; }
tr.show .badge { opacity: 1; transform: scale(1); transition-delay: 0.2s; }
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
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                <li><a href="manajemen_user.php" class="nav-link"><i class="bi bi-people me-2"></i> Manajemen User</a></li>
                <li><a href="manajemen_kelas.php" class="nav-link active"><i class="bi bi-building me-2"></i> Manajemen Kelas</a></li>
                <li><a href="absensi.php" class="nav-link"><i class="bi bi-calendar-check me-2"></i> Absensi</a></li>
                <li><a href="laporan.php" class="nav-link"><i class="bi bi-file-earmark-text me-2"></i> Laporan</a></li>
                <li><a href="../logout.php" class="nav-link"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="col-md-10 p-4">
        <div class="d-flex justify-content-between align-items-center">
            <h2 id="judul">Manajemen Kelas</h2>
            <div class="text-muted d-flex align-items-center gap-2">
                <i class="bi bi-person-circle fs-4"></i>
                <span><?php echo $_SESSION['nama'] ?? 'Nama Tidak Ada'; ?></span>
                <span class="role-badge"><?php echo $roleBadge; ?></span>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <?php echo ($_GET['success']=='1') ? 'Kelas berhasil ditambahkan!' : 'Kelas berhasil dihapus!'; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                <?php
                $errors = [
                    '1'=>'Nama kelas tidak boleh kosong!',
                    '2'=>'Nama kelas sudah ada!',
                    '3'=>'Gagal menambahkan kelas!',
                    '4'=>'Tidak dapat menghapus kelas yang masih memiliki siswa!',
                    '5'=>'Gagal menghapus kelas!'
                ];
                echo $errors[$_GET['error']] ?? '';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Form tambah kelas -->
        <div class="card mt-4 mb-4" id="form-tambah-kelas">
            <div class="card-header"><h5>Tambah Kelas Baru</h5></div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="kelas" class="form-label">Nama Kelas</label>
                            <input type="text" class="form-control" id="kelas" name="kelas" placeholder="Contoh: X RPL 1" required>
                        </div>
                        <div class="col-md-4 mb-3 d-flex align-items-end">
                            <button type="submit" name="tambah" class="btn btn-primary w-100"><i class="bi bi-plus-circle me-1"></i> Tambah Kelas</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Kelas -->
        <div class="card">
            <div class="card-header"><h5>Daftar Kelas</h5></div>
            <div class="card-body table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr><th>ID</th><th>Nama Kelas</th><th>Jumlah Siswa</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($kelas = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $kelas['id']; ?></td>
                                    <td><?php echo $kelas['kelas']; ?></td>
                                    <td><span class="badge bg-primary"><?php echo $kelas['jumlah_siswa']; ?></span></td>
                                    <td>
                                        <a href="edit_kelas.php?id=<?php echo $kelas['id']; ?>" class="btn btn-sm btn-warning action-btn"><i class="bi bi-pencil"></i></a>
                                        <a href="manajemen_kelas.php?hapus=<?php echo $kelas['id']; ?>" class="btn btn-sm btn-danger action-btn" onclick="return confirm('Yakin ingin menghapus kelas ini?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center">Tidak ada data kelas</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Animasi ngetik judul
function typeEffect(element, speed) {
    const text = element.innerText;
    element.innerText = '';
    let i = 0;
    const timer = setInterval(() => {
        if (i < text.length) { element.innerText += text.charAt(i); i++; } 
        else { clearInterval(timer); }
    }, speed);
}

document.addEventListener('DOMContentLoaded', () => {
    typeEffect(document.getElementById('judul'), 100);

    // Form dan tabel animasi
    const formTambah = document.getElementById('form-tambah-kelas');
    if (formTambah) setTimeout(() => { formTambah.classList.add('show'); }, 100);

    const rows = document.querySelectorAll('.table tbody tr');
    rows.forEach((row, index) => setTimeout(() => row.classList.add('show'), 400 + index*150));
});
</script>
</body>
</html>
