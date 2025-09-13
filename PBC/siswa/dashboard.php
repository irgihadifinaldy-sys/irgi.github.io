<?php
// Koneksi ke database
$host = "127.0.0.1";
$user = "root";
$password = "";
$dbname = "ds";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil user ID dari session (asumsi siswa sudah login)
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Anda harus login terlebih dahulu");
}
$user_id = $_SESSION['user_id'];

// Ambil data siswa
$sql_siswa = "SELECT u.nama, u.nis, k.kelas 
              FROM users u 
              LEFT JOIN kelas k ON u.kelas_id = k.id 
              WHERE u.id = $user_id AND u.role = 'siswa'";
$result_siswa = $conn->query($sql_siswa);

if ($result_siswa->num_rows > 0) {
    $siswa = $result_siswa->fetch_assoc();
} else {
    die("Data siswa tidak ditemukan");
}

// Ambil riwayat absensi
$sql_absensi = "SELECT tanggal, status, keterangan 
                FROM absensi 
                WHERE user_id = $user_id 
                ORDER BY tanggal DESC";
$result_absensi = $conn->query($sql_absensi);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            border: 2px solid white;
        }
        
        .dashboard-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
            margin-top: 30px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 25px;
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-title {
            font-size: 20px;
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .info-item {
            margin-bottom: 15px;
            display: flex;
        }
        
        .info-label {
            font-weight: bold;
            width: 100px;
            color: #7f8c8d;
        }
        
        .info-value {
            color: #2c3e50;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-hadir {
            background-color: #2ecc71;
            color: white;
        }
        
        .status-izin {
            background-color: #f39c12;
            color: white;
        }
        
        .status-sakit {
            background-color: #e74c3c;
            color: white;
        }
        
        .status-alpha {
            background-color: #95a5a6;
            color: white;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
        
        .logout-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: #c0392b;
        }
        
        @media (max-width: 768px) {
            .dashboard-content {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .user-info {
                margin-top: 15px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">Sistem Absensi Sekolah</div>
            <div class="user-info">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($siswa['nama']) ?>&background=6a11cb&color=fff&size=40" alt="Avatar">
                <div>
                    <div><?= htmlspecialchars($siswa['nama']) ?></div>
                    <small>Siswa</small>
                </div>
                <form action="logout.php" method="post" style="margin-left: 15px;">
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="dashboard-content">
            <div class="card">
                <h2 class="card-title">Informasi Pribadi</h2>
                <div class="info-item">
                    <span class="info-label">Nama</span>
                    <span class="info-value"><?= htmlspecialchars($siswa['nama']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">NIS</span>
                    <span class="info-value"><?= htmlspecialchars($siswa['nis']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Kelas</span>
                    <span class="info-value"><?= htmlspecialchars($siswa['kelas']) ?></span>
                </div>
                
                <h3 style="margin-top: 30px; margin-bottom: 15px; color: #2c3e50;">Statistik Kehadiran</h3>
                <?php
                // Hitung statistik kehadiran
                $sql_stats = "SELECT 
                                COUNT(*) as total,
                                SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                                SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin,
                                SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit,
                                SUM(CASE WHEN status = 'alpha' THEN 1 ELSE 0 END) as alpha
                              FROM absensi 
                              WHERE user_id = $user_id";
                $result_stats = $conn->query($sql_stats);
                $stats = $result_stats->fetch_assoc();
                ?>
                
                <div class="info-item">
                    <span class="info-label">Total</span>
                    <span class="info-value"><?= $stats['total'] ?> hari</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Hadir</span>
                    <span class="info-value"><?= $stats['hadir'] ?> hari</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Izin</span>
                    <span class="info-value"><?= $stats['izin'] ?> hari</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Sakit</span>
                    <span class="info-value"><?= $stats['sakit'] ?> hari</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Alpha</span>
                    <span class="info-value"><?= $stats['alpha'] ?> hari</span>
                </div>
            </div>
            
            <div class="card">
                <h2 class="card-title">Riwayat Kehadiran</h2>
                
                <?php if ($result_absensi->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result_absensi->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('d F Y', strtotime($row['tanggal'])) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $row['status'] ?>">
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['keterangan'] ?: '-') ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>Belum ada data kehadaran</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>