<?php
include '../config/koneksi.php';

// Check login
if (!isset($_SESSION['login'])) {
    header('Location: ../login.php');
    exit;
}

$admin_nama = $_SESSION['nama'] ?? 'Administrator';

// Check for ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

// Fetch bill record
$bill_query = "SELECT a.*, m.nim, m.nama FROM administrasi a
               JOIN mahasiswa m ON a.mahasiswa_id = m.id
               WHERE a.id = ?";
$stmt = mysqli_prepare($koneksi, $bill_query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$d = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$d) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if (isset($_POST['update'])) {
    $nominal = (int)$_POST['nominal'];
    $jumlah_bayar = (int)$_POST['jumlah_bayar'];
    $tanggal_bayar = !empty($_POST['tanggal_bayar']) ? $_POST['tanggal_bayar'] : null;
    $keterangan = trim($_POST['keterangan']);
    
    // Server-side validations
    if (empty($nominal)) {
        $error = 'Nominal tagihan wajib diisi!';
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
        
        // Update
        $stmt_update = mysqli_prepare($koneksi, "UPDATE administrasi SET nominal = ?, jumlah_bayar = ?, tanggal_bayar = ?, status_pembayaran = ?, keterangan = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_update, "iisssi", $nominal, $jumlah_bayar, $tanggal_bayar, $status_pembayaran, $keterangan, $id);
        
        if (mysqli_stmt_execute($stmt_update)) {
            $success = 'Catatan keuangan berhasil diperbarui!';
            mysqli_stmt_close($stmt_update);
            
            // Reload data
            $d['nominal'] = $nominal;
            $d['jumlah_bayar'] = $jumlah_bayar;
            $d['tanggal_bayar'] = $tanggal_bayar;
            $d['status_pembayaran'] = $status_pembayaran;
            $d['keterangan'] = $keterangan;
            
            header("refresh:2;url=index.php");
        } else {
            $error = 'Gagal memperbarui tagihan: ' . mysqli_error($koneksi);
            mysqli_stmt_close($stmt_update);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bayar / Edit Tagihan - SIAKAD</title>
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

        .payment-status-preview {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
        }

        .status-badge-lg {
            font-size: 28px;
            font-weight: 800;
            color: #475569;
            text-align: center;
            line-height: 1.2;
            margin-bottom: 5px;
        }

        .status-label-lg {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
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
    
    <h3 class="page-title">Bayar / Edit Tagihan Administrasi</h3>

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
            <div class="row g-4">
                
                <!-- Inputs Section -->
                <div class="col-lg-8">
                    <div class="row g-3">
                        <!-- Student Name (Readonly) -->
                        <div class="col-md-6">
                            <label class="form-label">Nama Mahasiswa</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($d['nama']) ?>" disabled readonly style="background-color: #e2e8f0;">
                        </div>
                        
                        <!-- Student NIM (Readonly) -->
                        <div class="col-md-6">
                            <label class="form-label">NIM Mahasiswa</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($d['nim']) ?>" disabled readonly style="background-color: #e2e8f0;">
                        </div>

                        <!-- Tagihan Category (Readonly) -->
                        <div class="col-md-6">
                            <label class="form-label">Kategori Tagihan</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($d['tagihan']) ?>" disabled readonly style="background-color: #e2e8f0;">
                        </div>
                        
                        <!-- Nominal Tagihan -->
                        <div class="col-md-6">
                            <label for="nominal" class="form-label">Nominal Tagihan (Rp)</label>
                            <input type="number" class="form-control" id="nominal" name="nominal" value="<?= htmlspecialchars($d['nominal']) ?>" required autocomplete="off">
                        </div>

                        <!-- Jumlah Terbayar -->
                        <div class="col-md-6">
                            <label for="jumlah_bayar" class="form-label">Jumlah Pembayaran Diterima (Rp)</label>
                            <input type="number" class="form-control" id="jumlah_bayar" name="jumlah_bayar" value="<?= htmlspecialchars($d['jumlah_bayar']) ?>" required autocomplete="off">
                        </div>

                        <!-- Tanggal Bayar -->
                        <div class="col-md-6">
                            <label for="tanggal_bayar" class="form-label">Tanggal Pembayaran Terakhir</label>
                            <input type="date" class="form-control" id="tanggal_bayar" name="tanggal_bayar" value="<?= $d['tanggal_bayar'] ?>">
                        </div>

                        <!-- Keterangan -->
                        <div class="col-12">
                            <label for="keterangan" class="form-label">Keterangan / Catatan Tambahan</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="3"><?= htmlspecialchars($d['keterangan']) ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Right Side: Status Badge Preview -->
                <div class="col-lg-4 border-start ps-lg-4 d-flex flex-column justify-content-center">
                    <div class="payment-status-preview">
                        <span class="status-label-lg">Status Kelunasan</span>
                        <div class="status-badge-lg" id="status-preview">-</div>
                        <span class="text-muted mt-2" style="font-size: 11px; font-weight: 500;" id="outstanding-info">Sisa Tunggakan: Rp 0</span>
                    </div>
                </div>

            </div>

            <!-- Form buttons -->
            <div class="d-flex gap-2 justify-content-end mt-5 pt-3 border-top">
                <a href="index.php" class="btn btn-outline-secondary btn-custom">
                    <i class="bi bi-arrow-left"></i>
                    <span>Batal</span>
                </a>
                <button type="submit" name="update" class="btn btn-custom btn-primary-custom">
                    <i class="bi bi-save"></i>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function updatePaymentStatus() {
        const nominalInput = document.getElementById("nominal").value.trim();
        const bayarInput = document.getElementById("jumlah_bayar").value.trim();
        const preview = document.getElementById("status-preview");
        const outstanding = document.getElementById("outstanding-info");
        
        if (nominalInput === '' || isNaN(nominalInput) || bayarInput === '' || isNaN(bayarInput)) {
            preview.textContent = "-";
            preview.style.color = "#475569";
            outstanding.textContent = "Sisa Tunggakan: -";
            return;
        }
        
        const nominal = parseInt(nominalInput);
        const bayar = parseInt(bayarInput);
        const sisa = nominal - bayar;
        
        if (nominal < 0 || bayar < 0) {
            preview.textContent = "Error";
            preview.style.color = "#ef4444";
            outstanding.textContent = "Nominal negatif tidak valid!";
            return;
        }
        
        if (bayar > nominal) {
            preview.textContent = "Kelebihan";
            preview.style.color = "#ef4444";
            outstanding.textContent = "Pembayaran melebihi tagihan!";
            return;
        }
        
        let status = "Belum Bayar";
        let color = "#ef4444"; // Red
        
        if (bayar === nominal) {
            status = "Lunas";
            color = "#10b981"; // Green
        } else if (bayar > 0) {
            status = "Cicilan";
            color = "#f59e0b"; // Amber/Orange
        }
        
        // Format to IDR
        const formatter = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        });
        
        preview.textContent = status;
        preview.style.color = color;
        outstanding.textContent = "Sisa Tunggakan: " + formatter.format(sisa);
    }

    document.getElementById("nominal").addEventListener("input", updatePaymentStatus);
    document.getElementById("jumlah_bayar").addEventListener("input", updatePaymentStatus);
    
    // Initial call
    document.addEventListener("DOMContentLoaded", updatePaymentStatus);
</script>
</body>
</html>
