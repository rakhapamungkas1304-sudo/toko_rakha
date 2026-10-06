<?php
session_start();
$_SESSION = [];
session_regenerate_id(true);
$_SESSION['flash_sweetalert'] = [
    'icon' => 'success',
    'title' => 'Logout berhasil',
    'text' => 'Anda telah keluar dari akun.'
];
header("Location: index1.php");
exit;