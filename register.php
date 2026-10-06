<?php
session_start();
include "koneksi.php";

// Jika sudah login, redirect
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$pesan = '';
$tipe_pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $nama     = trim($_POST['nama']);
    $email    = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $konf_password = $_POST['konf_password'];
    $hp       = trim($_POST['hp'] ?? '');
    $alamat   = trim($_POST['alamat'] ?? '');

    // Validasi
    $error = false;

    if ($nama === '') {
        $pesan = 'Nama tidak boleh kosong.';
        $error = true;
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesan = 'Email tidak valid.';
        $error = true;
    } elseif ($username === '') {
        $pesan = 'Username tidak boleh kosong.';
        $error = true;
    } elseif (strlen($username) < 4) {
        $pesan = 'Username minimal 4 karakter.';
        $error = true;
    } elseif ($password === '') {
        $pesan = 'Password tidak boleh kosong.';
        $error = true;
    } elseif (strlen($password) < 4) {
        $pesan = 'Password minimal 4 karakter.';
        $error = true;
    } elseif ($password !== $konf_password) {
        $pesan = 'Konfirmasi password tidak cocok.';
        $error = true;
    }

    if (!$error) {
        // Cek apakah username atau email sudah terdaftar
        $stmt = mysqli_prepare($koneksi, "SELECT id FROM tb_user WHERE username = ? OR email = ?");
        mysqli_stmt_bind_param($stmt, "ss", $username, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $pesan = 'Username atau email sudah terdaftar.';
            $tipe_pesan = 'danger';
            $error = true;
        }
    }

    if (!$error) {
        // Insert user baru dengan role 'pelanggan'
        $role = 'pelanggan';
        $stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO tb_user (nama, email, username, password, hp, alamat, role) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "sssssss", $nama, $email, $username, $password, $hp, $alamat, $role);

        if (mysqli_stmt_execute($stmt)) {
            $pesan = 'Pendaftaran berhasil! Silakan login.';
            $tipe_pesan = 'success';
            // Clear form
            $nama = $email = $username = $hp = $alamat = '';
        } else {
            $pesan = 'Gagal mendaftar. Coba lagi.';
            $tipe_pesan = 'danger';
        }
    } else {
        $tipe_pesan = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar - TokoKita</title>
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
    .register-wrapper{
        display:flex;
        min-height:100vh;
        align-items:center;
        justify-content:center;
        padding:20px;
    }
    .register-container{
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
    .register-left{
        background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        position:relative;
        display:flex;
        align-items:center;
        justify-content:center;
        color:#fff;
        padding:40px 20px;
        min-height:auto;
    }
    .register-left svg{
        max-width:90%;
        filter:drop-shadow(0 10px 30px rgba(0,0,0,0.2));
    }
    .register-right{
        padding:35px 45px;
        display:flex;
        flex-direction:column;
        justify-content:center;
        max-height:100vh;
        overflow-y:auto;
    }
    .register-header h2{
        font-weight:700;
        color:#2d3436;
        margin-bottom:8px;
    }
    .register-header p{
        color:#636e72;
        font-size:14px;
        margin-bottom:20px;
    }
    .form-group{
        margin-bottom:15px;
    }
    .form-group label{
        display:block;
        font-weight:600;
        color:#2d3436;
        margin-bottom:6px;
        font-size:13px;
    }
    .form-control{
        width:100%;
        padding:10px 14px;
        border:1.5px solid #dfe6e9;
        border-radius:8px;
        font-size:13px;
        transition:all 0.3s;
    }
    .form-control:focus{
        outline:none;
        border-color:var(--primary-color);
        box-shadow:0 0 0 3px rgba(255, 111, 60, 0.1);
    }
    .btn-register{
        width:100%;
        padding:12px;
        background:linear-gradient(135deg, var(--secondary-color), var(--primary-color));
        color:#fff;
        border:none;
        border-radius:8px;
        font-weight:600;
        font-size:14px;
        cursor:pointer;
        transition:transform 0.2s, box-shadow 0.2s;
        margin-top:8px;
    }
    .btn-register:hover{
        transform:translateY(-2px);
        box-shadow:0 8px 20px rgba(102, 126, 234, 0.4);
    }
    .register-footer{
        text-align:center;
        margin-top:15px;
        font-size:13px;
        color:#636e72;
    }
    .register-footer a{
        color:var(--primary-color);
        text-decoration:none;
        font-weight:600;
    }
    .register-footer a:hover{
        text-decoration:underline;
    }
    .alert{
        margin-bottom:15px;
        border-radius:8px;
        border:none;
        font-size:13px;
    }
    @media(max-width:768px){
        .register-container{
            grid-template-columns:1fr;
        }
        .register-left{
            min-height:200px;
        }
        .register-right{
            padding:25px 20px;
        }
    }
</style>
</head>
<body>

<div class="register-wrapper">
    <div class="register-container">
        <!-- LEFT SIDE - ILUSTRASI -->
        <div class="register-left">
            <svg viewBox="0 0 400 500" width="300" height="380">
                <!-- Decorative circles -->
                <circle cx="80" cy="80" r="60" fill="#ffbe76" opacity="0.3"/>
                <circle cx="320" cy="150" r="80" fill="#a29bfe" opacity="0.25"/>
                <circle cx="100" cy="350" r="70" fill="#74b9ff" opacity="0.2"/>
                
                <!-- Main illustration - Welcome/User -->
                <rect x="80" y="120" width="240" height="200" rx="30" fill="#fff" opacity="0.1"/>
                
                <!-- User icon -->
                <g transform="translate(150, 120)">
                    <!-- Head -->
                    <circle cx="50" cy="30" r="20" stroke="#fff" stroke-width="2" fill="none"/>
                    <!-- Body -->
                    <path d="M 35 55 L 35 85 M 65 55 L 65 85" stroke="#fff" stroke-width="2" fill="none"/>
                    <!-- Arms -->
                    <path d="M 35 65 L 15 75 M 65 65 L 85 75" stroke="#fff" stroke-width="2" fill="none"/>
                    <!-- Legs -->
                    <circle cx="35" cy="95" r="4" fill="#ffbe76" opacity="0.8"/>
                    <circle cx="65" cy="95" r="4" fill="#a29bfe" opacity="0.8"/>
                </g>

                <!-- Text -->
                <text x="200" y="380" font-size="18" font-weight="bold" fill="#fff" text-anchor="middle">Bergabunglah Sekarang</text>
                <text x="200" y="410" font-size="12" fill="#fff" text-anchor="middle" opacity="0.8">Dapatkan akses ke ribuan produk</text>
            </svg>
        </div>

        <!-- RIGHT SIDE - FORM -->
        <div class="register-right">
            <div class="register-header">
                <h2>Buat Akun Baru</h2>
                <p>Daftar untuk mulai berbelanja</p>
            </div>

            <?php if ($pesan): ?>
                <div class="alert alert-<?= $tipe_pesan ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($pesan) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="form-group">
                    <label for="nama">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama" name="nama" 
                           value="<?= htmlspecialchars($nama ?? '') ?>" placeholder="Nama lengkap" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?= htmlspecialchars($email ?? '') ?>" placeholder="Email" required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?= htmlspecialchars($username ?? '') ?>" placeholder="Min. 4 karakter" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Min. 4 karakter" required>
                </div>

                <div class="form-group">
                    <label for="konf_password">Konfirmasi Password</label>
                    <input type="password" class="form-control" id="konf_password" name="konf_password" 
                           placeholder="Ulangi password" required>
                </div>

                <div class="form-group">
                    <label for="hp">No. HP (Opsional)</label>
                    <input type="text" class="form-control" id="hp" name="hp" 
                           value="<?= htmlspecialchars($hp ?? '') ?>" placeholder="08xx-xxxx-xxxx">
                </div>

                <div class="form-group">
                    <label for="alamat">Alamat (Opsional)</label>
                    <textarea class="form-control" id="alamat" name="alamat" rows="2" 
                              placeholder="Alamat"><?= htmlspecialchars($alamat ?? '') ?></textarea>
                </div>

                <button type="submit" name="register" class="btn-register">
                    <i class="bi bi-person-plus"></i> Daftar
                </button>
            </form>

            <div class="register-footer">
                Sudah punya akun? <a href="login.php">Login di sini</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>