<?php
session_start();
require_once('../config/config.php');
checkLevel([1, 2]); // Admin dan Petugas

$is_admin = $_SESSION['id_level'] == 1;

// Definisikan fungsi bantuan jika belum ada
if (!function_exists('formatRupiah')) {
    function formatRupiah($angka) {
        if ($angka === null || $angka === '') return 'Rp 0';
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }
}

if (!function_exists('formatTanggal')) {
    function formatTanggal($tanggal) {
        if ($tanggal === null || $tanggal === '') return '-';
        $bulan = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        $tgl = date('d', strtotime($tanggal));
        $bln = date('n', strtotime($tanggal));
        $thn = date('Y', strtotime($tanggal));
        return $tgl . ' ' . $bulan[(int)$bln] . ' ' . $thn;
    }
}

// Handle Delete
if(isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Delete image file if exists
    $barang = mysqli_fetch_assoc(mysqli_query($conn, "SELECT gambar FROM tb_barang WHERE id_barang = $id"));
    if($barang['gambar'] && file_exists('../uploads/barang/' . $barang['gambar'])) {
        unlink('../uploads/barang/' . $barang['gambar']);
    }
    
    if(mysqli_query($conn, "DELETE FROM tb_barang WHERE id_barang = $id")) {
        $_SESSION['success'] = "Barang berhasil dihapus";
    } else {
        $_SESSION['error'] = "Gagal menghapus barang";
    }
    header('Location: data_barang.php');
    exit;
}

// Handle Add/Edit
if(isset($_POST['submit'])) {
    $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $tgl = $_POST['tgl'];
    $harga_awal = str_replace('.', '', $_POST['harga_awal']); // Remove dots from rupiah format
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi_barang']);
    $status = $_POST['status_barang'];
    
    // Handle file upload
    $gambar_query = "";
    $gambar_value = "";
    if(isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['gambar']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            // Create uploads directory if not exists
            if(!is_dir('../uploads/barang')) {
                mkdir('../uploads/barang', 0777, true);
            }
            
            $gambar = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $filename);
            $gambar_value = $gambar;
            
            // If update, delete old image
            if(isset($_POST['id_barang']) && $_POST['id_barang'] != '') {
                $id = intval($_POST['id_barang']);
                $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT gambar FROM tb_barang WHERE id_barang = $id"));
                if($old['gambar'] && file_exists('../uploads/barang/' . $old['gambar'])) {
                    unlink('../uploads/barang/' . $old['gambar']);
                }
            }
            
            move_uploaded_file($_FILES['gambar']['tmp_name'], '../uploads/barang/' . $gambar);
            $gambar_query = ", gambar = '$gambar'";
        } else {
            $_SESSION['error'] = "Format file tidak diizinkan. Gunakan JPG, PNG, atau GIF";
            header('Location: data_barang.php' . (isset($_POST['id_barang']) ? '?edit=' . $_POST['id_barang'] : ''));
            exit;
        }
    }
    
    if(isset($_POST['id_barang']) && $_POST['id_barang'] != '') {
        // Update
        $id = intval($_POST['id_barang']);
        $query = "UPDATE tb_barang SET 
                  nama_barang = '$nama_barang',
                  tgl = '$tgl',
                  harga_awal = '$harga_awal',
                  deskripsi_barang = '$deskripsi',
                  status_barang = '$status'
                  $gambar_query
                  WHERE id_barang = $id";
        $message = "Barang berhasil diupdate";
    } else {
        // Insert
        if($gambar_value) {
            $query = "INSERT INTO tb_barang (nama_barang, tgl, harga_awal, deskripsi_barang, status_barang, gambar) 
                      VALUES ('$nama_barang', '$tgl', '$harga_awal', '$deskripsi', '$status', '$gambar_value')";
        } else {
            $query = "INSERT INTO tb_barang (nama_barang, tgl, harga_awal, deskripsi_barang, status_barang) 
                      VALUES ('$nama_barang', '$tgl', '$harga_awal', '$deskripsi', '$status')";
        }
        $message = "Barang berhasil ditambahkan";
    }
    
    if(mysqli_query($conn, $query)) {
        $_SESSION['success'] = $message;
    } else {
        $_SESSION['error'] = "Gagal menyimpan data: " . mysqli_error($conn);
    }
    header('Location: data_barang.php');
    exit;
}

// Get data for edit
$edit_data = null;
if(isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $edit_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tb_barang WHERE id_barang = $id"));
}

// Pagination and Search
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

$where = [];
if(!empty($search)) {
    $where[] = "(nama_barang LIKE '%$search%' OR deskripsi_barang LIKE '%$search%')";
}
if(!empty($status_filter)) {
    $where[] = "status_barang = '$status_filter'";
}
$where_clause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";

// Get total records
$total_query = "SELECT COUNT(*) as total FROM tb_barang $where_clause";
$total_result = mysqli_query($conn, $total_query);
$total_records = $total_result ? mysqli_fetch_assoc($total_result)['total'] : 0;
$total_pages = ceil($total_records / $limit);

// Get barang data
$barang = mysqli_query($conn, "SELECT * FROM tb_barang $where_clause ORDER BY id_barang DESC LIMIT $offset, $limit");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang - Sistem Lelang Online</title>
    
    <!-- Font Professional -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
    <style>
        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* Elegant Blue Theme */
        :root {
            --primary-50: #eef2f8;
            --primary-100: #d9e2f0;
            --primary-200: #b3c5e1;
            --primary-300: #8da8d2;
            --primary-400: #678bc3;
            --primary-500: #416eb4;
            --primary-600: #2a4f8c;
            --primary-700: #1e3a66;
            --primary-800: #132a4a;
            --primary-900: #0a1a30;
            
            --secondary-50: #f8fafc;
            --secondary-100: #f1f5f9;
            --secondary-200: #e2e8f0;
            --secondary-300: #cbd5e1;
            --secondary-400: #94a3b8;
            --secondary-500: #64748b;
            --secondary-600: #475569;
            --secondary-700: #334155;
            --secondary-800: #1e293b;
            --secondary-900: #0f172a;
        }
        
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Custom Classes */
        .bg-primary { background-color: var(--primary-600); }
        .bg-primary-light { background-color: var(--primary-500); }
        .bg-primary-dark { background-color: var(--primary-700); }
        .text-primary { color: var(--primary-700); }
        .border-primary { border-color: var(--primary-200); }
        
        /* Modern Card with Hover Effects */
        .card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 25px -5px rgba(30, 58, 102, 0.08);
            border: 1px solid var(--primary-100);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-400), var(--primary-600));
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }
        
        .card:hover::before {
            transform: scaleX(1);
        }
        
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 25px 35px -8px rgba(30, 58, 102, 0.15);
        }
        
        /* Gradient Background */
        .gradient-bg {
            background: linear-gradient(135deg, var(--primary-700), var(--primary-600), var(--primary-500));
            background-size: 200% 200%;
            animation: gradientShift 8s ease infinite;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Glassmorphism */
        .glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(65, 110, 180, 0.15);
            transition: all 0.3s ease;
        }
        
        .glass:hover {
            background: rgba(255, 255, 255, 0.95);
            border-color: rgba(65, 110, 180, 0.3);
        }
        
        /* Table Styles */
        .modern-table {
            border-collapse: separate;
            border-spacing: 0 12px;
        }
        
        .modern-table tbody tr {
            background: white;
            border-radius: 18px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px -2px rgba(30, 58, 102, 0.06);
            animation: slideInRow 0.5s ease-out;
            animation-fill-mode: both;
        }
        
        @keyframes slideInRow {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .modern-table tbody tr:hover {
            transform: scale(1.02) translateY(-2px);
            box-shadow: 0 20px 25px -8px rgba(30, 58, 102, 0.15);
            background: linear-gradient(90deg, white, var(--primary-50));
        }
        
        /* Form Input Styles */
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--primary-200);
            border-radius: 14px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-400);
            box-shadow: 0 0 0 3px rgba(65, 110, 180, 0.15);
            transform: scale(1.02);
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--primary-700);
            font-size: 0.875rem;
        }
        
        /* Badge Styles */
        .badge {
            padding: 6px 14px;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .badge:hover {
            transform: scale(1.05);
            filter: brightness(1.1);
        }
        
        .badge-success {
            background: #e6f7e6;
            color: #0a5e0a;
            border: 1px solid #a3e0a3;
        }
        
        .badge-warning {
            background: #fff4e0;
            color: #9e5e0a;
            border: 1px solid #ffd79c;
        }
        
        .badge-danger {
            background: #ffe6e6;
            color: #a12323;
            border: 1px solid #ffb8b8;
        }
        
        .badge-info {
            background: var(--primary-100);
            color: var(--primary-800);
            border: 1px solid var(--primary-200);
        }
        
        /* Button Styles */
        .btn-primary {
            background: var(--primary-600);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
            z-index: -1;
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-primary:hover {
            background: var(--primary-700);
            transform: translateY(-2px);
            box-shadow: 0 12px 20px -8px rgba(30, 58, 102, 0.4);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--primary-700);
            border: 2px solid var(--primary-200);
            padding: 0.7rem 1.5rem;
            border-radius: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-outline:hover {
            background: var(--primary-50);
            border-color: var(--primary-500);
            color: var(--primary-800);
            transform: translateY(-2px);
        }
        
        /* Animations */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes pulse-soft {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .animate-pulse-soft {
            animation: pulse-soft 2s infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .spin-slow {
            animation: spin 8s linear infinite;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--primary-50);
            border-radius: 6px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary-300);
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-400);
        }
        
        /* Loading Spinner */
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--primary-200);
            border-top-color: var(--primary-600);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        /* File Upload Area */
        .upload-area {
            border: 2px dashed var(--primary-200);
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--primary-50);
        }
        
        .upload-area:hover {
            border-color: var(--primary-400);
            background: white;
            transform: scale(1.02);
            box-shadow: 0 10px 20px -5px rgba(65, 110, 180, 0.2);
        }
        
        .upload-area.dragover {
            border-color: var(--primary-600);
            background: var(--primary-100);
            transform: scale(1.05);
        }
    </style>
</head>
<body class="antialiased text-gray-800">
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
        <div class="bg-white rounded-2xl p-8 flex flex-col items-center animate__animated animate__zoomIn">
            <div class="spinner mb-4"></div>
            <p class="text-primary-600 font-medium">Memuat data...</p>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <!-- Particle Background -->
    <div id="particles" class="fixed inset-0 pointer-events-none"></div>

    <!-- Navbar -->
    <nav class="glass sticky top-0 z-30 border-b border-primary-100 shadow-sm">
        <div class="w-full px-0 flex">
            <div class="flex justify-between h-16 w-full">
                <div class="flex items-center" style="width:288px;min-width:288px;padding-left:1.5rem">
                    <div class="gradient-bg p-2.5 rounded-xl shadow-lg animate-float flex items-center justify-center" style="min-width:40px;min-height:40px">
                        <i class="fas fa-gavel text-white text-lg"></i>
                    </div>
                    <div class="flex flex-col leading-tight ml-3">
                        <span class="font-extrabold text-lg tracking-tight" style="background: linear-gradient(135deg, var(--primary-700), var(--primary-500)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; line-height:1.2">Sistem Lelang</span>
                        <span class="text-xs font-semibold tracking-widest uppercase" style="color: var(--primary-400); letter-spacing: 0.18em; line-height:1.2">Online<span class="inline-block w-1.5 h-1.5 rounded-full bg-green-400 ml-1.5 mb-0.5 animate-pulse align-middle"></span></span>
                    </div>
                </div>
                <div class="flex items-center space-x-4 pr-6">
                    
                    
                    <!-- User Menu -->
                    <div class="flex items-center space-x-3 ml-2 pl-2 border-l-2 border-primary-100">
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-semibold text-primary-800"><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></p>
                            <p class="text-xs text-primary-500 flex items-center justify-end">
                                <i class="fas fa-circle text-green-500 text-[6px] mr-1 animate-pulse"></i>
                                <?php echo $is_admin ? 'Administrator' : 'Petugas'; ?>
                            </p>
                        </div>
                        <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center text-white font-bold shadow-md ring-2 ring-primary-100 hover:scale-110 transition-transform">
                            <?php echo strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)); ?>
                        </div>
                    </div>
                    
                    <a href="../auth/logout.php" onclick="return confirm('Yakin ingin keluar?')" class="relative overflow-hidden group btn-outline text-sm py-2 px-4 flex items-center space-x-2">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="hidden sm:inline">Keluar</span>
                        <span class="absolute inset-0 bg-primary-100 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left"></span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex relative">
        <!-- Sidebar -->
        <aside class="w-72 bg-white border-r border-primary-100 min-h-screen shadow-xl relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 gradient-bg"></div>
            <div class="p-6 relative z-10">
                <!-- Profile Card -->
                <div class="gradient-bg rounded-2xl p-6 mb-6 text-white shadow-xl relative overflow-hidden group" data-aos="fade-right" data-aos-delay="50">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-white opacity-5 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-700"></div>
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-5 rounded-full -ml-8 -mb-8 group-hover:scale-150 transition-transform duration-700"></div>
                    
                    <div class="flex items-center space-x-4 relative z-10">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center text-2xl font-bold border-2 border-white/30 group-hover:scale-110 transition-transform">
                            <?php echo strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)); ?>
                        </div>
                        <div>
                            <p class="text-white/80 text-sm font-light">Selamat datang,</p>
                            <p class="text-xl font-bold group-hover:translate-x-1 transition-transform"><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></p>
                            <p class="text-white/80 text-xs mt-1 flex items-center">
                                <i class="fas fa-circle text-green-300 text-[6px] mr-2 animate-pulse"></i>
                                Sedang Aktif
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Navigation -->
                <nav class="space-y-2">
                    <p class="text-xs uppercase tracking-wider text-primary-300 mb-4 px-4 font-semibold" data-aos="fade-right">Menu Utama</p>
                    
                    <a href="dashboard.php" class="flex items-center px-4 py-3 text-primary-700 hover:bg-primary-50 rounded-xl transition-all duration-200 group relative overflow-hidden">
                        <i class="fas fa-home w-6 text-primary-400 group-hover:text-primary-600 group-hover:scale-110 transition-transform"></i>
                        <span class="ml-3 group-hover:translate-x-1 transition-transform">Beranda</span>
                    </a>
                    <a href="data_barang.php" class="flex items-center px-4 py-3 bg-primary-50 text-primary-700 rounded-xl font-medium group relative overflow-hidden">
                        <i class="fas fa-box w-6 text-primary-600"></i>
                        <span class="ml-3">Data Barang</span>
                        <i class="fas fa-chevron-right ml-auto text-sm text-primary-600"></i>
                    </a>

                    <a href="laporan.php" class="flex items-center px-4 py-3 text-primary-700 hover:bg-primary-50 rounded-xl transition-all duration-200 group relative overflow-hidden">
                        <i class="fas fa-chart-bar w-6 text-primary-400 group-hover:text-primary-600 group-hover:scale-110 transition-transform"></i>
                        <span class="ml-3 group-hover:translate-x-1 transition-transform">Laporan</span>
                    </a>
                </nav>
            </div>
            
            <!-- Sidebar Footer -->
            <div class="absolute bottom-6 left-6 right-6">
                <div class="bg-primary-50 rounded-xl p-4 border border-primary-100 hover:shadow-lg transition-all hover:scale-105 group">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm group-hover:rotate-12 transition-transform">
                            <i class="fas fa-headset text-primary-500 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-primary-800">Butuh Bantuan?</p>
                            <p class="text-xs text-primary-500 flex items-center">
                                <i class="fas fa-envelope mr-1"></i>
                                admin@lelang.com
                            </p>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-primary-100">
                        <p class="text-xs text-primary-400 text-center">
                            <i class="fas fa-circle text-green-500 text-[6px] mr-1 animate-pulse"></i>
                            Dukungan 24/7
                        </p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8 bg-secondary-50">
            <!-- Header Section -->
            <div class="mb-8" data-aos="fade-down">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-primary-800 mb-2">Kelola Barang</h1>
                        <p class="text-primary-500 flex items-center">
                            <i class="fas fa-box mr-2"></i>
                            Kelola data barang yang akan dilelang
                        </p>
                    </div>
                    <div class="mt-4 md:mt-0">
                        <div class="bg-white rounded-xl px-6 py-3 shadow-md border border-primary-100">
                            <span class="text-primary-600 font-semibold flex items-center">
                                <i class="fas fa-database mr-2"></i>
                                Total: <span class="text-primary-800 ml-1"><?php echo $total_records; ?></span> barang
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="mb-6" data-aos="fade-up">
                <div class="bg-white rounded-xl p-4 shadow-md border border-primary-100">
                    <form method="GET" class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1 relative group">
                            <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-primary-400 group-hover:scale-110 transition-transform"></i>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Cari nama barang atau deskripsi..." 
                                   class="w-full pl-12 pr-4 py-3 border border-primary-200 rounded-xl focus:outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100 transition-all hover:border-primary-300">
                        </div>
                        <div class="md:w-48">
                            <select name="status" class="w-full px-4 py-3 border border-primary-200 rounded-xl focus:outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100 transition-all hover:border-primary-300">
                                <option value="">Semua Status</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="dibuka" <?php echo $status_filter == 'dibuka' ? 'selected' : ''; ?>>Dibuka</option>
                                <option value="ditutup" <?php echo $status_filter == 'ditutup' ? 'selected' : ''; ?>>Ditutup</option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="btn-primary px-6 py-3">
                                <i class="fas fa-filter mr-2"></i>Filter
                            </button>
                            <a href="data_barang.php" class="btn-outline px-6 py-3">
                                <i class="fas fa-redo mr-2"></i>Perbarui
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Form Add/Edit -->
            <div class="card p-6 mb-8" data-aos="fade-up" data-aos-delay="100">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-primary-800">
                        <i class="fas fa-<?php echo $edit_data ? 'edit' : 'plus-circle'; ?> mr-3 text-primary-500"></i>
                        <?php echo $edit_data ? 'Edit Barang' : 'Tambah Barang Baru'; ?>
                    </h2>
                    <span class="bg-primary-100 text-primary-700 px-4 py-2 rounded-full text-sm font-semibold">
                        <i class="fas fa-cube mr-2"></i>Masukan Data Barang
                    </span>
                </div>
                
                <form method="POST" enctype="multipart/form-data" id="barangForm" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <input type="hidden" name="id_barang" value="<?php echo $edit_data['id_barang'] ?? ''; ?>">
                    
                    <!-- Nama Barang -->
                    <div class="space-y-2">
                        <label class="form-label">
                            <i class="fas fa-tag mr-2 text-primary-400"></i>Nama Barang
                        </label>
                        <input type="text" name="nama_barang" required
                               value="<?php echo htmlspecialchars($edit_data['nama_barang'] ?? ''); ?>"
                               placeholder="Masukkan nama barang"
                               class="form-input">
                    </div>

                    <!-- Tanggal -->
                    <div class="space-y-2">
                        <label class="form-label">
                            <i class="fas fa-calendar mr-2 text-primary-400"></i>Tanggal
                        </label>
                        <input type="date" name="tgl" required
                               value="<?php echo $edit_data['tgl'] ?? date('Y-m-d'); ?>"
                               class="form-input">
                    </div>

                    <!-- Harga Awal -->
                    <div class="space-y-2">
                        <label class="form-label">
                            <i class="fas fa-money-bill-wave mr-2 text-primary-400"></i>Harga Awal
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-primary-500 font-semibold">Rp</span>
                            <input type="text" name="harga_awal" id="harga_awal" required
                                   value="<?php echo isset($edit_data['harga_awal']) ? number_format($edit_data['harga_awal'], 0, ',', '.') : ''; ?>"
                                   placeholder="0"
                                   class="form-input pl-12"
                                   onkeyup="formatRupiah(this)">
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="space-y-2">
                        <label class="form-label">
                            <i class="fas fa-tasks mr-2 text-primary-400"></i>Status
                        </label>
                        <select name="status_barang" class="form-input">
                            <option value="pending" <?php echo ($edit_data['status_barang'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="dibuka" <?php echo ($edit_data['status_barang'] ?? '') == 'dibuka' ? 'selected' : ''; ?>>Dibuka</option>
                            <option value="ditutup" <?php echo ($edit_data['status_barang'] ?? '') == 'ditutup' ? 'selected' : ''; ?>>Ditutup</option>
                        </select>
                    </div>

                    <!-- Gambar -->
                    <div class="md:col-span-2 space-y-2">
                        <label class="form-label">
                            <i class="fas fa-image mr-2 text-primary-400"></i>Gambar Barang
                        </label>
                        
                        <!-- Upload Area -->
                        <div class="upload-area" id="uploadArea" onclick="document.getElementById('fileInput').click()">
                            <input type="file" id="fileInput" name="gambar" accept="image/*" class="hidden" onchange="previewImage(event)">
                            
                            <div id="uploadPlaceholder" class="<?php echo ($edit_data && $edit_data['gambar']) ? 'hidden' : ''; ?>">
                                <i class="fas fa-cloud-upload-alt text-5xl text-primary-300 mb-4 animate-float"></i>
                                <p class="text-primary-600 font-medium">Klik atau drag & drop untuk upload gambar</p>
                                <p class="text-sm text-primary-400 mt-2">Format: JPG, PNG, GIF, WEBP (Max 5MB)</p>
                            </div>
                            
                            <div id="previewContainer" class="relative <?php echo ($edit_data && $edit_data['gambar']) ? '' : 'hidden'; ?>">
                                <img id="previewImage" class="max-w-full h-64 rounded-xl mx-auto shadow-lg" 
                                     src="<?php echo ($edit_data && $edit_data['gambar']) ? '../uploads/barang/' . $edit_data['gambar'] : ''; ?>" 
                                     alt="Preview">
                                <button type="button" onclick="removeImage()" 
                                        class="absolute top-2 right-2 bg-red-500 text-white p-2 rounded-full hover:bg-red-600 transition-all hover:scale-110 shadow-lg">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        
                        <?php if($edit_data && $edit_data['gambar']): ?>
                            <p class="text-sm text-primary-500 mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                Gambar saat ini: <?php echo $edit_data['gambar']; ?> (akan diganti jika upload baru)
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Deskripsi -->
                    <div class="md:col-span-2 space-y-2">
                        <label class="form-label">
                            <i class="fas fa-align-left mr-2 text-primary-400"></i>Deskripsi
                        </label>
                        <textarea name="deskripsi_barang" rows="5"
                                  placeholder="Masukkan deskripsi lengkap barang..."
                                  class="form-input resize-none"><?php echo htmlspecialchars($edit_data['deskripsi_barang'] ?? ''); ?></textarea>
                    </div>

                    <!-- Buttons -->
                    <div class="md:col-span-2 flex flex-col sm:flex-row gap-4 mt-4">
                        <button type="submit" name="submit" class="btn-primary flex-1 py-4 text-lg">
                            <i class="fas fa-save mr-3"></i>
                            <?php echo $edit_data ? 'UPDATE DATA BARANG' : 'TAMBAH BARANG BARU'; ?>
                        </button>
                        
                        <?php if($edit_data): ?>
                        <a href="data_barang.php" class="btn-outline flex-1 py-4 text-lg text-center">
                            <i class="fas fa-times mr-3"></i>BATAL EDIT
                        </a>
                        <?php else: ?>
                        <button type="reset" class="btn-outline flex-1 py-4 text-lg" onclick="resetForm()">
                            <i class="fas fa-eraser mr-3"></i>BATAL EDIT
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-primary-100" data-aos="fade-up" data-aos-delay="200">
                <div class="p-6 border-b border-primary-100 bg-gradient-to-r from-primary-50 to-white">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-2xl font-bold text-primary-800">
                                <i class="fas fa-list mr-3 text-primary-500"></i>Daftar Barang
                            </h2>
                            <p class="text-primary-500 mt-1">Total <?php echo $total_records; ?> barang terdaftar</p>
                        </div>
                        <div class="bg-white px-4 py-2 rounded-xl border border-primary-200 shadow-sm hover:shadow-md transition-all">
                            <span class="text-primary-600 font-semibold flex items-center">
                                <i class="fas fa-filter mr-2 text-primary-400"></i>
                                <?php echo $status_filter ? ucfirst($status_filter) : 'Semua Status'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full modern-table">
                        <thead>
                            <tr class="bg-transparent">
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">No</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Gambar</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Nama Barang</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Tanggal</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Harga Awal</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($barang && mysqli_num_rows($barang) > 0): ?>
                                <?php 
                                $no = $offset + 1;
                                while($row = mysqli_fetch_assoc($barang)): 
                                ?>
                                <tr class="bg-white" style="animation-delay: <?php echo ($no * 0.05); ?>s">
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center justify-center w-8 h-8 bg-primary-100 text-primary-700 rounded-xl font-bold">
                                            <?php echo $no++; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if($row['gambar'] && file_exists('../uploads/barang/' . $row['gambar'])): ?>
                                            <div class="relative group w-16 h-16">
                                                <img src="../uploads/barang/<?php echo $row['gambar']; ?>" 
                                                     alt="<?php echo htmlspecialchars($row['nama_barang']); ?>" 
                                                     class="w-16 h-16 object-cover rounded-xl shadow-md group-hover:scale-110 transition-all duration-300">
                                                <div class="absolute inset-0 bg-primary-600 bg-opacity-0 group-hover:bg-opacity-30 rounded-xl transition-all duration-300 flex items-center justify-center">
                                                    <i class="fas fa-search-plus text-white opacity-0 group-hover:opacity-100 transition-all duration-300"></i>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="w-16 h-16 bg-primary-100 rounded-xl flex items-center justify-center">
                                                <i class="fas fa-image text-primary-400 text-2xl"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-primary-800"><?php echo htmlspecialchars($row['nama_barang']); ?></p>
                                        <p class="text-sm text-gray-500 mt-1 line-clamp-2 max-w-xs"><?php echo htmlspecialchars($row['deskripsi_barang'] ?? '-'); ?></p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center text-gray-700">
                                            <i class="fas fa-calendar-alt mr-2 text-primary-400"></i>
                                            <?php echo formatTanggal($row['tgl']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="font-bold text-primary-600 bg-primary-50 px-3 py-1 rounded-full">
                                            <?php echo formatRupiah($row['harga_awal']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if($row['status_barang'] == 'dibuka'): ?>
                                            <span class="badge badge-success">
                                                <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse-soft"></span>
                                                Dibuka
                                            </span>
                                        <?php elseif($row['status_barang'] == 'ditutup'): ?>
                                            <span class="badge badge-danger">
                                                <i class="fas fa-stop-circle mr-1"></i>
                                                Ditutup
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">
                                                <i class="fas fa-clock mr-1"></i>
                                                Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-2">
                                            <a href="?edit=<?php echo $row['id_barang']; ?>" 
                                               style="background:var(--primary-600);color:white;"
                                               class="px-4 py-2 rounded-xl flex items-center transition-all hover:scale-105 hover:opacity-90 shadow-md">
                                                <i class="fas fa-edit mr-2"></i>Edit
                                            </a>
                                            <a href="?delete=<?php echo $row['id_barang']; ?>" 
                                               onclick="return confirmDelete(event, 'Yakin ingin menghapus barang <?php echo htmlspecialchars($row['nama_barang']); ?>?')"
                                               style="background:var(--primary-100);color:var(--primary-700);border:1px solid var(--primary-200);"
                                               class="px-4 py-2 rounded-xl flex items-center transition-all hover:scale-105">
                                                <i class="fas fa-trash mr-2"></i>Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center mb-4 animate-float">
                                                <i class="fas fa-box-open text-primary-400 text-3xl"></i>
                                            </div>
                                            <p class="text-primary-600 font-medium text-lg">Belum ada data barang</p>
                                            <p class="text-sm text-primary-400 mt-2">Data barang akan muncul di sini</p>
                                            <?php if(!$search && !$status_filter): ?>
                                            <a href="#form" onclick="document.getElementById('nama_barang').focus()" class="btn-primary text-sm mt-4 px-6 py-2.5">
                                                <i class="fas fa-plus mr-2"></i>Tambah Barang
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                <div class="p-4 bg-primary-50 border-t border-primary-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-sm text-primary-600">
                        <i class="fas fa-info-circle mr-2 animate-pulse"></i>
                        Menampilkan <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $total_records); ?> dari <?php echo $total_records; ?> barang
                    </p>
                    <div class="flex items-center space-x-3">
                        <select class="text-sm border border-primary-200 rounded-lg px-3 py-2 bg-white text-primary-700 focus:ring-2 focus:ring-primary-100 transition-all hover:border-primary-300" onchange="changePerPage(this.value)">
                            <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10 per halaman</option>
                            <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25 per halaman</option>
                            <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50 per halaman</option>
                        </select>
                        <div class="flex space-x-2">
                            <?php if($page > 1): ?>
                            <a href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                               class="w-9 h-9 flex items-center justify-center rounded-xl bg-white text-primary-600 hover:bg-primary-600 hover:text-white transition-all border border-primary-200 hover:scale-110">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);
                            for($i = $start; $i <= $end; $i++):
                            ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                               class="w-9 h-9 flex items-center justify-center rounded-xl <?php echo $i == $page ? 'bg-primary-600 text-white' : 'bg-white text-primary-600 hover:bg-primary-50'; ?> transition-all border border-primary-200 hover:scale-110">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; ?>
                            
                            <?php if($page < $total_pages): ?>
                            <a href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                               class="w-9 h-9 flex items-center justify-center rounded-xl bg-white text-primary-600 hover:bg-primary-600 hover:text-white transition-all border border-primary-200 hover:scale-110">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Footer Note -->
            <div class="mt-6 text-center text-sm text-primary-400">
                <i class="fas fa-sync-alt mr-1 spin-slow"></i>
                Data diperbarui secara real-time • <?php
$_bulan = ["","Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
echo date('d') . ' ' . $_bulan[(int)date('n')] . ' ' . date('Y H:i:s');
?> WIB
            </div>
        </main>
    </div>

    <!-- AOS Animation Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 600,
            once: false,
            mirror: true,
            offset: 20,
            easing: 'ease-out-cubic'
        });

        // Particle Background
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            if(!particlesContainer) return;
            
            for (let i = 0; i < 15; i++) {
                const particle = document.createElement('div');
                particle.className = 'absolute bg-primary-200 rounded-full pointer-events-none';
                particle.style.width = (Math.random() * 6 + 2) + 'px';
                particle.style.height = particle.style.width;
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animation = `float ${Math.random() * 10 + 10}s linear infinite`;
                particle.style.opacity = '0.2';
                particlesContainer.appendChild(particle);
            }
        }
        createParticles();

        // Format Rupiah
        function formatRupiah(input) {
            let value = input.value.replace(/[^0-9]/g, '');
            if(value) {
                value = parseInt(value).toLocaleString('id-ID');
                input.value = value;
            }
        }

        // Preview Image
        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                if (!file.type.startsWith('image/')) {
                    showToast('File harus berupa gambar', 'error');
                    return;
                }
                
                if (file.size > 5 * 1024 * 1024) {
                    showToast('Ukuran file maksimal 5MB', 'error');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('uploadPlaceholder').classList.add('hidden');
                    document.getElementById('previewContainer').classList.remove('hidden');
                    document.getElementById('previewImage').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        }

        // Remove Image
        function removeImage() {
            document.getElementById('fileInput').value = '';
            document.getElementById('uploadPlaceholder').classList.remove('hidden');
            document.getElementById('previewContainer').classList.add('hidden');
            document.getElementById('previewImage').src = '';
        }

        // Drag and Drop
        const dropArea = document.querySelector('.upload-area');
        if(dropArea) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropArea.addEventListener(eventName, () => {
                    dropArea.classList.add('dragover');
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, () => {
                    dropArea.classList.remove('dragover');
                });
            });

            dropArea.addEventListener('drop', (e) => {
                const file = e.dataTransfer.files[0];
                if (file && file.type.startsWith('image/')) {
                    document.getElementById('fileInput').files = e.dataTransfer.files;
                    previewImage({ target: { files: [file] } });
                } else {
                    showToast('File harus berupa gambar', 'error');
                }
            });
        }

        // Reset Form
        function resetForm() {
            document.getElementById('barangForm').reset();
            removeImage();
        }

        // Confirm Delete
        function confirmDelete(event, message) {
            if (!confirm(message)) {
                event.preventDefault();
                return false;
            }
            showToast('Menghapus data...', 'info');
            return true;
        }

        // Change Per Page
        function changePerPage(value) {
            const url = new URL(window.location.href);
            url.searchParams.set('limit', value);
            url.searchParams.set('page', 1);
            window.location.href = url.toString();
        }

        // Toast Notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `flex items-center p-4 rounded-xl shadow-lg text-sm transform transition-all duration-500 animate__animated animate__fadeInRight ${
                type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' :
                type === 'error' ? 'bg-red-50 text-red-800 border border-red-200' :
                'bg-blue-50 text-blue-800 border border-blue-200'
            }`;
            
            const icon = type === 'success' ? 'fa-check-circle' : 
                        type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
            
            toast.innerHTML = `
                <i class="fas ${icon} mr-3 text-lg"></i>
                <span class="flex-1">${message}</span>
                <button onclick="this.parentElement.remove()" class="ml-3 text-gray-500 hover:text-gray-700 hover:scale-110 transition-transform">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            document.getElementById('toastContainer').appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('animate__fadeOutRight');
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }

        // Show Loading
        function showLoading() {
            document.getElementById('loadingOverlay').classList.remove('hidden');
            document.getElementById('loadingOverlay').classList.add('flex');
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').classList.add('hidden');
            document.getElementById('loadingOverlay').classList.remove('flex');
        }

        // Auto refresh data every 5 minutes
        setInterval(() => {
            showLoading();
            setTimeout(() => {
                location.reload();
            }, 1000);
        }, 300000);
    </script>

    <?php if(isset($_SESSION['success'])): ?>
    <script>
        showToast('<?php echo addslashes($_SESSION['success']); ?>', 'success');
    </script>
    <?php unset($_SESSION['success']); endif; ?>

    <?php if(isset($_SESSION['error'])): ?>
    <script>
        showToast('<?php echo addslashes($_SESSION['error']); ?>', 'error');
    </script>
    <?php unset($_SESSION['error']); endif; ?>
</body>
</html>