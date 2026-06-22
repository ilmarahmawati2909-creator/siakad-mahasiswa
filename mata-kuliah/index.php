<?php
include '../config/koneksi.php';

// Check login
if (!isset($_SESSION['login'])) {
    header('Location: ../login.php');
    exit;
}

$admin_nama = $_SESSION['nama'] ?? 'Administrator';

// Get search input
$cari = isset($_GET['cari']) ? trim($_GET['cari']) : '';

// Build Query
$where_sql = '';
$params = [];
$types = '';

if ($cari !== '') {
    $where_sql = " WHERE kode_mk LIKE ? OR nama_mk LIKE ?";
    $search_param = "%$cari%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

$query = "SELECT * FROM mata_kuliah" . $where_sql . " ORDER BY kode_mk ASC";

if (count($params) > 0) {
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $q = mysqli_stmt_get_result($stmt);
} else {
    $q = mysqli_query($koneksi, $query);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mata Kuliah - SIAKAD</title>
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
        .form-control {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            border-radius: 10px;
            padding: 10px 16px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background-color: white;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

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
            <a href="../mahasiswa/index.php" class="sidebar-link">
                <i class="bi bi-people-fill"></i>
                <span>Data Mahasiswa</span>
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="index.php" class="sidebar-link active">
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
    
    <!-- Top Bar -->
    <div class="topbar">
        <div>
            <h3 class="page-title">Kelola Mata Kuliah</h3>
            <p class="text-muted mb-0" style="font-size: 14px;">Tambah, edit, hapus, dan cari kurikulum mata kuliah</p>
        </div>
        <div class="card-header-actions d-flex gap-2">
            <!-- Add Button -->
            <a href="tambah.php" class="btn btn-custom btn-primary-custom">
                <i class="bi bi-plus-lg"></i>
                <span>Tambah Mata Kuliah</span>
            </a>
        </div>
    </div>

    <!-- Table and Search Container -->
    <div class="table-card">
        
        <!-- Search Form -->
        <form method="get" action="" class="mb-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" name="cari" placeholder="Cari Kode atau Nama Mata Kuliah..." value="<?= htmlspecialchars($cari) ?>">
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary w-100 btn-custom justify-content-center" style="border-radius:10px;">Cari</button>
                    <?php if ($cari !== ''): ?>
                        <a href="index.php" class="btn btn-outline-danger btn-custom justify-content-center p-2" title="Reset Search">
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
                        <th>Kode MK</th>
                        <th>Nama Mata Kuliah</th>
                        <th>Bobot SKS</th>
                        <th style="width: 200px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($q) == 0): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="bi bi-inbox-fill d-block mb-2" style="font-size: 32px;"></i>
                                Tidak ada data mata kuliah ditemukan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $no = 1;
                        while ($d = mysqli_fetch_assoc($q)): 
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="fw-bold" style="color: var(--primary-color);"><?= htmlspecialchars($d['kode_mk']) ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($d['nama_mk']) ?></td>
                                <td><span class="badge bg-light text-dark border px-3 py-2" style="font-size: 13px;"><?= htmlspecialchars($d['sks']) ?> SKS</span></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a class="btn btn-outline-warning btn-sm" style="border-radius: 6px; font-weight: 500;" href="edit.php?id=<?= $d['id'] ?>">Edit</a>
                                        <a class="btn btn-outline-danger btn-sm" 
                                           style="border-radius: 6px; font-weight: 500;" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus mata kuliah <?= addslashes($d['nama_mk']) ?>? Semua nilai terkait mata kuliah ini juga akan ikut terhapus.')" 
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

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
