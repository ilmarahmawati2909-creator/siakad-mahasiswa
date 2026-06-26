<?php
include '../config/koneksi.php';

// Check login
if (!isset($_SESSION['login'])) {
    header('Location: ../login.php');
    exit;
}

$admin_nama = $_SESSION['nama'] ?? 'Administrator';

// Get filters
$cari = isset($_GET['cari']) ? trim($_GET['cari']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// 1. Build Query
$where_clauses = [];
$params = [];
$types = '';

if ($cari !== '') {
    $where_clauses[] = "(m.nama LIKE ? OR m.nim LIKE ? OR a.tagihan LIKE ?)";
    $search_param = "%$cari%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if ($status !== '') {
    $where_clauses[] = "a.status_pembayaran = ?";
    $params[] = $status;
    $types .= 's';
}

$where_sql = '';
if (count($where_clauses) > 0) {
    $where_sql = ' WHERE ' . implode(' AND ', $where_clauses);
}

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan_keuangan_' . date('Ymd_His') . '.csv');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    
    fputcsv($output, ['No', 'NIM', 'Nama Mahasiswa', 'Tagihan', 'Nominal Tagihan', 'Jumlah Terbayar', 'Sisa Tunggakan', 'Status Pembayaran', 'Keterangan']);
    
    $export_query = "SELECT a.*, m.nim, m.nama FROM administrasi a JOIN mahasiswa m ON a.mahasiswa_id = m.id" . $where_sql . " ORDER BY a.id DESC";
    
    if (count($params) > 0) {
        $stmt = mysqli_prepare($koneksi, $export_query);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $export_result = mysqli_stmt_get_result($stmt);
    } else {
        $export_result = mysqli_query($koneksi, $export_query);
    }
    
    $no = 1;
    while ($row = mysqli_fetch_assoc($export_result)) {
        $tunggakan = $row['nominal'] - $row['jumlah_bayar'];
        fputcsv($output, [
            $no++,
            $row['nim'],
            $row['nama'],
            $row['tagihan'],
            $row['nominal'],
            $row['jumlah_bayar'],
            $tunggakan,
            $row['status_pembayaran'],
            $row['keterangan']
        ]);
    }
    fclose($output);
    exit;
}

// Fetch lists
$list_query = "SELECT a.*, m.nim, m.nama, m.foto FROM administrasi a
               JOIN mahasiswa m ON a.mahasiswa_id = m.id" . 
               $where_sql . " ORDER BY a.id DESC";

if (count($params) > 0) {
    $stmt = mysqli_prepare($koneksi, $list_query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $q = mysqli_stmt_get_result($stmt);
} else {
    $q = mysqli_query($koneksi, $list_query);
}

// Calculate summary stats for the current filter
$summary_query = "SELECT SUM(a.nominal) as total_nominal, SUM(a.jumlah_bayar) as total_terbayar 
                  FROM administrasi a JOIN mahasiswa m ON a.mahasiswa_id = m.id" . $where_sql;
if (count($params) > 0) {
    $stmt_sum = mysqli_prepare($koneksi, $summary_query);
    mysqli_stmt_bind_param($stmt_sum, $types, ...$params);
    mysqli_stmt_execute($stmt_sum);
    $sum_res = mysqli_stmt_get_result($stmt_sum);
} else {
    $sum_res = mysqli_query($koneksi, $summary_query);
}
$sum_data = mysqli_fetch_assoc($sum_res);
$sum_nominal = $sum_data['total_nominal'] ?? 0;
$sum_terbayar = $sum_data['total_terbayar'] ?? 0;
$sum_tunggakan = $sum_nominal - $sum_terbayar;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrasi Keuangan - SIAKAD</title>
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

        /* Stats Card small */
        .stat-card-sm {
            background-color: white;
            border: none;
            border-radius: 12px;
            padding: 16px 20px;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-card-sm-title {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 4px;
        }

        .stat-card-sm-value {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
        }

        /* Card Custom */
        .table-card {
            background-color: white;
            border: none;
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--card-shadow);
        }

        /* Filters and Inputs */
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

        /* Badges */
        .badge-lunas { background-color: rgba(16, 185, 129, 0.1); color: #10b981; }
        .badge-cicilan { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .badge-belumbayar { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; }

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

        .table-custom tr:hover td {
            background-color: #f8fafc;
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

        /* Avatar styles */
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
            flex-shrink: 0;
        }

        .avatar-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e2e8f0;
            flex-shrink: 0;
        }

        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
        }

        @media print {
            .sidebar, .topbar, .table-card form, .actions-col, .btn-print-non, th:last-child, td:last-child {
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
            .table-card {
                box-shadow: none !important;
                padding: 0 !important;
            }
            .table-custom th {
                background-color: #f1f5f9 !important;
                color: black !important;
                border-bottom: 2px solid black !important;
            }
            .table-custom td {
                border-bottom: 1px solid #94a3b8 !important;
            }
            .print-only-title {
                display: block !important;
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 3px double #000;
                padding-bottom: 10px;
            }
        }

        .print-only-title {
            display: none;
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
    
    <!-- Print Only Header -->
    <div class="print-only-title">
        <h2>PORTAL AKADEMIK - SIAKAD</h2>
        <h4>LAPORAN ADMINISTRASI KEUANGAN MAHASISWA</h4>
        <p class="mb-0" style="font-size: 12px; color: #555;">Dicetak pada: <?= date('d F Y H:i') ?></p>
    </div>

    <!-- Top Bar -->
    <div class="topbar">
        <div>
            <h3 class="page-title">Administrasi Keuangan</h3>
            <p class="text-muted mb-0" style="font-size: 14px;">Atur tagihan SPP, UKT, dan riwayat pembayaran kuliah mahasiswa</p>
        </div>
        <div class="card-header-actions d-flex gap-2">
            <!-- Add Button -->
            <a href="tambah.php" class="btn btn-custom btn-primary-custom">
                <i class="bi bi-plus-lg"></i>
                <span>Buat Tagihan Baru</span>
            </a>
            <!-- Export CSV -->
            <a href="index.php?export=csv&cari=<?= urlencode($cari) ?>&status=<?= urlencode($status) ?>" class="btn btn-custom btn-outline-secondary">
                <i class="bi bi-file-earmark-excel"></i>
                <span>Ekspor CSV</span>
            </a>
            <!-- Print -->
            <button onclick="window.print()" class="btn btn-custom btn-outline-dark">
                <i class="bi bi-printer"></i>
                <span>Cetak Laporan / PDF</span>
            </button>
        </div>
    </div>

    <!-- Finance Mini Dashboard / Stats -->
    <div class="row g-3 mb-4 btn-print-non">
        <div class="col-md-4">
            <div class="stat-card-sm" style="border-left: 4px solid var(--primary-color);">
                <div>
                    <div class="stat-card-sm-title">Total Tagihan (Filter)</div>
                    <div class="stat-card-sm-value">Rp <?= number_format($sum_nominal, 0, ',', '.') ?></div>
                </div>
                <i class="bi bi-wallet2 text-primary" style="font-size: 24px;"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card-sm" style="border-left: 4px solid #10b981;">
                <div>
                    <div class="stat-card-sm-title">Total Terbayar (Filter)</div>
                    <div class="stat-card-sm-value">Rp <?= number_format($sum_terbayar, 0, ',', '.') ?></div>
                </div>
                <i class="bi bi-cash-stack text-success" style="font-size: 24px;"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card-sm" style="border-left: 4px solid #ef4444;">
                <div>
                    <div class="stat-card-sm-title">Total Tunggakan (Filter)</div>
                    <div class="stat-card-sm-value text-danger">Rp <?= number_format($sum_tunggakan, 0, ',', '.') ?></div>
                </div>
                <i class="bi bi-exclamation-octagon text-danger" style="font-size: 24px;"></i>
            </div>
        </div>
    </div>

    <!-- Table Container -->
    <div class="table-card">
        
        <!-- Filter Form -->
        <form method="get" action="" class="mb-4 btn-print-non">
            <div class="row g-3">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" name="cari" placeholder="Cari nama, NIM, atau jenis tagihan..." value="<?= htmlspecialchars($cari) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="status">
                        <option value="">-- Semua Status Kelunasan --</option>
                        <option value="Lunas" <?= $status === 'Lunas' ? 'selected' : '' ?>>Lunas</option>
                        <option value="Cicilan" <?= $status === 'Cicilan' ? 'selected' : '' ?>>Cicilan</option>
                        <option value="Belum Bayar" <?= $status === 'Belum Bayar' ? 'selected' : '' ?>>Belum Bayar</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary w-100 btn-custom justify-content-center" style="border-radius:10px;">Filter</button>
                    <?php if ($cari !== '' || $status !== ''): ?>
                        <a href="index.php" class="btn btn-outline-danger btn-custom justify-content-center p-2" title="Reset Filters">
                            <i class="bi bi-x-lg m-0"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-custom">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Mahasiswa</th>
                        <th>Kategori Tagihan</th>
                        <th>Nominal Tagihan</th>
                        <th>Jumlah Terbayar</th>
                        <th>Sisa Tunggakan</th>
                        <th>Status</th>
                        <th>Tgl Bayar Terakhir</th>
                        <th class="actions-col" style="width: 180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($q) == 0): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="bi bi-inbox-fill d-block mb-2" style="font-size: 32px;"></i>
                                Tidak ada catatan keuangan ditemukan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $no = 1;
                        while ($d = mysqli_fetch_assoc($q)): 
                            $tunggakan = $d['nominal'] - $d['jumlah_bayar'];
                            
                            $status_class = 'badge-belumbayar';
                            if ($d['status_pembayaran'] === 'Lunas') $status_class = 'badge-lunas';
                            elseif ($d['status_pembayaran'] === 'Cicilan') $status_class = 'badge-cicilan';
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if (!empty($d['foto']) && file_exists('../mahasiswa/uploads/' . $d['foto'])): ?>
                                            <img class="avatar-img" src="../mahasiswa/uploads/<?= htmlspecialchars($d['foto']) ?>" alt="Foto Profil" style="flex-shrink: 0; width: 40px; height: 40px;">
                                        <?php else: ?>
                                            <div class="avatar-initial" style="flex-shrink: 0;">
                                                <?= strtoupper(substr($d['nama'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-bold" style="color: #1e293b;"><?= htmlspecialchars($d['nama']) ?></div>
                                            <div class="text-muted" style="font-size: 12px;"><?= htmlspecialchars($d['nim']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-semibold text-secondary"><?= htmlspecialchars($d['tagihan']) ?></td>
                                <td class="fw-bold">Rp <?= number_format($d['nominal'], 0, ',', '.') ?></td>
                                <td class="text-success fw-semibold">Rp <?= number_format($d['jumlah_bayar'], 0, ',', '.') ?></td>
                                <td class="<?= $tunggakan > 0 ? 'text-danger fw-semibold' : 'text-muted' ?>">
                                    Rp <?= number_format($tunggakan, 0, ',', '.') ?>
                                </td>
                                <td>
                                    <span class="badge <?= $status_class ?> px-2.5 py-1.5" style="font-size: 12px; font-weight: 600;"><?= $d['status_pembayaran'] ?></span>
                                </td>
                                <td><?= $d['tanggal_bayar'] ? date('d F Y', strtotime($d['tanggal_bayar'])) : '<span class="text-muted">-</span>' ?></td>
                                <td class="actions-col">
                                    <div class="d-flex gap-2">
                                        <a href="edit.php?id=<?= $d['id'] ?>" class="btn btn-outline-primary btn-sm" style="border-radius: 6px; font-weight: 500; font-size: 12px; padding: 6px 12px;">Bayar / Edit</a>
                                        <a href="hapus.php?id=<?= $d['id'] ?>" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus tagihan <?= addslashes($d['tagihan']) ?> untuk <?= addslashes($d['nama']) ?>?')"
                                           class="btn btn-outline-danger btn-sm" 
                                           style="border-radius: 6px; font-weight: 500; font-size: 12px; padding: 6px 12px;">Hapus</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
