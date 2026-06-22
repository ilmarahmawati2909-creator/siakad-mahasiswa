<?php
include 'config/koneksi.php';

// Redirect if already logged in
if (isset($_SESSION['login'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if (isset($_POST['register'])) {
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Server-side validation
    if (empty($nama) || empty($username) || empty($password) || empty($confirm_password)) {
        $error = 'Semua kolom wajib diisi!';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username hanya boleh mengandung huruf, angka, dan underscore (_)!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal terdiri dari 6 karakter!';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak sesuai!';
    } else {
        // Check if username already exists
        $stmt_check = mysqli_prepare($koneksi, "SELECT id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt_check, "s", $username);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);
        
        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $error = "Username '$username' sudah terdaftar!";
            mysqli_stmt_close($stmt_check);
        } else {
            mysqli_stmt_close($stmt_check);
            
            // Hash password securely
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert
            $stmt_insert = mysqli_prepare($koneksi, "INSERT INTO users (nama, username, password) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt_insert, "sss", $nama, $username, $hashed_password);
            
            if (mysqli_stmt_execute($stmt_insert)) {
                $success = 'Pendaftaran berhasil! Mengalihkan ke halaman login...';
                mysqli_stmt_close($stmt_insert);
                header("refresh:2;url=login.php");
            } else {
                $error = 'Gagal menyimpan data: ' . mysqli_error($koneksi);
                mysqli_stmt_close($stmt_insert);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Admin - Portal Akademik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #311042 100%);
            --card-bg: rgba(30, 41, 59, 0.7);
            --card-border: rgba(255, 255, 255, 0.08);
            --accent-color: #8b5cf6;
            --accent-hover: #7c3aed;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-main);
            overflow-x: hidden;
            position: relative;
            padding: 30px 0;
        }

        body::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(139, 92, 246, 0.2);
            border-radius: 50%;
            top: 15%;
            left: 15%;
            filter: blur(80px);
            z-index: 0;
            pointer-events: none;
        }

        body::after {
            content: '';
            position: absolute;
            width: 350px;
            height: 350px;
            background: rgba(236, 72, 153, 0.15);
            border-radius: 50%;
            bottom: 15%;
            right: 15%;
            filter: blur(100px);
            z-index: 0;
            pointer-events: none;
        }

        .login-container {
            z-index: 10;
            width: 100%;
            max-width: 460px;
            padding: 15px;
        }

        .login-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 40px 35px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card:hover {
            box-shadow: 0 25px 50px rgba(139, 92, 246, 0.15);
        }

        .brand-logo {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 28px;
            font-weight: 700;
            color: white;
            box-shadow: 0 8px 16px rgba(139, 92, 246, 0.3);
        }

        .login-title {
            font-size: 24px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .login-subtitle {
            font-size: 14px;
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 30px;
        }

        .form-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .form-control {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--card-border);
            color: var(--text-main);
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(15, 23, 42, 0.8);
            border-color: var(--accent-color);
            color: var(--text-main);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.25);
            outline: none;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--accent-color) 0%, #a78bfa 100%);
            border: none;
            color: white;
            border-radius: 12px;
            padding: 12px;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
            margin-top: 10px;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, var(--accent-hover) 0%, var(--accent-color) 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(139, 92, 246, 0.4);
        }

        .alert-custom {
            border-radius: 12px;
            font-size: 14px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }

        .alert-custom-danger {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-custom-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #a7f3d0;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <div class="brand-logo">R</div>
        <h3 class="login-title">Daftar Admin Baru</h3>
        <p class="login-subtitle">Lengkapi formulir untuk membuat akun admin</p>
        
        <?php if (!empty($error)): ?>
            <div class="alert-custom alert-custom-danger d-flex align-items-center" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-circle-fill me-2 flex-shrink-0" viewBox="0 0 16 16">
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                </svg>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert-custom alert-custom-success d-flex align-items-center" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle-fill me-2 flex-shrink-0" viewBox="0 0 16 16">
                    <path d="M16 8a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                </svg>
                <div><?= $success ?></div>
            </div>
        <?php endif; ?>
        
        <form method="post" action="" autocomplete="off">
            <div class="mb-3">
                <label for="nama" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" id="nama" name="nama" placeholder="Masukkan nama lengkap Anda" required value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : '' ?>">
            </div>

            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Buat username unik" required autocomplete="new-username" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 6 karakter" required autocomplete="new-password">
            </div>

            <div class="mb-4">
                <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Ulangi password Anda" required autocomplete="new-password">
            </div>
            
            <button type="submit" class="btn btn-login w-100" name="register">Daftar Sekarang</button>
            
            <div class="text-center mt-4">
                <p class="mb-0 text-secondary" style="font-size: 13px;">Sudah punya akun? <a href="login.php" style="color: var(--accent-color); font-weight: 600; text-decoration: none;">Masuk di sini</a></p>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
