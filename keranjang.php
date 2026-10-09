<?php
session_start();
include "koneksi.php";

// ==============================
// Siapkan session keranjang
// Struktur: $_SESSION['keranjang'][id_produk] = jumlah
// ==============================
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

// ==============================
// ATURAN DISKON (ubah angka di sini sesuai kebutuhan)
// Format: minimal_belanja => persen_diskon
// ==============================
const ATURAN_DISKON = [
    500000 => 15,   // belanja >= Rp 500.000 -> diskon 15%
    250000 => 10,   // belanja >= Rp 250.000 -> diskon 10%
    100000 => 5,    // belanja >= Rp 100.000 -> diskon 5%
];

// Biaya kirim per kurir & metode bayar yang diizinkan (dipakai server DAN tampilan)
const BIAYA_KIRIM  = ['Reguler' => 15000, 'Express' => 25000];
const METODE_BAYAR = ['Transfer Bank', 'E-Wallet', 'COD'];

function hitungPersenDiskon(int $subtotal): int {
    foreach (ATURAN_DISKON as $minimal => $persen) {   // urut dari terbesar
        if ($subtotal >= $minimal) return $persen;
    }
    return 0;
}

// Cari tingkat diskon berikutnya (untuk pesan "belanja lagi Rp ... untuk diskon ...")
function diskonBerikutnya(int $subtotal): ?array {
    $aturan = ATURAN_DISKON;
    ksort($aturan);                                    // urut dari terkecil
    foreach ($aturan as $minimal => $persen) {
        if ($subtotal < $minimal) return ['minimal' => $minimal, 'persen' => $persen];
    }
    return null;
}

$is_login  = isset($_SESSION['user_id']);
$nama_user = $is_login ? $_SESSION['user_nama'] : '';
$pesan     = '';
$tipePesan = 'success';

// ==============================
// AKSI: ubah jumlah / hapus / kosongkan / checkout
// ==============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Ubah jumlah produk ---
    if (isset($_POST['update'])) {
        foreach ($_POST['jumlah'] as $id_produk => $jml) {
            $id_produk = (int) $id_produk;
            $jml       = (int) $jml;

            // Ambil stok asli dari database
            $stmt = mysqli_prepare($koneksi, "SELECT stok FROM tb_produk WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id_produk);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

            if ($row) {
                if ($jml <= 0) {
                    unset($_SESSION['keranjang'][$id_produk]);
                } else {
                    // Jumlah tidak boleh melebihi stok
                    $_SESSION['keranjang'][$id_produk] = min($jml, (int) $row['stok']);
                }
            }
        }
        $pesan = "Keranjang berhasil diperbarui.";
    }

    // --- Hapus satu produk ---
    if (isset($_POST['hapus'])) {
        $id_hapus = (int) $_POST['hapus'];
        unset($_SESSION['keranjang'][$id_hapus]);
        $pesan = "Produk berhasil dihapus dari keranjang.";
    }

    // --- Kosongkan keranjang ---
    if (isset($_POST['kosongkan'])) {
        $_SESSION['keranjang'] = [];
        $pesan = "Keranjang sudah dikosongkan.";
    }

    // --- Checkout / simpan transaksi ke database ---
    if (isset($_POST['checkout'])) {
        if (!$is_login) {
            $pesan = "Silakan login terlebih dahulu untuk melakukan checkout.";
            $tipePesan = "warning";
        } elseif (count($_SESSION['keranjang']) === 0) {
            $pesan = "Keranjang masih kosong.";
            $tipePesan = "warning";
        } else {
            // Ambil pilihan metode pembayaran dan pengiriman
            $metode_bayar = trim($_POST['metode_bayar'] ?? 'Transfer Bank');
            $metode_kirim = trim($_POST['metode_kirim'] ?? 'Reguler');
            if (!in_array($metode_bayar, METODE_BAYAR, true)) $metode_bayar = 'Transfer Bank';
            if (!isset(BIAYA_KIRIM[$metode_kirim])) $metode_kirim = 'Reguler';
            $biaya_kirim  = BIAYA_KIRIM[$metode_kirim];

            // Hitung ulang subtotal produk dari database
            $subtotal_produk = 0;
            $itemValid = [];

            foreach ($_SESSION['keranjang'] as $id_produk => $jml) {
                $stmt = mysqli_prepare($koneksi, "SELECT id, harga, stok FROM tb_produk WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $id_produk);
                mysqli_stmt_execute($stmt);
                $p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

                if ($p && $p['stok'] >= $jml) {
                    $subtotal_produk += $p['harga'] * $jml;
                    $itemValid[$id_produk] = $jml;
                }
            }

            if (count($itemValid) === 0) {
                $pesan = "Stok produk tidak mencukupi untuk checkout.";
                $tipePesan = "danger";
            } else {
                $id_pelanggan = (int) $_SESSION['user_id'];
                $tanggal = date("Y-m-d");
                $persen_diskon  = hitungPersenDiskon($subtotal_produk);
                $nominal_diskon = (int) round($subtotal_produk * $persen_diskon / 100);
                $total_akhir    = $subtotal_produk - $nominal_diskon + $biaya_kirim;

                // 1. Simpan ke tb_transaksi (pastikan tabel Anda sudah mendukung kolom metode_pembayaran, metode_pengiriman, biaya_kirim jika diperlukan, atau sesuaikan)
                // Jika struktur tabel belum ada kolom tersebut, kita masukkan ke total_harga saja atau simpan standar
                try {
                    $stmt = mysqli_prepare(
                        $koneksi,
                        "INSERT INTO tb_transaksi (id_pelanggan, tanggal, total_harga, metode_pembayaran, metode_pengiriman, biaya_kirim, diskon)
                         VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );
                    mysqli_stmt_bind_param($stmt, "isissii", $id_pelanggan, $tanggal, $total_akhir, $metode_bayar, $metode_kirim, $biaya_kirim, $nominal_diskon);
                    mysqli_stmt_execute($stmt);
                } catch (mysqli_sql_exception $e) {
                    // Kolom belum ada (jalankan UPDATE_TRANSAKSI_PEMBAYARAN.sql) -> simpan cara lama
                    if ($e->getCode() !== 1054) throw $e;
                    $stmt = mysqli_prepare(
                        $koneksi,
                        "INSERT INTO tb_transaksi (id_pelanggan, tanggal, total_harga) VALUES (?, ?, ?)"
                    );
                    mysqli_stmt_bind_param($stmt, "isi", $id_pelanggan, $tanggal, $total_akhir);
                    mysqli_stmt_execute($stmt);
                }
                $id_transaksi = mysqli_insert_id($koneksi);

                // 2. Simpan tiap produk ke tb_detail + kurangi stok
                foreach ($itemValid as $id_produk => $jml) {
                    $stmt = mysqli_prepare(
                        $koneksi,
                        "INSERT INTO tb_detail (id_transaksi, id_produk, jumlah) VALUES (?, ?, ?)"
                    );
                    mysqli_stmt_bind_param($stmt, "iii", $id_transaksi, $id_produk, $jml);
                    mysqli_stmt_execute($stmt);

                    $stmt = mysqli_prepare($koneksi, "UPDATE tb_produk SET stok = stok - ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt, "ii", $jml, $id_produk);
                    mysqli_stmt_execute($stmt);
                }

                // 3. Kosongkan keranjang, lalu arahkan ke invoice
                $_SESSION['keranjang'] = [];
                $_SESSION['flash_sweetalert'] = [
                    'icon' => 'success',
                    'title' => 'Checkout berhasil!',
                    'text' => 'Pesanan Anda berhasil diproses. Invoice telah dibuat.'
                ];
                header("Location: invoice.php?id=" . $id_transaksi);
                exit;
            }
        }
    }
}

// ==============================
// Ambil data produk yang ada di keranjang
// ==============================
$itemKeranjang = [];
$totalHarga = 0;
$totalItem = 0;

foreach ($_SESSION['keranjang'] as $id_produk => $jml) {
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT p.id, p.nama, p.harga, p.stok, p.foto, k.nama_kategori
         FROM tb_produk p
         LEFT JOIN tb_kategori k ON p.id_kategori = k.id_kategori
         WHERE p.id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $id_produk);
    mysqli_stmt_execute($stmt);
    $p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($p) {
        $p['jumlah']   = $jml;
        $p['subtotal'] = $p['harga'] * $jml;
        $itemKeranjang[] = $p;
        $totalHarga += $p['subtotal'];
        $totalItem  += $jml;
    }
}

// Diskon untuk tampilan ringkasan
$persenDiskon  = hitungPersenDiskon((int) $totalHarga);
$nominalDiskon = (int) round($totalHarga * $persenDiskon / 100);
$infoBerikutnya = diskonBerikutnya((int) $totalHarga);

// Pilihan kurir/pembayaran yang sedang dipilih (default: pilihan pertama)
$kirimTerpilih  = $_POST['metode_kirim'] ?? 'Reguler';
if (!isset(BIAYA_KIRIM[$kirimTerpilih])) $kirimTerpilih = 'Reguler';
$bayarTerpilih  = $_POST['metode_bayar'] ?? 'Transfer Bank';
if (!in_array($bayarTerpilih, METODE_BAYAR, true)) $bayarTerpilih = 'Transfer Bank';
$ongkirTerpilih = BIAYA_KIRIM[$kirimTerpilih];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Keranjang Belanja - TokoKita</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
    :root{ --primary-color:#ff6f3c; --dark-color:#212529; }
    body{ font-family:'Segoe UI', sans-serif; background-color:#f8f9fa; }
    .navbar{ box-shadow:0 2px 8px rgba(0,0,0,0.08); }
    .btn-primary-custom{ background-color:var(--primary-color); border-color:var(--primary-color); color:#fff; }
    .btn-primary-custom:hover{ background-color:#e85a28; border-color:#e85a28; color:#fff; }
    .img-keranjang{ width:80px; height:80px; object-fit:cover; border-radius:8px; }
    .card-ringkasan{ border:none; border-radius:14px; box-shadow:0 3px 10px rgba(0,0,0,0.08); }
    .harga{ color:var(--primary-color); font-weight:600; }
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
        <li class="nav-item"><a class="nav-link" href="produk.php">Produk</a></li>
        <li class="nav-item"><a class="nav-link" href="kategori.php">Kategori</a></li>
        <li class="nav-item"><a class="nav-link active" href="keranjang.php">Keranjang</a></li>
        <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
      </ul>
      <div class="d-flex align-items-center gap-3">
        <a href="keranjang.php" class="btn btn-outline-dark position-relative">
          <i class="bi bi-cart3"></i>
          <?php if ($totalItem > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $totalItem ?></span>
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

<!-- ===== ISI KERANJANG ===== -->
<div class="container py-5">
  <h3 class="fw-bold mb-4"><i class="bi bi-cart3 me-2"></i>Keranjang Belanja</h3>

  <?php if (count($itemKeranjang) === 0): ?>
    <div class="text-center py-5 bg-white rounded shadow-sm">
      <i class="bi bi-cart-x" style="font-size:4rem;color:#ccc;"></i>
      <h5 class="mt-3">Keranjang kamu masih kosong</h5>
      <p class="text-muted">Yuk, pilih produk favoritmu dulu.</p>
      <a href="produk.php" class="btn btn-primary-custom">Mulai Belanja</a>
    </div>
  <?php else: ?>
    <form action="keranjang.php" method="POST">
      <div class="row g-4">
        <!-- Tabel produk -->
        <div class="col-lg-8">
          <div class="card card-ringkasan mb-4">
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Produk</th>
                      <th class="text-center">Harga Satuan</th>
                      <th class="text-center" style="width:130px;">Kuantiti</th>
                      <th class="text-center">Total Harga</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($itemKeranjang as $item):
                        $fotoSrc = !empty($item['foto'])
                            ? (filter_var($item['foto'], FILTER_VALIDATE_URL) ? $item['foto'] : "assets/img/" . $item['foto'])
                            : "https://placehold.co/100x100?text=Produk";
                    ?>
                      <tr>
                        <td>
                          <div class="d-flex align-items-center gap-3">
                            <img src="<?= htmlspecialchars($fotoSrc) ?>" class="img-keranjang" alt="<?= htmlspecialchars($item['nama']) ?>">
                            <div>
                              <div class="fw-semibold"><?= htmlspecialchars($item['nama']) ?></div>
                              <small class="text-muted"><?= htmlspecialchars(trim($item['nama_kategori'] ?? 'Tanpa Kategori')) ?></small><br>
                              <small class="text-muted">Stok tersedia: <?= (int) $item['stok'] ?></small>
                            </div>
                          </div>
                        </td>
                        <td class="text-center harga">Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                        <td class="text-center">
                          <input type="number" name="jumlah[<?= $item['id'] ?>]" class="form-control form-control-sm text-center"
                                 value="<?= (int) $item['jumlah'] ?>" min="1" max="<?= (int) $item['stok'] ?>">
                        </td>
                        <td class="text-center fw-semibold">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                        <td class="text-center">
                          <button type="submit" name="hapus" value="<?= $item['id'] ?>" class="btn btn-sm btn-outline-danger" title="Hapus">
                            <i class="bi bi-trash"></i>
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Opsi Pengiriman & Pembayaran -->
          <div class="card card-ringkasan p-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-truck me-2"></i>Opsi Pengiriman & Pembayaran</h5>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Pilihan Cara Pengiriman</label>
                <select name="metode_kirim" id="metodeKirim" class="form-select">
                  <option value="Reguler" data-biaya="15000" <?= $kirimTerpilih === 'Reguler' ? 'selected' : '' ?>>Kurir Reguler (Estimasi 2-3 Hari) - Rp 15.000</option>
                  <option value="Express" data-biaya="25000" <?= $kirimTerpilih === 'Express' ? 'selected' : '' ?>>Kurir Express (Estimasi 1 Hari) - Rp 25.000</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Pilihan Cara Membayar</label>
                <select name="metode_bayar" class="form-select">
                  <option value="Transfer Bank" <?= $bayarTerpilih === 'Transfer Bank' ? 'selected' : '' ?>>Transfer Bank (BCA / Mandiri / BNI)</option>
                  <option value="E-Wallet" <?= $bayarTerpilih === 'E-Wallet' ? 'selected' : '' ?>>E-Wallet (Dana / OVO / GoPay)</option>
                  <option value="COD" <?= $bayarTerpilih === 'COD' ? 'selected' : '' ?>>Bayar di Tempat (COD)</option>
                </select>
              </div>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="produk.php" class="btn btn-outline-dark"><i class="bi bi-arrow-left"></i> Lanjut Belanja</a>
            <button type="submit" name="update" class="btn btn-outline-primary"><i class="bi bi-arrow-repeat"></i> Perbarui Jumlah</button>
            <button type="submit" name="kosongkan" value="1" class="btn btn-outline-danger"
                    onclick="return confirm('Kosongkan seluruh keranjang?')">
              <i class="bi bi-x-circle"></i> Kosongkan
            </button>
          </div>
        </div>

        <!-- Ringkasan belanja -->
        <div class="col-lg-4">
          <div class="card card-ringkasan">
            <div class="card-body">
              <h5 class="fw-bold mb-3">Ringkasan Belanja</h5>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Total Item</span>
                <span><?= $totalItem ?> barang</span>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Subtotal Produk</span>
                <span>Rp <?= number_format($totalHarga, 0, ',', '.') ?></span>
              </div>
              <?php if ($persenDiskon > 0): ?>
              <div class="d-flex justify-content-between mb-2 text-success">
                <span>Diskon (<?= $persenDiskon ?>%)</span>
                <span>- Rp <?= number_format($nominalDiskon, 0, ',', '.') ?></span>
              </div>
              <?php endif; ?>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Ongkos Kirim (<span id="labelKurir"><?= htmlspecialchars($kirimTerpilih) ?></span>)</span>
                <span id="ongkirText">Rp <?= number_format($ongkirTerpilih, 0, ',', '.') ?></span>
              </div>
              <?php if ($infoBerikutnya): ?>
              <div class="alert alert-info small py-2 mb-2">
                <i class="bi bi-tag"></i> Belanja Rp <?= number_format($infoBerikutnya['minimal'] - $totalHarga, 0, ',', '.') ?> lagi untuk diskon <?= $infoBerikutnya['persen'] ?>%!
              </div>
              <?php endif; ?>
              <hr>
              <div class="d-flex justify-content-between mb-3">
                <span class="fw-bold">Total Biaya</span>
                <span class="harga fs-5" id="totalText">Rp <?= number_format($totalHarga - $nominalDiskon + $ongkirTerpilih, 0, ',', '.') ?></span>
              </div>

              <?php if ($is_login): ?>
                <div class="d-grid">
                  <button type="submit" name="checkout" class="btn btn-primary-custom btn-lg">
                    <i class="bi bi-bag-check"></i> Checkout Sekarang
                  </button>
                </div>
              <?php else: ?>
                <div class="alert alert-warning small mb-2">Kamu harus login dulu untuk checkout.</div>
                <a href="login.php" class="btn btn-primary-custom w-100">Login Sekarang</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </form>
  <?php endif; ?>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if ($pesan !== ''): ?>
<script>
Swal.fire({
    icon: <?= json_encode($tipePesan === 'danger' ? 'error' : $tipePesan) ?>,
    title: <?= json_encode($tipePesan === 'success' ? 'Berhasil' : 'Perhatian') ?>,
    text: <?= json_encode($pesan, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    confirmButtonColor: '#ff6f3c'
});
</script>
<?php endif; ?>
<script>
// Ongkir & total ikut berubah saat kurir dipilih
(function () {
    const pilih = document.getElementById('metodeKirim');
    if (!pilih) return;
    const subtotal = <?= (int) $totalHarga ?>;
    const diskon   = <?= (int) $nominalDiskon ?>;
    const rp = (n) => 'Rp ' + n.toLocaleString('id-ID');
    function hitung() {
        const opsi  = pilih.options[pilih.selectedIndex];
        const biaya = parseInt(opsi.dataset.biaya, 10) || 0;
        document.getElementById('labelKurir').textContent = opsi.value;
        document.getElementById('ongkirText').textContent = rp(biaya);
        document.getElementById('totalText').textContent  = rp(subtotal - diskon + biaya);
    }
    pilih.addEventListener('change', hitung);
    hitung();
})();
</script>
<script>
document.querySelector('form[action="keranjang.php"]')?.addEventListener('submit', function (event) {
    const tombolCheckout = event.submitter;
    if (!tombolCheckout || tombolCheckout.name !== 'checkout') {
        return;
    }

    if (this.dataset.checkoutConfirmed === 'true') {
        delete this.dataset.checkoutConfirmed;
        return;
    }

    event.preventDefault();
    Swal.fire({
        icon: 'question',
        title: 'Proses checkout?',
        text: 'Pastikan jumlah produk dan metode pembayaran sudah sesuai.',
        showCancelButton: true,
        confirmButtonText: 'Ya, checkout',
        cancelButtonText: 'Periksa lagi',
        confirmButtonColor: '#ff6f3c',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            this.dataset.checkoutConfirmed = 'true';
            this.requestSubmit(tombolCheckout);
        }
    });
});
</script>
</body>
</html>
