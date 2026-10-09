<?php
session_start();
include "koneksi.php";

// ==============================
// Wajib login untuk membuka halaman ini
// ==============================
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user = (int) $_SESSION['user_id'];
$pesan = '';
$tipePesan = 'success';

// Jumlah item di keranjang (untuk badge navbar)
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}
$jumlah_keranjang = array_sum($_SESSION['keranjang']);

// ==============================
// PROSES UPDATE PROFILE
// ==============================
if (isset($_POST['simpan_profile'])) {
    $nama     = trim($_POST['nama']);
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']);
    $hp       = trim($_POST['hp']);
    $alamat   = trim($_POST['alamat']);

    if ($nama === '' || $email === '' || $username === '') {
        $pesan = "Nama, username, dan email tidak boleh kosong.";
        $tipePesan = "danger";
    } elseif (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $username)) {
        $pesan = "Username 3-30 karakter, hanya huruf, angka, titik, dan underscore.";
        $tipePesan = "danger";
    } else {
        // Pastikan username belum dipakai user lain
        $cek = mysqli_prepare($koneksi, "SELECT id FROM tb_user WHERE username = ? AND id != ?");
        mysqli_stmt_bind_param($cek, "si", $username, $id_user);
        mysqli_stmt_execute($cek);
        $dipakai = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));

        if ($dipakai) {
            $pesan = "Username \"" . $username . "\" sudah dipakai pengguna lain.";
            $tipePesan = "danger";
        } else {
            $stmt = mysqli_prepare(
                $koneksi,
                "UPDATE tb_user SET nama = ?, username = ?, email = ?, hp = ?, alamat = ? WHERE id = ?"
            );
            mysqli_stmt_bind_param($stmt, "sssssi", $nama, $username, $email, $hp, $alamat, $id_user);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['user_nama'] = $nama; // perbarui nama di navbar
                $pesan = "Profil berhasil diperbarui.";
            } else {
                $pesan = "Gagal memperbarui profil.";
                $tipePesan = "danger";
            }
        }
    }
}

// ==============================
// PROSES GANTI PASSWORD
// ==============================
if (isset($_POST['simpan_password'])) {
    $pass_lama = $_POST['password_lama'];
    $pass_baru = $_POST['password_baru'];
    $pass_ulang = $_POST['password_ulang'];

    // Ambil password saat ini
    $stmt = mysqli_prepare($koneksi, "SELECT password FROM tb_user WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);
    $rowPass = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$rowPass || $rowPass['password'] !== $pass_lama) {
        $pesan = "Password lama salah.";
        $tipePesan = "danger";
    } elseif (strlen($pass_baru) < 4) {
        $pesan = "Password baru minimal 4 karakter.";
        $tipePesan = "danger";
    } elseif ($pass_baru !== $pass_ulang) {
        $pesan = "Konfirmasi password baru tidak cocok.";
        $tipePesan = "danger";
    } else {
        $stmt = mysqli_prepare($koneksi, "UPDATE tb_user SET password = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $pass_baru, $id_user);
        mysqli_stmt_execute($stmt);
        $pesan = "Password berhasil diubah.";
    }
}

// ==============================
// AMBIL DATA USER
// ==============================
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT id, nama, email, username, hp, alamat, role FROM tb_user WHERE id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// ==============================
// AMBIL RIWAYAT TRANSAKSI
// ==============================
$riwayat = [];
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT t.id_transaksi, t.tanggal, t.total_harga,
            (SELECT COALESCE(SUM(d.jumlah),0) FROM tb_detail d WHERE d.id_transaksi = t.id_transaksi) AS total_item
     FROM tb_transaksi t
     WHERE t.id_pelanggan = ?
     ORDER BY t.id_transaksi DESC"
);
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $riwayat[] = $row;
}

// Total belanja keseluruhan
$totalBelanja = 0;
foreach ($riwayat as $r) {
    $totalBelanja += $r['total_harga'];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - TokoKita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #ff6f3c;
            --dark-color: #212529;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f8f9fa;
        }

        .navbar {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .btn-primary-custom {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: #fff;
        }

        .btn-primary-custom:hover {
            background-color: #e85a28;
            border-color: #e85a28;
            color: #fff;
        }

        .card-profile {
            border: none;
            border-radius: 14px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.4rem;
            font-weight: 700;
            margin: 0 auto 12px auto;
        }

        .nav-pills .nav-link.active {
            background-color: var(--primary-color);
        }

        .nav-pills .nav-link {
            color: var(--dark-color);
            font-weight: 500;
        }

        footer {
            background-color: var(--dark-color);
            color: #adb5bd;
        }
    </style>
</head>

<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center fw-bold" href="index1.php">
                <i class="bi bi-shop me-2" style="font-size:1.7rem;color:var(--primary-color);"></i>
                Toko<span style="color:var(--primary-color);">Kita</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMenu">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="index1.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="index1.php#produk">Produk</a></li>
                    <li class="nav-item"><a class="nav-link" href="kategori.php">Kategori</a></li>
                    <li class="nav-item"><a class="nav-link" href="keranjang.php">Keranjang</a></li>
                    <li class="nav-item"><a class="nav-link active" href="profile.php">Profile</a></li>
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <a href="keranjang.php" class="btn btn-outline-dark position-relative">
                        <i class="bi bi-cart3"></i>
                        <?php if ($jumlah_keranjang > 0): ?>
                            <span
                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $jumlah_keranjang ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-primary-custom dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($nama_user ?? '') ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i
                                        class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <?php if ($pesan): ?>
            <div class="alert alert-<?= $tipePesan ?> alert-dismissible fade show">
                <?= htmlspecialchars($pesan) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- ===== KARTU IDENTITAS ===== -->
            <div class="col-lg-4">
                <div class="card card-profile text-center">
                    <div class="card-body py-4">
                        <div class="avatar">
                            <?= !empty($user['nama']) ? strtoupper(substr(trim($user['nama']), 0, 1)) : '?' ?>
                        </div>
                        <h5 class="fw-bold mb-0"><?= htmlspecialchars($nama_user ?? '') ?></h5>
                       <p class="text-muted small mb-2"><?= htmlspecialchars($user['username'] ?? '') ?></p>
                        <span class="badge bg-secondary text-capitalize"><?= htmlspecialchars($user['role']) ?></span>
                        <hr>
                        <div class="row text-center">
                            <div class="col-6 border-end">
                                <div class="fw-bold fs-5"><?= count($riwayat) ?></div>
                                <small class="text-muted">Transaksi</small>
                            </div>
                            <div class="col-6">
                                <div class="fw-bold fs-6">Rp <?= number_format($totalBelanja, 0, ',', '.') ?></div>
                                <small class="text-muted">Total Belanja</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== TAB KONTEN ===== -->
            <div class="col-lg-8">
                <div class="card card-profile">
                    <div class="card-body">
                        <ul class="nav nav-pills mb-4" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-edit">
                                    <i class="bi bi-pencil-square me-1"></i> Edit Profile
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-riwayat">
                                    <i class="bi bi-receipt me-1"></i> Riwayat Transaksi
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-password">
                                    <i class="bi bi-shield-lock me-1"></i> Ganti Password
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <!-- TAB EDIT PROFILE -->
                            <div class="tab-pane fade show active" id="tab-edit">
                                <form action="profile.php" method="POST">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Nama Lengkap</label>
                                            <input type="text" name="nama" class="form-control"
                                                value="<?= htmlspecialchars(trim($user['nama'] ?? '')) ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Username</label>
                                            <input type="text" name="username" class="form-control"
                                                value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                                                required pattern="[A-Za-z0-9_.]{3,30}">
                                            <small class="text-muted">3-30 karakter: huruf, angka, titik, underscore. Dipakai untuk login.</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control"
                                                value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">No. HP</label>
                                            <input type="text" name="hp" class="form-control"
                                                value="<?= htmlspecialchars($user['hp'] ?? '') ?>">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Alamat</label>
                                            <textarea name="alamat" class="form-control"
                                                rows="3"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" name="simpan_profile" class="btn btn-primary-custom">
                                                <i class="bi bi-save me-1"></i> Simpan Perubahan
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- TAB RIWAYAT TRANSAKSI -->
                            <div class="tab-pane fade" id="tab-riwayat">
                                <?php if (count($riwayat) === 0): ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-receipt" style="font-size:3rem;color:#ccc;"></i>
                                        <p class="text-muted mt-3 mb-3">Belum ada transaksi.</p>
                                        <a href="produk.php" class="btn btn-primary-custom">Mulai Belanja</a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>No. Invoice</th>
                                                    <th>Tanggal</th>
                                                    <th class="text-center">Jumlah Item</th>
                                                    <th class="text-end">Total</th>
                                                    <th class="text-center">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($riwayat as $trx): ?>
                                                    <tr>
                                                        <td class="fw-semibold">
                                                            INV-<?= str_pad($trx['id_transaksi'], 5, "0", STR_PAD_LEFT) ?></td>
                                                        <td><?= date("d M Y", strtotime($trx['tanggal'])) ?></td>
                                                        <td class="text-center"><?= (int) $trx['total_item'] ?> barang</td>
                                                        <td class="text-end fw-semibold">Rp
                                                            <?= number_format($trx['total_harga'], 0, ',', '.') ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <a href="invoice.php?id=<?= $trx['id_transaksi'] ?>" target="_blank"
                                                                class="btn btn-sm btn-outline-dark">
                                                                <i class="bi bi-printer"></i> Invoice
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- TAB GANTI PASSWORD -->
                            <div class="tab-pane fade" id="tab-password">
                                <form action="profile.php" method="POST">
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label class="form-label">Password Lama</label>
                                            <input type="password" name="password_lama" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Password Baru</label>
                                            <input type="password" name="password_baru" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Ulangi Password Baru</label>
                                            <input type="password" name="password_ulang" class="form-control" required>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" name="simpan_password" class="btn btn-primary-custom">
                                                <i class="bi bi-shield-check me-1"></i> Ubah Password
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== FOOTER ===== -->
    <footer class="pt-4 pb-3 mt-5">
        <div class="container text-center">
            <h5 class="text-white fw-bold">TokoKita</h5>
            <p class="small mb-1">Belanja online mudah, aman, dan terpercaya.</p>
            <p class="small mb-0">&copy; <?= date("Y") ?> TokoKita. Semua Hak Cipta Dilindungi.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
