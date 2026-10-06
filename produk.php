<?php
session_start();
include "koneksi.php";
$notifikasi = $_SESSION['flash_sweetalert'] ?? null;
unset($_SESSION['flash_sweetalert']);

// ==============================
// Status login
// ==============================
$is_login   = isset($_SESSION['user_id']);
$nama_user  = $is_login ? $_SESSION['user_nama'] : '';

// Jumlah item di keranjang
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}
$jumlah_keranjang = array_sum($_SESSION['keranjang']);

// ==============================
// Filter kategori dan search
// ==============================
$id_kategori_filter = isset($_GET['kategori']) ? (int) $_GET['kategori'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sortby = isset($_GET['sort']) ? $_GET['sort'] : 'terbaru';

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
// Build query produk dengan filter
// ==============================
$sql = "SELECT p.id, p.nama, p.harga, p.stok, p.foto, p.deskripsi, k.nama_kategori
        FROM tb_produk p
        LEFT JOIN tb_kategori k ON p.id_kategori = k.id_kategori
        WHERE 1=1";

if ($id_kategori_filter > 0) {
    $sql .= " AND p.id_kategori = $id_kategori_filter";
}

if ($search !== '') {
    $search_esc = mysqli_real_escape_string($koneksi, $search);
    $sql .= " AND (p.nama LIKE '%$search_esc%' OR p.deskripsi LIKE '%$search_esc%')";
}

// Sort
if ($sortby === 'harga_terendah') {
    $sql .= " ORDER BY p.harga ASC";
} elseif ($sortby === 'harga_tertinggi') {
    $sql .= " ORDER BY p.harga DESC";
} else {
    $sql .= " ORDER BY p.id DESC"; // terbaru
}

$produkList = [];
$resProduk = mysqli_query($koneksi, $sql);
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
<title>Produk - TokoKita</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
    :root{ --primary-color:#ff6f3c; --dark-color:#212529; }
    body{ font-family:'Segoe UI', sans-serif; background-color:#f8f9fa; }
    .navbar{ box-shadow:0 2px 8px rgba(0,0,0,0.08); }
    .btn-primary-custom{ background-color:var(--primary-color); border-color:var(--primary-color); color:#fff; }
    .btn-primary-custom:hover{ background-color:#e85a28; border-color:#e85a28; color:#fff; }
    .sidebar-filter{ background:#fff; border-radius:14px; padding:20px; box-shadow:0 3px 10px rgba(0,0,0,0.08); }
    .filter-title{ font-weight:700; margin-bottom:15px; }
    .filter-group{ margin-bottom:20px; }
    .filter-group label{ display:flex; align-items:center; margin-bottom:8px; cursor:pointer; }
    .filter-group input[type="radio"], .filter-group input[type="checkbox"]{ margin-right:8px; }
    .card-produk{ border:none; border-radius:14px; overflow:hidden; box-shadow:0 3px 10px rgba(0,0,0,0.08); transition:transform 0.2s ease, box-shadow 0.2s ease; height:100%; }
    .card-produk:hover{ transform:translateY(-5px); box-shadow:0 8px 18px rgba(0,0,0,0.15); }
    .card-produk img{ height:200px; object-fit:cover; width:100%; }
    .badge-kategori{ background-color:#ffe4d6; color:var(--primary-color); font-weight:500; }
    .harga-produk{ color:var(--primary-color); font-weight:700; font-size:1.15rem; }
    .search-box{ background:#fff; border-radius:14px; padding:30px; box-shadow:0 3px 10px rgba(0,0,0,0.08); margin-bottom:30px; }
    footer{ background-color:var(--dark-color); color:#adb5bd; }
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
        <li class="nav-item"><a class="nav-link" href="kategori.php">Kategori</a></li>
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

<!-- ===== SEARCH BOX ===== -->
<div class="search-box container my-4">
  <h3 class="fw-bold mb-3">Cari Produk</h3>
  <form action="produk.php" method="GET" class="row g-3">
    <div class="col-md-8">
      <input type="text" class="form-control form-control-lg" name="search" 
             placeholder="Cari nama produk..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-4">
      <button type="submit" class="btn btn-primary-custom btn-lg w-100">
        <i class="bi bi-search"></i> Cari
      </button>
    </div>
  </form>
</div>

<div class="container py-4">
  <div class="row g-4">
    <!-- ===== SIDEBAR FILTER ===== -->
    <div class="col-lg-3">
      <div class="sidebar-filter position-sticky" style="top:20px;">
        <div class="filter-title">Filter Produk</div>

        <!-- Filter Kategori -->
        <div class="filter-group">
          <label class="fw-bold mb-2">Kategori</label>
          <form action="produk.php" method="GET">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <div>
              <label>
                <input type="radio" name="kategori" value="0" 
                       <?= $id_kategori_filter == 0 ? 'checked' : '' ?> onchange="this.form.submit()">
                Semua Kategori
              </label>
            </div>
            <?php foreach ($kategoriList as $kat): ?>
              <label>
                <input type="radio" name="kategori" value="<?= $kat['id_kategori'] ?>"
                       <?= $id_kategori_filter == $kat['id_kategori'] ? 'checked' : '' ?> onchange="this.form.submit()">
                <?= htmlspecialchars(trim($kat['nama_kategori'])) ?>
              </label>
            <?php endforeach; ?>
          </form>
        </div>

        <!-- Filter Sorting -->
        <div class="filter-group">
          <label class="fw-bold mb-2">Urutkan</label>
          <form action="produk.php" method="GET">
            <input type="hidden" name="kategori" value="<?= $id_kategori_filter ?>">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <select name="sort" class="form-select" onchange="this.form.submit()">
              <option value="terbaru" <?= $sortby === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
              <option value="harga_terendah" <?= $sortby === 'harga_terendah' ? 'selected' : '' ?>>Harga Terendah</option>
              <option value="harga_tertinggi" <?= $sortby === 'harga_tertinggi' ? 'selected' : '' ?>>Harga Tertinggi</option>
            </select>
          </form>
        </div>
      </div>
    </div>

    <!-- ===== DAFTAR PRODUK ===== -->
    <div class="col-lg-9">
      <?php if (count($produkList) === 0): ?>
        <div class="alert alert-info text-center py-5">
          <i class="bi bi-inbox" style="font-size:3rem;"></i>
          <h5 class="mt-3">Produk tidak ditemukan</h5>
          <p class="text-muted">Coba gunakan kata kunci lain atau ubah filter.</p>
          <a href="produk.php" class="btn btn-primary-custom">Reset Filter</a>
        </div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($produkList as $produk):
              $stok_habis = $produk['stok'] <= 0;
              $fotoSrc = !empty($produk['foto'])
                  ? (filter_var($produk['foto'], FILTER_VALIDATE_URL) ? $produk['foto'] : "assets/img/" . $produk['foto'])
                  : "https://placehold.co/400x300?text=Produk";
          ?>
            <div class="col-6 col-md-4">
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
                        <input type="hidden" name="return_to" value="produk.php">
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