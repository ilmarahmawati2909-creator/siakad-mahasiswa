<?php
include '../config/koneksi.php';

// Check login
if (!isset($_SESSION['login'])) {
    header('Location: ../login.php');
    exit;
}

$admin_nama = $_SESSION['nama'] ?? 'Administrator';

// Fetch all students for the dropdown
$mhs_query = mysqli_query($koneksi, "SELECT id, nim, nama FROM mahasiswa ORDER BY nim ASC");

$error = '';
$success = '';

if (isset($_POST['simpan'])) {
    $mhs_id = (int)$_POST['mahasiswa_id'];
    $tagihan = trim($_POST['tagihan']);
    $nominal = (int)$_POST['nominal'];
    $jumlah_bayar = (int)$_POST['jumlah_bayar'];
    $tanggal_bayar = !empty($_POST['tanggal_bayar']) ? $_POST['tanggal_bayar'] : null;
    $keterangan = trim($_POST['keterangan']);
    
    // Server-side validations
    if (empty($mhs_id) || empty($tagihan) || empty($nominal)) {
        $error = 'Semua kolom wajib diisi (kecuali jumlah bayar, tanggal bayar, & keterangan)!';
    } elseif ($nominal < 0 || $jumlah_bayar < 0) {
        $error = 'Nominal tagihan dan jumlah bayar tidak boleh bernilai negatif!';
    } elseif ($jumlah_bayar > $nominal) {
        $error = 'Jumlah bayar tidak boleh melebihi total nominal tagihan!';
    } else {
        // Auto-calculate payment status
        $status_pembayaran = 'Belum Bayar';
        if ($jumlah_bayar === $nominal) {
            $status_pembayaran = 'Lunas';
        } elseif ($jumlah_bayar > 0) {
            $status_pembayaran = 'Cicilan';
        }
        
        // Insert
        $stmt = mysqli_prepare($koneksi, "INSERT INTO administrasi (mahasiswa_id, tagihan, nominal, jumlah_bayar, tanggal_bayar, status_pembayaran, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isiisss", $mhs_id, $tagihan, $nominal, $jumlah_bayar, $tanggal_bayar, $status_pembayaran, $keterangan);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Tagihan administrasi baru berhasil disimpan!';
            mysqli_stmt_close($stmt);
            header("refresh:2;url=index.php");
        } else {
            $error = 'Gagal menyimpan tagihan: ' . mysqli_error($koneksi);
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Tagihan Baru - SIAKAD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-color: #6366f1;
            --primary-hover: #4f46e5;
            --bg-body: #f8fafc;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            --transition-speed: 0.3s;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            overflow-x: hidden;
        }

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #0f172a;
            color: #94a3b8;
            z-index: 100;
            transition: all var(--transition-speed) ease;
            box-shadow: 4px 0 10px rgba(0,0,0,0.05);
        }

        .sidebar-brand {
            padding: 24px;
            font-size: 20px;
            font-weight: 700;
            color: #f8fafc;
            border-bottom: 1px solid #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-brand i {
            color: var(--primary-color);
        }

        .sidebar-menu {
            padding: 20px 12px;
            list-style: none;
            margin: 0;
        }

        .sidebar-menu-item {
            margin-bottom: 6px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .sidebar-link:hover, .sidebar-link.active {
            background-color: #1e293b;
            color: #f8fafc;
        }

        .sidebar-link.active i {
            color: var(--primary-color);
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 20px;
            border-top: 1px solid #1e293b;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: all var(--transition-speed) ease;
            padding: 30px;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 30px;
        }

        .form-card {
            background-color: white;
            border: none;
            border-radius: 16px;
            padding: 32px;
            box-shadow: var(--card-shadow);
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            border-radius: 10px;
            padding: 10px 16px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            background-color: white;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .btn-custom {
            border-radius: 10px;
            padding: 10px 24px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-primary-custom {
            background-color: var(--primary-color);
            border: none;
            color: white;
        }

        .btn-primary-custom:hover {
            background-color: var(--primary-hover);
        }

        .avatar-initial {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 15px;
        }

        .alert {
            border-radius: 12px;
            border: none;
            font-size: 14px;
            padding: 14px 20px;
        }

        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-mortarboard-fill"></i>
        <span>Portal Akademik</span>
    </div>
    <ul class="sidebar-menu">
        <li class="sidebar-menu-item">
            <a href="../dashboard.php" class="sidebar-link">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="../mahasiswa/index.php" class="sidebar-link">
                <i class="bi bi-people-fill"></i>
                <span>Data Mahasiswa</span>
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="../mata-kuliah/index.php" class="sidebar-link">
                <i class="bi bi-book-fill"></i>
                <span>Mata Kuliah</span>
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="../nilai/index.php" class="sidebar-link">
                <i class="bi bi-journal-bookmark-fill"></i>
                <span>Nilai Mahasiswa</span>
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="index.php" class="sidebar-link active">
                <i class="bi bi-cash-coin"></i>
                <span>Administrasi</span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        <div class="d-flex align-items-center gap-3 mb-3 text-white">
            <div class="avatar-initial">
                <?= strtoupper(substr($admin_nama, 0, 1)) ?>
            </div>
            <div>
                <h6 class="mb-0 text-truncate" style="max-width: 140px; font-size: 14px;"><?= htmlspecialchars($admin_nama) ?></h6>
                <small class="text-muted" style="font-size: 11px;">Administrator</small>
            </div>
        </div>
        <a href="../logout.php" class="btn btn-outline-danger btn-sm w-100 py-2 d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-box-arrow-right"></i>
            <span>Log Keluar</span>
        </a>
    </div>
</div>

<!-- Main Content Panel -->
<div class="main-content">
    
    <h3 class="page-title">Buat Tagihan Administrasi Baru</h3>

    <!-- Status Alerts -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 18px;"></i>
            <div><?= $error ?></div>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2" style="font-size: 18px;"></i>
            <div><?= $success ?> - <em>mengalihkan halaman...</em></div>
        </div>
    <?php endif; ?>

    <!-- Form Card -->
    <div class="form-card">
        <form method="post" action="">
            <div class="row g-3">
                
                <!-- Mahasiswa -->
                <div class="col-md-6">
                    <label for="mahasiswa_id" class="form-label">Mahasiswa Penerima Tagihan</label>
                    <select class="form-select" id="mahasiswa_id" name="mahasiswa_id" required>
                        <option value="">-- Pilih Mahasiswa --</option>
                        <?php while ($m = mysqli_fetch_assoc($mhs_query)): ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nim']) ?> - <?= htmlspecialchars($m['nama']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Tagihan -->
                <div class="col-md-6">
                    <label for="tagihan" class="form-label">Nama / Kategori Tagihan</label>
                    <input type="text" class="form-control" id="tagihan" name="tagihan" placeholder="Contoh: SPP Semester Ganjil 2025/2026" required>
                </div>
                
                <!-- Nominal -->
                <div class="col-md-4">
                    <label for="nominal" class="form-label">Nominal Tagihan (Rp)</label>
                    <input type="number" class="form-control" id="nominal" name="nominal" placeholder="Masukkan total tagihan, misal: 2500000" required>
                </div>

                <!-- Jumlah Bayar -->
                <div class="col-md-4">
                    <label for="jumlah_bayar" class="form-label">Jumlah Pembayaran Awal (Rp)</label>
                    <input type="number" class="form-control" id="jumlah_bayar" name="jumlah_bayar" value="0" placeholder="Isi jika ada pembayaran awal" required>
                </div>

                <!-- Tanggal Bayar -->
                <div class="col-md-4">
                    <label for="tanggal_bayar" class="form-label">Tanggal Pembayaran Awal</label>
                    <input type="date" class="form-control" id="tanggal_bayar" name="tanggal_bayar">
                </div>

                <!-- Keterangan -->
                <div class="col-12">
                    <label for="keterangan" class="form-label">Keterangan / Catatan Tambahan</label>
                    <textarea class="form-control" id="keterangan" name="keterangan" rows="3" placeholder="Masukkan keterangan (opsional), misal: Cicilan ke-1 SPP"></textarea>
                </div>
            </div>

            <!-- Form buttons -->
            <div class="d-flex gap-2 justify-content-end mt-5 pt-3 border-top">
                <a href="index.php" class="btn btn-outline-secondary btn-custom">
                    <i class="bi bi-arrow-left"></i>
                    <span>Batal</span>
                </a>
                <button type="submit" name="simpan" class="btn btn-custom btn-primary-custom">
                    <i class="bi bi-save"></i>
                    <span>Simpan Tagihan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
