<?php
session_start();
if(!isset($_SESSION['loggedin']) || $_SESSION['role'] != 'guru'){
    header('Location: ../login.php');
    exit;
}
?>
<h1>Dashboard guru</h1>
<p>Selamat datang, <?php echo $_SESSION['nama']; ?></p>
<a href="../logout.php">Logout</a>
