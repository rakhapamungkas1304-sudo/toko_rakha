<?php
session_start();
include "koneksi.php";

// Pengaman agar pelanggan tidak bisa masuk ke dashboard
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
$notifikasi = $_SESSION['flash_sweetalert'] ?? null;
unset($_SESSION['flash_sweetalert']);

// ==============================
// TENTUKAN PAGE/MENU
// ==============================
$page = isset($_GET['page']) ? $_GET['page'] : 'produk';
$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : '';

// ==============================
// PROSES AKSI PRODUK
// ==============================
if ($page === 'produk') {
    if ($aksi == "edit") {
        $id = (int) ($_GET['id'] ?? 0);
        $stmt = mysqli_prepare($koneksi, "SELECT * FROM tb_produk p INNER JOIN tb_kategori k ON p.id_kategori = k.id_kategori WHERE p.id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $hasil = mysqli_stmt_get_result($stmt);
        while ($data = mysqli_fetch_array($hasil)) {
            $nama_produk = $data['nama'];
            $harga = $data['harga'];
            $stok = $data['stok'];
            $kategori = $data['id_kategori'];
            $deskripsi = $data['deskripsi'];
            $poto = $data['foto'];
        }
    } else if ($aksi == "hapus") {
        $id = (int) ($_GET['id'] ?? 0);
        $stmt = mysqli_prepare($koneksi, "DELETE FROM tb_produk WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        $hapus = mysqli_stmt_execute($stmt);
        $_SESSION['flash_sweetalert'] = $hapus && mysqli_stmt_affected_rows($stmt) > 0
            ? ['icon' => 'success', 'title' => 'Produk dihapus', 'text' => 'Produk berhasil dihapus.']
            : ['icon' => 'error', 'title' => 'Gagal menghapus produk', 'text' => 'Produk tidak ditemukan atau gagal dihapus.'];
        header("Location: dashboard.php?page=produk");
        exit;
    }

    if (isset($_POST['tambah'])) {
        $nama_produk = $_POST['nama'];
        $harga = $_POST['harga'];
        $stok = $_POST['stok'];
        $kategori = $_POST['kategori'];
        $deskripsi = $_POST['deskripsi'];
        $poto = $_FILES['foto']['name'];

        if ($aksi == 'edit') {
            if (!empty($poto)) {
                $path = "assets/img/" . $poto;
                $file_tmp = $_FILES['foto']['tmp_name'];
                move_uploaded_file($file_tmp, $path);
            } else {
                $poto = $_POST['foto_lama'];
            }

            $simpan = mysqli_query($koneksi, "UPDATE tb_produk SET nama='$nama_produk', harga='$harga', stok='$stok', id_kategori='$kategori', deskripsi='$deskripsi', foto='$poto' WHERE id='$id'");
            $_SESSION['flash_sweetalert'] = $simpan
                ? ['icon' => 'success', 'title' => 'Produk diperbarui', 'text' => 'Perubahan produk berhasil disimpan.']
                : ['icon' => 'error', 'title' => 'Gagal memperbarui produk', 'text' => 'Perubahan produk tidak berhasil disimpan.'];
            header("Location: dashboard.php?page=produk");
            exit;
        } else {
            $path = "assets/img/" . $poto;
            $file_tmp = $_FILES['foto']['tmp_name'];
            move_uploaded_file($file_tmp, $path);
            $simpan = mysqli_query($koneksi, "INSERT INTO tb_produk (nama, harga, stok, id_kategori, deskripsi, foto) VALUES ('$nama_produk', '$harga', '$stok','$kategori','$deskripsi', '$poto')");

            $_SESSION['flash_sweetalert'] = $simpan
                ? ['icon' => 'success', 'title' => 'Produk ditambahkan', 'text' => 'Produk baru berhasil ditambahkan.']
                : ['icon' => 'error', 'title' => 'Gagal menambahkan produk', 'text' => 'Produk baru tidak berhasil disimpan.'];
            header("Location: dashboard.php?page=produk");
            exit;
        }
    }
}

// ==============================
// PROSES AKSI TRANSAKSI
// ==============================
if ($page === 'transaksi' && $aksi == 'hapus') {
    $id = (int) ($_GET['id'] ?? 0);
    $stmtDetail = mysqli_prepare($koneksi, "DELETE FROM tb_detail WHERE id_transaksi = ?");
    mysqli_stmt_bind_param($stmtDetail, "i", $id);
    $hapusDetail = mysqli_stmt_execute($stmtDetail);
    $stmtTransaksi = mysqli_prepare($koneksi, "DELETE FROM tb_transaksi WHERE id_transaksi = ?");
    mysqli_stmt_bind_param($stmtTransaksi, "i", $id);
    $hapusTransaksi = mysqli_stmt_execute($stmtTransaksi);
    $_SESSION['flash_sweetalert'] = $hapusDetail && $hapusTransaksi && mysqli_stmt_affected_rows($stmtTransaksi) > 0
        ? ['icon' => 'success', 'title' => 'Transaksi dihapus', 'text' => 'Transaksi dan detailnya berhasil dihapus.']
        : ['icon' => 'error', 'title' => 'Gagal menghapus transaksi', 'text' => 'Transaksi tidak ditemukan atau gagal dihapus.'];
    header("Location: dashboard.php?page=transaksi");
    exit;
}

// ==============================
// PROSES RESET PASSWORD PELANGGAN
// ==============================
if ($page === 'pelanggan' && $aksi == 'reset_password') {
    $id = (int) ($_GET['id'] ?? 0);
    $password_default = '1234'; // password default setelah reset
    $stmt = mysqli_prepare($koneksi, "UPDATE tb_user SET password = ? WHERE id = ? AND role = 'pelanggan'");
    mysqli_stmt_bind_param($stmt, "si", $password_default, $id);
    $update = mysqli_stmt_execute($stmt);
    $_SESSION['flash_sweetalert'] = $update
        ? ['icon' => 'success', 'title' => 'Password direset', 'text' => 'Password pelanggan direset menjadi 1234.']
        : ['icon' => 'error', 'title' => 'Gagal reset password', 'text' => 'Password pelanggan tidak berhasil direset.'];
    header("Location: dashboard.php?page=pelanggan");
    exit;
}

// ==============================
// PROSES HAPUS PELANGGAN
// ==============================
if ($page === 'pelanggan' && $aksi == 'hapus') {
    $id = (int) ($_GET['id'] ?? 0);
    $stmt = mysqli_prepare($koneksi, "DELETE FROM tb_user WHERE id = ? AND role = 'pelanggan'");
    mysqli_stmt_bind_param($stmt, "i", $id);
    $hapus = mysqli_stmt_execute($stmt);
    $_SESSION['flash_sweetalert'] = $hapus && mysqli_stmt_affected_rows($stmt) > 0
        ? ['icon' => 'success', 'title' => 'Pelanggan dihapus', 'text' => 'Akun pelanggan berhasil dihapus.']
        : ['icon' => 'error', 'title' => 'Gagal menghapus pelanggan', 'text' => 'Pelanggan tidak ditemukan atau gagal dihapus.'];
    header("Location: dashboard.php?page=pelanggan");
    exit;
}
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard Admin - TokoKita</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <style>
    :root {
      --primary-color: #ff6f3c;
      --dark-color: #212529;
    }

    body {
      background-color: #f8f9fa;
    }

    .sidebar {
      background-color: #343a40;
      min-height: 100vh;
      padding: 0;
    }

    .sidebar .nav-link {
      color: #adb5bd;
      font-weight: 500;
      padding: 12px 20px;
      border-left: 3px solid transparent;
      transition: all 0.3s ease;
    }

    .sidebar .nav-link:hover {
      color: #fff;
      background-color: rgba(255, 111, 60, 0.1);
      border-left-color: var(--primary-color);
    }

    .sidebar .nav-link.active {
      color: #fff;
      background-color: rgba(255, 111, 60, 0.2);
      border-left-color: var(--primary-color);
    }

    .sidebar .nav-heading {
      color: #6c757d;
      font-size: 0.8rem;
      font-weight: 700;
      text-transform: uppercase;
      padding: 12px 20px;
      margin-top: 15px;
      margin-bottom: 10px;
    }

    .main-content {
      padding: 30px;
    }

    .page-header {
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 2px solid #dee2e6;
    }

    .page-header h1 {
      color: var(--dark-color);
      font-weight: 700;
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

    .form-section {
      background-color: #fff;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
      margin-bottom: 30px;
    }

    .form-section h3 {
      color: var(--dark-color);
      font-weight: 700;
      margin-bottom: 20px;
      font-size: 1.3rem;
    }

    .table-section {
      background-color: #fff;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .table-section h3 {
      color: var(--dark-color);
      font-weight: 700;
      margin-bottom: 20px;
      font-size: 1.3rem;
    }

    .table thead {
      background-color: #f8f9fa;
    }

    .table thead th {
      color: var(--dark-color);
      font-weight: 600;
      border-bottom: 2px solid #dee2e6;
    }

    .table tbody tr {
      border-bottom: 1px solid #dee2e6;
    }

    .table tbody tr:hover {
      background-color: #f8f9fa;
    }

    .badge-kategori {
      background-color: #ffe4d6;
      color: var(--primary-color);
      font-weight: 500;
    }

    .brand-text {
      color: #fff;
      font-weight: 700;
      font-size: 1.3rem;
      padding: 15px 20px;
      border-bottom: 1px solid #495057;
      display: block;
    }

    .brand-text .text-primary {
      color: var(--primary-color) !important;
    }

    footer {
      background-color: var(--dark-color);
      color: #adb5bd;
      padding: 20px;
      text-align: center;
      border-top: 1px solid #495057;
    }

    .logout-btn {
      color: #adb5bd;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 12px 20px;
      transition: all 0.3s ease;
    }

    .logout-btn:hover {
      color: #dc3545;
    }
  </style>
</head>
<body>
  <div class="container-fluid">
    <div class="row g-0">
      <!-- SIDEBAR -->
      <nav class="col-md-3 col-lg-2 sidebar">
        <div class="brand-text">
          Toko<span class="text-primary">Kita</span>
        </div>

        <ul class="nav flex-column">
          <li class="nav-heading">MANAJEMEN</li>
          <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-2 <?= ($page === 'produk') ? 'active' : '' ?>" href="dashboard.php?page=produk">
              <i class="bi bi-box-seam"></i> Produk
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-2 <?= ($page === 'transaksi') ? 'active' : '' ?>" href="dashboard.php?page=transaksi">
              <i class="bi bi-receipt"></i> Transaksi
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-2 <?= ($page === 'pelanggan') ? 'active' : '' ?>" href="dashboard.php?page=pelanggan">
              <i class="bi bi-people"></i> Pelanggan
            </a>
          </li>
        </ul>

        <hr class="my-3" style="border-color: #495057;">

        <ul class="nav flex-column">
          <li class="nav-item">
            <a class="logout-btn" href="logout.php" data-swal-confirm data-swal-title="Logout?" data-swal-text="Anda akan keluar dari akun admin.">
              <i class="bi bi-box-arrow-right"></i> Logout
            </a>
          </li>
        </ul>
      </nav>

      <!-- MAIN CONTENT -->
      <main class="col-md-9 col-lg-10">
        <div class="main-content">

          <!-- ===== HALAMAN PRODUK ===== -->
          <?php if ($page === 'produk'): ?>
            <div class="page-header">
              <h1><i class="bi bi-box-seam me-2"></i>Manajemen Produk</h1>
            </div>

            <!-- Form Tambah/Edit Produk -->
            <div class="form-section">
              <h3><?= ($aksi == 'edit') ? 'Edit Produk' : 'Tambah Produk Baru' ?></h3>
              <form action="" method="POST" enctype="multipart/form-data" data-swal-form data-swal-title="<?= ($aksi == 'edit') ? 'Simpan perubahan produk?' : 'Tambahkan produk?' ?>" data-swal-text="Pastikan informasi produk sudah benar.">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label fw-bold">Nama Produk</label>
                    <input type="text" class="form-control" name="nama" value="<?= @$nama_produk ?>" required>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fw-bold">Kategori</label>
                    <select name="kategori" class="form-select" required>
                      <option value="">-- Pilih Kategori --</option>
                      <?php
                        $result = mysqli_query($koneksi, "SELECT * FROM tb_kategori");
                        while ($list = mysqli_fetch_array($result)) { ?>
                          <option value="<?= $list['id_kategori'] ?>" 
                          <?php if (isset($kategori) && $list['id_kategori'] == $kategori) echo "selected"; ?>>
                            <?= $list['nama_kategori'] ?>
                          </option>
                      <?php } ?>
                    </select>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fw-bold">Harga (Rp)</label>
                    <div class="input-group">
                      <span class="input-group-text">Rp</span>
                      <input type="number" class="form-control" name="harga" value="<?= @$harga ?>" required>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fw-bold">Stok</label>
                    <input type="number" class="form-control" name="stok" value="<?= @$stok ?>" required>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fw-bold">Foto Produk</label>
                    <input type="file" class="form-control" name="foto" accept="image/*">
                    <input type="hidden" name="foto_lama" value="<?= @$poto ?>">
                    <?php if (isset($poto)) { ?>
                      <div class="mt-2">
                        <small class="text-muted">Foto saat ini:</small>
                        <br>
                        <img src="assets/img/<?= $poto ?>" width="80px" class="mt-1 rounded" alt="<?= @$nama_produk ?>">
                      </div>
                    <?php } ?>
                  </div>

                  <div class="col-12">
                    <label class="form-label fw-bold">Deskripsi</label>
                    <textarea class="form-control" name="deskripsi" rows="3"><?= @$deskripsi ?></textarea>
                  </div>

                  <div class="col-12">
                    <button type="submit" name="tambah" class="btn btn-primary-custom">
                      <i class="bi bi-save me-1"></i> <?= ($aksi == 'edit') ? 'Perbarui' : 'Tambah' ?>
                    </button>
                    <?php if ($aksi == 'edit') { ?>
                      <a href="dashboard.php?page=produk" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Batal
                      </a>
                    <?php } ?>
                  </div>
                </div>
              </form>
            </div>

            <!-- Tabel Produk -->
            <div class="table-section">
              <h3>Daftar Produk</h3>
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th style="width: 5%;">No</th>
                      <th>Nama Produk</th>
                      <th style="width: 12%;">Kategori</th>
                      <th style="width: 15%;">Harga</th>
                      <th style="width: 8%;">Stok</th>
                      <th style="width: 15%;">Foto</th>
                      <th style="width: 20%;">Deskripsi</th>
                      <th style="width: 15%;">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                      $no = 1;
                      $hasil = mysqli_query($koneksi, "SELECT * FROM tb_produk p LEFT JOIN tb_kategori k ON p.id_kategori = k.id_kategori");
                      while ($data = mysqli_fetch_array($hasil)) { ?>
                        <tr>
                          <td><?= $no++ ?></td>
                          <td class="fw-bold"><?= $data['nama'] ?></td>
                          <td><span class="badge badge-kategori"><?= $data['nama_kategori'] ?? 'N/A' ?></span></td>
                          <td>Rp <?= number_format($data['harga'] ?? 0, 0, ',', '.') ?></td>
                          <td>
                            <span class="badge <?= (($data['stok'] ?? 0) > 0) ? 'bg-success' : 'bg-danger' ?>">
                              <?= (int)($data['stok'] ?? 0) ?>
                            </span>
                          </td>
                          <td>
                            <?php if (!empty($data['foto'])) { ?>
                              <img src="assets/img/<?= $data['foto'] ?>" width="60px" class="rounded" alt="">
                            <?php } else { ?>
                              <span class="text-muted">-</span>
                            <?php } ?>
                          </td>
                          <td><small><?= substr($data['deskripsi'] ?? '', 0, 40) ?>...</small></td>
                          <td>
                            <a href="dashboard.php?page=produk&aksi=edit&id=<?= $data['id'] ?>" class="btn btn-sm btn-warning">
                              <i class="bi bi-pencil"></i> Edit
                            </a>
                            <a href="dashboard.php?page=produk&aksi=hapus&id=<?= (int) $data['id'] ?>" class="btn btn-sm btn-danger" data-swal-confirm data-swal-title="Hapus produk?" data-swal-text="Produk ini akan dihapus secara permanen.">
                              <i class="bi bi-trash"></i> Hapus
                            </a>
                          </td>
                        </tr>
                      <?php } ?>
                  </tbody>
                </table>
              </div>
            </div>

          <!-- ===== HALAMAN TRANSAKSI ===== -->
          <?php elseif ($page === 'transaksi'): ?>
            <div class="page-header">
              <h1><i class="bi bi-receipt me-2"></i>Data Transaksi</h1>
            </div>

            <div class="table-section">
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th style="width: 5%;">No</th>
                      <th style="width: 12%;">No. Invoice</th>
                      <th style="width: 15%;">Nama Pelanggan</th>
                      <th style="width: 12%;">Tanggal</th>
                      <th style="width: 10%;">Jumlah Item</th>
                      <th style="width: 15%;">Total Harga</th>
                      <th style="width: 20%;">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                      $no = 1;
                      $modalBuffer = ''; // menampung HTML modal, dicetak nanti DI LUAR tabel
                      $hasil = mysqli_query($koneksi, "SELECT t.*, u.nama, u.email, u.hp, u.alamat FROM tb_transaksi t JOIN tb_user u ON t.id_pelanggan = u.id ORDER BY t.id_transaksi DESC");
                      if (mysqli_num_rows($hasil) > 0) {
                        while ($data = mysqli_fetch_array($hasil)) {
                          $id_transaksi = $data['id_transaksi'];

                          // Hitung jumlah item
                          $detail = mysqli_query($koneksi, "SELECT SUM(jumlah) as total_item FROM tb_detail WHERE id_transaksi = '$id_transaksi'");
                          $item_count = mysqli_fetch_array($detail);

                          // Ambil detail produk untuk transaksi ini (dipakai di modal)
                          $itemsHasil = mysqli_query($koneksi, "
                            SELECT td.jumlah, p.nama AS nama_produk, p.harga, p.foto
                            FROM tb_detail td
                            LEFT JOIN tb_produk p ON td.id_produk = p.id
                            WHERE td.id_transaksi = '$id_transaksi'
                          ");

                          // Ambil metode pembayaran/pengiriman jika kolomnya sudah ada
                          $payment_method  = $data['payment_method']  ?? null;
                          $shipping_method = $data['shipping_method'] ?? null;
                          $biaya_kirim     = $data['biaya_pengiriman'] ?? 0;

                          $labelPayment = [
                            'transfer_bank'    => 'Transfer Bank',
                            'e_wallet'         => 'E-Wallet',
                            'cash_on_delivery' => 'Cash On Delivery (COD)',
                          ];
                          $labelShipping = [
                            'regular'  => 'Regular (2-5 Hari)',
                            'express'  => 'Express (1-2 Hari)',
                            'same_day' => 'Same Day',
                          ];
                    ?>
                        <tr>
                          <td><?= $no++ ?></td>
                          <td><strong>INV-<?= str_pad($id_transaksi, 5, '0', STR_PAD_LEFT) ?></strong></td>
                          <td><?= $data['nama'] ?></td>
                          <td><?= date('d M Y', strtotime($data['tanggal'])) ?></td>
                          <td><?= $item_count['total_item'] ?? 0 ?> item</td>
                          <td><strong>Rp <?= number_format($data['total_harga'], 0, ',', '.') ?></strong></td>
                          <td>
                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $id_transaksi ?>">
                              <i class="bi bi-receipt-cutoff"></i> Detail Transaksi
                            </button>
                            <a href="dashboard.php?page=transaksi&aksi=hapus&id=<?= (int) $id_transaksi ?>" class="btn btn-sm btn-danger" data-swal-confirm data-swal-title="Hapus transaksi?" data-swal-text="Transaksi beserta detailnya akan dihapus.">
                              <i class="bi bi-trash"></i> Hapus
                            </a>
                          </td>
                        </tr>

                        <?php ob_start(); // mulai tangkap HTML modal, JANGAN dicetak di sini (di dalam tbody) ?>
                        <!-- ===== MODAL DETAIL TRANSAKSI (berisi invoice lengkap) ===== -->
                        <div class="modal fade" id="modalDetail<?= $id_transaksi ?>" tabindex="-1" aria-hidden="true">
                          <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                              <div class="modal-header" style="background-color:var(--primary-color);color:#fff;">
                                <h5 class="modal-title fw-bold">
                                  <i class="bi bi-receipt-cutoff me-2"></i>Detail Transaksi - INV-<?= str_pad($id_transaksi, 5, '0', STR_PAD_LEFT) ?>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                              </div>
                              <div class="modal-body p-4">

                                <!-- Info Invoice & Pelanggan -->
                                <div class="row g-3 mb-4">
                                  <div class="col-md-6">
                                    <h6 class="fw-bold text-muted mb-2">INFORMASI PELANGGAN</h6>
                                    <p class="mb-1"><strong><?= htmlspecialchars($data['nama']) ?></strong></p>
                                    <p class="mb-1 small text-muted"><?= htmlspecialchars($data['email'] ?? '-') ?></p>
                                    <p class="mb-1 small text-muted"><?= htmlspecialchars($data['hp'] ?? '-') ?></p>
                                    <p class="mb-0 small text-muted"><?= htmlspecialchars($data['alamat'] ?? '-') ?></p>
                                  </div>
                                  <div class="col-md-6 text-md-end">
                                    <h6 class="fw-bold text-muted mb-2">INFORMASI INVOICE</h6>
                                    <p class="mb-1">No. Invoice: <strong>INV-<?= str_pad($id_transaksi, 5, '0', STR_PAD_LEFT) ?></strong></p>
                                    <p class="mb-1">Tanggal: <strong><?= date('d M Y H:i', strtotime($data['tanggal'])) ?></strong></p>
                                    <p class="mb-1">Pembayaran: <strong><?= $labelPayment[$payment_method] ?? '-' ?></strong></p>
                                    <p class="mb-0">Pengiriman: <strong><?= $labelShipping[$shipping_method] ?? '-' ?></strong></p>
                                  </div>
                                </div>

                                <!-- Tabel Produk -->
                                <div class="table-responsive">
                                  <table class="table table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                      <tr>
                                        <th>Produk</th>
                                        <th class="text-center" style="width:90px;">Qty</th>
                                        <th class="text-end" style="width:150px;">Harga Satuan</th>
                                        <th class="text-end" style="width:150px;">Subtotal</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <?php
                                        $subtotalProduk = 0;
                                        mysqli_data_seek($itemsHasil, 0);
                                        while ($item = mysqli_fetch_assoc($itemsHasil)):
                                          $st = ($item['harga'] ?? 0) * $item['jumlah'];
                                          $subtotalProduk += $st;
                                      ?>
                                        <tr>
                                          <td><?= htmlspecialchars($item['nama_produk'] ?? 'Produk telah dihapus') ?></td>
                                          <td class="text-center"><?= (int) $item['jumlah'] ?></td>
                                          <td class="text-end">Rp <?= number_format($item['harga'] ?? 0, 0, ',', '.') ?></td>
                                          <td class="text-end">Rp <?= number_format($st, 0, ',', '.') ?></td>
                                        </tr>
                                      <?php endwhile; ?>
                                    </tbody>
                                  </table>
                                </div>

                                <!-- Ringkasan Total -->
                                <div class="row justify-content-end mt-3">
                                  <div class="col-md-6">
                                    <div class="d-flex justify-content-between mb-1">
                                      <span class="text-muted">Subtotal Produk</span>
                                      <span>Rp <?= number_format($subtotalProduk, 0, ',', '.') ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                      <span class="text-muted">Biaya Pengiriman</span>
                                      <span>Rp <?= number_format($biaya_kirim, 0, ',', '.') ?></span>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between fs-5">
                                      <strong>Total Bayar</strong>
                                      <strong style="color:var(--primary-color);">Rp <?= number_format($data['total_harga'], 0, ',', '.') ?></strong>
                                    </div>
                                  </div>
                                </div>

                              </div>
                              <div class="modal-footer">
                                <a href="invoice.php?id=<?= $id_transaksi ?>" target="_blank" class="btn btn-outline-secondary">
                                  <i class="bi bi-printer me-1"></i> Cetak Invoice
                                </a>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                              </div>
                            </div>
                          </div>
                        </div>
                        <?php $modalBuffer .= ob_get_clean(); // simpan HTML modal, akan dicetak setelah tabel ?>
                    <?php }
                      } else { ?>
                        <tr>
                          <td colspan="7" class="text-center text-muted py-4">Belum ada transaksi</td>
                        </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Modal-modal Detail Transaksi dicetak DI SINI, di luar <table>, agar HTML valid -->
            <?= $modalBuffer ?>

          <!-- ===== HALAMAN PELANGGAN ===== -->
          <?php elseif ($page === 'pelanggan'): ?>
            <div class="page-header">
              <h1><i class="bi bi-people me-2"></i>Data Pelanggan</h1>
            </div>

            <div class="table-section">
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th style="width: 5%;">No</th>
                      <th style="width: 15%;">Nama</th>
                      <th style="width: 18%;">Email</th>
                      <th style="width: 12%;">Username</th>
                      <th style="width: 12%;">No. HP</th>
                      <th style="width: 20%;">Alamat</th>
                      <th style="width: 18%;">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                      $no = 1;
                      $hasil = mysqli_query($koneksi, "SELECT * FROM tb_user WHERE role = 'pelanggan' ORDER BY id DESC");
                      if (mysqli_num_rows($hasil) > 0) {
                        while ($data = mysqli_fetch_array($hasil)) { ?>
                          <tr>
                            <td><?= $no++ ?></td>
                            <td class="fw-bold"><?= $data['nama'] ?></td>
                            <td><?= $data['email'] ?></td>
                            <td><code><?= $data['username'] ?></code></td>
                            <td><?= $data['hp'] ?></td>
                            <td><small><?= substr($data['alamat'] ?? '', 0, 30) ?>...</small></td>
                            <td>
                              <a href="dashboard.php?page=pelanggan&aksi=reset_password&id=<?= (int) $data['id'] ?>" class="btn btn-sm btn-warning" data-swal-confirm data-swal-title="Reset password?" data-swal-text="Password pelanggan akan diubah menjadi 1234.">
                                <i class="bi bi-key"></i> Reset Password
                              </a>
                              <a href="dashboard.php?page=pelanggan&aksi=hapus&id=<?= (int) $data['id'] ?>" class="btn btn-sm btn-danger" data-swal-confirm data-swal-title="Hapus pelanggan?" data-swal-text="Akun pelanggan ini akan dihapus secara permanen.">
                                <i class="bi bi-trash"></i> Hapus
                              </a>
                            </td>
                          </tr>
                    <?php }
                      } else { ?>
                        <tr>
                          <td colspan="7" class="text-center text-muted py-4">Belum ada pelanggan</td>
                        </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
            </div>

          <?php endif; ?>

        </div>

        <!-- FOOTER -->
        <footer>
          <p class="mb-0">&copy; <?= date("Y") ?> TokoKita Dashboard. Semua Hak Cipta Dilindungi.</p>
        </footer>
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <?php if (is_array($notifikasi)): ?>
  <script>
    Swal.fire(<?= json_encode($notifikasi, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>);
  </script>
  <?php endif; ?>
  <script>
    document.querySelectorAll('[data-swal-confirm]').forEach((link) => {
      link.addEventListener('click', (event) => {
        event.preventDefault();
        Swal.fire({
          icon: 'warning',
          title: link.dataset.swalTitle,
          text: link.dataset.swalText,
          showCancelButton: true,
          confirmButtonText: 'Ya, lanjutkan',
          cancelButtonText: 'Batal',
          confirmButtonColor: '#dc3545',
          cancelButtonColor: '#6c757d'
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = link.href;
          }
        });
      });
    });

    document.querySelectorAll('form[data-swal-form]').forEach((form) => {
      form.addEventListener('submit', (event) => {
        if (form.dataset.confirmed === 'true') {
          delete form.dataset.confirmed;
          return;
        }
        event.preventDefault();
        Swal.fire({
          icon: 'question',
          title: form.dataset.swalTitle,
          text: form.dataset.swalText,
          showCancelButton: true,
          confirmButtonText: 'Ya, simpan',
          cancelButtonText: 'Periksa lagi',
          confirmButtonColor: '#ff6f3c',
          cancelButtonColor: '#6c757d'
        }).then((result) => {
          if (result.isConfirmed) {
            form.dataset.confirmed = 'true';
            form.requestSubmit();
          }
        });
      });
    });
  </script>
</body>
</html>