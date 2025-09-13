<?php
// admin/import_excel.php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
include '../koneksi.php';

// Cek apakah file diupload
if (isset($_FILES['file_excel']) && $_FILES['file_excel']['error'] == 0) {
    $file = $_FILES['file_excel']['tmp_name'];
    $file_type = pathinfo($_FILES['file_excel']['name'], PATHINFO_EXTENSION);
    
    // Cek ekstensi file
    if ($file_type != 'xlsx' && $file_type != 'xls') {
        header("Location: laporan.php?error=3");
        exit();
    }
    
    // Baca file Excel
    require_once '../vendor/autoload.php'; // Pastikan Anda sudah menginstall PhpSpreadsheet
    
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        
        // Mulai dari baris ke-2 (baris pertama adalah header)
        $success_count = 0;
        $error_count = 0;
        
        for ($row = 2; $row <= $highestRow; $row++) {
            $nis = $worksheet->getCellByColumnAndRow(1, $row)->getValue();
            $tanggal = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
            $status = strtolower($worksheet->getCellByColumnAndRow(3, $row)->getValue());
            $keterangan = $worksheet->getCellByColumnAndRow(4, $row)->getValue();
            
            // Validasi data
            if (empty($nis) || empty($tanggal) || empty($status)) {
                $error_count++;
                continue;
            }
            
            // Validasi status
            if (!in_array($status, ['hadir', 'izin', 'sakit', 'alpha'])) {
                $error_count++;
                continue;
            }
            
            // Format tanggal jika perlu
            if (is_numeric($tanggal)) {
                $date_obj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($tanggal);
                $tanggal = $date_obj->format('Y-m-d');
            } else {
                // Coba parse tanggal dalam format Indonesia
                $date_obj = DateTime::createFromFormat('d/m/Y', $tanggal);
                if ($date_obj) {
                    $tanggal = $date_obj->format('Y-m-d');
                } else {
                    $error_count++;
                    continue;
                }
            }
            
            // Cari user berdasarkan NIS
            $query_user = "SELECT id FROM users WHERE nis = ? AND role = 'siswa'";
            $stmt = mysqli_prepare($koneksi, $query_user);
            mysqli_stmt_bind_param($stmt, "s", $nis);
            mysqli_stmt_execute($stmt);
            $result_user = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result_user) == 0) {
                $error_count++;
                continue;
            }
            
            $user = mysqli_fetch_assoc($result_user);
            $user_id = $user['id'];
            
            // Cek apakah absensi sudah ada
            $query_cek = "SELECT id FROM absensi WHERE user_id = ? AND tanggal = ?";
            $stmt = mysqli_prepare($koneksi, $query_cek);
            mysqli_stmt_bind_param($stmt, "is", $user_id, $tanggal);
            mysqli_stmt_execute($stmt);
            $result_cek = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result_cek) > 0) {
                // Update jika sudah ada
                $query_update = "UPDATE absensi SET status = ?, keterangan = ? WHERE user_id = ? AND tanggal = ?";
                $stmt = mysqli_prepare($koneksi, $query_update);
                mysqli_stmt_bind_param($stmt, "ssis", $status, $keterangan, $user_id, $tanggal);
                mysqli_stmt_execute($stmt);
            } else {
                // Insert jika belum ada
                $query_insert = "INSERT INTO absensi (user_id, tanggal, status, keterangan) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($koneksi, $query_insert);
                mysqli_stmt_bind_param($stmt, "isss", $user_id, $tanggal, $status, $keterangan);
                mysqli_stmt_execute($stmt);
            }
            
            $success_count++;
        }
        
        // Redirect dengan pesan sukses
        header("Location: laporan.php?success=3&imported={$success_count}&errors={$error_count}");
        exit();
        
    } catch (Exception $e) {
        // Redirect dengan pesan error
        header("Location: laporan.php?error=3");
        exit();
    }
} else {
    header("Location: laporan.php?error=3");
    exit();
}
?>