<?php
include 'config/koneksi.php';

// Redirect if already logged in
if (isset($_SESSION['login'])) {
    header('Location: dashboard.php');
    exit;
}

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi!';
    } else {
        // Secure prepared statement
        $stmt = mysqli_prepare($koneksi, "SELECT * FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($user = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['login'] = true;
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['username'] = $user['username'];
                header('Location: dashboard.php');
                exit;
            }
        }
        $error = 'Username atau password tidak sesuai!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Portal Admin Mahasiswa</title>
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
        }

        /* Decorative background glow circles */
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
            max-width: 440px;
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
            transform: translateY(-5px);
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
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .form-control {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--card-border);
            color: var(--text-main);
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(15, 23, 42, 0.8);
            border-color: var(--accent-color);
            color: var(--text-main);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.25);
            outline: none;
        }

        .form-control::placeholder {
            color: #64748b;
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
        }

        .btn-login:hover {
            background: linear-gradient(135deg, var(--accent-hover) 0%, var(--accent-color) 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(139, 92, 246, 0.4);
        }

        .btn-login:active {
            transform: translateY(1px);
        }

        .alert-custom {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            border-radius: 12px;
            font-size: 14px;
            padding: 12px 16px;
            margin-bottom: 20px;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-6px); }
            75% { transform: translateX(6px); }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <div class="brand-logo">M</div>
        <h3 class="login-title">Portal Akademik</h3>
        <p class="login-subtitle">Silakan masuk ke akun administrator Anda</p>
        
        <?php if (isset($error)): ?>
            <div class="alert-custom d-flex align-items-center" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-circle-fill me-2 flex-shrink-0" viewBox="0 0 16 16">
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                </svg>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>
        
        <form method="post" action="" autocomplete="off">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username admin" required>
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
            </div>
            
            <button type="submit" class="btn btn-login w-100" name="login">Masuk Sekarang</button>
            
            <div class="text-center mt-4">
                <p class="mb-0 text-secondary" style="font-size: 13px;">Belum punya akun? <a href="register.php" style="color: var(--accent-color); font-weight: 600; text-decoration: none;">Daftar di sini</a></p>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
