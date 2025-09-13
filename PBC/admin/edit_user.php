<?php
// admin/edit_user.php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
include '../koneksi.php';
if (!isset($_GET['id'])) {
    header("Location: manajemen_user.php");
    exit();
}
$id = $_GET['id'];
// Ambil data user
$query = "SELECT * FROM users WHERE id = ?";
$stmt  = $koneksi->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user   = $result->fetch_assoc();
if (!$user) {
    header("Location: manajemen_user.php");
    exit();
}
// Proses update
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nis      = $_POST['nis'];
    $nama     = $_POST['nama'];
    $role     = $_POST['role'];
    $kelas_id = $_POST['kelas_id'] ?: NULL;
    $password = $_POST['password'];
    if (!empty($password)) {
        $password_hash = md5($password);
        $query_update = "UPDATE users SET nis=?, nama=?, role=?, kelas_id=?, password=? WHERE id=?";
        $stmt = $koneksi->prepare($query_update);
        $stmt->bind_param("sssisi", $nis, $nama, $role, $kelas_id, $password_hash, $id);
    } else {
        $query_update = "UPDATE users SET nis=?, nama=?, role=?, kelas_id=? WHERE id=?";
        $stmt = $koneksi->prepare($query_update);
        $stmt->bind_param("sssii", $nis, $nama, $role, $kelas_id, $id);
    }
    if ($stmt->execute()) {
        $success = "Data user berhasil diperbarui!";
    } else {
        $error = "Gagal memperbarui data user!";
    }
}
// Ambil daftar kelas
$query_kelas = "SELECT * FROM kelas ORDER BY kelas ASC";
$result_kelas = mysqli_query($koneksi, $query_kelas);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit User</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        padding: 0;
    }
    .container {
        max-width: 600px;
        margin: 50px auto;
        background: #fff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    h2 {
        margin-bottom: 20px;
        text-align: center;
    }
    .alert {
        padding: 10px 15px;
        margin-bottom: 15px;
        border-radius: 5px;
        color: #fff;
    }
    .alert-success { background-color: #28a745; }
    .alert-error { background-color: #dc3545; }
    label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    input[type="text"], input[type="password"], select {
        width: 100%;
        padding: 8px 10px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
    }
    
    /* Custom Dropdown untuk Kelas */
    .custom-select-wrapper {
        position: relative;
        width: 100%;
        margin-bottom: 15px;
    }
    
    .custom-select {
        position: relative;
    }
    
    .select-selected {
        background-color: #fff;
        border: 1px solid #ccc;
        border-radius: 4px;
        padding: 8px 10px;
        cursor: pointer;
        user-select: none;
        transition: border 0.3s, box-shadow 0.3s, background-color 0.3s;
    }
    
    .select-selected:hover {
        border-color: #007bff;
        box-shadow: 0 2px 8px rgba(0,123,255,0.3);
    }
    
    .select-selected:after {
        position: absolute;
        content: "";
        top: 14px;
        right: 10px;
        width: 0;
        height: 0;
        border: 6px solid transparent;
        border-color: #000 transparent transparent transparent;
    }
    
    .select-selected.select-arrow-active:after {
        border-color: transparent transparent #000 transparent;
        top: 7px;
    }
    
    .select-items {
        position: absolute;
        background-color: #fff;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 99;
        border: 1px solid #ccc;
        border-radius: 4px;
        margin-top: 5px;
        max-height: 150px;
        overflow-y: auto;
        display: none;
        animation: fadeIn 0.3s forwards;
    }
    
    .select-items div {
        padding: 8px 10px;
        cursor: pointer;
        transition: background-color 0.3s, color 0.3s;
    }
    
    .select-items div:hover, .select-items div.selected {
        background-color: #007bff;
        color: #fff;
    }
    
    /* Custom Scrollbar */
    .select-items::-webkit-scrollbar {
        width: 8px;
    }
    
    .select-items::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    .select-items::-webkit-scrollbar-thumb {
        background: #007bff;
        border-radius: 4px;
        transition: background-color 0.3s;
    }
    
    .select-items::-webkit-scrollbar-thumb:hover {
        background: #0056b3;
    }
    
    .select-items {
        scrollbar-width: thin;
        scrollbar-color: #007bff #f1f1f1;
    }
    
    @keyframes fadeIn {
        0% { opacity: 0; transform: translateY(-5px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    
    .form-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .btn-back, button {
        padding: 10px 20px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 14px;
        cursor: pointer;
    }
    .btn-back {
        background-color: #343a40;
        color: #fff;
        border: none;
    }
    .btn-back:hover { background-color: #495057; }
    button {
        background-color: #007bff;
        color: #fff;
        border: none;
    }
    button:hover { background-color: #0056b3; }
    small { font-weight: normal; color: #555; }
</style>
</head>
<body>
<div class="container">
    <h2>Edit User</h2>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    <form method="POST">
        <label for="nis">NIS</label>
        <input type="text" id="nis" name="nis" value="<?= $user['nis'] ?>" required>
        <label for="nama">Nama</label>
        <input type="text" id="nama" name="nama" value="<?= $user['nama'] ?>" required>
        <label for="role">Role</label>
        <select id="role" name="role" required>
            <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
            <option value="guru" <?= $user['role']=='guru'?'selected':'' ?>>Guru</option>
            <option value="siswa" <?= $user['role']=='siswa'?'selected':'' ?>>Siswa</option>
        </select>
        <label for="kelas_id">Kelas</label>
        <div class="custom-select-wrapper">
            <div class="custom-select" id="custom-kelas">
                <div class="select-selected" data-value="">-- Pilih Kelas --</div>
                <div class="select-items">
                    <div data-value="">-- Pilih Kelas --</div>
                    <?php 
                    // Reset pointer result_kelas karena sudah digunakan sebelumnya
                    mysqli_data_seek($result_kelas, 0);
                    while ($kelas = mysqli_fetch_assoc($result_kelas)): 
                        $selected = $kelas['id']==$user['kelas_id'] ? 'selected' : '';
                    ?>
                        <div data-value="<?= $kelas['id'] ?>" class="<?= $selected ?>"><?= $kelas['kelas'] ?></div>
                    <?php endwhile; ?>
                </div>
            </div>
            <input type="hidden" id="kelas_id" name="kelas_id" value="<?= $user['kelas_id'] ?>">
        </div>
        <label for="password">Password <small>(Kosongkan jika tidak ingin diubah)</small></label>
        <input type="password" id="password" name="password">
        <div class="form-footer">
            <a href="manajemen_user.php" class="btn-back">&#8592; Kembali</a>
            <button type="submit">Update User</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const customSelects = document.querySelectorAll('.custom-select');
    
    customSelects.forEach(function(customSelect) {
        const selected = customSelect.querySelector('.select-selected');
        const items = customSelect.querySelector('.select-items');
        const hiddenInput = customSelect.parentElement.querySelector('input[type="hidden"]');
        
        // Set initial value
        const initialValue = hiddenInput.value;
        if (initialValue) {
            const options = items.querySelectorAll('div');
            options.forEach(function(option) {
                if (option.getAttribute('data-value') === initialValue) {
                    selected.textContent = option.textContent;
                    option.classList.add('selected');
                }
            });
        }
        
        // Toggle dropdown
        selected.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent event bubbling
            
            // Close other dropdowns
            document.querySelectorAll('.select-items').forEach(function(item) {
                if (item !== items) {
                    item.style.display = 'none';
                }
            });
            
            // Toggle current dropdown
            items.style.display = items.style.display === 'block' ? 'none' : 'block';
            
            // Add active class for arrow rotation
            this.classList.toggle('select-arrow-active');
        });
        
        // Select option
        const options = items.querySelectorAll('div');
        options.forEach(function(option) {
            option.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent event bubbling
                
                // Remove selected class from all options
                options.forEach(function(opt) {
                    opt.classList.remove('selected');
                });
                
                // Add selected class to clicked option
                this.classList.add('selected');
                
                // Update selected text and hidden input value
                selected.textContent = this.textContent;
                hiddenInput.value = this.getAttribute('data-value');
                
                // Close dropdown
                items.style.display = 'none';
                selected.classList.remove('select-arrow-active');
            });
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        document.querySelectorAll('.select-items').forEach(function(items) {
            items.style.display = 'none';
        });
        document.querySelectorAll('.select-selected').forEach(function(selected) {
            selected.classList.remove('select-arrow-active');
        });
    });
});
</script>
</body>
</html>