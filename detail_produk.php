<?php
session_start();
include "koneksi.php";

// ==============================
// Ambil ID produk dari URL
// ==============================
$id_produk = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_produk === 0) {
    header("Location: produk.php");
    exit;
}

// ==============================
// Ambil data produk dari database
// ==============================
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT p.id, p.nama, p.harga, p.stok, p.foto, p.deskripsi, k.nama_kategori
     FROM tb_produk p
     LEFT JOIN tb_kategori k ON p.id_kategori = k.id_kategori
     WHERE p.id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $id_produk);
mysqli_stmt_execute($stmt);
$produk = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$produk) {
    header("Location: produk.php");
    exit;
}

// ==============================
// Status login & keranjang
// ==============================
$is_login   = isset($_SESSION['user_id']);
$nama_user  = $is_login ? $_SESSION['user_nama'] : '';

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}
$jumlah_keranjang = array_sum($_SESSION['keranjang']);
$jumlah_di_keranjang = isset($_SESSION['keranjang'][$id_produk]) ? $_SESSION['keranjang'][$id_produk] : 0;

// ==============================
// Proses tambah ke keranjang
// ==============================
$pesan_keranjang = '';
$tipe_pesan = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_keranjang'])) {
    $qty = (int) $_POST['qty'] ?? 1;

    if ($qty <= 0 || $qty > $produk['stok']) {
        $pesan_keranjang = 'Jumlah tidak valid.';
        $tipe_pesan = 'danger';
    } else {
        if (isset($_SESSION['keranjang'][$id_produk])) {
            $_SESSION['keranjang'][$id_produk] += $qty;
        } else {
            $_SESSION['keranjang'][$id_produk] = $qty;
        }
        $pesan_keranjang = '✓ Produk berhasil ditambahkan! (' . $qty . ' barang ditambahkan ke keranjang)';
        $tipe_pesan = 'success';
    }
}

$fotoSrc = !empty($produk['foto'])
    ? (filter_var($produk['foto'], FILTER_VALIDATE_URL) ? $produk['foto'] : "assets/img/" . $produk['foto'])
    : "https://placehold.co/500x500?text=Produk";
$stok_habis = $produk['stok'] <= 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($produk['nama']) ?> - TokoKita</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
    :root{ --primary-color:#ff6f3c; --dark-color:#212529; }
    body{ font-family:'Segoe UI', sans-serif; background-color:#f8f9fa; }
    .navbar{ box-shadow:0 2px 8px rgba(0,0,0,0.08); }
    .btn-primary-custom{ background-color:var(--primary-color); border-color:var(--primary-color); color:#fff; }
    .btn-primary-custom:hover{ background-color:#e85a28; border-color:#e85a28; color:#fff; }
    .breadcrumb-custom{ background-color:#fff; border-radius:12px; padding:15px 20px; }
    .detail-container{ background:#fff; border-radius:14px; padding:30px; box-shadow:0 3px 10px rgba(0,0,0,0.08); }
    .produk-foto{ border-radius:14px; overflow:hidden; box-shadow:0 5px 15px rgba(0,0,0,0.1); }
    .produk-foto img{ width:100%; height:auto; display:block; }
    .harga-besar{ color:var(--primary-color); font-weight:700; font-size:2rem; }
    .stok-badge{ padding:8px 16px; border-radius:25px; }
    .stok-ada{ background-color:#d4edda; color:#155724; }
    .stok-habis{ background-color:#f8d7da; color:#721c24; }
    .info-produk{ border-top:1px solid #e0e0e0; border-bottom:1px solid #e0e0e0; padding:15px 0; margin:20px 0; }
    .info-row{ display:flex; justify-content:space-between; padding:8px 0; }
    .info-label{ color:#666; font-weight:500; }
    .info-value{ font-weight:600; color:#2d3436; }
    .qty-input{ width:100px; }
    .related-products{ margin-top:50px; padding-top:30px; border-top:1px solid #e0e0e0; }
    .card-related{ border:none; border-radius:12px; overflow:hidden; box-shadow:0 3px 10px rgba(0,0,0,0.08); transition:transform 0.2s ease; }
    .card-related:hover{ transform:translateY(-3px); }
    footer{ background-color:var(--dark-color); color:#adb5bd; }
    .alert-notif{ animation:slideDown 0.3s ease-out; font-weight:600; }
    @keyframes slideDown{ from{ transform:translateY(-100px); opacity:0; } to{ transform:translateY(0); opacity:1; } }
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
        <li class="nav-item"><a class="nav-link active" href="produk.php">Produk</a></li>
        <li class="nav-item"><a class="nav-link" href="kategori.php#produk">Kategori</a></li>
        <li class="nav-item"><a class="nav-link" href="keranjang.php">Keranjang</a></li>
        <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
      </ul>
      <div class="d-flex align-items-center gap-3">
        <a href="keranjang.php" class="btn btn-outline-dark position-relative">
          <i class="bi bi-cart3"></i>
          <?php if ($jumlah_keranjang > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $jumlah_keranjang ?></span>
          <?php endif; ?>
        </a>
        <?php if ($is_login): ?>
          <div class="dropdown">
            <button class="btn btn-primary-custom dropdown-toggle" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars(trim($nama_user)) ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a href="login.php" class="btn btn-outline-primary">Login</a>
          <a href="register.php" class="btn btn-primary-custom">Registrasi</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- ===== BREADCRUMB ===== -->
<div class="container py-3">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb breadcrumb-custom mb-0">
      <li class="breadcrumb-item"><a href="index1.php">Home</a></li>
      <li class="breadcrumb-item"><a href="produk.php">Produk</a></li>
      <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($produk['nama']) ?></li>
    </ol>
  </nav>
</div>

<!-- ===== NOTIFIKASI TOAST ===== -->
<?php if (!empty($pesan_keranjang)): ?>
  <div class="position-fixed top-0 start-50 translate-middle-x p-3 alert-notif" style="z-index:9999; margin-top:90px; width:90%; max-width:400px;">
    <div class="alert alert-<?= $tipe_pesan ?> alert-dismissible fade show shadow-lg mb-0" role="alert">
      <i class="bi bi-<?= $tipe_pesan === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill' ?> me-2"></i>
      <strong><?= $tipe_pesan === 'success' ? 'Berhasil!' : 'Peringatan!' ?></strong> 
      <?= $pesan_keranjang ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  </div>
<?php endif; ?>

<!-- ===== DETAIL PRODUK ===== -->
<div class="container py-4">
  <div class="detail-container">

    <div class="row g-5">
      <!-- Kolom Foto -->
      <div class="col-lg-5">
        <div class="produk-foto">
          <img id="mainImage" src="<?= htmlspecialchars($fotoSrc) ?>" alt="<?= htmlspecialchars($produk['nama']) ?>">
        </div>
      </div>

      <!-- Kolom Informasi -->
      <div class="col-lg-7">
        <span class="badge bg-info mb-2">
          <?= htmlspecialchars(trim($produk['nama_kategori'] ?? 'Tanpa Kategori')) ?>
        </span>

        <h2 class="fw-bold mb-2"><?= htmlspecialchars($produk['nama']) ?></h2>

        <div class="d-flex align-items-center gap-2 mb-3">
          <div class="text-warning">
            <i class="bi bi-star-fill"></i>
            <i class="bi bi-star-fill"></i>
            <i class="bi bi-star-fill"></i>
            <i class="bi bi-star-fill"></i>
            <i class="bi bi-star-half"></i>
          </div>
          <span class="text-muted small">(145 ulasan)</span>
        </div>

        <div class="harga-besar mb-3">Rp <?= number_format($produk['harga'], 0, ',', '.') ?></div>

        <div class="info-produk">
          <div class="info-row">
            <span class="info-label">Kategori</span>
            <span class="info-value"><?= htmlspecialchars(trim($produk['nama_kategori'] ?? 'Tanpa Kategori')) ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Stok Tersedia</span>
            <span class="info-value <?= $stok_habis ? 'text-danger' : 'text-success' ?>">
              <?= $stok_habis ? 'Habis' : (int) $produk['stok'] . ' unit' ?>
            </span>
          </div>
          <div class="info-row">
            <span class="info-label">Status</span>
            <span class="stok-badge <?= $stok_habis ? 'stok-habis' : 'stok-ada' ?>">
              <?= $stok_habis ? '❌ Stok Habis' : '✓ Tersedia' ?>
            </span>
          </div>
        </div>

        <h5 class="fw-bold mt-4 mb-2">Deskripsi Produk</h5>
        <p class="text-muted" style="line-height:1.8;">
          <?= nl2br(htmlspecialchars($produk['deskripsi'])) ?>
        </p>

        <!-- Form Tambah ke Keranjang -->
        <?php if (!$stok_habis): ?>
          <form action="detail_produk.php?id=<?= $id_produk ?>" method="POST" class="mt-4">
            <div class="row g-3 mb-3">
              <div class="col-auto">
                <label for="qty" class="form-label fw-bold">Jumlah:</label>
                <input type="number" class="form-control qty-input" id="qty" name="qty" 
                       value="1" min="1" max="<?= (int) $produk['stok'] ?>" required>
              </div>
              <div class="col-auto">
                <button type="submit" name="tambah_keranjang" class="btn btn-primary-custom btn-lg mt-4">
                  <i class="bi bi-cart-plus me-2"></i> Tambah ke Keranjang
                </button>
              </div>
            </div>
          </form>
        <?php else: ?>
          <button class="btn btn-secondary btn-lg w-100 mt-4" disabled>
            <i class="bi bi-x-circle me-2"></i> Stok Habis
          </button>
        <?php endif; ?>

        <a href="keranjang.php" class="btn btn-outline-dark btn-lg w-100 mt-2">
          <i class="bi bi-bag-check me-2"></i> Lihat Keranjang
        </a>
      </div>
    </div>
  </div>

  <!-- ===== PRODUK TERKAIT ===== -->
  <div class="related-products">
    <h4 class="fw-bold mb-4">Produk Sejenis Lainnya</h4>
    <div class="row g-4">
      <?php
      $sqlRelated = "SELECT p.id, p.nama, p.harga, p.stok, p.foto
                     FROM tb_produk p
                     WHERE p.id_kategori = ? AND p.id != ?
                     ORDER BY p.id DESC LIMIT 4";
      $stmt = mysqli_prepare($koneksi, $sqlRelated);
      $id_kategori = $produk['id_kategori'] ?? 0;
      mysqli_stmt_bind_param($stmt, "ii", $id_kategori, $id_produk);
      mysqli_stmt_execute($stmt);
      $relatedList = [];
      $res = mysqli_stmt_get_result($stmt);
      while ($row = mysqli_fetch_assoc($res)) {
          $relatedList[] = $row;
      }

      if (count($relatedList) === 0): ?>
        <p class="text-muted text-center col-12">Tidak ada produk sejenis lainnya.</p>
      <?php else: ?>
        <?php foreach ($relatedList as $rel):
            $foto = !empty($rel['foto'])
                ? (filter_var($rel['foto'], FILTER_VALIDATE_URL) ? $rel['foto'] : "assets/img/" . $rel['foto'])
                : "https://placehold.co/300x300?text=Produk";
        ?>
          <div class="col-6 col-md-3">
            <div class="card card-related">
              <img src="<?= htmlspecialchars($foto) ?>" class="card-img-top" style="height:180px;object-fit:cover;" alt="<?= htmlspecialchars($rel['nama']) ?>">
              <div class="card-body">
                <h6 class="card-title fw-bold"><?= htmlspecialchars($rel['nama']) ?></h6>
                <p class="text-primary fw-bold mb-2">Rp <?= number_format($rel['harga'], 0, ',', '.') ?></p>
                <a href="detail_produk.php?id=<?= (int) $rel['id'] ?>" class="btn btn-sm btn-outline-dark w-100">
                  <i class="bi bi-eye-fill"></i> Lihat
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ===== FOOTER ===== -->
<footer class="pt-5 pb-4 mt-4">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4">
        <h5>TokoKita</h5>
        <p class="small">TokoKita adalah toko online terpercaya yang menyediakan berbagai produk berkualitas dengan harga terjangkau dan pengiriman cepat ke seluruh Indonesia.</p>
      </div>
      <div class="col-md-4">
        <h5>Kontak Kami</h5>
        <p class="small mb-1"><i class="bi bi-telephone me-2"></i>0821-3072-4680</p>
        <p class="small mb-1"><i class="bi bi-envelope me-2"></i>info@tokokita.com</p>
        <p class="small mb-1"><i class="bi bi-geo-alt me-2"></i>Jl. Suratno No. 12, Kota Cirebon, Indonesia</p>
      </div>
      <div class="col-md-4">
        <h5>Ikuti Kami</h5>
        <a href="#" class="social-icon"><i class="bi bi-facebook"></i></a>
        <a href="#" class="social-icon"><i class="bi bi-instagram"></i></a>
        <a href="#" class="social-icon"><i class="bi bi-twitter-x"></i></a>
        <a href="#" class="social-icon"><i class="bi bi-whatsapp"></i></a>
      </div>
    </div>
    <hr class="border-secondary mt-4">
    <p class="text-center small mb-0">&copy; <?= date("Y") ?> TokoKita. Semua Hak Cipta Dilindungi.</p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-dismiss alert setelah 4 detik
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 4000);
    });
});
</script>
</body>
</html>