<?php
include '../config/koneksi.php';

// Check login
if (!isset($_SESSION['login'])) {
    header('Location: ../login.php');
    exit;
}

$admin_nama = $_SESSION['nama'] ?? 'Administrator';

// Check for student ID
if (!isset($_GET['mahasiswa_id']) || empty($_GET['mahasiswa_id'])) {
    header('Location: index.php');
    exit;
}

$mhs_id = (int)$_GET['mahasiswa_id'];

// Fetch student info
$stmt_mhs = mysqli_prepare($koneksi, "SELECT * FROM mahasiswa WHERE id = ?");
mysqli_stmt_bind_param($stmt_mhs, "i", $mhs_id);
mysqli_stmt_execute($stmt_mhs);
$res_mhs = mysqli_stmt_get_result($stmt_mhs);
$mhs = mysqli_fetch_assoc($res_mhs);
mysqli_stmt_close($stmt_mhs);

if (!$mhs) {
    header('Location: index.php');
    exit;
}

// Fetch grades
$grades_query = "SELECT n.id, n.nilai_angka, n.nilai_huruf, n.semester, mk.kode_mk, mk.nama_mk, mk.sks
                FROM nilai n
                JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id
                WHERE n.mahasiswa_id = ?
                ORDER BY n.semester ASC, mk.kode_mk ASC";

$stmt_grades = mysqli_prepare($koneksi, $grades_query);
mysqli_stmt_bind_param($stmt_grades, "i", $mhs_id);
mysqli_stmt_execute($stmt_grades);
$grades_res = mysqli_stmt_get_result($stmt_grades);

$grades = [];
$total_sks = 0;
$total_bobot_sks = 0.0;

while ($row = mysqli_fetch_assoc($grades_res)) {
    // Map letter to weight
    $letter = $row['nilai_huruf'];
    $weight = 0.0;
    switch ($letter) {
        case 'A': $weight = 4.0; break;
        case 'B+': $weight = 3.5; break;
        case 'B': $weight = 3.0; break;
        case 'C+': $weight = 2.5; break;
        case 'C': $weight = 2.0; break;
        case 'D': $weight = 1.0; break;
        case 'E': $weight = 0.0; break;
    }
    $row['bobot'] = $weight;
    $row['bobot_sks'] = $weight * $row['sks'];
    
    $grades[] = $row;
    
    $total_sks += $row['sks'];
    $total_bobot_sks += $row['bobot_sks'];
}
mysqli_stmt_close($stmt_grades);

$ipk = $total_sks > 0 ? ($total_bobot_sks / $total_sks) : 0.00;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Hasil Studi (KHS) - <?= htmlspecialchars($mhs['nama']) ?> - SIAKAD</title>
    <!-- CSS Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
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

        /* Sidebar Styling */
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

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: all var(--transition-speed) ease;
            padding: 30px;
        }

        /* Top Bar */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0;
        }

        /* Card Custom */
        .details-card {
            background-color: white;
            border: none;
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }

        .student-profile {
            display: flex;
            align-items: center;
            gap: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 20px;
        }

        .student-avatar {
            width: 80px;
            height: 80px;
            border-radius: 16px;
            object-fit: cover;
            border: 3px solid #e2e8f0;
        }

        .student-avatar-initial {
            width: 80px;
            height: 80px;
            border-radius: 16px;
            background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
            color: white;
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Tables Styling */
        .table > :not(caption) > * > * {
            padding: 14px 18px;
            vertical-align: middle;
        }

        .table-custom th {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            background-color: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }

        .table-custom td {
            font-size: 14px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }

        /* Buttons custom */
        .btn-custom {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 500;
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

        .badge-aktif { background-color: rgba(16, 185, 129, 0.1); color: #10b981; }
        .badge-cuti { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .badge-lulus { background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .badge-do { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; }

        /* Print Header Style (hidden on screen) */
        .print-header {
            display: none;
        }

        /* Printing overrides */
        @media print {
            .sidebar, .topbar, .actions-col, .btn-print, .non-print {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            body {
                background-color: white !important;
                color: black !important;
            }
            .details-card {
                box-shadow: none !important;
                padding: 0 !important;
            }
            .print-header {
                display: block !important;
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 3px double #000;
                padding-bottom: 10px;
            }
            .print-header h2 {
                margin: 0;
                font-weight: 700;
            }
            .print-header p {
                margin: 5px 0 0;
                font-size: 14px;
                color: #555;
            }
            .table-custom th {
                background-color: #f1f5f9 !important;
                color: black !important;
                border-bottom: 2px solid black !important;
            }
            .table-custom td {
                border-bottom: 1px solid #94a3b8 !important;
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
    
    <!-- Printable Header (Only visible in Print/PDF) -->
    <div class="print-header">
        <h2>UNIVERSITAS DIAN NUSWANTORO</h2>
        <p>KARTU HASIL STUDI (KHS) AKADEMIK MAHASISWA</p>
    </div>

    <!-- Top Bar -->
    <div class="topbar">
        <div>
            <h3 class="page-title">Kartu Hasil Studi (KHS)</h3>
            <p class="text-muted mb-0" style="font-size: 14px;">Detail pencapaian akademik dan transkrip nilai</p>
        </div>
        <div class="card-header-actions d-flex gap-2">
            <!-- Add Grade Button -->
            <a href="tambah.php?mahasiswa_id=<?= $mhs['id'] ?>" class="btn btn-custom btn-primary-custom">
                <i class="bi bi-plus-lg"></i>
                <span>Input Nilai</span>
            </a>
            <!-- Print Button -->
            <button onclick="window.print()" class="btn btn-custom btn-outline-dark btn-print">
                <i class="bi bi-printer"></i>
                <span>Cetak Transkrip / PDF</span>
            </button>
            <!-- Back Button -->
            <a href="index.php" class="btn btn-custom btn-outline-secondary non-print">
                <i class="bi bi-arrow-left"></i>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    <!-- Details Card -->
    <div class="details-card">
        <!-- Student Summary Profile -->
        <div class="student-profile">
            <?php if (!empty($mhs['foto']) && file_exists('../mahasiswa/uploads/' . $mhs['foto'])): ?>
                <img class="student-avatar" src="../mahasiswa/uploads/<?= htmlspecialchars($mhs['foto']) ?>" alt="Profil">
            <?php else: ?>
                <div class="student-avatar-initial">
                    <?= strtoupper(substr($mhs['nama'], 0, 1)) ?>
                </div>
            <?php endif; ?>
            
            <div class="row w-100 g-2">
                <div class="col-md-5">
                    <h4 class="fw-bold mb-1" style="color: #0f172a;"><?= htmlspecialchars($mhs['nama']) ?></h4>
                    <p class="text-secondary mb-2" style="font-size: 14px; font-weight: 500;">NIM: <?= htmlspecialchars($mhs['nim']) ?></p>
                    <?php 
                    $status = $mhs['status'];
                    $badge_class = 'badge-aktif';
                    if ($status === 'Cuti') $badge_class = 'badge-cuti';
                    elseif ($status === 'Lulus') $badge_class = 'badge-lulus';
                    elseif ($status === 'Drop Out') $badge_class = 'badge-do';
                    ?>
                    <span class="badge <?= $badge_class ?> px-3 py-1.5" style="font-size: 11px; font-weight: 600;"><?= $status ?></span>
                </div>
                <div class="col-md-4">
                    <div style="font-size: 12px; color: #64748b;" class="fw-semibold">PROGRAM STUDI</div>
                    <div style="font-size: 15px; color: #1e293b;" class="fw-bold mb-3"><?= htmlspecialchars($mhs['jurusan']) ?></div>
                    <div style="font-size: 12px; color: #64748b;" class="fw-semibold">EMAIL MAHASISWA</div>
                    <div style="font-size: 14px; color: #334155;"><?= htmlspecialchars($mhs['email']) ?></div>
                </div>
                <div class="col-md-3 d-flex flex-column align-items-md-end justify-content-center text-start text-md-end mt-3 mt-md-0 border-start ps-md-4">
                    <div class="mb-2">
                        <div class="text-muted" style="font-size: 11px; font-weight: 600; text-transform: uppercase;">IPK Kumulatif</div>
                        <div class="fw-bold" style="font-size: 32px; color: var(--primary-color); line-height: 1;"><?= number_format($ipk, 2) ?></div>
                    </div>
                    <div>
                        <span class="badge bg-light text-dark border px-2 py-1.5" style="font-size: 12px;"><?= $total_sks ?> SKS Terpenuhi</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grades Table -->
        <h5 class="fw-bold mb-3 d-flex align-items-center gap-2 non-print" style="color: #1e293b;">
            <i class="bi bi-list-task text-primary"></i>
            <span>Daftar Nilai KHS</span>
        </h5>
        
        <div class="table-responsive">
            <table class="table table-custom">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Kode MK</th>
                        <th>Mata Kuliah</th>
                        <th>SKS</th>
                        <th>Semester</th>
                        <th>Nilai Angka</th>
                        <th>Nilai Huruf</th>
                        <th>Bobot</th>
                        <th>Bobot × SKS</th>
                        <th class="actions-col" style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($grades)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                <i class="bi bi-emoji-neutral d-block mb-2" style="font-size: 32px;"></i>
                                Belum ada nilai yang diinputkan untuk mahasiswa ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $no = 1;
                        foreach ($grades as $g): 
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($g['kode_mk']) ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($g['nama_mk']) ?></td>
                                <td><?= htmlspecialchars($g['sks']) ?> SKS</td>
                                <td><span class="badge bg-light text-secondary border px-2.5 py-1.5" style="font-size: 11px;"><?= htmlspecialchars($g['semester']) ?></span></td>
                                <td class="fw-bold"><?= number_format($g['nilai_angka'], 1) ?></td>
                                <td>
                                    <?php 
                                    $lh = $g['nilai_huruf'];
                                    $badge = 'bg-danger';
                                    if ($lh === 'A') $badge = 'bg-success';
                                    elseif ($lh === 'B+' || $lh === 'B') $badge = 'bg-primary';
                                    elseif ($lh === 'C+' || $lh === 'C') $badge = 'bg-warning text-dark';
                                    elseif ($lh === 'D') $badge = 'bg-info text-dark';
                                    ?>
                                    <span class="badge <?= $badge ?> px-2.5 py-1.5" style="font-weight: 700;"><?= $lh ?></span>
                                </td>
                                <td><?= number_format($g['bobot'], 2) ?></td>
                                <td class="fw-bold"><?= number_format($g['bobot_sks'], 2) ?></td>
                                <td class="actions-col">
                                    <div class="d-flex gap-2">
                                        <a href="edit.php?id=<?= $g['id'] ?>" class="btn btn-outline-warning btn-sm" style="border-radius: 6px; font-weight: 500; font-size: 12px; padding: 4px 8px;">Ubah</a>
                                        <a href="hapus.php?id=<?= $g['id'] ?>" 
                                           onclick="return confirm('Yakin ingin menghapus nilai mata kuliah <?= addslashes($g['nama_mk']) ?>?')"
                                           class="btn btn-outline-danger btn-sm" 
                                           style="border-radius: 6px; font-weight: 500; font-size: 12px; padding: 4px 8px;">Hapus</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <!-- Total Sum Row -->
                        <tr class="table-light fw-bold" style="border-top: 2px solid #cbd5e1;">
                            <td colspan="3" class="text-end">TOTAL :</td>
                            <td><?= $total_sks ?> SKS</td>
                            <td colspan="4"></td>
                            <td><?= number_format($total_bobot_sks, 2) ?></td>
                            <td class="actions-col"></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
