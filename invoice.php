<?php
session_start();
include "koneksi.php";
$notifikasi = $_SESSION['flash_sweetalert'] ?? null;
unset($_SESSION['flash_sweetalert']);

// Harus login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user      = (int) $_SESSION['user_id'];
$id_transaksi = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Ambil data transaksi + pastikan milik user yang login
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT t.id_transaksi, t.tanggal, t.total_harga,
            u.nama, u.email, u.hp, u.alamat
     FROM tb_transaksi t
     JOIN tb_user u ON t.id_pelanggan = u.id
     WHERE t.id_transaksi = ? AND t.id_pelanggan = ?"
);
mysqli_stmt_bind_param($stmt, "ii", $id_transaksi, $id_user);
mysqli_stmt_execute($stmt);
$trx = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$trx) {
    die("<div style='font-family:sans-serif;padding:40px;text-align:center'>
         <h3>Transaksi tidak ditemukan</h3>
         <a href='profile.php'>Kembali ke Profil</a></div>");
}

// Ambil detail produk transaksi
$detail = [];
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT d.jumlah, p.nama, p.harga
     FROM tb_detail d
     JOIN tb_produk p ON d.id_produk = p.id
     WHERE d.id_transaksi = ?"
);
mysqli_stmt_bind_param($stmt, "i", $id_transaksi);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $detail[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice INV-<?= str_pad($trx['id_transaksi'], 5, "0", STR_PAD_LEFT) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
    body{ font-family:'Segoe UI', sans-serif; background:#f8f9fa; }
    .invoice-box{ background:#fff; max-width:800px; margin:40px auto; padding:40px; border-radius:12px; box-shadow:0 3px 12px rgba(0,0,0,0.08); }
    .brand{ color:#ff6f3c; font-weight:700; }
    @media print{
        body{ background:#fff; }
        .no-print{ display:none !important; }
        .invoice-box{ box-shadow:none; margin:0; max-width:100%; }
    }
</style>
</head>
<body>

<div class="invoice-box">
  <div class="d-flex justify-content-between align-items-start flex-wrap mb-4">
    <div>
      <h3 class="brand mb-1"><i class="bi bi-shop"></i> TokoKita</h3>
      <small class="text-muted">
        Jl. Contoh Alamat No. 123, Jakarta<br>
        0812-3456-7890 &middot; info@tokokita.com
      </small>
    </div>
    <div class="text-end">
      <h4 class="mb-1">INVOICE</h4>
      <div class="fw-semibold">INV-<?= str_pad($trx['id_transaksi'], 5, "0", STR_PAD_LEFT) ?></div>
      <small class="text-muted"><?= date("d M Y", strtotime($trx['tanggal'])) ?></small>
    </div>
  </div>

  <hr>

  <div class="mb-4">
    <h6 class="fw-bold mb-1">Ditagihkan Kepada:</h6>
    <div><?= htmlspecialchars(trim($trx['nama'])) ?></div>
    <small class="text-muted">
      <?= htmlspecialchars($trx['email']) ?><br>
      <?= htmlspecialchars($trx['hp']) ?><br>
      <?= htmlspecialchars($trx['alamat']) ?>
    </small>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:40px;">#</th>
          <th>Produk</th>
          <th class="text-end">Harga</th>
          <th class="text-center">Qty</th>
          <th class="text-end">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1; foreach ($detail as $d): ?>
          <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($d['nama']) ?></td>
            <td class="text-end">Rp <?= number_format($d['harga'], 0, ',', '.') ?></td>
            <td class="text-center"><?= (int) $d['jumlah'] ?></td>
            <td class="text-end">Rp <?= number_format($d['harga'] * $d['jumlah'], 0, ',', '.') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="table-light">
          <th colspan="4" class="text-end">TOTAL</th>
          <th class="text-end brand">Rp <?= number_format($trx['total_harga'], 0, ',', '.') ?></th>
        </tr>
      </tfoot>
    </table>
  </div>

  <p class="text-muted small mt-4 mb-4">
    Terima kasih telah berbelanja di TokoKita. Invoice ini sah dan diproses oleh komputer.
  </p>

  <div class="no-print d-flex gap-2">
    <button onclick="window.print()" class="btn btn-dark"><i class="bi bi-printer"></i> Cetak Invoice</button>
    <a href="profile.php" class="btn btn-outline-secondary">Kembali ke Profil</a>
  </div>
</div>

<?php if (is_array($notifikasi)): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
Swal.fire(<?= json_encode($notifikasi, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>);
</script>
<?php endif; ?>
</body>
</html>