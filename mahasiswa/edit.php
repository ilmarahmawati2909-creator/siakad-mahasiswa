<?php
include '../config/koneksi.php';
// Redirect if not logged in
if (!isset($_SESSION['login'])) {
    header('Location: ../login.php');
    exit;
}

$admin_nama = $_SESSION['nama'] ?? 'Administrator';

$error = '';
$success = '';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

// Fetch current student data
$stmt_fetch = mysqli_prepare($koneksi, "SELECT * FROM mahasiswa WHERE id = ?");
mysqli_stmt_bind_param($stmt_fetch, "i", $id);
mysqli_stmt_execute($stmt_fetch);
$result = mysqli_stmt_get_result($stmt_fetch);
$d = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt_fetch);

if (!$d) {
    header('Location: index.php');
    exit;
}

if (isset($_POST['update'])) {
    $nim = trim($_POST['nim']);
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $jurusan = trim($_POST['jurusan']);
    $jenis_kelamin = isset($_POST['jenis_kelamin']) ? $_POST['jenis_kelamin'] : '';
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $telepon = trim($_POST['telepon']);
    $alamat = trim($_POST['alamat']);
    $status = isset($_POST['status']) ? $_POST['status'] : 'Aktif';
    
    // Server-side validation
    if (empty($nim) || empty($nama) || empty($email) || empty($jurusan) || empty($jenis_kelamin) || empty($tanggal_lahir) || empty($telepon) || empty($alamat)) {
        $error = 'Semua kolom wajib diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } else {
        // Check for duplicate NIM on OTHER students
        $stmt_check = mysqli_prepare($koneksi, "SELECT id FROM mahasiswa WHERE nim = ? AND id != ?");
        mysqli_stmt_bind_param($stmt_check, "si", $nim, $id);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);
        
        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $error = "NIM $nim sudah terdaftar oleh mahasiswa lain!";
            mysqli_stmt_close($stmt_check);
        } else {
            mysqli_stmt_close($stmt_check);
            
            $foto_name = $d['foto']; // Keep existing photo name by default
            $new_photo_uploaded = false;
            
            // Handle New Photo Upload if selected
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['foto']['tmp_name'];
                $file_name = $_FILES['foto']['name'];
                $file_size = $_FILES['foto']['size'];
                
                $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                if (!in_array($file_ext, $allowed_exts)) {
                    $error = 'Format foto harus berupa JPG, JPEG, PNG, atau WEBP!';
                } elseif ($file_size > 2 * 1024 * 1024) { // 2MB Limit
                    $error = 'Ukuran foto maksimal adalah 2MB!';
                } else {
                    // Generate unique filename
                    $foto_name = time() . '_' . preg_replace('/[^A-Za-z0-9]/', '', $nim) . '.' . $file_ext;
                    $upload_dir = 'uploads/';
                    
                    if (move_uploaded_file($file_tmp, $upload_dir . $foto_name)) {
                        $new_photo_uploaded = true;
                        
                        // Delete old photo file if it exists
                        if (!empty($d['foto']) && file_exists($upload_dir . $d['foto'])) {
                            unlink($upload_dir . $d['foto']);
                        }
                    } else {
                        $error = 'Gagal mengunggah foto baru ke server!';
                        $foto_name = $d['foto'];
                    }
                }
            }
            
            // Update Data if no errors
            if (empty($error)) {
                $stmt_update = mysqli_prepare($koneksi, "UPDATE mahasiswa SET nim = ?, nama = ?, email = ?, jurusan = ?, jenis_kelamin = ?, tanggal_lahir = ?, telepon = ?, alamat = ?, status = ?, foto = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt_update, "ssssssssssi", $nim, $nama, $email, $jurusan, $jenis_kelamin, $tanggal_lahir, $telepon, $alamat, $status, $foto_name, $id);
                
                if (mysqli_stmt_execute($stmt_update)) {
                    $success = 'Data mahasiswa berhasil diperbarui!';
                    mysqli_stmt_close($stmt_update);
                    
                    // Reload data
                    $d['nim'] = $nim;
                    $d['nama'] = $nama;
                    $d['email'] = $email;
                    $d['jurusan'] = $jurusan;
                    $d['jenis_kelamin'] = $jenis_kelamin;
                    $d['tanggal_lahir'] = $tanggal_lahir;
                    $d['telepon'] = $telepon;
                    $d['alamat'] = $alamat;
                    $d['status'] = $status;
                    $d['foto'] = $foto_name;
                    
                    header("refresh:2;url=index.php");
                } else {
                    $error = 'Gagal memperbarui data: ' . mysqli_error($koneksi);
                    mysqli_stmt_close($stmt_update);
                }
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
    <title>Edit Mahasiswa - SIAKAD</title>
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

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 30px;
        }

        /* Card Custom */
        .form-card {
            background-color: white;
            border: none;
            border-radius: 16px;
            padding: 32px;
            box-shadow: var(--card-shadow);
        }

        /* Inputs design */
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

        /* Avatar preview styling */
        .preview-container {
            width: 120px;
            height: 120px;
            border-radius: 16px;
            border: 2px dashed #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-color: #f8fafc;
            position: relative;
        }

        #image-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .preview-placeholder {
            text-align: center;
            color: #94a3b8;
            font-size: 11px;
            padding: 10px;
            display: none;
        }

        .preview-placeholder i {
            font-size: 24px;
            display: block;
            margin-bottom: 4px;
        }

        /* Buttons */
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

        /* Alerts design */
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
    
    <h3 class="page-title">Edit Data Mahasiswa</h3>

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
        <form method="post" action="" enctype="multipart/form-data">
            
            <div class="row g-4">
                
                <!-- Left column: Main form details -->
                <div class="col-lg-8">
                    <div class="row g-3">
                        
                        <!-- NIM -->
                        <div class="col-md-6">
                            <label for="nim" class="form-label">NIM (Nomor Induk Mahasiswa)</label>
                            <input type="text" class="form-control" id="nim" name="nim" value="<?= htmlspecialchars($d['nim']) ?>" required>
                        </div>
                        
                        <!-- Nama -->
                        <div class="col-md-6">
                            <label for="nama" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($d['nama']) ?>" required>
                        </div>
                        
                        <!-- Email -->
                        <div class="col-md-6">
                            <label for="email" class="form-label">Surel (Email)</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($d['email']) ?>" required>
                        </div>
                        
                        <!-- Jurusan -->
                        <div class="col-md-6">
                            <label for="jurusan" class="form-label">Program Studi (Jurusan)</label>
                            <input type="text" class="form-control" id="jurusan" name="jurusan" value="<?= htmlspecialchars($d['jurusan']) ?>" required>
                        </div>
                        
                        <!-- Jenis Kelamin -->
                        <div class="col-md-6">
                            <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
                            <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
                                <option value="">-- Pilih Jenis Kelamin --</option>
                                <option value="Laki-laki" <?= $d['jenis_kelamin'] === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="Perempuan" <?= $d['jenis_kelamin'] === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>
                        
                        <!-- Tanggal Lahir -->
                        <div class="col-md-6">
                            <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
                            <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" value="<?= $d['tanggal_lahir'] ?>" required>
                        </div>

                        <!-- Telepon -->
                        <div class="col-md-6">
                            <label for="telepon" class="form-label">Nomor Telepon</label>
                            <input type="text" class="form-control" id="telepon" name="telepon" value="<?= htmlspecialchars($d['telepon']) ?>" required>
                        </div>
                        
                        <!-- Status Akademik -->
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status Akademik</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Aktif" <?= $d['status'] === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                                <option value="Cuti" <?= $d['status'] === 'Cuti' ? 'selected' : '' ?>>Cuti</option>
                                <option value="Lulus" <?= $d['status'] === 'Lulus' ? 'selected' : '' ?>>Lulus</option>
                                <option value="Drop Out" <?= $d['status'] === 'Drop Out' ? 'selected' : '' ?>>Drop Out</option>
                            </select>
                        </div>
                        
                        <!-- Alamat -->
                        <div class="col-12">
                            <label for="alamat" class="form-label">Alamat Rumah Lengkap</label>
                            <textarea class="form-control" id="alamat" name="alamat" rows="3" required><?= htmlspecialchars($d['alamat']) ?></textarea>
                        </div>
                        
                    </div>
                </div>

                <!-- Right column: Photo Upload & Preview -->
                <div class="col-lg-4 d-flex flex-column align-items-center justify-content-start border-start ps-lg-4">
                    <div class="w-100 text-center mb-3">
                        <label class="form-label d-block text-lg-start ps-lg-4">Foto Profil Mahasiswa</label>
                    </div>
                    
                    <!-- Preview Box -->
                    <div class="preview-container mb-3">
                        <?php if (!empty($d['foto']) && file_exists('uploads/' . $d['foto'])): ?>
                            <img id="image-preview" src="uploads/<?= htmlspecialchars($d['foto']) ?>" alt="Foto Profil">
                            <div class="preview-placeholder" id="preview-placeholder">
                                <i class="bi bi-camera"></i>
                                <span>Pilih file foto untuk melihat pratinjau</span>
                            </div>
                        <?php else: ?>
                            <img id="image-preview" src="#" alt="Pratinjau Foto" style="display: none;">
                            <div class="preview-placeholder" id="preview-placeholder" style="display: block;">
                                <i class="bi bi-camera"></i>
                                <span>Pilih file foto untuk melihat pratinjau</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- File input -->
                    <div class="w-100 px-lg-4">
                        <input class="form-control" type="file" id="foto" name="foto" accept="image/*">
                        <small class="text-muted d-block mt-2 text-center" style="font-size: 11px;">Mendukung format JPG, JPEG, PNG, WEBP (Maksimal 2MB)</small>
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
    // Live Image Preview script
    document.getElementById("foto").addEventListener("change", function() {
        const file = this.files[0];
        const preview = document.getElementById("image-preview");
        const placeholder = document.getElementById("preview-placeholder");
        
        if (file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = "block";
                placeholder.style.display = "none";
            }
            
            reader.readAsDataURL(file);
        } else {
            // Revert to original database image if available, or placeholder
            <?php if (!empty($d['foto']) && file_exists('uploads/' . $d['foto'])): ?>
                preview.src = "uploads/<?= htmlspecialchars($d['foto']) ?>";
                preview.style.display = "block";
                placeholder.style.display = "none";
            <?php else: ?>
                preview.src = "#";
                preview.style.display = "none";
                placeholder.style.display = "block";
            <?php endif; ?>
        }
    });
</script>
</body>
</html>
