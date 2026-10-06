<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_produk'])) {
    $id_produk = (int) $_POST['id_produk'];
    $halamanKembali = ($_POST['return_to'] ?? '') === 'produk.php' ? 'produk.php' : 'index1.php';

    // Cek produk & stok di database (jangan percaya input dari form)
    $stmt = mysqli_prepare($koneksi, "SELECT id, nama, stok FROM tb_produk WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_produk);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $produk = mysqli_fetch_assoc($result);

    if ($produk && $produk['stok'] > 0) {
        if (!isset($_SESSION['keranjang'][$id_produk]) || $_SESSION['keranjang'][$id_produk] < $produk['stok']) {
            $_SESSION['keranjang'][$id_produk] = ($_SESSION['keranjang'][$id_produk] ?? 0) + 1;
            $_SESSION['flash_sweetalert'] = [
                'icon' => 'success',
                'title' => 'Berhasil ditambahkan',
                'text' => $produk['nama'] . ' berhasil ditambahkan ke keranjang.'
            ];
        } else {
            $_SESSION['flash_sweetalert'] = [
                'icon' => 'warning',
                'title' => 'Stok maksimal',
                'text' => 'Jumlah produk di keranjang sudah mencapai stok yang tersedia.'
            ];
        }
    } else {
        $_SESSION['flash_sweetalert'] = [
            'icon' => 'error',
            'title' => 'Produk tidak tersedia',
            'text' => 'Produk tidak ditemukan atau stoknya sudah habis.'
        ];
    }

    header("Location: " . $halamanKembali);
    exit;
}

$_SESSION['flash_sweetalert'] = [
    'icon' => 'error',
    'title' => 'Permintaan tidak valid',
    'text' => 'Produk tidak dapat ditambahkan ke keranjang.'
];
header("Location: index1.php");
exit;