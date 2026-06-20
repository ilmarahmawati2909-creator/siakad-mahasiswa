CREATE DATABASE IF NOT EXISTS db_sekolah;
USE db_sekolah;

-- Drop tables if they exist to allow clean migrations
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
