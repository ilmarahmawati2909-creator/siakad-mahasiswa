<?php
if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
    // Local (XAMPP)
    $koneksi = mysqli_connect("localhost", "root", "", "db_sekolah");
} else {
    // Online (InfinityFree)
    $koneksi = mysqli_connect("sql307.infinityfree.com", "if0_42227826", "ilmarhm29", "if0_42227826_db_sekolah");
}

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>