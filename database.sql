-- CREATE DATABASE IF NOT EXISTS db_sekolah;
-- USE db_sekolah;

-- Drop tables if they exist to allow clean migrations
DROP TABLE IF EXISTS nilai;
DROP TABLE IF EXISTS administrasi;
DROP TABLE IF EXISTS mata_kuliah;
DROP TABLE IF EXISTS mahasiswa;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- Default admin user: admin / admin123 (using Bcrypt hash)
INSERT INTO users (nama, username, password)
VALUES ('Administrator', 'admin', '$2y$10$VQuHAY7u0ITbfACESPxYHOLLgAPh0NlgnoI80LrsZUrWQhYIN4fcS');

CREATE TABLE mahasiswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nim VARCHAR(15) UNIQUE NOT NULL,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    jurusan VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('Laki-laki', 'Perempuan') NOT NULL,
    tanggal_lahir DATE NOT NULL,
    telepon VARCHAR(20) NOT NULL,
    alamat TEXT NOT NULL,
    status ENUM('Aktif', 'Cuti', 'Lulus', 'Drop Out') DEFAULT 'Aktif' NOT NULL,
    foto VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO mahasiswa (nim, nama, email, jurusan, jenis_kelamin, tanggal_lahir, telepon, alamat, status, foto) VALUES
('A11.2023.14901', 'Budi Santoso', 'budi.santoso@mhs.dinus.ac.id', 'Teknik Informatika', 'Laki-laki', '2004-05-12', '081234567890', 'Jl. Pemuda No. 123, Semarang', 'Aktif', NULL),
('A12.2023.14902', 'Siti Aminah', 'siti.aminah@mhs.dinus.ac.id', 'Sistem Informasi', 'Perempuan', '2004-09-21', '089876543210', 'Jl. Pahlawan No. 45, Solo', 'Aktif', NULL),
('A11.2023.14903', 'Rian Hidayat', 'rian.hidayat@mhs.dinus.ac.id', 'Teknik Informatika', 'Laki-laki', '2003-11-05', '085223344556', 'Jl. Ahmad Yani No. 10, Semarang', 'Cuti', NULL),
('A15.2022.14101', 'Dewi Lestari', 'dewi.lestari@mhs.dinus.ac.id', 'Desain Komunikasi Visual', 'Perempuan', '2003-02-28', '087788990011', 'Jl. Pandanaran No. 8, Kudus', 'Lulus', NULL);

CREATE TABLE mata_kuliah (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_mk VARCHAR(15) UNIQUE NOT NULL,
    nama_mk VARCHAR(100) NOT NULL,
    sks INT NOT NULL
);

INSERT INTO mata_kuliah (kode_mk, nama_mk, sks) VALUES
('MK001', 'Pemrograman Web', 3),
('MK002', 'Struktur Data & Algoritma', 4),
('MK003', 'Sistem Komputer', 3),
('MK004', 'Basis Data Terdistribusi', 3),
('MK005', 'Metode Penelitian', 2),
('MK006', 'Pemrograman Berorientasi Objek', 4),
('MK007', 'Interaksi Manusia dan Komputer', 3);

CREATE TABLE nilai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mahasiswa_id INT NOT NULL,
    mata_kuliah_id INT NOT NULL,
    nilai_angka DECIMAL(5,2) NOT NULL,
    nilai_huruf VARCHAR(2) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    FOREIGN KEY (mahasiswa_id) REFERENCES mahasiswa(id) ON DELETE CASCADE,
    FOREIGN KEY (mata_kuliah_id) REFERENCES mata_kuliah(id) ON DELETE CASCADE
);

CREATE TABLE administrasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mahasiswa_id INT NOT NULL,
    tagihan VARCHAR(100) NOT NULL,
    nominal INT NOT NULL,
    jumlah_bayar INT NOT NULL DEFAULT 0,
    tanggal_bayar DATE NULL,
    status_pembayaran ENUM('Lunas', 'Cicilan', 'Belum Bayar') NOT NULL DEFAULT 'Belum Bayar',
    keterangan TEXT NULL,
    FOREIGN KEY (mahasiswa_id) REFERENCES mahasiswa(id) ON DELETE CASCADE
);
