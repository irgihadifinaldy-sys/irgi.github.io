<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
include '../koneksi.php';
// Statistik Pengguna
$query_total = "SELECT 
    (SELECT COUNT(*) FROM users WHERE role='admin') AS admin,
    (SELECT COUNT(*) FROM users WHERE role='guru') AS guru,
    (SELECT COUNT(*) FROM users WHERE role='siswa') AS siswa,
    (SELECT COUNT(*) FROM users) AS total";
$result_total = mysqli_query($koneksi, $query_total);
$data_total = mysqli_fetch_assoc($result_total);
// Statistik Kehadiran
$query_kehadiran = "SELECT 
    (SELECT COUNT(*) FROM absensi WHERE status='hadir') AS hadir,
    (SELECT COUNT(*) FROM absensi WHERE status='sakit') AS sakit,
    (SELECT COUNT(*) FROM absensi WHERE status='izin') AS izin,
    (SELECT COUNT(*) FROM absensi WHERE status='alfa') AS alfa";
$result_kehadiran = mysqli_query($koneksi, $query_kehadiran);
$data_kehadiran = mysqli_fetch_assoc($result_kehadiran);
// Data Kelas
$query_kelas = "SELECT k.id, k.kelas, COUNT(u.id) AS jumlah_siswa 
    FROM kelas k 
    LEFT JOIN users u ON k.id=u.kelas_id AND u.role='siswa' 
    GROUP BY k.id, k.kelas";
$result_kelas = mysqli_query($koneksi, $query_kelas);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { overflow-x: hidden; padding-top: 70px; }
/* Navbar */
.navbar { z-index:1100; }
.navbar-brand { font-weight:bold; }
/* Sidebar */
.sidebar { min-height:100vh; background-color:#343a40; width:250px; position:fixed; top:53px; left:0; padding-top:20px; transition:all 0.3s; overflow-x:hidden; }
.sidebar .sidebar-logo img { transition: all 0.3s; max-height:80px; }
.sidebar .nav-link { color:#adb5bd; padding:10px 15px; border-radius:8px; margin-bottom:5px; transition:0.2s; }
.sidebar .nav-link:hover { background-color:#495057; color:#fff; }
.sidebar .nav-link.active { background-color:#007bff; color:#fff; }
.sidebar.collapsed { width:70px; }
.sidebar.collapsed .link-text { display:none; }
.sidebar.collapsed .sidebar-logo img { max-height:40px; }
/* Content */
.content { transition: margin-left 0.3s ease; margin-left:250px; }
.content.expanded { margin-left:70px; }
/* Card efek */
.stat-card { transition: transform 0.3s; }
.stat-card:hover { transform: translateY(-5px); }
/* Typing effect */
.typing-text { display:inline-block; }
.cursor { display:inline-block; margin-left:2px; width:1ch; animation: blink 0.7s infinite; }
@keyframes blink { 0%,50%,100%{opacity:1;} 25%,75%{opacity:0;} }
/* Fade-slide-up animasi */
.fade-slide-up { opacity: 0; transform: translateY(20px); animation: fadeSlideUp 0.8s forwards; }
@keyframes fadeSlideUp { to { opacity: 1; transform: translateY(0); } }
/* Responsive Sidebar */
@media (max-width:768px){
    .content { margin-left:0; }
    .sidebar { left:-250px; width:250px; }
    .sidebar.collapsed { left:0; width:250px; }
}
</style>
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <button class="btn btn-outline-light me-2" id="sidebarToggle"><i class="bi bi-list"></i></button>
        <span class="navbar-brand">
            <span id="navbarText" class="typing-text"></span><span id="navbarCursor" class="cursor">|</span>
        </span>
        <div class="d-flex align-items-center text-white ms-auto" id="userFade" style="opacity:0;">
            <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['nama']); ?> | 
            <span class="badge bg-primary"><?= ucfirst($_SESSION['role']); ?></span>
        </div>
    </div>
</nav>
<!-- Sidebar -->
<div class="sidebar p-3" id="sidebar">
    <div class="text-center mb-4 sidebar-logo">
        <a href="dashboard.php">
            <img src="../img/whd.jpg" alt="Logo" class="img-fluid rounded">
        </a>
    </div>
    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
    <ul class="nav nav-pills flex-column mb-auto">
        <li><a href="dashboard.php" class="nav-link <?= $current_page=='dashboard.php'?'active':'' ?>"><i class="bi bi-speedometer2 me-2"></i> <span class="link-text">Dashboard</span></a></li>
        <li><a href="manajemen_user.php" class="nav-link <?= $current_page=='manajemen_user.php'?'active':'' ?>"><i class="bi bi-people me-2"></i> <span class="link-text">Manajemen User</span></a></li>
        <li><a href="manajemen_kelas.php" class="nav-link <?= $current_page=='manajemen_kelas.php'?'active':'' ?>"><i class="bi bi-building me-2"></i> <span class="link-text">Manajemen Kelas</span></a></li>
        <li><a href="absensi.php" class="nav-link <?= $current_page=='absensi.php'?'active':'' ?>"><i class="bi bi-calendar-check-fill me-2"></i> <span class="link-text">Absensi</span></a></li>
        <li><a href="laporan.php" class="nav-link <?= $current_page=='laporan.php'?'active':'' ?>"><i class="bi bi-file-earmark-text me-2"></i> <span class="link-text">Laporan</span></a></li>
        <li class="mt-auto"><a href="../logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right me-2"></i> <span class="link-text">Logout</span></a></li>
    </ul>
</div>
<!-- Main Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col p-4 content" id="mainContent">
            <h2 class="mb-4 fw-bold fade-slide-up" style="animation-delay:0.2s;">
                <span id="typingText" class="typing-text"></span><span id="cursor" class="cursor">|</span>
            </h2>
            <!-- Statistik Pengguna -->
            <div class="row mb-4">
            <?php
            $delay = 0.4;
            $cards = [
                ["Total Pengguna", $data_total['total'], "bi-people-fill", "bg-primary"],
                ["Admin", $data_total['admin'], "bi-person-badge-fill", "bg-success"],
                ["Guru", $data_total['guru'], "bi-person-workspace", "bg-warning"],
                ["Siswa", $data_total['siswa'], "bi-person-arms-up", "bg-info"],
            ];
            foreach($cards as $c): ?>
                <div class="col-md-3 mb-3 fade-slide-up" style="animation-delay:<?= $delay ?>s;">
                    <div class="card stat-card <?= $c[3] ?> text-white shadow">
                        <div class="card-body d-flex justify-content-between">
                            <div>
                                <h6><?= $c[0] ?></h6>
                                <h2><?= $c[1] ?></h2>
                            </div>
                            <i class="bi <?= $c[2] ?> fs-1 align-self-center"></i>
                        </div>
                    </div>
                </div>
            <?php $delay += 0.2; endforeach; ?>
            </div>
            <!-- Statistik Kehadiran -->
            <div class="row mb-4">
            <?php
            $delay = 1.2;
            $attendance = [
                ["Hadir", $data_kehadiran['hadir'], "bi-check-circle-fill", "bg-success"],
                ["Sakit", $data_kehadiran['sakit'], "bi-thermometer-half", "bg-warning"],
                ["Izin", $data_kehadiran['izin'], "bi-file-text-fill", "bg-info"],
                ["Alfa", $data_kehadiran['alfa'], "bi-x-circle-fill", "bg-danger"],
            ];
            foreach($attendance as $a): ?>
                <div class="col-md-3 mb-3 fade-slide-up" style="animation-delay:<?= $delay ?>s;">
                    <div class="card stat-card <?= $a[3] ?> text-white shadow">
                        <div class="card-body d-flex justify-content-between">
                            <div>
                                <h6><?= $a[0] ?></h6>
                                <h2><?= $a[1] ?></h2>
                            </div>
                            <i class="bi <?= $a[2] ?> fs-1 align-self-center"></i>
                        </div>
                    </div>
                </div>
            <?php $delay += 0.2; endforeach; ?>
            </div>
            <!-- Grafik -->
            <div class="row">
                <?php
                $chart_delay = 2.0;
                $charts = [
                    ["Distribusi Pengguna", "userChart"],
                    ["Statistik Kehadiran", "attendanceChart"],
                    ["Jumlah Siswa per Kelas", "classChart"]
                ];
                foreach($charts as $c): ?>
                <div class="col-md-4 mb-4 fade-slide-up" style="animation-delay:<?= $chart_delay ?>s;">
                    <div class="card shadow">
                        <div class="card-header"><h5><?= $c[0] ?></h5></div>
                        <div class="card-body"><canvas id="<?= $c[1] ?>"></canvas></div>
                    </div>
                </div>
                <?php $chart_delay += 0.2; endforeach; ?>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar toggle
const toggleBtn = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const content = document.getElementById('mainContent');
toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    content.classList.toggle('expanded');
});
// Fungsi delay random
function randomDelay(min,max){ return Math.floor(Math.random()*(max-min+1))+min; }
// Animasi Navbar
const navbarTextContent = "Absensi Digital";
const navbarTarget = document.getElementById("navbarText");
let navIndex = 0;
function typeNavbar() {
    if(navIndex < navbarTextContent.length) {
        navbarTarget.innerHTML += navbarTextContent.charAt(navIndex);
        navIndex++;
        setTimeout(typeNavbar, randomDelay(50,150));
    } else {
        document.getElementById('userFade').style.transition="opacity 1s";
        document.getElementById('userFade').style.opacity=1;
    }
}
// Animasi Header Konten
const text = "📊 Selamat datang Admin";
const typingTarget = document.getElementById("typingText");
const cursor = document.getElementById("cursor");
let index = 0;
function typeCharacter(){
    if(index<text.length){ typingTarget.innerHTML += text.charAt(index); index++; setTimeout(typeCharacter, randomDelay(50,200)); }
    else setTimeout(deleteCharacter,2000);
}
function deleteCharacter(){
    if(index>0){ typingTarget.innerHTML = text.substring(0,index-1); index--; setTimeout(deleteCharacter, randomDelay(30,100)); }
    else setTimeout(typeCharacter,500);
}
// Mulai animasi saat window load
window.onload = () => {
    typeNavbar();
    typeCharacter();
};
// ChartJS
new Chart(document.getElementById('userChart'), {
    type:'doughnut', data:{ labels:['Admin','Guru','Siswa'], datasets:[{ data:[<?= $data_total['admin'] ?>,<?= $data_total['guru'] ?>,<?= $data_total['siswa'] ?>], backgroundColor:['#dc3545','#ffc107','#17a2b8'] }]},
    options:{ plugins:{ legend:{ position:'bottom' } } }
});
new Chart(document.getElementById('attendanceChart'), {
    type:'pie', data:{ labels:['Hadir','Sakit','Izin','Alfa'], datasets:[{ data:[<?= $data_kehadiran['hadir'] ?>,<?= $data_kehadiran['sakit'] ?>,<?= $data_kehadiran['izin'] ?>,<?= $data_kehadiran['alfa'] ?>], backgroundColor:['#28a745','#ffc107','#17a2b8','#dc3545'] }]},
    options:{ plugins:{ legend:{ position:'bottom' } } }
});
const classLabels=[], classData=[];
<?php mysqli_data_seek($result_kelas,0); while($row=mysqli_fetch_assoc($result_kelas)): ?>
classLabels.push('<?= $row['kelas'] ?>');
classData.push(<?= $row['jumlah_siswa'] ?>);
<?php endwhile; ?>
new Chart(document.getElementById('classChart'), {
    type:'bar', data:{ labels:classLabels, datasets:[{ label:'Jumlah Siswa', data:classData, backgroundColor:'#007bff' }] },
    options:{ scales:{ y:{ beginAtZero:true, ticks:{ stepSize:1 } } } }
});
</script>
</body>
</html>