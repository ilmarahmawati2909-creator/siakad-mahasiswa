<?php
include 'config/koneksi.php';

// Check login
if (!isset($_SESSION['login'])) {
    header('Location: login.php');
    exit;
}

$admin_nama = $_SESSION['nama'] ?? 'Administrator';

// Fetch Statistics
$total_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM mahasiswa");
$total_mhs = mysqli_fetch_assoc($total_query)['total'];

$aktif_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM mahasiswa WHERE status = 'Aktif'");
$total_aktif = mysqli_fetch_assoc($aktif_query)['total'];

$laki_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM mahasiswa WHERE jenis_kelamin = 'Laki-laki'");
$total_l = mysqli_fetch_assoc($laki_query)['total'];

$perempuan_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM mahasiswa WHERE jenis_kelamin = 'Perempuan'");
$total_p = mysqli_fetch_assoc($perempuan_query)['total'];

// Fetch Jurusan Distribution for Chart.js
$chart_query = mysqli_query($koneksi, "SELECT jurusan, COUNT(*) as jml FROM mahasiswa GROUP BY jurusan");
$chart_labels = [];
$chart_values = [];
while ($row = mysqli_fetch_assoc($chart_query)) {
    $chart_labels[] = $row['jurusan'];
    $chart_values[] = (int)$row['jml'];
}

// Fetch recent 5 students
// Fetch recent 5 students
$recent_mhs = mysqli_query($koneksi, "SELECT * FROM mahasiswa ORDER BY id DESC LIMIT 5");

// Fetch Financial Statistics
$fin_query = mysqli_query($koneksi, "SELECT SUM(nominal) as total_tagihan, SUM(jumlah_bayar) as total_terbayar FROM administrasi");
$fin_data = mysqli_fetch_assoc($fin_query);
$total_tagihan = $fin_data['total_tagihan'] ?? 0;
$total_terbayar = $fin_data['total_terbayar'] ?? 0;
$total_tunggakan = $total_tagihan - $total_terbayar;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Informasi Akademik</title>
    <!-- CSS Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        /* Header Card */
        .header-card {
            background: linear-gradient(135deg, #1e1b4b 0%, #311042 100%);
            border-radius: 20px;
            padding: 32px;
            color: white;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.15);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .header-card::after {
            content: '';
            position: absolute;
            right: -50px;
            top: -50px;
            width: 200px;
            height: 200px;
            background: rgba(99, 102, 241, 0.15);
            border-radius: 50%;
            filter: blur(40px);
        }

        /* Stat Card */
        .stat-card {
            background-color: white;
            border: none;
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
        }

        .stat-icon-blue { background-color: rgba(99, 102, 241, 0.1); color: var(--primary-color); }
        .stat-icon-green { background-color: rgba(16, 185, 129, 0.1); color: #10b981; }
        .stat-icon-purple { background-color: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
        .stat-icon-pink { background-color: rgba(236, 72, 153, 0.1); color: #ec4899; }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 2px;
        }

        .stat-label {
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
        }

        /* Tables & Charts styling */
        .section-card {
            background-color: white;
            border: none;
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--card-shadow);
            height: 100%;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary-color);
        }

        .badge-aktif { background-color: rgba(16, 185, 129, 0.1); color: #10b981; }
        .badge-cuti { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .badge-lulus { background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .badge-do { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; }

        .table > :not(caption) > * > * {
            padding: 12px 16px;
            vertical-align: middle;
        }

        .table-custom th {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .table-custom td {
            font-size: 14px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }

        /* Avatar styles */
        .avatar-initial {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            flex-shrink: 0;
        }

        .avatar-img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
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
            <a href="dashboard.php" class="sidebar-link active">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="mahasiswa/index.php" class="sidebar-link">
                <i class="bi bi-people-fill"></i>
                <span>Data Mahasiswa</span>
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="mata-kuliah/index.php" class="sidebar-link">
                <i class="bi bi-book-fill"></i>
                <span>Mata Kuliah</span>
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="nilai/index.php" class="sidebar-link">
                <i class="bi bi-journal-bookmark-fill"></i>
                <span>Nilai Mahasiswa</span>
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="administrasi/index.php" class="sidebar-link">
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
        <a href="logout.php" class="btn btn-outline-danger btn-sm w-100 py-2 d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-box-arrow-right"></i>
            <span>Log Keluar</span>
        </a>
    </div>
</div>

<!-- Main Content Panel -->
<div class="main-content">
    <!-- Header Card -->
    <div class="header-card">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span class="badge bg-primary px-3 py-2 mb-2" style="font-size: 11px; background: rgba(99, 102, 241, 0.25) !important;">SIAKAD v2.0</span>
                <h2 class="fw-bold mb-2">Selamat Datang, <?= htmlspecialchars($admin_nama) ?>!</h2>
                <p class="text-white-50 mb-0">Kelola informasi data mahasiswa, monitor statistik, dan pantau aktivitas akademik dengan mudah dan instan.</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <div class="text-white-50" style="font-size: 13px;">
                    <i class="bi bi-calendar3 me-1"></i> <span id="current-date"><?= date('d F Y') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <!-- Total Mahasiswa -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div>
                    <h3 class="stat-number"><?= $total_mhs ?></h3>
                    <div class="stat-label">Total Mahasiswa</div>
                </div>
                <div class="stat-icon stat-icon-blue">
                    <i class="bi bi-people"></i>
                </div>
            </div>
        </div>
        <!-- Mahasiswa Aktif -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div>
                    <h3 class="stat-number"><?= $total_aktif ?></h3>
                    <div class="stat-label">Mahasiswa Aktif</div>
                </div>
                <div class="stat-icon stat-icon-green">
                    <i class="bi bi-check-circle"></i>
                </div>
            </div>
        </div>
        <!-- Laki-laki -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div>
                    <h3 class="stat-number"><?= $total_l ?></h3>
                    <div class="stat-label">Laki-laki</div>
                </div>
                <div class="stat-icon stat-icon-purple">
                    <i class="bi bi-gender-male"></i>
                </div>
            </div>
        </div>
        <!-- Perempuan -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div>
                    <h3 class="stat-number"><?= $total_p ?></h3>
                    <div class="stat-label">Perempuan</div>
                </div>
                <div class="stat-icon stat-icon-pink">
                    <i class="bi bi-gender-female"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Statistics Row -->
    <div class="row g-4 mb-4">
        <!-- Total Tagihan -->
        <div class="col-sm-6 col-xl-4">
            <div class="stat-card" style="border-left: 4px solid var(--primary-color);">
                <div>
                    <h3 class="stat-number">Rp <?= number_format($total_tagihan, 0, ',', '.') ?></h3>
                    <div class="stat-label">Total Tagihan Keuangan</div>
                </div>
                <div class="stat-icon stat-icon-blue">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
        </div>
        <!-- Total Terbayar -->
        <div class="col-sm-6 col-xl-4">
            <div class="stat-card" style="border-left: 4px solid #10b981;">
                <div>
                    <h3 class="stat-number">Rp <?= number_format($total_terbayar, 0, ',', '.') ?></h3>
                    <div class="stat-label">Total Pembayaran Diterima</div>
                </div>
                <div class="stat-icon stat-icon-green">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
        </div>
        <!-- Total Tunggakan -->
        <div class="col-sm-6 col-xl-4">
            <div class="stat-card" style="border-left: 4px solid #ef4444;">
                <div>
                    <h3 class="stat-number">Rp <?= number_format($total_tunggakan, 0, ',', '.') ?></h3>
                    <div class="stat-label">Total Piutang / Tunggakan</div>
                </div>
                <div class="stat-icon stat-icon-pink" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444;">
                    <i class="bi bi-exclamation-octagon"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Chart.js Visualization Card -->
        <div class="col-lg-6">
            <div class="section-card">
                <h5 class="section-title">
                    <i class="bi bi-pie-chart-fill"></i> Distribusi Mahasiswa per Jurusan
                </h5>
                <div style="position: relative; height: 320px; display: flex; align-items: center; justify-content: center;">
                    <?php if (empty($chart_labels)): ?>
                        <div class="text-center text-muted">Belum ada data mahasiswa untuk digambarkan.</div>
                    <?php else: ?>
                        <canvas id="jurusanChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Registered Students -->
        <div class="col-lg-6">
            <div class="section-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="section-title mb-0">
                        <i class="bi bi-clock-history"></i> Registrasi Mahasiswa Terbaru
                    </h5>
                    <a href="mahasiswa/index.php" class="btn btn-link btn-sm text-decoration-none" style="color: var(--primary-color); font-weight: 600;">Lihat Semua</a>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Mahasiswa</th>
                                <th>Jurusan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($recent_mhs) == 0): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Belum ada data registrasi mahasiswa terbaru.</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($mhs = mysqli_fetch_assoc($recent_mhs)): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if (!empty($mhs['foto']) && file_exists('mahasiswa/uploads/' . $mhs['foto'])): ?>
                                                    <img class="avatar-img" src="mahasiswa/uploads/<?= htmlspecialchars($mhs['foto']) ?>" alt="Avatar">
                                                <?php else: ?>
                                                    <div class="avatar-initial" style="width: 36px; height: 36px; font-size: 12px;">
                                                        <?= strtoupper(substr($mhs['nama'], 0, 1)) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-semibold" style="font-size: 13px;"><?= htmlspecialchars($mhs['nama']) ?></div>
                                                    <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($mhs['nim']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="font-size: 13px;"><?= htmlspecialchars($mhs['jurusan']) ?></td>
                                        <td>
                                            <?php 
                                            $status = $mhs['status'];
                                            $badge_class = 'badge-aktif';
                                            if ($status === 'Cuti') $badge_class = 'badge-cuti';
                                            elseif ($status === 'Lulus') $badge_class = 'badge-lulus';
                                            elseif ($status === 'Drop Out') $badge_class = 'badge-do';
                                            ?>
                                            <span class="badge <?= $badge_class ?> px-2.5 py-1.5" style="font-size: 11px; font-weight: 600;"><?= $status ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if (!empty($chart_labels)): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('jurusanChart').getContext('2d');
        const labels = <?= json_encode($chart_labels) ?>;
        const dataValues = <?= json_encode($chart_values) ?>;
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Mahasiswa',
                    data: dataValues,
                    backgroundColor: [
                        'rgba(99, 102, 241, 0.75)',  // Indigo
                        'rgba(139, 92, 246, 0.75)',  // Violet
                        'rgba(236, 72, 153, 0.75)',  // Pink
                        'rgba(16, 185, 129, 0.75)',  // Emerald Green
                        'rgba(20, 184, 166, 0.75)',  // Teal
                        'rgba(245, 158, 11, 0.75)'   // Amber
                    ],
                    borderColor: [
                        '#ffffff',
                    ],
                    borderWidth: 2,
                    hoverOffset: 12
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                family: "'Inter', sans-serif",
                                size: 12
                            },
                            color: '#475569',
                            boxWidth: 15,
                            padding: 15
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: {
                            family: "'Inter', sans-serif",
                            size: 12,
                            weight: 'bold'
                        },
                        bodyFont: {
                            family: "'Inter', sans-serif",
                            size: 12
                        },
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                cutout: '70%'
            }
        });
    });
</script>
<?php endif; ?>

</body>
</html>