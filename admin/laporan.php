<?php
session_start();
require_once('../config/config.php');
checkLevel([1, 2]); // Admin dan Petugas

$is_admin = $_SESSION['id_level'] == 1;

// Get filter dengan validasi
$tanggal_dari   = isset($_GET['dari']) ? mysqli_real_escape_string($conn, $_GET['dari']) : date('Y-m-01');
$tanggal_sampai = isset($_GET['sampai']) ? mysqli_real_escape_string($conn, $_GET['sampai']) : date('Y-m-d');
$status_filter  = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'all';

// Validasi tanggal
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_dari)) $tanggal_dari = date('Y-m-01');
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_sampai)) $tanggal_sampai = date('Y-m-d');

// Build query with proper escaping
$where = "WHERE l.tgl_lelang BETWEEN '$tanggal_dari' AND '$tanggal_sampai'";
if($status_filter !== 'all') {
    $status_filter = mysqli_real_escape_string($conn, $status_filter);
    $where .= " AND l.status = '$status_filter'";
}

$query = "SELECT l.*, b.nama_barang, b.harga_awal, u.nama_lengkap as pemenang
          FROM tb_lelang l
          JOIN tb_barang b ON l.id_barang = b.id_barang
          LEFT JOIN tb_user u ON l.id_user = u.id_user
          $where
          ORDER BY l.tgl_lelang DESC";

$laporan = mysqli_query($conn, $query);

if(!$laporan) {
    die("Query Error: " . mysqli_error($conn));
}

// Calculate statistics
$rows_data = [];
$total_lelang  = 0;
$total_nilai   = 0;
$lelang_selesai = 0;
$lelang_aktif  = 0;

while($r = mysqli_fetch_assoc($laporan)) {
    $rows_data[] = $r;
    $total_lelang++;
    if($r['status'] == 'ditutup') {
        $lelang_selesai++;
        $total_nilai += floatval($r['harga_akhir'] ?? 0);
    } else {
        $lelang_aktif++;
    }
}

// Helper functions
if(!function_exists('formatRupiah')) {
    function formatRupiah($angka) {
        if($angka === null || $angka === '') return 'Rp 0';
        return 'Rp ' . number_format(floatval($angka), 0, ',', '.');
    }
}
if(!function_exists('formatTanggal')) {
    function formatTanggal($tanggal) {
        if($tanggal === null || $tanggal === '' || $tanggal == '0000-00-00') return '-';
        return date('d/m/Y', strtotime($tanggal));
    }
}
if(!function_exists('formatTanggalWaktu')) {
    function formatTanggalWaktu($tanggal) {
        if($tanggal === null || $tanggal === '' || $tanggal == '0000-00-00 00:00:00') return '-';
        return date('d/m/Y H:i', strtotime($tanggal));
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Lelang - Sistem Lelang Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        
        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
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
        }
        
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
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
        
        .glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(65, 110, 180, 0.15);
            transition: all 0.3s ease;
        }
        
        .stat-card {
            background: white;
            border-radius: 24px;
            padding: 1.5rem;
            box-shadow: 0 10px 25px -5px rgba(30, 58, 102, 0.08);
            border: 1px solid var(--primary-100);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
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
        
        .stat-card:hover::before {
            transform: scaleX(1);
        }
        
        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 25px 35px -8px rgba(30, 58, 102, 0.2);
        }
        
        .modern-table {
            border-collapse: separate;
            border-spacing: 0 8px;
            width: 100%;
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
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .modern-table tbody tr:hover {
            transform: scale(1.01) translateY(-2px);
            box-shadow: 0 20px 25px -8px rgba(30, 58, 102, 0.15);
            background: linear-gradient(90deg, white, var(--primary-50));
        }
        
        .modern-table th {
            padding: 1rem 1.5rem;
            text-align: left;
            color: var(--primary-700);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .modern-table td {
            padding: 1rem 1.5rem;
            color: var(--secondary-700);
        }
        
        .modern-table td:first-child { border-radius: 18px 0 0 18px; }
        .modern-table td:last-child { border-radius: 0 18px 18px 0; }
        
        .badge {
            padding: 6px 14px;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .badge:hover { transform: scale(1.05); filter: brightness(1.1); }
        .badge-success { background: #e6f7e6; color: #0a5e0a; border: 1px solid #a3e0a3; }
        .badge-info { background: #e6f3ff; color: #0a5e8c; border: 1px solid #a3d0ff; }
        .badge-warning { background: #fff3e0; color: #b45b0a; border: 1px solid #ffd7a3; }
        
        .btn-primary {
            background: var(--primary-600);
            color: white;
            padding: 0.65rem 1.5rem;
            border-radius: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            border: none;
            cursor: pointer;
            text-decoration: none;
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
        
        .btn-primary:hover::before { left: 100%; }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px -8px rgba(30, 58, 102, 0.4);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-200);
            color: var(--primary-700);
            padding: 0.65rem 1.5rem;
            border-radius: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            text-decoration: none;
        }
        
        .btn-outline:hover {
            background: var(--primary-50);
            border-color: var(--primary-400);
            transform: translateY(-2px);
        }
        
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        
        .slide-in-left { animation: slideInLeft 0.5s ease-out; }
        
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .slide-in-right { animation: slideInRight 0.5s ease-out; }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .fade-in-up { animation: fadeInUp 0.6s ease-out; }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .progress-bar {
            width: 0;
            animation: progressFill 1.5s ease-out forwards;
        }
        
        @keyframes progressFill {
            to { width: var(--target-width); }
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--primary-200);
            border-top-color: var(--primary-600);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .particle {
            position: fixed;
            width: 4px;
            height: 4px;
            background: var(--primary-300);
            border-radius: 50%;
            opacity: 0.3;
            animation: particleFloat 15s infinite linear;
        }
        
        @keyframes particleFloat {
            from { transform: translateY(100vh) rotate(0deg); }
            to { transform: translateY(-100vh) rotate(360deg); }
        }
        
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--primary-50); border-radius: 6px; }
        ::-webkit-scrollbar-thumb { background: var(--primary-300); border-radius: 6px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary-400); }
        
        .page-banner {
            background: linear-gradient(135deg, var(--primary-700) 0%, var(--primary-600) 50%, var(--primary-500) 100%);
            border-radius: 28px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .page-banner::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 220px;
            height: 220px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
        }
        
        .page-banner::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: 30%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.04);
            border-radius: 50%;
        }
        
        .filter-input {
            border: 2px solid var(--primary-100);
            border-radius: 14px;
            padding: 0.65rem 1rem;
            width: 100%;
            font-size: 0.9rem;
            background: white;
            color: var(--secondary-800);
            transition: all 0.3s ease;
        }
        
        .filter-input:focus {
            outline: none;
            border-color: var(--primary-400);
            box-shadow: 0 0 0 4px rgba(65,110,180,0.1);
        }
        
        .counter {
            animation: countUp 2s ease-out;
        }
        
        @keyframes countUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .hover-glow {
            transition: all 0.3s ease;
        }
        
        .hover-glow:hover {
            box-shadow: 0 0 20px rgba(65, 110, 180, 0.3);
            transform: translateY(-2px);
        }
        
        .scale-in { animation: scaleIn 0.4s ease-out; }
        
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: inherit;
        }
        
        .profile-image-wrapper {
            position: relative;
            overflow: hidden;
            border-radius: inherit;
        }
        
        @media print {
            body { background: white; }
            aside, nav, .no-print, .particle { display: none !important; }
            main { padding: 0 !important; width: 100% !important; margin: 0 !important; }
            .stat-card, .page-banner { box-shadow: none; border: 1px solid #ccc; }
            .modern-table tbody tr { box-shadow: none; border: 1px solid #eee; animation: none; }
        }
    </style>
</head>
<body class="antialiased">
    <!-- Particles -->
    <div id="particles" class="fixed inset-0 pointer-events-none z-0"></div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
        <div class="bg-white rounded-2xl p-8 flex flex-col items-center scale-in">
            <div class="spinner mb-4"></div>
            <p class="text-primary-600 font-medium">Memuat data...</p>
        </div>
    </div>

    <!-- Toast -->
    <div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>

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
                            <p class="text-sm font-semibold text-primary-800"><?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User'); ?></p>
                            <p class="text-xs text-primary-500 flex items-center justify-end">
                                <i class="fas fa-circle text-green-500 text-[6px] mr-1 animate-pulse"></i>
                                <?php echo $is_admin ? 'Administrator' : 'Petugas'; ?>
                            </p>
                        </div>
                        <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center text-white font-bold shadow-md ring-2 ring-primary-100 hover:scale-110 transition-transform">
                            <?php echo strtoupper(substr($_SESSION['nama_lengkap'] ?? 'U', 0, 1)); ?>
                        </div>
                    </div>
                    
                    <a href="../auth/logout.php" onclick="return confirm('Yakin ingin keluar?')" class="btn-outline text-sm py-2 px-4 flex items-center space-x-2">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="hidden sm:inline">Keluar</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex relative z-10">
        <!-- Sidebar -->
        <aside class="w-72 bg-white border-r border-primary-100 min-h-screen shadow-xl relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 gradient-bg"></div>
            <div class="p-6 relative z-10">
                <!-- Profile Card -->
                <div class="gradient-bg rounded-2xl p-6 mb-6 text-white shadow-xl relative overflow-hidden group" data-aos="fade-right">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-white opacity-5 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-700"></div>
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-5 rounded-full -ml-8 -mb-8 group-hover:scale-150 transition-transform duration-700"></div>
                    
                    <div class="flex items-center space-x-4 relative z-10">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center text-2xl font-bold border-2 border-white/30 group-hover:scale-110 transition-transform">
                            <?php echo strtoupper(substr($_SESSION['nama_lengkap'] ?? 'U', 0, 1)); ?>
                        </div>
                        <div>
                            <p class="text-white/80 text-sm font-light">Selamat datang,</p>
                            <p class="text-xl font-bold group-hover:translate-x-1 transition-transform"><?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User'); ?></p>
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
                    </a>
                    <a href="buat_barang.php" class="flex items-center px-4 py-3 bg-primary-50 text-primary-700 rounded-xl font-medium group relative overflow-hidden">
                        <i class="fas fa-plus w-6 text-primary-600"></i>
                        <span class="ml-3">Buat Barang</span>
                    </a>
                    <a href="laporan.php" class="flex items-center px-4 py-3 text-primary-700 hover:bg-primary-50 rounded-xl transition-all duration-200 group relative overflow-hidden">
                        <i class="fas fa-chart-bar w-6 text-primary-400 group-hover:text-primary-600 group-hover:scale-110 transition-transform"></i>
                        <span class="ml-3 group-hover:translate-x-1 transition-transform">Laporan</span>
                        <i class="fas fa-chevron-right ml-auto text-sm text-primary-600"></i>
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
        <main class="flex-1 p-8" style="background: var(--secondary-50)">
            <!-- Breadcrumb -->
            <div class="flex items-center text-sm mb-4 slide-in-left no-print" style="color: var(--primary-500)">
                <a href="dashboard.php" class="transition" style="color: var(--primary-500)" onmouseover="this.style.color='var(--primary-700)'" onmouseout="this.style.color='var(--primary-500)'">
                    <i class="fas fa-home mr-1"></i>Beranda
                </a>
                <i class="fas fa-chevron-right mx-3 text-xs" style="color: var(--primary-300)"></i>
                <span class="font-medium" style="color: var(--primary-800)">Laporan Lelang</span>
            </div>

            <!-- Page Banner -->
            <div class="page-banner no-print" data-aos="fade-down">
                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-chart-bar text-white text-2xl"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">Laporan Lelang</h1>
                            <p class="text-white/70 text-sm mt-1">Laporan data lelang periode tertentu</p>
                        </div>
                    </div>
                    <div class="mt-4 md:mt-0 flex gap-3">
                        <button onclick="window.print()" class="flex items-center px-5 py-3 rounded-2xl font-semibold text-sm transition-all"
                                style="background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3)"
                                onmouseover="this.style.background='rgba(255,255,255,0.25)'"
                                onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                            <i class="fas fa-print mr-2"></i>Cetak
                        </button>
                        <a href="#" onclick="alert('Fitur export Excel akan segera tersedia')" 
                           class="flex items-center px-5 py-3 rounded-2xl font-semibold text-sm transition-all"
                           style="background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3)"
                           onmouseover="this.style.background='rgba(255,255,255,0.25)'"
                           onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                            <i class="fas fa-file-excel mr-2"></i>Export Excel
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <?php
                $pct_selesai = $total_lelang > 0 ? round($lelang_selesai/$total_lelang*100) : 0;
                $pct_aktif   = $total_lelang > 0 ? round($lelang_aktif/$total_lelang*100) : 0;
                $stat_cards = [
                    ['label' => 'Total Lelang', 'value' => $total_lelang, 'icon' => 'fa-gavel', 'color' => 'var(--primary-600)', 'bg' => 'var(--primary-50)', 'pct' => 100, 'delay' => 0, 'sub' => 'Periode ini'],
                    ['label' => 'Lelang Aktif', 'value' => $lelang_aktif, 'icon' => 'fa-play-circle', 'color' => '#0891b2', 'bg' => '#ecfeff', 'pct' => $pct_aktif, 'delay' => 100, 'sub' => $pct_aktif.'% dari total'],
                    ['label' => 'Lelang Selesai', 'value' => $lelang_selesai, 'icon' => 'fa-check-circle', 'color' => '#16a34a', 'bg' => '#f0fdf4', 'pct' => $pct_selesai, 'delay' => 200, 'sub' => $pct_selesai.'% dari total'],
                ];
                foreach($stat_cards as $sc):
                ?>
                <div class="stat-card" data-aos="fade-up" data-aos-delay="<?php echo $sc['delay']; ?>">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm font-medium" style="color: var(--secondary-500)"><?php echo $sc['label']; ?></p>
                            <p class="text-3xl font-bold mt-1 counter" style="color: var(--primary-800)" data-target="<?php echo $sc['value']; ?>">0</p>
                            <p class="text-xs mt-1" style="color: var(--secondary-400)"><?php echo $sc['sub']; ?></p>
                        </div>
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background: <?php echo $sc['bg']; ?>">
                            <i class="fas <?php echo $sc['icon']; ?> text-2xl" style="color: <?php echo $sc['color']; ?>"></i>
                        </div>
                    </div>
                    <div class="h-1.5 rounded-full overflow-hidden" style="background: var(--primary-100)">
                        <div class="progress-bar h-full rounded-full" style="background: <?php echo $sc['color']; ?>; --target-width: <?php echo $sc['pct']; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Total Nilai Banner -->
            <div class="rounded-2xl p-6 mb-6 flex items-center justify-between hover-glow" style="background: var(--primary-50); border: 1px solid var(--primary-100)" data-aos="fade-up">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: var(--primary-100)">
                        <i class="fas fa-coins text-xl" style="color: var(--primary-600)"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium" style="color: var(--primary-500)">Total Nilai Transaksi Periode Ini</p>
                        <p class="text-2xl font-bold" style="color: var(--primary-800)"><?php echo formatRupiah($total_nilai); ?></p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm" style="color: var(--secondary-400)"><?php echo formatTanggal($tanggal_dari); ?> — <?php echo formatTanggal($tanggal_sampai); ?></p>
                    <p class="text-xs mt-1" style="color: var(--secondary-400)">Dari <?php echo $lelang_selesai; ?> lelang selesai</p>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="bg-white rounded-2xl p-6 border mb-6 no-print hover-glow" style="border-color: var(--primary-100)" data-aos="fade-up">
                <form method="GET" class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-semibold mb-2" style="color: var(--primary-700)">Tanggal Dari</label>
                        <input type="date" name="dari" value="<?php echo $tanggal_dari; ?>" class="filter-input">
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-semibold mb-2" style="color: var(--primary-700)">Tanggal Sampai</label>
                        <input type="date" name="sampai" value="<?php echo $tanggal_sampai; ?>" class="filter-input">
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-semibold mb-2" style="color: var(--primary-700)">Keterangan</label>
                        <select name="status" class="filter-input">
                            <option value="all" <?php echo $status_filter=='all' ? 'selected' : ''; ?>>Semua Status</option>
                            <option value="dibuka" <?php echo $status_filter=='dibuka' ? 'selected' : ''; ?>>Dibuka</option>
                            <option value="ditutup" <?php echo $status_filter=='ditutup' ? 'selected' : ''; ?>>Selesai</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-3">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                        <a href="laporan.php" class="btn-outline">
                            <i class="fas fa-sync-alt mr-2"></i>Perbarui
                        </a>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl border overflow-hidden hover-glow" style="border-color: var(--primary-100)" data-aos="fade-up" data-aos-delay="100">
                <div class="p-6 flex items-center justify-between" style="border-bottom: 1px solid var(--primary-100)">
                    <div>
                        <h2 class="text-xl font-bold" style="color: var(--primary-800)">
                            <i class="fas fa-calendar-alt mr-2" style="color: var(--primary-500)"></i>
                            Data Lelang Periode <?php echo formatTanggal($tanggal_dari); ?> – <?php echo formatTanggal($tanggal_sampai); ?>
                        </h2>
                        <p class="text-sm mt-1" style="color: var(--secondary-400)"><?php echo count($rows_data); ?> data ditemukan</p>
                    </div>
                </div>

                <div class="overflow-x-auto p-4">
                    <table class="w-full modern-table">
                        <thead>
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">No</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Tanggal</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Nama Barang</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Harga Awal</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Harga Akhir</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Pemenang</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($rows_data) > 0): ?>
                                <?php foreach($rows_data as $i => $row): ?>
                                <tr class="bg-white" style="animation-delay: <?php echo $i * 0.05; ?>s">
                                    <td class="px-6 py-4">
                                        <span class="font-bold text-sm" style="color: var(--primary-600)"><?php echo $i+1; ?></span>
                                    </td>
                                    <td class="px-6 py-4" style="color: var(--secondary-500)"><?php echo formatTanggal($row['tgl_lelang']); ?></td>
                                    <td class="px-6 py-4">
                                        <span class="font-semibold text-sm" style="color: var(--primary-800)"><?php echo htmlspecialchars($row['nama_barang']); ?></span>
                                    </td>
                                    <td class="px-6 py-4" style="color: var(--secondary-500)"><?php echo formatRupiah($row['harga_awal']); ?></td>
                                    <td class="px-6 py-4">
                                        <span class="font-bold text-sm" style="color: var(--primary-600)"><?php echo formatRupiah($row['harga_akhir']); ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if(!empty($row['pemenang'])): ?>
                                        <div class="flex items-center space-x-2">
                                            <div class="w-8 h-8 rounded-lg gradient-bg flex items-center justify-center text-white text-xs font-bold">
                                                <?php echo strtoupper(substr($row['pemenang'], 0, 1)); ?>
                                            </div>
                                            <span class="text-sm" style="color: var(--secondary-700)"><?php echo htmlspecialchars($row['pemenang']); ?></span>
                                        </div>
                                        <?php else: ?>
                                            <span style="color: var(--secondary-400)">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if($row['status'] == 'dibuka'): ?>
                                            <span class="badge badge-info">
                                                <i class="fas fa-circle text-[6px] mr-1 animate-pulse"></i>Dibuka
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-check-circle mr-1"></i>Selesai
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-20 h-20 bg-primary-50 rounded-full flex items-center justify-center mb-4 animate-float">
                                                <i class="fas fa-file-alt text-3xl" style="color: var(--primary-300)"></i>
                                            </div>
                                            <p class="font-semibold text-lg" style="color: var(--primary-600)">Tidak ada data laporan</p>
                                            <p class="text-sm mt-2" style="color: var(--secondary-400)">Tidak ada lelang pada periode ini</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer Note -->
            <div class="mt-6 text-center text-sm no-print" style="color: var(--secondary-400)">
                <i class="fas fa-print mr-1"></i>
                Gunakan tombol Cetak untuk mencetak laporan • Dihasilkan: <?php echo date('d/m/Y H:i'); ?> WIB
            </div>
        </main>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 700, once: false, mirror: true, offset: 40, easing: 'ease-out-cubic' });

        // Particles
        (function() {
            const c = document.getElementById('particles');
            if(!c) return;
            for(let i = 0; i < 20; i++) {
                const p = document.createElement('div');
                p.className = 'particle';
                p.style.left = Math.random()*100+'%';
                p.style.animationDuration = (Math.random()*10+10)+'s';
                p.style.animationDelay = Math.random()*5+'s';
                const s = (Math.random()*6+2)+'px';
                p.style.width = s; p.style.height = s;
                c.appendChild(p);
            }
        })();

        // Counter animation
        document.querySelectorAll('.counter').forEach(el => {
            const target = parseInt(el.dataset.target) || 0;
            let start = 0;
            const duration = 1500;
            const step = timestamp => {
                if(!start) start = timestamp;
                const progress = Math.min((timestamp - start) / duration, 1);
                el.textContent = Math.floor(progress * target);
                if(progress < 1) requestAnimationFrame(step);
                else el.textContent = target;
            };
            requestAnimationFrame(step);
        });

        // Progress bars
        document.querySelectorAll('.progress-bar').forEach(bar => {
            const target = bar.style.getPropertyValue('--target-width');
            bar.style.width = target;
        });

        // Toast
        function showToast(message, type = 'success') {
            const colors = {
                success: 'bg-green-50 text-green-800 border-green-200',
                error: 'bg-red-50 text-red-800 border-red-200',
                info: 'bg-blue-50 text-blue-800 border-blue-200'
            };
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                info: 'fa-info-circle'
            };
            const toast = document.createElement('div');
            toast.className = `flex items-center p-4 rounded-xl shadow-lg text-sm border animate__animated animate__fadeInRight ${colors[type]}`;
            toast.innerHTML = `<i class="fas ${icons[type]} mr-3 text-lg"></i><span class="flex-1">${message}</span><button onclick="this.parentElement.remove()" class="ml-3 text-gray-500 hover:text-gray-700 hover:scale-110 transition-transform"><i class="fas fa-times"></i></button>`;
            document.getElementById('toastContainer').appendChild(toast);
            setTimeout(() => { toast.classList.add('animate__fadeOutRight'); setTimeout(() => toast.remove(), 500); }, 3000);
        }

        // Loading functions
        function showLoading() {
            document.getElementById('loadingOverlay').classList.remove('hidden');
            document.getElementById('loadingOverlay').classList.add('flex');
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').classList.add('hidden');
            document.getElementById('loadingOverlay').classList.remove('flex');
        }

        // Print optimization
        window.onbeforeprint = () => document.querySelectorAll('.particle').forEach(el => el.style.display = 'none');
        window.onafterprint  = () => document.querySelectorAll('.particle').forEach(el => el.style.display = '');

        <?php if(isset($_SESSION['success'])): ?>
        showToast('<?php echo addslashes($_SESSION['success']); ?>', 'success');
        <?php unset($_SESSION['success']); endif; ?>
        <?php if(isset($_SESSION['error'])): ?>
        showToast('<?php echo addslashes($_SESSION['error']); ?>', 'error');
        <?php unset($_SESSION['error']); endif; ?>
    </script>
</body>
</html>