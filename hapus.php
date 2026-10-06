<?php
include 'koneksi.php';
$id = $_GET['id'];
$hapus = mysqli_query($koneksi, "DELETE FROM tb_produk WHERE id = '$id'");
if($hapus > 0){
    header("location:dashboard.php");
}