<?php
// Enable error reporting for debugging HTTP 500 error on hosting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
    // Local (XAMPP)
    $koneksi = mysqli_connect("localhost", "root", "", "db_sekolah");
} else {
    // Online (InfinityFree)
    $koneksi = mysqli_connect("sql307.infinityfree.com", "if0_42227826", "ilmarhm29", "if0_42227826_db_mahasiswa");
}

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auto migration check and execute
$check_table = mysqli_query($koneksi, "SHOW TABLES LIKE 'nilai'");
if (mysqli_num_rows($check_table) == 0) {
    // Create mata_kuliah table
    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS mata_kuliah (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kode_mk VARCHAR(15) UNIQUE NOT NULL,
        nama_mk VARCHAR(100) NOT NULL,
        sks INT NOT NULL
    )");
    
    // Create nilai table
    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS nilai (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mahasiswa_id INT NOT NULL,
        mata_kuliah_id INT NOT NULL,
        nilai_angka DECIMAL(5,2) NOT NULL,
        nilai_huruf VARCHAR(2) NOT NULL,
        semester VARCHAR(20) NOT NULL,
        FOREIGN KEY (mahasiswa_id) REFERENCES mahasiswa(id) ON DELETE CASCADE,
        FOREIGN KEY (mata_kuliah_id) REFERENCES mata_kuliah(id) ON DELETE CASCADE
    )");
    
    // Create administrasi table
    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS administrasi (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mahasiswa_id INT NOT NULL,
        tagihan VARCHAR(100) NOT NULL,
        nominal INT NOT NULL,
        jumlah_bayar INT NOT NULL DEFAULT 0,
        tanggal_bayar DATE NULL,
        status_pembayaran ENUM('Lunas', 'Cicilan', 'Belum Bayar') NOT NULL DEFAULT 'Belum Bayar',
        keterangan TEXT NULL,
        FOREIGN KEY (mahasiswa_id) REFERENCES mahasiswa(id) ON DELETE CASCADE
    )");
    
    // Insert some default mata_kuliah
    $dummy_mk = [
        ['MK001', 'Pemrograman Web', 3],
        ['MK002', 'Struktur Data & Algoritma', 4],
        ['MK003', 'Sistem Komputer', 3],
        ['MK004', 'Basis Data Terdistribusi', 3],
        ['MK005', 'Metode Penelitian', 2],
        ['MK006', 'Pemrograman Berorientasi Objek', 4],
        ['MK007', 'Interaksi Manusia dan Komputer', 3]
    ];
    foreach ($dummy_mk as $mk) {
        $check_exists = mysqli_query($koneksi, "SELECT id FROM mata_kuliah WHERE kode_mk = '{$mk[0]}'");
        if (mysqli_num_rows($check_exists) == 0) {
            mysqli_query($koneksi, "INSERT INTO mata_kuliah (kode_mk, nama_mk, sks) VALUES ('{$mk[0]}', '{$mk[1]}', {$mk[2]})");
        }
    }
}
?>