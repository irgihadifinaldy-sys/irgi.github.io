<?php
session_start();
$error = '';
// Redirect jika sudah login
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: admin/dashboard.php');
            exit;
        case 'guru':
            header('Location: guru/dashboard.php');
            exit;
        case 'siswa':
            header('Location: siswa/dashboard.php');
            exit;
    }
}
// Proses login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    if (empty($username) || empty($password)) {
        $error = "Username dan password wajib diisi!";
    } else {
        $conn = new mysqli('localhost', 'root', '', 'ds');
        if ($conn->connect_error) {
            die("Koneksi gagal: " . $conn->connect_error);
        }
        
        $stmt = $conn->prepare("SELECT u.id, u.username, u.nama, u.password, u.role, u.kelas_id, k.kelas 
                               FROM users u 
                               LEFT JOIN kelas k ON u.kelas_id = k.id 
                               WHERE u.username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if ($user['password'] === md5($password)) {
                session_regenerate_id(true);
                $_SESSION['loggedin'] = true;
                $_SESSION['id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['kelas_id'] = $user['kelas_id'];
                $_SESSION['kelas'] = $user['kelas'];
                
                switch ($user['role']) {
                    case 'admin':
                        header('Location: admin/dashboard.php'); exit;
                    case 'guru':
                        header('Location: guru/dashboard.php'); exit;
                    case 'siswa':
                        header('Location: siswa/dashboard.php'); exit;
                }
            } else {
                $error = "Password salah!";
            }
        } else {
            $error = "Username tidak ditemukan!";
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Sistem</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    padding: 0;
    overflow-x: hidden;
    background: #000;
    color: #fff;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Animated Background */
.bg-animation {
    position: fixed;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    z-index: -2;
    background: linear-gradient(45deg, #1a1a2e, #16213e, #0f3460, #533483);
    background-size: 400% 400%;
    animation: gradientShift 15s ease infinite;
}

@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Particles */
.particles {
    position: fixed;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    z-index: -1;
    overflow: hidden;
}

.particle {
    position: absolute;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    pointer-events: none;
    opacity: 0;
    animation: floatUp 20s linear infinite;
}

@keyframes floatUp {
    0% {
        transform: translateY(100vh) scale(0);
        opacity: 0;
    }
    10% {
        opacity: 0.4;
    }
    90% {
        opacity: 0.4;
    }
    100% {
        transform: translateY(-100vh) scale(1);
        opacity: 0;
    }
}

/* Login Container */
.login-container {
    position: relative;
    width: 100%;
    max-width: 420px;
    z-index: 10;
}

/* Login Card */
.login-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(15px);
    border-radius: 24px;
    padding: 40px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.1);
    position: relative;
    overflow: hidden;
    animation: cardEntry 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
    transform: translateY(50px);
    opacity: 0;
}

@keyframes cardEntry {
    0% {
        transform: translateY(50px);
        opacity: 0;
    }
    100% {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Glow Effect */
.login-card::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(45deg, #ff00cc, #3333ff, #00ccff, #ff00cc);
    border-radius: 24px;
    z-index: -1;
    opacity: 0.7;
    filter: blur(10px);
    animation: glowRotate 8s linear infinite;
}

@keyframes glowRotate {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Header */
.login-header {
    text-align: center;
    margin-bottom: 35px;
    position: relative;
}

.login-header h1 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 10px;
    background: linear-gradient(90deg, #00ccff, #ff00cc);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    animation: textGlow 3s ease-in-out infinite alternate;
}

@keyframes textGlow {
    from {
        filter: drop-shadow(0 0 5px rgba(0, 204, 255, 0.5));
    }
    to {
        filter: drop-shadow(0 0 15px rgba(255, 0, 204, 0.7));
    }
}

.login-header p {
    color: rgba(255, 255, 255, 0.7);
    font-size: 15px;
    font-weight: 300;
}

/* Icon Container */
.icon-container {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ff00cc, #3333ff);
    margin-bottom: 25px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    position: relative;
    animation: iconFloat 4s ease-in-out infinite;
}

@keyframes iconFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.icon-container i {
    font-size: 36px;
    color: white;
}

/* Form Group */
.form-group {
    margin-bottom: 25px;
    position: relative;
}

.form-control {
    width: 100%;
    padding: 18px 20px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    color: #fff;
    font-size: 15px;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.12);
    box-shadow: 0 0 20px rgba(0, 204, 255, 0.2);
}

.form-label {
    position: absolute;
    left: 20px;
    top: 18px;
    color: rgba(255, 255, 255, 0.6);
    font-size: 15px;
    pointer-events: none;
    transition: all 0.3s ease;
}

.form-control:focus + .form-label,
.form-control:not(:placeholder-shown) + .form-label {
    top: -10px;
    left: 15px;
    font-size: 12px;
    color: #00ccff;
    background: rgba(0, 0, 0, 0.7);
    padding: 0 8px;
    border-radius: 4px;
}

/* Input Icon */
.input-icon {
    position: absolute;
    right: 18px;
    top: 18px;
    color: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: all 0.3s ease;
}

.input-icon:hover {
    color: #00ccff;
}

/* Form Options */
.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.remember-me {
    display: flex;
    align-items: center;
}

.remember-me input {
    width: 18px;
    height: 18px;
    margin-right: 10px;
    accent-color: #00ccff;
}

.remember-me label {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.7);
    cursor: pointer;
}

.forgot-password {
    font-size: 14px;
    color: #00ccff;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
}

.forgot-password:hover {
    color: #ff00cc;
}

.forgot-password::after {
    content: '';
    position: absolute;
    width: 0;
    height: 1px;
    bottom: -2px;
    left: 0;
    background: #ff00cc;
    transition: width 0.3s ease;
}

.forgot-password:hover::after {
    width: 100%;
}

/* Login Button */
.btn-login {
    width: 100%;
    padding: 18px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(90deg, #00ccff, #ff00cc);
    color: white;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    margin-bottom: 30px;
}

.btn-login:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 30px rgba(0, 204, 255, 0.3);
}

.btn-login:active {
    transform: translateY(0);
}

.btn-login i {
    margin-right: 10px;
}

/* Ripple Effect */
.ripple {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.5);
    transform: scale(0);
    animation: rippleEffect 0.6s linear;
}

@keyframes rippleEffect {
    to {
        transform: scale(4);
        opacity: 0;
    }
}

/* Error Message */
.error-message {
    background: rgba(255, 59, 48, 0.15);
    color: #ff3b30;
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    border-left: 4px solid #ff3b30;
    font-size: 14px;
    animation: shake 0.6s cubic-bezier(.36,.07,.19,.97) both;
}

@keyframes shake {
    10%, 90% { transform: translate3d(-1px, 0, 0); }
    20%, 80% { transform: translate3d(2px, 0, 0); }
    30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
    40%, 60% { transform: translate3d(4px, 0, 0); }
}

/* Demo Accounts */
.demo-accounts {
    margin-top: 30px;
    padding-top: 25px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.demo-accounts h3 {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.6);
    margin-bottom: 15px;
    text-align: center;
    font-weight: 400;
}

.demo-account {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    font-size: 14px;
    color: rgba(255, 255, 255, 0.7);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    transition: all 0.3s ease;
}

.demo-account:last-child {
    border-bottom: none;
}

.demo-account:hover {
    color: #fff;
    padding-left: 5px;
}

.demo-account .role {
    font-weight: 600;
    background: linear-gradient(90deg, #00ccff, #ff00cc);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

/* Loading Animation */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.loading-overlay.active {
    opacity: 1;
    visibility: visible;
}

.loading-spinner {
    width: 60px;
    height: 60px;
    border: 3px solid rgba(255, 255, 255, 0.1);
    border-top: 3px solid #00ccff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 480px) {
    .login-card {
        padding: 30px 20px;
        margin: 20px;
    }
    
    .login-header h1 {
        font-size: 28px;
    }
    
    .form-control {
        padding: 15px 18px;
    }
    
    .btn-login {
        padding: 15px;
    }
}
</style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation"></div>
    
    <!-- Particles -->
    <div class="particles" id="particles"></div>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>
    
    <!-- Login Container -->
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="icon-container">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h1>Sistem Absensi</h1>
                <p>Silakan login untuk melanjutkan</p>
            </div>
            
            <!-- Error Message -->
            <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle mr-3"></i>
                <span><?php echo $error; ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="post" action="" id="loginForm">
                <div class="form-group">
                    <input type="text" id="username" name="username" class="form-control" placeholder=" " required>
                    <label for="username" class="form-label">Username</label>
                </div>
                
                <div class="form-group">
                    <input type="password" id="password" name="password" class="form-control" placeholder=" " required>
                    <label for="password" class="form-label">Password</label>
                    <i class="fas fa-eye input-icon" id="togglePassword"></i>
                </div>
                
                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Ingat saya</label>
                    </div>
                    <a href="#" class="forgot-password">Lupa password?</a>
                </div>
                
                <button type="submit" class="btn-login" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </button>
            </form>
            
            <!-- Demo Accounts -->
            <div class="demo-accounts">
                <h3>Contoh akun:</h3>
                <div class="demo-account">
                    <span><span class="role">Admin:</span> admin</span>
                    <span>admin123</span>
                </div>
                <div class="demo-account">
                    <span><span class="role">Guru:</span> guru1</span>
                    <span>guru123</span>
                </div>
                <div class="demo-account">
                    <span><span class="role">Siswa:</span> siswa1</span>
                    <span>siswa123</span>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle Password Visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        // Create Particles
        const particlesContainer = document.getElementById('particles');
        const particleCount = 50;
        
        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.classList.add('particle');
            
            // Random size
            const size = Math.random() * 15 + 5;
            particle.style.width = `${size}px`;
            particle.style.height = `${size}px`;
            
            // Random position
            particle.style.left = `${Math.random() * 100}%`;
            
            // Random animation duration
            const duration = Math.random() * 20 + 15;
            particle.style.animationDuration = `${duration}s`;
            
            // Random delay
            particle.style.animationDelay = `${Math.random() * 5}s`;
            
            particlesContainer.appendChild(particle);
        }
        
        // Ripple Effect on Button Click
        const loginBtn = document.getElementById('loginBtn');
        
        loginBtn.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
        
        // Show Loading on Form Submit
        const loginForm = document.getElementById('loginForm');
        const loadingOverlay = document.getElementById('loadingOverlay');
        
        loginForm.addEventListener('submit', function() {
            loadingOverlay.classList.add('active');
        });
        
        // Add focus effects to inputs
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>