<?php
// admin/export_excel.php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
include '../koneksi.php';

// Fungsi untuk menghasilkan nama file
function generateFileName($type) {
    $date = date('Y-m-d');
    switch ($type) {
        case 'user':
            return "Laporan_Pengguna_{$date}.xls";
        case 'kelas':
            return "Laporan_Kelas_{$date}.xls";
        case 'absensi':
            return "Laporan_Absensi_{$date}.xls";
        case 'rekap':
            return "Rekap_Kehadiran_{$date}.xls";
        default:
            return "Laporan_{$date}.xls";
    }
}

// Fungsi untuk menulis header Excel
function writeExcelHeader($type) {
    header("Content-type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=" . generateFileName($type));
    header("Pragma: no-cache");
    header("Expires: 0");
}

// Fungsi untuk membuat baris tabel HTML
function createTableRow($data) {
    $row = "<tr>";
    foreach ($data as $cell) {
        $row .= "<td>{$cell}</td>";
    }
    $row .= "</tr>";
    return $row;
}

// Ambil parameter type dari URL
$type = isset($_GET['type']) ? $_GET['type'] : '';

// Set header berdasarkan type
writeExcelHeader($type);

// Buat konten berdasarkan type
echo '<table border="1">';

switch ($type) {
    case 'user':
        // Header tabel
        echo createTableRow(['ID', 'NIS', 'Nama', 'Role', 'Kelas']);
        
        // Data pengguna
        $query = "SELECT u.id, u.nis, u.nama, u.role, k.kelas 
                 FROM users u 
                 LEFT JOIN kelas k ON u.kelas_id = k.id 
                 ORDER BY u.role, u.nama";
        $result = mysqli_query($koneksi, $query);
        
        while ($user = mysqli_fetch_assoc($result)) {
            echo createTableRow([
                $user['id'],
                $user['nis'],
                $user['nama'],
                ucfirst($user['role']),
                $user['kelas'] ? $user['kelas'] : '-'
            ]);
        }
        break;
        
    case 'kelas':
        // Header tabel
        echo createTableRow(['ID', 'Nama Kelas', 'Jumlah Siswa']);
        
        // Data kelas
        $query = "SELECT k.id, k.kelas, COUNT(u.id) AS jumlah_siswa 
                 FROM kelas k 
                 LEFT JOIN users u ON k.id = u.kelas_id AND u.role = 'siswa'
                 GROUP BY k.id, k.kelas
                 ORDER BY k.kelas";
        $result = mysqli_query($koneksi, $query);
        
        while ($kelas = mysqli_fetch_assoc($result)) {
            echo createTableRow([
                $kelas['id'],
                $kelas['kelas'],
                $kelas['jumlah_siswa']
            ]);
        }
        break;
        
    case 'absensi':
        // Header tabel
        echo createTableRow(['ID', 'Tanggal', 'NIS', 'Nama', 'Kelas', 'Status', 'Keterangan']);
        
        // Data absensi
        $query = "SELECT a.id, a.tanggal, a.status, a.keterangan, u.nama, u.nis, k.kelas 
                 FROM absensi a 
                 JOIN users u ON a.user_id = u.id 
                 LEFT JOIN kelas k ON u.kelas_id = k.id 
                 ORDER BY a.tanggal DESC, u.nama";
        $result = mysqli_query($koneksi, $query);
        
        while ($absensi = mysqli_fetch_assoc($result)) {
            echo createTableRow([
                $absensi['id'],
                date('d-m-Y', strtotime($absensi['tanggal'])),
                $absensi['nis'],
                $absensi['nama'],
                $absensi['kelas'] ? $absensi['kelas'] : '-',
                ucfirst($absensi['status']),
                $absensi['keterangan'] ? $absensi['keterangan'] : '-'
            ]);
        }
        break;
        
    case 'rekap':
        // Header tabel
        echo createTableRow(['NIS', 'Nama', 'Kelas', 'Hadir', 'Izin', 'Sakit', 'Alpha']);
        
        // Data rekap kehadiran
        $query = "SELECT u.id, u.nama, u.nis, k.kelas,
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
        $result = mysqli_query($koneksi, $query);
        
        while ($rekap = mysqli_fetch_assoc($result)) {
            echo createTableRow([
                $rekap['nis'],
                $rekap['nama'],
                $rekap['kelas'] ? $rekap['kelas'] : '-',
                $rekap['hadir'],
                $rekap['izin'],
                $rekap['sakit'],
                $rekap['alpha']
            ]);
        }
        break;
        
    default:
        echo "<tr><td colspan='5'>Tipe laporan tidak valid!</td></tr>";
        break;
}

echo '</table>';
?>