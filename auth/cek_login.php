<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['login'])) {
    // Dynamic calculation of the web path for auth/login.php
    $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $dir_path = str_replace('\\', '/', __DIR__);
    
    // Remove doc_root prefix to get the relative web path
    if (strpos($dir_path, $doc_root) === 0) {
        $web_path = substr($dir_path, strlen($doc_root));
    } else {
        // Fallback if document root mapping differs (e.g. symlinks)
        $web_path = '/crud-sekolah-xampp/auth';
    }
    
    $login_url = '/' . ltrim($web_path, '/') . '/login.php';
    
    // Redirect to login
    header("Location: " . $login_url);
    exit;
}
?>