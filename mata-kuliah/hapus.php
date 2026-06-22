<?php
include '../config/koneksi.php';

// Check login
if (!isset($_SESSION['login'])) {
    header('Location: ../login.php');
    exit;
}

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Prepare delete statement
    $stmt = mysqli_prepare($koneksi, "DELETE FROM mata_kuliah WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header('Location: index.php');
        exit;
    } else {
        echo "Gagal menghapus data: " . mysqli_error($koneksi);
    }
    mysqli_stmt_close($stmt);
} else {
    header('Location: index.php');
    exit;
}
?>
