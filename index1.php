<?php
session_start();
include "koneksi.php";
$notifikasi = $_SESSION['flash_sweetalert'] ?? null;
unset($_SESSION['flash_sweetalert']);

// ==============================
// Ambil status login pelanggan
// ==============================
$is_login   = isset($_SESSION['user_id']);
$nama_user  = $is_login ? $_SESSION['user_nama'] : '';

// ==============================
// Hitung jumlah item di keranjang (disimpan di session)
// Struktur: $_SESSION['keranjang'][id_produk] = jumlah
// ==============================
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}
$jumlah_keranjang = 0;
foreach ($_SESSION['keranjang'] as $qty) {
    $jumlah_keranjang += $qty;
}

// ==============================
// Ambil filter kategori dari URL (?kategori=id)
// ==============================
$id_kategori_filter = isset($_GET['kategori']) ? (int) $_GET['kategori'] : 0;

// ==============================
// Ambil semua kategori dari database
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
// Ambil produk dari database (dengan/ tanpa filter kategori)
// Menggunakan prepared statement agar aman dari SQL Injection
// ==============================
$produkList = [];
if ($id_kategori_filter > 0) {
    $sqlProduk = "SELECT p.id, p.nama, p.harga, p.stok, p.foto, p.deskripsi, k.nama_kategori
                  FROM tb_produk p
                  LEFT JOIN tb_kategori k ON p.id_kategori = k.id_kategori
                  WHERE p.id_kategori = ?
                  ORDER BY p.id DESC";
    $stmt = mysqli_prepare($koneksi, $sqlProduk);
    mysqli_stmt_bind_param($stmt, "i", $id_kategori_filter);
    mysqli_stmt_execute($stmt);
    $resProduk = mysqli_stmt_get_result($stmt);
} else {
    $sqlProduk = "SELECT p.id, p.nama, p.harga, p.stok, p.foto, p.deskripsi, k.nama_kategori
                  FROM tb_produk p
                  LEFT JOIN tb_kategori k ON p.id_kategori = k.id_kategori
                  ORDER BY p.id DESC";
    $resProduk = mysqli_query($koneksi, $sqlProduk);
}

if ($resProduk) {
    while ($row = mysqli_fetch_assoc($resProduk)) {
        $produkList[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Toko Kita - Belanja Online Mudah & Terpercaya</title>

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
    :root{
        --primary-color:#ff6f3c;
        --dark-color:#212529;
    }
    body{
        font-family: 'Segoe UI', sans-serif;
        background-color:#f8f9fa;
    }

    /* ===== NAVBAR ===== */
    .navbar-brand img{
        height:40px;
        margin-right:8px;
    }
    .navbar{
        box-shadow:0 2px 8px rgba(0,0,0,0.08);
    }
    .nav-link{
        font-weight:500;
    }
    .cart-badge{
        position:relative;
        top:-10px;
        right:5px;
        font-size:0.7rem;
    }

    /* ===== CAROUSEL ===== */
    .carousel-item img{
        height:420px;
        object-fit:cover;
        filter:brightness(60%);
    }
    .carousel-caption{
        bottom:50%;
        transform:translateY(50%);
    }
    .carousel-caption h2{
        font-weight:700;
        font-size:2.2rem;
    }

    /* ===== KEUNGGULAN ===== */
    .keunggulan-icon{
        width:70px;
        height:70px;
        background-color:var(--primary-color);
        color:#fff;
        border-radius:50%;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:1.8rem;
        margin:0 auto 15px auto;
    }

    /* ===== KATEGORI ===== */
    .btn-kategori{
        border-radius:30px;
        padding:8px 22px;
        margin:5px;
        font-weight:500;
    }

    /* ===== PRODUK ===== */
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
    .card-produk img{
        height:200px;
        object-fit:cover;
        width:100%;
    }
    .badge-kategori{
        background-color:#ffe4d6;
        color:var(--primary-color);
        font-weight:500;
    }
    .harga-produk{
        color:var(--primary-color);
        font-weight:700;
        font-size:1.15rem;
    }
    .btn-primary-custom{
        background-color:var(--primary-color);
        border-color:var(--primary-color);
        color:#fff;
    }
    .btn-primary-custom:hover{
        background-color:#e85a28;
        border-color:#e85a28;
        color:#fff;
    }

    /* ===== FOOTER ===== */
    footer{
        background-color:var(--dark-color);
        color:#adb5bd;
    }
    footer h5{
        color:#fff;
        font-weight:600;
    }
    footer a{
        color:#adb5bd;
        text-decoration:none;
    }
    footer a:hover{
        color:#fff;
    }
    .social-icon{
        width:38px;
        height:38px;
        border-radius:50%;
        background-color:#343a40;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        margin-right:8px;
        color:#fff;
    }
    .social-icon:hover{
        background-color:var(--primary-color);
    }
</style>
</head>
<body>

<!-- ============================== -->
<!-- NAVBAR -->
<!-- ============================== -->
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
        <li class="nav-item"><a class="nav-link active" href="index1.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="produk.php">Produk</a></li>
        <li class="nav-item"><a class="nav-link" href="kategori.php">Kategori</a></li>
        <li class="nav-item"><a class="nav-link" href="keranjang.php">Keranjang</a></li>
        <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
      </ul>

      <div class="d-flex align-items-center gap-3">
        <!-- Tombol Keranjang -->
        <a href="keranjang.php" class="btn btn-outline-dark position-relative">
          <i class="bi bi-cart3"></i>
          <?php if ($jumlah_keranjang > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-badge">
              <?= $jumlah_keranjang ?>
            </span>
          <?php endif; ?>
        </a>

        <?php if ($is_login): ?>
          <!-- Jika sudah login -->
          <div class="dropdown">
            <button class="btn btn-primary-custom dropdown-toggle" type="button" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($nama_user ?? '') ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
              <li><a class="dropdown-item" href="riwayat.php"><i class="bi bi-receipt me-2"></i>Riwayat Transaksi</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </div>
        <?php else: ?>
          <!-- Jika belum login -->
          <a href="login.php" class="btn btn-outline-primary">Login</a>
          <a href="register.php" class="btn btn-primary-custom">Registrasi</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- ============================== -->
<!-- CAROUSEL -->
<!-- ============================== -->
<div id="carouselToko" class="carousel slide" data-bs-ride="carousel">
  <div class="carousel-indicators">
    <button type="button" data-bs-target="#carouselToko" data-bs-slide-to="0" class="active"></button>
    <button type="button" data-bs-target="#carouselToko" data-bs-slide-to="1"></button>
    <button type="button" data-bs-target="#carouselToko" data-bs-slide-to="2"></button>
  </div>
  <div class="carousel-inner">
    <div class="carousel-item active">
      <img src="https://images.unsplash.com/photo-1483985988355-763728e1935b?w=1400" class="d-block w-100" alt="Banner 1">
      <div class="carousel-caption text-white">
        <h2>Koleksi Terbaru 2026</h2>
        <p class="d-none d-md-block">Temukan berbagai produk fashion terbaru dengan kualitas terbaik.</p>
        <a href="#produk" class="btn btn-primary-custom btn-lg">Belanja Sekarang</a>
      </div>
    </div>
    <div class="carousel-item">
      <img src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1400" class="d-block w-100" alt="Banner 2">
      <div class="carousel-caption text-white">
        <h2>Diskon Spesial Hingga 50%</h2>
        <p class="d-none d-md-block">Jangan lewatkan promo menarik untuk semua kategori produk pilihan.</p>
        <a href="#produk" class="btn btn-primary-custom btn-lg">Belanja Sekarang</a>
      </div>
    </div>
    <div class="carousel-item">
      <img src="https://images.unsplash.com/photo-1445205170230-053b83016050?w=1400" class="d-block w-100" alt="Banner 3">
      <div class="carousel-caption text-white">
        <h2>Gratis Ongkir Se-Indonesia</h2>
        <p class="d-none d-md-block">Belanja lebih hemat dengan gratis ongkir untuk pembelian tertentu.</p>
        <a href="#produk" class="btn btn-primary-custom btn-lg">Belanja Sekarang</a>
      </div>
    </div>
  </div>
  <button class="carousel-control-prev" type="button" data-bs-target="#carouselToko" data-bs-slide="prev">
    <span class="carousel-control-prev-icon"></span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#carouselToko" data-bs-slide="next">
    <span class="carousel-control-next-icon"></span>
  </button>
</div>

<!-- ============================== -->
<!-- KEUNGGULAN / INFORMASI -->
<!-- ============================== -->
<section class="py-5 bg-white">
  <div class="container text-center">
    <h3 class="fw-bold mb-1">Kenapa Belanja di TokoKita?</h3>
    <p class="text-muted mb-5">Kami berkomitmen memberikan pengalaman belanja terbaik untuk Anda</p>
    <div class="row g-4">
      <div class="col-6 col-md-3">
        <div class="keunggulan-icon"><i class="bi bi-patch-check-fill"></i></div>
        <h6 class="fw-bold">Produk Berkualitas</h6>
        <p class="text-muted small">Setiap produk melalui seleksi kualitas terbaik.</p>
      </div>
      <div class="col-6 col-md-3">
        <div class="keunggulan-icon"><i class="bi bi-tags-fill"></i></div>
        <h6 class="fw-bold">Harga Terjangkau</h6>
        <p class="text-muted small">Harga bersaing untuk semua kalangan.</p>
      </div>
      <div class="col-6 col-md-3">
        <div class="keunggulan-icon"><i class="bi bi-cart-check-fill"></i></div>
        <h6 class="fw-bold">Proses Mudah</h6>
        <p class="text-muted small">Belanja cepat hanya beberapa klik saja.</p>
      </div>
      <div class="col-6 col-md-3">
        <div class="keunggulan-icon"><i class="bi bi-truck"></i></div>
        <h6 class="fw-bold">Pengiriman Cepat</h6>
        <p class="text-muted small">Pesanan dikirim dengan cepat dan aman.</p>
      </div>
    </div>
  </div>
</section>

<!-- ============================== -->
<!-- KATEGORI PRODUK -->
<!-- ============================== -->
<section class="py-5">
  <div class="container">
    <h3 class="fw-bold text-center mb-4">Kategori Produk</h3>
    <div class="text-center">
      <a href="produk.php" class="btn btn-kategori <?= $id_kategori_filter == 0 ? 'btn-primary-custom' : 'btn-outline-secondary' ?>">
        Semua Produk
      </a>
      <?php foreach ($kategoriList as $kat): ?>
        <a href="kategori.php?kategori=<?= (int) $kat['id_kategori'] ?>#produk"
           class="btn btn-kategori <?= $id_kategori_filter == $kat['id_kategori'] ? 'btn-primary-custom' : 'btn-outline-secondary' ?>">
          <?= htmlspecialchars(trim($kat['nama_kategori'])) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============================== -->
<!-- DAFTAR PRODUK -->
<!-- ============================== -->
<section class="py-4" id="produk">
  <div class="container">
    <h3 class="fw-bold text-center mb-4">Produk Kami</h3>

    <?php if (count($produkList) === 0): ?>
      <div class="alert alert-warning text-center">
        Belum ada produk untuk ditampilkan.
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($produkList as $produk):
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
                      <input type="hidden" name="return_to" value="index1.php">
                      <button type="submit" class="btn btn-primary-custom btn-sm w-100">
                        <i class="bi bi-cart-plus"></i> Tambah ke Keranjang
                      </button>
                    </form>
                  <?php endif; ?>
                  <a href="detail_produk.php?id=<?= (int) $produk['id'] ?>" class="btn btn-outline-dark btn-sm">
                    <i class="bi bi-eye"></i> Lihat Detail
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- ============================== -->
<!-- FOOTER -->
<!-- ============================== -->
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
<?php if (is_array($notifikasi)): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
Swal.fire(<?= json_encode($notifikasi, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>);
</script>
<?php endif; ?>

</body>
</html>