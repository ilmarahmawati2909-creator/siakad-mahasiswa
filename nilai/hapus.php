<?php
include '../config/koneksi.php';

// Check login
if (!isset($_SESSION['login'])) {
    header('Location: ../login.php');
    exit;
}

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Fetch student ID first to redirect back correctly
    $stmt_fetch = mysqli_prepare($koneksi, "SELECT mahasiswa_id FROM nilai WHERE id = ?");
    mysqli_stmt_bind_param($stmt_fetch, "i", $id);
    mysqli_stmt_execute($stmt_fetch);
    $res = mysqli_stmt_get_result($stmt_fetch);
    $d = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt_fetch);
    
    if ($d) {
        $mhs_id = $d['mahasiswa_id'];
        
        // Delete
        $stmt_del = mysqli_prepare($koneksi, "DELETE FROM nilai WHERE id = ?");
        mysqli_stmt_bind_param($stmt_del, "i", $id);
        
        if (mysqli_stmt_execute($stmt_del)) {
            mysqli_stmt_close($stmt_del);
            header('Location: detail.php?mahasiswa_id=' . $mhs_id);
            exit;
        } else {
            echo "Gagal menghapus nilai: " . mysqli_error($koneksi);
        }
        mysqli_stmt_close($stmt_del);
    } else {
        header('Location: index.php');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
?>
