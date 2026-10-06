<?php
session_start();
include "koneksi.php";

// ==============================
// Status login & keranjang
// ==============================
$is_login   = isset($_SESSION['user_id']);
$nama_user  = $is_login ? $_SESSION['user_nama'] : '';

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}
$jumlah_keranjang = array_sum($_SESSION['keranjang']);

// ==============================
// Filter kategori spesifik (jika ada di URL)
// ==============================
$id_kategori_filter = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// ==============================
// Ambil semua kategori
// ==============================
$kategoriList = [];
$sqlKategori = "SELECT id_kategori, nama_kategori FROM tb_kategori ORDER BY nama_kategori ASC";
$resKategori = mysqli_query($koneksi, $sqlKategori);
if ($resKategori) {
    while ($row = mysqli_fetch_assoc($resKategori)) {
        $kategoriList[] = $row;
    }
}

// ==============================
// Jika ada filter kategori, tampil hanya kategori itu
// ==============================
if ($id_kategori_filter > 0) {
    // Ambil data kategori yang dipilih
    $stmt = mysqli_prepare($koneksi, "SELECT id_kategori, nama_kategori FROM tb_kategori WHERE id_kategori = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_kategori_filter);
    mysqli_stmt_execute($stmt);
    $kategoriTerpilih = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$kategoriTerpilih) {
        header("Location: kategori.php");
        exit;
    }

    // Ambil produk dari kategori yang dipilih
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT p.id, p.nama, p.harga, p.stok, p.foto, p.deskripsi, k.nama_kategori
         FROM tb_produk p
         LEFT JOIN tb_kategori k ON p.id_kategori = k.id_kategori
         WHERE p.id_kategori = ?
         ORDER BY p.id DESC"
    );
    mysqli_stmt_bind_param($stmt, "i", $id_kategori_filter);
    mysqli_stmt_execute($stmt);
    $produkKategori = [];
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $produkKategori[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kategori Produk - TokoKita</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
    :root{ --primary-color:#ff6f3c; --dark-color:#212529; }
    body{ font-family:'Segoe UI', sans-serif; background-color:#f8f9fa; }
    .navbar{ box-shadow:0 2px 8px rgba(0,0,0,0.08); }
    .nav-link{ font-weight:500; }
    .btn-primary-custom{ background-color:var(--primary-color); border-color:var(--primary-color); color:#fff; }
    .btn-primary-custom:hover{ background-color:#e85a28; border-color:#e85a28; color:#fff; }
    .cart-badge{ position:relative; top:-10px; right:5px; font-size:0.7rem; }
    
    .card-kategori{
        border:none;
        border-radius:14px;
        overflow:hidden;
        box-shadow:0 3px 10px rgba(0,0,0,0.08);
        transition:transform 0.2s ease, box-shadow 0.2s ease;
        cursor:pointer;
        height:100%;
        text-decoration:none;
        color:inherit;
        display:block;
    }
    .card-kategori:hover{
        transform:translateY(-5px);
        box-shadow:0 8px 18px rgba(0,0,0,0.15);
        text-decoration:none;
        color:inherit;
    }
    .card-kategori-img{
        height:140px;
        background:linear-gradient(135deg, var(--primary-color), #ff9166);
        display:flex;
        align-items:center;
        justify-content:center;
        color:#fff;
        font-size:2.5rem;
    }
    .card-kategori-body{
        padding:20px;
        text-align:center;
    }
    .card-kategori-title{
        font-weight:700;
        font-size:1.05rem;
        margin-bottom:8px;
        color:#2d3436;
    }
    .card-kategori-count{
        color:#636e72;
        font-size:0.9rem;
    }
    
    .card-produk{
        border:none;
        border-radius:14px;
        overflow:hidden;
        box-shadow:0 3px 10px rgba(0,0,0,0.08);
        transition:transform 0.2s ease, box-shadow 0.2s ease;
        height:100%;
    }
    .card-produk:hover{
        transform:translateY(-5px);
        box-shadow:0 8px 18px rgba(0,0,0,0.15);
    }
    .card-produk img{ height:200px; object-fit:cover; width:100%; }
    .badge-kategori{ background-color:#ffe4d6; color:var(--primary-color); font-weight:500; }
    .harga-produk{ color:var(--primary-color); font-weight:700; font-size:1.15rem; }
    .breadcrumb-custom{ background-color:#fff; border-radius:12px; padding:15px 20px; box-shadow:0 2px 8px rgba(0,0,0,0.05); }
    footer{ background-color:var(--dark-color); color:#adb5bd; }
    .social-icon{ width:38px; height:38px; border-radius:50%; background-color:#343a40; display:inline-flex; align-items:center; justify-content:center; margin-right:8px; color:#fff; }
    .social-icon:hover{ background-color:var(--primary-color); }
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
      <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="index1.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="produk.php">Produk</a></li>
        <li class="nav-item"><a class="nav-link active" href="kategori.php">Kategori</a></li>
        <li class="nav-item"><a class="nav-link" href="keranjang.php">Keranjang</a></li>
        <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
      </ul>
      <div class="d-flex align-items-center gap-3">
        <a href="keranjang.php" class="btn btn-outline-dark position-relative">
          <i class="bi bi-cart3"></i>
          <?php if ($jumlah_keranjang > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-badge"><?= $jumlah_keranjang ?></span>
          <?php endif; ?>
        </a>
        <?php if ($is_login): ?>
          <div class="dropdown">
            <button class="btn btn-primary-custom dropdown-toggle" type="button" data-bs-toggle="dropdown">
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

<?php if ($id_kategori_filter > 0): ?>
  <!-- ===== BREADCRUMB ===== -->
  <div class="container py-3">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb breadcrumb-custom mb-0">
        <li class="breadcrumb-item"><a href="index1.php">Home</a></li>
        <li class="breadcrumb-item"><a href="kategori.php">Kategori</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars(trim($kategoriTerpilih['nama_kategori'])) ?></li>
      </ol>
    </nav>
  </div>

  <!-- ===== HALAMAN KATEGORI SPESIFIK ===== -->
  <div class="container py-4">
    <h2 class="fw-bold mb-1"><i class="bi bi-tag-fill me-2" style="color:var(--primary-color);"></i><?= htmlspecialchars(trim($kategoriTerpilih['nama_kategori'])) ?></h2>
    <p class="text-muted mb-4">Menampilkan <?= count($produkKategori) ?> produk</p>

    <?php if (count($produkKategori) === 0): ?>
      <div class="alert alert-info text-center py-5">
        <i class="bi bi-inbox" style="font-size:3rem;"></i>
        <h5 class="mt-3">Belum ada produk di kategori ini</h5>
        <a href="kategori.php" class="btn btn-primary-custom mt-3">Kembali ke Kategori</a>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($produkKategori as $produk):
            $stok_habis = $produk['stok'] <= 0;
            $fotoSrc = !empty($produk['foto'])
                ? (filter_var($produk['foto'], FILTER_VALIDATE_URL) ? $produk['foto'] : "assets/img/" . $produk['foto'])
                : "https://placehold.co/400x300?text=Produk";
        ?>
          <div class="col-6 col-md-4 col-lg-3">
            <div class="card card-produk">
              <img src="<?= htmlspecialchars($fotoSrc) ?>" alt="<?= htmlspecialchars($produk['nama']) ?>">
              <div class="card-body d-flex flex-column">
                <span class="badge badge-kategori mb-2 align-self-start">
                  <?= htmlspecialchars(trim($produk['nama_kategori'] ?? 'Tanpa Kategori')) ?>
                </span>
                <h6 class="fw-bold mb-1"><?= htmlspecialchars($produk['nama']) ?></h6>
                <p class="text-muted small mb-2">
                  <?= htmlspecialchars(mb_strimwidth($produk['deskripsi'], 0, 60, '...')) ?>
                </p>
                <p class="harga-produk mb-1">Rp <?= number_format($produk['harga'], 0, ',', '.') ?></p>
                <p class="small mb-3 <?= $stok_habis ? 'text-danger' : 'text-success' ?>">
                  <?= $stok_habis ? 'Stok Habis' : 'Stok: ' . (int) $produk['stok'] ?>
                </p>

                <div class="mt-auto d-grid gap-2">
                  <?php if ($stok_habis): ?>
                    <button class="btn btn-secondary btn-sm" disabled>
                      <i class="bi bi-x-circle"></i> Stok Habis
                    </button>
                  <?php else: ?>
                    <form action="tambah_keranjang.php" method="POST">
                      <input type="hidden" name="id_produk" value="<?= (int) $produk['id'] ?>">
                      <button type="submit" class="btn btn-primary-custom btn-sm w-100">
                        <i class="bi bi-cart-plus"></i> Keranjang
                      </button>
                    </form>
                  <?php endif; ?>
                  <a href="detail_produk.php?id=<?= (int) $produk['id'] ?>" class="btn btn-outline-dark btn-sm">
                    <i class="bi bi-eye"></i> Detail
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="mt-5 text-center">
      <a href="kategori.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i> Kembali ke Semua Kategori
      </a>
    </div>
  </div>

<?php else: ?>
  <!-- ===== HALAMAN SEMUA KATEGORI ===== -->
  <section class="py-5 bg-white">
    <div class="container text-center">
      <h2 class="fw-bold mb-1">Jelajahi Kategori Produk</h2>
      <p class="text-muted mb-0">Temukan produk favorit Anda dari berbagai kategori pilihan</p>
    </div>
  </section>

  <section class="py-4">
    <div class="container">
      <?php if (count($kategoriList) === 0): ?>
        <div class="alert alert-info text-center py-5">
          <i class="bi bi-inbox" style="font-size:3rem;"></i>
          <h5 class="mt-3">Belum ada kategori</h5>
        </div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($kategoriList as $kat):
              // Hitung jumlah produk per kategori
              $stmtCount = mysqli_prepare($koneksi, "SELECT COUNT(*) as jumlah FROM tb_produk WHERE id_kategori = ?");
              mysqli_stmt_bind_param($stmtCount, "i", $kat['id_kategori']);
              mysqli_stmt_execute($stmtCount);
              $rowCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCount));
              $jumlahProduk = $rowCount['jumlah'];

              // Icon untuk setiap kategori
              $icons = [
                  'Baju' => 'bi-bag',
                  'Celana' => 'bi-pin',
                  'Makanan' => 'bi-cup-hot',
                  'Minuman' => 'bi-cup-straw',
                  'Pakaian' => 'bi-bag',
                  'Aksesoris' => 'bi-bracelet'
              ];
              $katName = trim($kat['nama_kategori']);
              $icon = $icons[$katName] ?? 'bi-tag';
          ?>
            <div class="col-6 col-md-4 col-lg-3">
              <a href="kategori.php?id=<?= $kat['id_kategori'] ?>" class="card-kategori">
                <div class="card-kategori-img">
                  <i class="bi <?= $icon ?>"></i>
                </div>
                <div class="card-kategori-body">
                  <div class="card-kategori-title"><?= htmlspecialchars($katName) ?></div>
                  <div class="card-kategori-count">
                    <i class="bi bi-box me-1"></i><?= $jumlahProduk ?> Produk
                  </div>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

<?php endif; ?>

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

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>