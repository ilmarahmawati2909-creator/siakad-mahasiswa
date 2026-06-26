<?php
include '../config/koneksi.php';
// Redirect if not logged in
if (!isset($_SESSION['login'])) {
    header('Location: ../login.php');
    exit;
}

$admin_nama = $_SESSION['nama'] ?? 'Administrator';

// Get filter inputs
$cari = isset($_GET['cari']) ? trim($_GET['cari']) : '';
$filter_jurusan = isset($_GET['jurusan']) ? trim($_GET['jurusan']) : '';
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';

// 1. Build Query for Filtered Results (used for count, export, and list)
$where_clauses = [];
$params = [];
$types = '';

if ($cari !== '') {
    $where_clauses[] = "(nama LIKE ? OR nim LIKE ? OR email LIKE ?)";
    $search_param = "%$cari%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if ($filter_jurusan !== '') {
    $where_clauses[] = "jurusan = ?";
    $params[] = $filter_jurusan;
    $types .= 's';
}

if ($filter_status !== '') {
    $where_clauses[] = "status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

$where_sql = '';
if (count($where_clauses) > 0) {
    $where_sql = ' WHERE ' . implode(' AND ', $where_clauses);
}

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=data_mahasiswa_' . date('Ymd_His') . '.csv');
    
    $output = fopen('php://output', 'w');
    // Add UTF-8 BOM for proper excel formatting
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['No', 'NIM', 'Nama', 'Email', 'Jurusan', 'Jenis Kelamin', 'Tanggal Lahir', 'Telepon', 'Alamat', 'Status']);
    
    $export_query = "SELECT * FROM mahasiswa" . $where_sql . " ORDER BY nim ASC";
    
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
        fputcsv($output, [
            $no++,
            $row['nim'],
            $row['nama'],
            $row['email'],
            $row['jurusan'],
            $row['jenis_kelamin'],
            $row['tanggal_lahir'],
            $row['telepon'],
            $row['alamat'],
            $row['status']
        ]);
    }
    fclose($output);
    exit;
}

// 2. Pagination Logic
$limit = 5; // Records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Count Total Filtered Rows
$count_query = "SELECT COUNT(*) as total FROM mahasiswa" . $where_sql;
if (count($params) > 0) {
    $stmt = mysqli_prepare($koneksi, $count_query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $count_result = mysqli_stmt_get_result($stmt);
} else {
    $count_result = mysqli_query($koneksi, $count_query);
}
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $limit);
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;

// Fetch Filtered & Paginated Rows
$data_query = "SELECT * FROM mahasiswa" . $where_sql . " ORDER BY id DESC LIMIT ? OFFSET ?";
$page_params = $params;
$page_params[] = $limit;
$page_params[] = $offset;
$page_types = $types . 'ii';

$stmt = mysqli_prepare($koneksi, $data_query);
mysqli_stmt_bind_param($stmt, $page_types, ...$page_params);
mysqli_stmt_execute($stmt);
$q = mysqli_stmt_get_result($stmt);

// Fetch all unique departments for the dropdown filter
$jurusan_list_query = mysqli_query($koneksi, "SELECT DISTINCT jurusan FROM mahasiswa ORDER BY jurusan ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mahasiswa - SIAKAD</title>
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
        .badge-aktif { background-color: rgba(16, 185, 129, 0.1); color: #10b981; }
        .badge-cuti { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .badge-lulus { background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .badge-do { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; }

        /* Tables Styling */
        .table > :not(caption) > * > * {
            padding: 16px 20px;
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

        /* Pagination Design */
        .pagination {
            margin-bottom: 0;
            gap: 4px;
        }

        .page-link {
            border: 1px solid #e2e8f0;
            color: #64748b;
            border-radius: 8px !important;
            padding: 8px 14px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .page-link:hover {
            background-color: #f1f5f9;
            color: #1e293b;
            border-color: #cbd5e1;
        }

        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .page-item.disabled .page-link {
            background-color: #f8fafc;
            color: #cbd5e1;
            border-color: #e2e8f0;
        }

        /* Modal styling */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }

        .modal-header {
            background-color: #0f172a;
            color: white;
            border-bottom: none;
            padding: 20px 24px;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        .modal-body {
            padding: 24px;
        }

        .modal-profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .modal-avatar-lg {
            width: 90px;
            height: 90px;
            border-radius: 20px;
            object-fit: cover;
            border: 3px solid #cbd5e1;
        }

        .modal-avatar-initial-lg {
            width: 90px;
            height: 90px;
            border-radius: 20px;
            background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
            color: white;
            font-size: 32px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Printing logic overrides */
        @media print {
            .sidebar, .topbar, .table-card form, .table-card .card-header-actions, .pagination, .actions-col, th:last-child, td:last-child {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            body {
                background-color: white;
                color: black;
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
            h2.print-title {
                display: block !important;
                text-align: center;
                margin-bottom: 20px;
            }
        }

        h2.print-title {
            display: none;
        }

        /* Responsive sidebar */
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
            <a href="index.php" class="sidebar-link active">
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
    
    <h2 class="print-title fw-bold">LAPORAN DATA MAHASISWA</h2>

    <!-- Top Bar -->
    <div class="topbar">
        <div>
            <h3 class="page-title">Kelola Data Mahasiswa</h3>
            <p class="text-muted mb-0" style="font-size: 14px;">Tambah, edit, hapus, filter, dan cari mahasiswa</p>
        </div>
        <div class="card-header-actions d-flex gap-2">
            <!-- Add Button -->
            <a href="tambah.php" class="btn btn-custom btn-primary-custom">
                <i class="bi bi-plus-lg"></i>
                <span>Tambah Mahasiswa</span>
            </a>
            <!-- Export CSV -->
            <a href="index.php?export=csv&cari=<?= urlencode($cari) ?>&jurusan=<?= urlencode($filter_jurusan) ?>&status=<?= urlencode($filter_status) ?>" class="btn btn-custom btn-outline-secondary">
                <i class="bi bi-file-earmark-excel"></i>
                <span>Ekspor CSV</span>
            </a>
            <!-- Print -->
            <button onclick="window.print()" class="btn btn-custom btn-outline-dark">
                <i class="bi bi-printer"></i>
                <span>Cetak / PDF</span>
            </button>
        </div>
    </div>

    <!-- Table and Filter Container -->
    <div class="table-card">
        
        <!-- Filter Form -->
        <form method="get" action="" class="mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" name="cari" placeholder="Cari nama, NIM, atau email..." value="<?= htmlspecialchars($cari) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="jurusan">
                        <option value="">-- Semua Jurusan --</option>
                        <?php while ($j_row = mysqli_fetch_assoc($jurusan_list_query)): ?>
                            <option value="<?= htmlspecialchars($j_row['jurusan']) ?>" <?= $filter_jurusan === $j_row['jurusan'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($j_row['jurusan']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">-- Semua Status --</option>
                        <option value="Aktif" <?= $filter_status === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="Cuti" <?= $filter_status === 'Cuti' ? 'selected' : '' ?>>Cuti</option>
                        <option value="Lulus" <?= $filter_status === 'Lulus' ? 'selected' : '' ?>>Lulus</option>
                        <option value="Drop Out" <?= $filter_status === 'Drop Out' ? 'selected' : '' ?>>Drop Out</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary w-100 btn-custom justify-content-center" style="border-radius:10px;">Filter</button>
                    <?php if ($cari !== '' || $filter_jurusan !== '' || $filter_status !== ''): ?>
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
                        <th>Email</th>
                        <th>Jurusan</th>
                        <th>Status</th>
                        <th class="actions-col" style="width: 180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($q) == 0): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-inbox-fill d-block mb-2" style="font-size: 32px;"></i>
                                Tidak ada data mahasiswa ditemukan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $no = $offset + 1;
                        while ($d = mysqli_fetch_assoc($q)): 
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if (!empty($d['foto']) && file_exists('uploads/' . $d['foto'])): ?>
                                            <img class="avatar-img" src="uploads/<?= htmlspecialchars($d['foto']) ?>" alt="Foto Profil" style="flex-shrink: 0; width: 40px; height: 40px;">
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
                                <td><?= htmlspecialchars($d['email']) ?></td>
                                <td><?= htmlspecialchars($d['jurusan']) ?></td>
                                <td>
                                    <?php 
                                    $status = $d['status'];
                                    $badge_class = 'badge-aktif';
                                    if ($status === 'Cuti') $badge_class = 'badge-cuti';
                                    elseif ($status === 'Lulus') $badge_class = 'badge-lulus';
                                    elseif ($status === 'Drop Out') $badge_class = 'badge-do';
                                    ?>
                                    <span class="badge <?= $badge_class ?> px-2.5 py-1.5" style="font-size: 12px; font-weight: 600;"><?= $status ?></span>
                                </td>
                                <td class="actions-col">
                                    <div class="d-flex gap-1.5">
                                        <!-- Detail Button (Triggers Javascript Modal) -->
                                        <button class="btn btn-outline-info btn-sm btn-detail" 
                                                style="border-radius: 6px; font-weight: 500;"
                                                data-nim="<?= htmlspecialchars($d['nim']) ?>"
                                                data-nama="<?= htmlspecialchars($d['nama']) ?>"
                                                data-email="<?= htmlspecialchars($d['email']) ?>"
                                                data-jurusan="<?= htmlspecialchars($d['jurusan']) ?>"
                                                data-gender="<?= htmlspecialchars($d['jenis_kelamin']) ?>"
                                                data-lahir="<?= date('d F Y', strtotime($d['tanggal_lahir'])) ?>"
                                                data-telp="<?= htmlspecialchars($d['telepon']) ?>"
                                                data-alamat="<?= htmlspecialchars($d['alamat']) ?>"
                                                data-status="<?= $status ?>"
                                                data-foto="<?= !empty($d['foto']) && file_exists('uploads/' . $d['foto']) ? 'uploads/' . htmlspecialchars($d['foto']) : '' ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#detailModal">
                                            Detail
                                        </button>
                                        
                                        <a class="btn btn-outline-warning btn-sm" style="border-radius: 6px; font-weight: 500;" href="edit.php?id=<?= $d['id'] ?>">Edit</a>
                                        
                                        <a class="btn btn-outline-danger btn-sm" 
                                           style="border-radius: 6px; font-weight: 500;" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus data <?= addslashes($d['nama']) ?>? Semua file terkait juga akan terhapus.')" 
                                           href="hapus.php?id=<?= $d['id'] ?>">
                                            Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-4">
                <div class="text-muted" style="font-size: 13px;">
                    Menampilkan data <?= $offset + 1 ?> sampai <?= min($offset + $limit, $total_rows) ?> dari total <?= $total_rows ?> mahasiswa.
                </div>
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <!-- Previous Page -->
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&cari=<?= urlencode($cari) ?>&jurusan=<?= urlencode($filter_jurusan) ?>&status=<?= urlencode($filter_status) ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        
                        <!-- Page Numbers -->
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&cari=<?= urlencode($cari) ?>&jurusan=<?= urlencode($filter_jurusan) ?>&status=<?= urlencode($filter_status) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <!-- Next Page -->
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&cari=<?= urlencode($cari) ?>&jurusan=<?= urlencode($filter_jurusan) ?>&status=<?= urlencode($filter_status) ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- Student Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="detailModalLabel">Kartu Detail Mahasiswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="modal-profile-header">
                    <div id="modal-photo-container">
                        <!-- Injected by JavaScript -->
                    </div>
                    <div>
                        <h4 class="fw-bold mb-1" id="modal-nama" style="color: #0f172a;">-</h4>
                        <p class="text-muted mb-2" id="modal-nim" style="font-size: 14px;">-</p>
                        <span class="badge" id="modal-status" style="font-size: 12px; font-weight: 600;">-</span>
                    </div>
                </div>
                
                <!-- Detailed info grid -->
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-muted" style="font-size: 11px; font-weight: 600; text-transform: uppercase;">Program Studi</div>
                        <div class="fw-semibold" id="modal-jurusan" style="color: #334155; font-size: 14px;">-</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size: 11px; font-weight: 600; text-transform: uppercase;">Jenis Kelamin</div>
                        <div class="fw-semibold" id="modal-gender" style="color: #334155; font-size: 14px;">-</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size: 11px; font-weight: 600; text-transform: uppercase;">Tanggal Lahir</div>
                        <div class="fw-semibold" id="modal-lahir" style="color: #334155; font-size: 14px;">-</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size: 11px; font-weight: 600; text-transform: uppercase;">Telepon</div>
                        <div class="fw-semibold" id="modal-telp" style="color: #334155; font-size: 14px;">-</div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted" style="font-size: 11px; font-weight: 600; text-transform: uppercase;">Surel (Email)</div>
                        <div class="fw-semibold" id="modal-email" style="color: #334155; font-size: 14px;">-</div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted" style="font-size: 11px; font-weight: 600; text-transform: uppercase;">Alamat Rumah</div>
                        <div class="text-secondary" id="modal-alamat" style="font-size: 13px; line-height: 1.5;">-</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const detailButtons = document.querySelectorAll(".btn-detail");
        
        detailButtons.forEach(button => {
            button.addEventListener("click", function() {
                const nim = this.getAttribute("data-nim");
                const nama = this.getAttribute("data-nama");
                const email = this.getAttribute("data-email");
                const jurusan = this.getAttribute("data-jurusan");
                const gender = this.getAttribute("data-gender");
                const lahir = this.getAttribute("data-lahir");
                const telp = this.getAttribute("data-telp");
                const alamat = this.getAttribute("data-alamat");
                const status = this.getAttribute("data-status");
                const foto = this.getAttribute("data-foto");
                
                // Populate Modal Elements
                document.getElementById("modal-nama").textContent = nama;
                document.getElementById("modal-nim").textContent = nim;
                document.getElementById("modal-jurusan").textContent = jurusan;
                document.getElementById("modal-gender").textContent = gender;
                document.getElementById("modal-lahir").textContent = lahir;
                document.getElementById("modal-telp").textContent = telp;
                document.getElementById("modal-email").textContent = email;
                document.getElementById("modal-alamat").textContent = alamat;
                
                // Status Badge class mapping
                const statusBadge = document.getElementById("modal-status");
                statusBadge.textContent = status;
                statusBadge.className = "badge"; // Reset classes
                if (status === 'Aktif') {
                    statusBadge.classList.add("badge-aktif");
                } else if (status === 'Cuti') {
                    statusBadge.classList.add("badge-cuti");
                } else if (status === 'Lulus') {
                    statusBadge.classList.add("badge-lulus");
                } else if (status === 'Drop Out') {
                    statusBadge.classList.add("badge-do");
                }
                
                // Handle Avatar / Photo
                const photoContainer = document.getElementById("modal-photo-container");
                photoContainer.innerHTML = ''; // Clear previous
                
                if (foto && foto !== '') {
                    const img = document.createElement("img");
                    img.src = foto;
                    img.alt = "Foto " + nama;
                    img.className = "modal-avatar-lg";
                    photoContainer.appendChild(img);
                } else {
                    const initialDiv = document.createElement("div");
                    initialDiv.className = "modal-avatar-initial-lg";
                    initialDiv.textContent = nama.substring(0, 1).toUpperCase();
                    photoContainer.appendChild(initialDiv);
                }
            });
        });
    });
</script>
</body>
</html>
