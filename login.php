<?php
session_start();
include "koneksi.php";

// Jika sudah login, redirect ke index
if (isset($_SESSION['user_id'])) {
    header("Location: index1.php");
    exit;
}

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($username === '' || $password === '') {
        $pesan = 'Username dan password tidak boleh kosong.';
    } else {
        // Query user berdasarkan username (bukan email, karena di db kolom username dipakai untuk login)
        $stmt = mysqli_prepare($koneksi, "SELECT id, nama, password, role FROM tb_user WHERE username = ? OR email = ?");
        mysqli_stmt_bind_param($stmt, "ss", $username, $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && $user['password'] === $password) {
            // Login berhasil, set session
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['flash_sweetalert'] = [
                'icon' => 'success',
                'title' => 'Login berhasil',
                'text' => 'Selamat datang, ' . $user['nama'] . '!'
            ];

            // Redirect ke halaman yang berbeda berdasarkan role
            if ($user['role'] === 'admin') {
                header("Location: dashboard.php");
            } else {
                header("Location: index1.php");
            }
            exit;
        } else {
            $pesan = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - TokoKita</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
    :root{
        --primary-color:#ff6f3c;
        --secondary-color:#667eea;
        --tertiary-color:#764ba2;
    }
    * { margin:0; padding:0; }
    body{
        font-family:'Segoe UI', sans-serif;
        background:#f5f5f5;
        min-height:100vh;
    }
    .login-wrapper{
        display:flex;
        min-height:100vh;
        align-items:center;
        justify-content:center;
        padding:20px;
    }
    .login-container{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:0;
        background:#fff;
        border-radius:20px;
        box-shadow:0 15px 50px rgba(0,0,0,0.15);
        max-width:900px;
        width:100%;
        overflow:hidden;
    }
    .login-left{
        background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        position:relative;
        display:flex;
        align-items:center;
        justify-content:center;
        color:#fff;
        padding:40px 20px;
        min-height:500px;
    }
    .login-left svg{
        max-width:90%;
        filter:drop-shadow(0 10px 30px rgba(0,0,0,0.2));
    }
    .login-right{
        padding:50px 45px;
        display:flex;
        flex-direction:column;
        justify-content:center;
    }
    .login-header h2{
        font-weight:700;
        color:#2d3436;
        margin-bottom:8px;
    }
    .login-header p{
        color:#636e72;
        font-size:14px;
        margin-bottom:30px;
    }
    .form-group{
        margin-bottom:20px;
    }
    .form-group label{
        display:block;
        font-weight:600;
        color:#2d3436;
        margin-bottom:8px;
        font-size:14px;
    }
    .form-control{
        width:100%;
        padding:12px 16px;
        border:1.5px solid #dfe6e9;
        border-radius:10px;
        font-size:14px;
        transition:all 0.3s;
    }
    .form-control:focus{
        outline:none;
        border-color:var(--primary-color);
        box-shadow:0 0 0 4px rgba(255, 111, 60, 0.1);
    }
    .btn-login{
        width:100%;
        padding:14px;
        background:linear-gradient(135deg, var(--secondary-color), var(--primary-color));
        color:#fff;
        border:none;
        border-radius:10px;
        font-weight:600;
        font-size:15px;
        cursor:pointer;
        transition:transform 0.2s, box-shadow 0.2s;
        margin-top:10px;
    }
    .btn-login:hover{
        transform:translateY(-2px);
        box-shadow:0 8px 20px rgba(102, 126, 234, 0.4);
    }
    .login-footer{
        text-align:center;
        margin-top:20px;
        font-size:14px;
        color:#636e72;
    }
    .login-footer a{
        color:var(--primary-color);
        text-decoration:none;
        font-weight:600;
    }
    .login-footer a:hover{
        text-decoration:underline;
    }
    .alert{
        margin-bottom:20px;
        border-radius:10px;
        border:none;
    }
    @media(max-width:768px){
        .login-container{
            grid-template-columns:1fr;
        }
        .login-left{
            min-height:250px;
        }
        .login-right{
            padding:35px 25px;
        }
    }
</style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-container">
        <!-- LEFT SIDE - ILUSTRASI -->
        <div class="login-left">
            <svg viewBox="0 0 400 500" width="300" height="380">
                <!-- Decorative circles -->
                <circle cx="80" cy="80" r="60" fill="#ffbe76" opacity="0.3"/>
                <circle cx="320" cy="150" r="80" fill="#a29bfe" opacity="0.25"/>
                <circle cx="100" cy="350" r="70" fill="#74b9ff" opacity="0.2"/>
                
                <!-- Main illustration - Shopping bag -->
                <rect x="80" y="120" width="240" height="200" rx="30" fill="#fff" opacity="0.1"/>
                
                <!-- Shopping cart icon -->
                <g transform="translate(150, 150)">
                    <!-- Cart body -->
                    <path d="M 20 10 L 30 30 L 80 30 L 85 10 Z" stroke="#fff" stroke-width="2" fill="none"/>
                    <!-- Cart wheels -->
                    <circle cx="40" cy="35" r="3" fill="#fff"/>
                    <circle cx="70" cy="35" r="3" fill="#fff"/>
                    <!-- Items in cart -->
                    <rect x="30" y="15" width="12" height="12" fill="#ffbe76" opacity="0.8"/>
                    <rect x="50" y="12" width="15" height="15" fill="#a29bfe" opacity="0.8"/>
                    <rect x="70" y="16" width="10" height="10" fill="#74b9ff" opacity="0.8"/>
                </g>

                <!-- Text -->
                <text x="200" y="380" font-size="18" font-weight="bold" fill="#fff" text-anchor="middle">Belanja Sekarang</text>
                <text x="200" y="410" font-size="12" fill="#fff" text-anchor="middle" opacity="0.8">Ribuan produk menunggu Anda</text>
            </svg>
        </div>

        <!-- RIGHT SIDE - FORM -->
        <div class="login-right">
            <div class="login-header">
                <h2>Masuk Akun</h2>
                <p>Belanja dengan mudah di TokoKita</p>
            </div>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="username">Username atau Email</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Masukkan username atau email" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Masukkan password" required>
                </div>

                <button type="submit" name="login" class="btn-login">
                    <i class="bi bi-box-arrow-in-right"></i> Masuk
                </button>
            </form>

            <div class="login-footer">
                Belum punya akun? <a href="register.php">Daftar sekarang</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if ($pesan !== ''): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'Login gagal',
    text: <?= json_encode($pesan, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    confirmButtonColor: '#ff6f3c'
});
</script>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>