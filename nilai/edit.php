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

// Fetch grade record
$grade_query = "SELECT n.*, m.nama as mhs_nama, m.nim as mhs_nim, mk.nama_mk, mk.kode_mk
               FROM nilai n
               JOIN mahasiswa m ON n.mahasiswa_id = m.id
               JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id
               WHERE n.id = ?";
$stmt = mysqli_prepare($koneksi, $grade_query);
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
    $nilai_angka = (float)$_POST['nilai_angka'];
    $semester = trim($_POST['semester']);
    
    // Auto-calculate letter grade server-side to prevent tampering
    $nilai_huruf = 'E';
    if ($nilai_angka >= 85) $nilai_huruf = 'A';
    elseif ($nilai_angka >= 80) $nilai_huruf = 'B+';
    elseif ($nilai_angka >= 70) $nilai_huruf = 'B';
    elseif ($nilai_angka >= 65) $nilai_huruf = 'C+';
    elseif ($nilai_angka >= 56) $nilai_huruf = 'C';
    elseif ($nilai_angka >= 40) $nilai_huruf = 'D';
    
    if (empty($semester) || $_POST['nilai_angka'] === '') {
        $error = 'Semua kolom wajib diisi!';
    } elseif ($nilai_angka < 0 || $nilai_angka > 100) {
        $error = 'Nilai angka harus berkisar antara 0 sampai 100!';
    } else {
        // Update
        $stmt_update = mysqli_prepare($koneksi, "UPDATE nilai SET nilai_angka = ?, nilai_huruf = ?, semester = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_update, "dssi", $nilai_angka, $nilai_huruf, $semester, $id);
        
        if (mysqli_stmt_execute($stmt_update)) {
            $success = 'Nilai akademik berhasil diperbarui!';
            mysqli_stmt_close($stmt_update);
            header("refresh:2;url=detail.php?mahasiswa_id=" . $d['mahasiswa_id']);
        } else {
            $error = 'Gagal memperbarui nilai: ' . mysqli_error($koneksi);
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
    <title>Edit Nilai - <?= htmlspecialchars($d['mhs_nama']) ?> - SIAKAD</title>
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

        .grade-preview-box {
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

        .grade-letter-lg {
            font-size: 64px;
            font-weight: 800;
            color: #475569;
            line-height: 1;
            margin-bottom: 5px;
        }

        .grade-label-lg {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
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
            <a href="index.php" class="sidebar-link active">
                <i class="bi bi-journal-bookmark-fill"></i>
                <span>Nilai Mahasiswa</span>
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="../administrasi/index.php" class="sidebar-link">
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
    
    <h3 class="page-title">Edit Nilai Akademik</h3>

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
                
                <!-- Left panel: input forms -->
                <div class="col-lg-8">
                    <div class="row g-3">
                        <!-- Student Name (Readonly) -->
                        <div class="col-md-6">
                            <label class="form-label">Nama Mahasiswa</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($d['mhs_nama']) ?>" disabled readonly style="background-color: #e2e8f0;">
                        </div>
                        
                        <!-- Student NIM (Readonly) -->
                        <div class="col-md-6">
                            <label class="form-label">NIM Mahasiswa</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($d['mhs_nim']) ?>" disabled readonly style="background-color: #e2e8f0;">
                        </div>

                        <!-- Mata Kuliah (Readonly) -->
                        <div class="col-md-6">
                            <label class="form-label">Mata Kuliah</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($d['kode_mk']) ?> - <?= htmlspecialchars($d['nama_mk']) ?>" disabled readonly style="background-color: #e2e8f0;">
                        </div>
                        
                        <!-- Semester -->
                        <div class="col-md-6">
                            <label for="semester" class="form-label">Semester / Tahun Ajaran</label>
                            <select class="form-select" id="semester" name="semester" required>
                                <option value="Ganjil 2025/2026" <?= $d['semester'] === 'Ganjil 2025/2026' ? 'selected' : '' ?>>Ganjil 2025/2026</option>
                                <option value="Genap 2025/2026" <?= $d['semester'] === 'Genap 2025/2026' ? 'selected' : '' ?>>Genap 2025/2026</option>
                                <option value="Ganjil 2026/2027" <?= $d['semester'] === 'Ganjil 2026/2027' ? 'selected' : '' ?>>Ganjil 2026/2027</option>
                                <option value="Genap 2026/2027" <?= $d['semester'] === 'Genap 2026/2027' ? 'selected' : '' ?>>Genap 2026/2027</option>
                            </select>
                        </div>

                        <!-- Nilai Angka -->
                        <div class="col-md-6">
                            <label for="nilai_angka" class="form-label">Nilai Angka (0 - 100)</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control" id="nilai_angka" name="nilai_angka" value="<?= htmlspecialchars($d['nilai_angka']) ?>" required autocomplete="off">
                        </div>
                    </div>
                </div>

                <!-- Right panel: Interactive Letter Grade Preview -->
                <div class="col-lg-4 border-start ps-lg-4 d-flex flex-column justify-content-center">
                    <div class="grade-preview-box">
                        <span class="grade-label-lg">Nilai Huruf</span>
                        <div class="grade-letter-lg" id="letter-preview">-</div>
                        <span class="text-muted" style="font-size: 11px; font-weight: 500;" id="scale-info">Sistem Konversi Otomatis</span>
                    </div>
                </div>

            </div>

            <!-- Form buttons -->
            <div class="d-flex gap-2 justify-content-end mt-5 pt-3 border-top">
                <a href="detail.php?mahasiswa_id=<?= $d['mahasiswa_id'] ?>" class="btn btn-outline-secondary btn-custom">
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
    // Real-time Grade Converter
    function updateGrade() {
        const scoreVal = document.getElementById("nilai_angka").value.trim();
        const preview = document.getElementById("letter-preview");
        const scale = document.getElementById("scale-info");
        
        if (scoreVal === '' || isNaN(scoreVal)) {
            preview.textContent = "-";
            preview.style.color = "#475569";
            scale.textContent = "Sistem Konversi Otomatis";
            return;
        }
        
        const score = parseFloat(scoreVal);
        
        if (score < 0 || score > 100) {
            preview.textContent = "Err";
            preview.style.color = "#ef4444";
            scale.textContent = "Range nilai 0 - 100!";
            return;
        }
        
        let letter = "E";
        let color = "#ef4444"; // Red for E
        let desc = "Bobot: 0.00 (Sangat Kurang)";
        
        if (score >= 85) {
            letter = "A";
            color = "#10b981"; // Emerald Green
            desc = "Bobot: 4.00 (Sempurna)";
        } else if (score >= 80) {
            letter = "B+";
            color = "#6366f1"; // Indigo
            desc = "Bobot: 3.50 (Sangat Baik)";
        } else if (score >= 70) {
            letter = "B";
            color = "#3b82f6"; // Blue
            desc = "Bobot: 3.00 (Baik)";
        } else if (score >= 65) {
            letter = "C+";
            color = "#f59e0b"; // Orange
            desc = "Bobot: 2.50 (Cukup Baik)";
        } else if (score >= 56) {
            letter = "C";
            color = "#ec4899"; // Pink
            desc = "Bobot: 2.00 (Cukup)";
        } else if (score >= 40) {
            letter = "D";
            color = "#06b6d4"; // Cyan
            desc = "Bobot: 1.00 (Kurang)";
        }
        
        preview.textContent = letter;
        preview.style.color = color;
        scale.textContent = desc;
    }

    document.getElementById("nilai_angka").addEventListener("input", updateGrade);
    
    // Initial call on page load
    document.addEventListener("DOMContentLoaded", updateGrade);
</script>
</body>
</html>
