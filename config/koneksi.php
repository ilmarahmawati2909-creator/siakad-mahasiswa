<?php
$koneksi = mysqli_connect("localhost","root","","db_sekolah");
if(!$koneksi){ die("Koneksi gagal: ".mysqli_connect_error()); }
session_start();
?>