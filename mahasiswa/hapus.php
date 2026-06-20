<?php
include '../config/koneksi.php';
// Redirect if not logged in
if (!isset($_SESSION['login'])) {
    header('Location: ../auth/login.php');
    exit;
}

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Fetch current data to get the photo file name
    $stmt_fetch = mysqli_prepare($koneksi, "SELECT foto FROM mahasiswa WHERE id = ?");
    mysqli_stmt_bind_param($stmt_fetch, "i", $id);
    mysqli_stmt_execute($stmt_fetch);
    $result = mysqli_stmt_get_result($stmt_fetch);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $foto = $row['foto'];
        
        // Delete photo file if exists on server
        if (!empty($foto) && file_exists('uploads/' . $foto)) {
            unlink('uploads/' . $foto);
        }
        
        // Delete student record
        $stmt_delete = mysqli_prepare($koneksi, "DELETE FROM mahasiswa WHERE id = ?");
        mysqli_stmt_bind_param($stmt_delete, "i", $id);
        mysqli_stmt_execute($stmt_delete);
        mysqli_stmt_close($stmt_delete);
    }
    
    mysqli_stmt_close($stmt_fetch);
}

header('Location: index.php');
exit;
?>
