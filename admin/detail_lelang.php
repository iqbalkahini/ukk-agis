<?php
session_start();
require_once('../config/config.php');

// Debug: Check if user is logged in
if(!isset($_SESSION['id_user'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Check level access
checkLevel([1, 2]); // Admin dan petugas

$is_admin = $_SESSION['id_level'] == 1;

// Get lelang ID from URL
$id_lelang = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Debug: Check if ID is valid
if($id_lelang == 0) {
    $_SESSION['error'] = "ID lelang tidak valid";
    header('Location: total_lelang.php');
    exit;
}

// Get user data (tanpa foto_profil karena kolom tidak ada)
$user_id = $_SESSION['id_user'];
// Tidak perlu query foto_profil karena kolom tidak ada di database

// Get lelang details
// Cek apakah kolom id_petugas ada di tabel tb_lelang
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM tb_lelang LIKE 'id_petugas'");
$has_petugas = mysqli_num_rows($check_col) > 0;

if($has_petugas) {
    $query_lelang = "SELECT l.*, b.nama_barang, b.deskripsi_barang, b.harga_awal, b.gambar, 
                            b.tgl as tanggal_input_barang, b.status_barang, 
                            u.nama_lengkap as pemenang, 
                            p.nama_lengkap as petugas_nama,
                            p.username as petugas_username
                     FROM tb_lelang l 
                     JOIN tb_barang b ON l.id_barang = b.id_barang
                     LEFT JOIN tb_user u ON l.id_user = u.id_user
                     LEFT JOIN tb_user p ON l.id_petugas = p.id_user
                     WHERE l.id_lelang = $id_lelang";
} else {
    $query_lelang = "SELECT l.*, b.nama_barang, b.deskripsi_barang, b.harga_awal, b.gambar, 
                            b.tgl as tanggal_input_barang, b.status_barang, 
                            u.nama_lengkap as pemenang,
                            NULL as petugas_nama,
                            NULL as petugas_username
                     FROM tb_lelang l 
                     JOIN tb_barang b ON l.id_barang = b.id_barang
                     LEFT JOIN tb_user u ON l.id_user = u.id_user
                     WHERE l.id_lelang = $id_lelang";
}

$result_lelang = mysqli_query($conn, $query_lelang);

if(!$result_lelang) {
    die("Query Error: " . mysqli_error($conn));
}

$lelang = mysqli_fetch_assoc($result_lelang);

if(!$lelang) {
    $_SESSION['error'] = "Data lelang dengan ID $id_lelang tidak ditemukan";
    header('Location: total_lelang.php');
    exit;
}

// Cek apakah tabel history ada
$history_available = false;
$history = null;
$history_error = "";

// Coba cek tabel history_lelang
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'tb_history_lelang'");
if(mysqli_num_rows($check_table) > 0) {
    // Tabel tb_history_lelang ada
    $history = mysqli_query($conn, "SELECT h.*, u.nama_lengkap
                                    FROM tb_history_lelang h
                                    JOIN tb_user u ON h.id_user = u.id_user
                                    WHERE h.id_lelang = $id_lelang
                                    ORDER BY h.penawaran_harga DESC, h.created_at DESC");
    
    if($history) {
        $history_available = true;
    } else {
        $history_error = mysqli_error($conn);
    }
} else {
    // Coba cek tabel history
    $check_table2 = mysqli_query($conn, "SHOW TABLES LIKE 'history_lelang'");
    if(mysqli_num_rows($check_table2) > 0) {
        $history = mysqli_query($conn, "SELECT h.*, u.nama_lengkap
                                        FROM history_lelang h
                                        JOIN tb_user u ON h.id_user = u.id_user
                                        WHERE h.id_lelang = $id_lelang
                                        ORDER BY h.penawaran_harga DESC, h.created_at DESC");
        if($history) {
            $history_available = true;
        } else {
            $history_error = mysqli_error($conn);
        }
    } else {
        // Coba cek di tb_lelang apakah ada kolom history
        $history_error = "Tabel history tidak ditemukan. Sistem mungkin menyimpan penawaran di tempat lain.";
    }
}

// Get payment information if lelang is closed
$pembayaran = null;
if($lelang['status'] == 'ditutup' && $lelang['id_user']) {
    $pembayaran = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tb_pembayaran WHERE id_lelang = $id_lelang"));
}

// Define helper functions if they don't exist in config
if(!function_exists('formatRupiah')) {
    function formatRupiah($angka) {
        if($angka === null || $angka === '') return 'Rp 0';
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }
}

if(!function_exists('formatTanggal')) {
    function formatTanggal($tanggal) {
        if($tanggal === null || $tanggal === '') return '-';
        return date('d/m/Y', strtotime($tanggal));
    }
}

if(!function_exists('formatTanggalWaktu')) {
    function formatTanggalWaktu($tanggal) {
        if($tanggal === null || $tanggal === '') return '-';
        return date('d/m/Y H:i', strtotime($tanggal));
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Lelang - <?php echo htmlspecialchars($lelang['nama_barang']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        
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
        
        /* Gradient Background with Animation */
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
        
        /* Glassmorphism with Animation */
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
        
        /* Modern Table with Row Animation */
        .modern-table {
            border-collapse: separate;
            border-spacing: 0 8px;
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
        
        /* Pulse Animation for Badges */
        .animate-pulse-soft {
            animation: pulseSoft 2s infinite;
        }
        
        @keyframes pulseSoft {
            0%, 100% { 
                opacity: 1;
                transform: scale(1);
            }
            50% { 
                opacity: 0.7;
                transform: scale(1.05);
            }
        }
        
        /* Floating Animation */
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        
        /* Shimmer Effect */
        .shimmer {
            position: relative;
            overflow: hidden;
        }
        
        .shimmer::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 50%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            100% { left: 200%; }
        }
        
        /* Slide In Animation */
        .slide-in-left {
            animation: slideInLeft 0.5s ease-out;
        }
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .slide-in-right {
            animation: slideInRight 0.5s ease-out;
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Fade In Up */
        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Scale In */
        .scale-in {
            animation: scaleIn 0.4s ease-out;
        }
        
        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        /* Badge Styles */
        .badge {
            padding: 4px 12px;
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
        
        .badge-danger {
            background: #ffe6e6;
            color: #a12323;
            border: 1px solid #ffb8b8;
        }
        
        .badge-warning {
            background: #fff3e0;
            color: #b45b0a;
            border: 1px solid #ffd7a3;
        }
        
        .badge-info {
            background: #e6f3ff;
            color: #0a5e8c;
            border: 1px solid #a3d0ff;
        }
        
        /* Button Styles with Animation */
        .btn-primary {
            background: var(--primary-600);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
            display: inline-flex;
            align-items: center;
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
            transform: translateY(-2px);
            box-shadow: 0 12px 20px -8px rgba(30, 58, 102, 0.4);
        }
        
        .btn-outline-primary {
            background: transparent;
            border: 2px solid var(--primary-200);
            color: var(--primary-700);
            padding: 0.5rem 1.5rem;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-50);
            border-color: var(--primary-400);
            transform: translateY(-2px);
            box-shadow: 0 8px 15px -6px rgba(30, 58, 102, 0.2);
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
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
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
            transform: scale(1.1);
        }
        
        /* Particle Background */
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
            from {
                transform: translateY(100vh) rotate(0deg);
            }
            to {
                transform: translateY(-100vh) rotate(360deg);
            }
        }
        
        /* Hover Glow */
        .hover-glow {
            transition: all 0.3s ease;
        }
        
        .hover-glow:hover {
            box-shadow: 0 0 20px rgba(65, 110, 180, 0.3);
            transform: translateY(-2px);
        }
        
        /* Profile Image Styles */
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
        
        /* Detail Card */
        .detail-card {
            background: white;
            border-radius: 24px;
            padding: 1.5rem;
            box-shadow: 0 10px 25px -5px rgba(30, 58, 102, 0.08);
            border: 1px solid var(--primary-100);
            transition: all 0.3s ease;
        }
        
        .detail-card:hover {
            box-shadow: 0 20px 30px -8px rgba(30, 58, 102, 0.15);
        }
        
        /* Image Container */
        .image-container {
            width: 100%;
            height: 280px;
            background: var(--primary-50);
            border-radius: 20px;
            overflow: hidden;
            position: relative;
        }
        
        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .image-container:hover img {
            transform: scale(1.05);
        }
        
        /* Status Banner */
        .status-banner {
            border-radius: 16px;
            padding: 1rem 1.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .status-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1), rgba(255,255,255,0));
            pointer-events: none;
        }
        
        /* Info Item */
        .info-item {
            display: flex;
            align-items: flex-start;
            padding: 0.75rem 0;
            border-bottom: 1px dashed var(--primary-100);
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-icon {
            width: 36px;
            height: 36px;
            background: var(--primary-50);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-600);
            margin-right: 1rem;
            flex-shrink: 0;
        }
        
        /* Winner Card */
        .winner-card {
            background: linear-gradient(135deg, #fff9e6, #fff);
            border: 1px solid #ffd966;
            border-radius: 20px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .winner-card::before {
            content: '\f091';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            bottom: -10px;
            right: -10px;
            font-size: 80px;
            color: rgba(255, 193, 7, 0.1);
            transform: rotate(15deg);
        }
        
        /* Table Styles */
        .table-container {
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid var(--primary-100);
        }
        
        .table-header {
            background: var(--primary-50);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--primary-200);
        }
        
        .table-header h3 {
            color: var(--primary-800);
            font-weight: 700;
        }
        
        .table-wrapper {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background: var(--primary-50);
            color: var(--primary-700);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1rem 1.5rem;
            text-align: left;
            border-bottom: 1px solid var(--primary-200);
        }
        
        .data-table td {
            padding: 1rem 1.5rem;
            color: var(--secondary-700);
            border-bottom: 1px solid var(--primary-100);
            transition: all 0.3s ease;
        }
        
        .data-table tbody tr:hover td {
            background: var(--primary-50);
        }
        
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Breadcrumb */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-500);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .breadcrumb a {
            color: var(--primary-600);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .breadcrumb a:hover {
            color: var(--primary-800);
            transform: translateX(2px);
        }
        
        .breadcrumb i {
            font-size: 0.7rem;
            color: var(--primary-400);
        }
    </style>
</head>
<body class="antialiased text-gray-800">
    <!-- Particle Background -->
    <div id="particles"></div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
        <div class="bg-white rounded-2xl p-8 flex flex-col items-center scale-in">
            <div class="spinner mb-4"></div>
            <p class="text-primary-600 font-medium">Memuat data...</p>
        </div>
    </div>

    <!-- Toast Notification -->
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
                    
                    
                    <!-- User Menu without Profile Photo -->
                    <div class="flex items-center space-x-3 ml-2 pl-2 border-l-2 border-primary-100">
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-semibold text-primary-800"><?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User'); ?></p>
                            <p class="text-xs text-primary-500 flex items-center justify-end">
                                <i class="fas fa-circle text-green-500 text-[6px] mr-1 animate-pulse"></i>
                                <?php echo $is_admin ? 'Administrator' : 'Petugas'; ?>
                            </p>
                        </div>
                        
                        <!-- User Avatar (Initial only) -->
                        <div class="relative group">
                            <div class="w-10 h-10 rounded-xl overflow-hidden ring-2 ring-primary-100 hover:ring-primary-300 transition-all shadow-md">
                                <div class="w-full h-full gradient-bg flex items-center justify-center text-white font-bold text-lg">
                                    <?php echo strtoupper(substr($_SESSION['nama_lengkap'] ?? 'U', 0, 1)); ?>
                                </div>
                            </div>
                        </div>
                        
                        <a href="../auth/logout.php" onclick="return confirm('Yakin ingin keluar?')" class="relative overflow-hidden group text-sm py-2 px-4 flex items-center space-x-2 border-2 border-primary-200 text-primary-700 rounded-xl hover:bg-primary-600 hover:text-white hover:border-primary-600 transition-all">
                            <i class="fas fa-sign-out-alt"></i>
                            <span class="hidden sm:inline">Keluar</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex relative">
        <!-- Sidebar -->
        <aside class="w-72 bg-white border-r border-primary-100 min-h-screen shadow-xl relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 gradient-bg"></div>
            <div class="p-6 relative z-10">
                <!-- Profile Card without Photo -->
                <div class="gradient-bg rounded-2xl p-6 mb-6 text-white shadow-xl relative overflow-hidden group" data-aos="fade-right">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-white opacity-5 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-700"></div>
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-5 rounded-full -ml-8 -mb-8 group-hover:scale-150 transition-transform duration-700"></div>
                    
                    <div class="flex items-center space-x-4 relative z-10">
                        <!-- User Avatar (Initial only) -->
                        <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center text-2xl font-bold border-2 border-white/30 group-hover:scale-110 transition-transform overflow-hidden">
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
                    <p class="text-xs uppercase tracking-wider text-primary-300 mb-4 px-4 font-semibold slide-in-left">Menu Utama</p>
                    
                    <a href="dashboard.php" class="flex items-center px-4 py-3 text-primary-700 hover:bg-primary-50 rounded-xl transition-all duration-200 group">
                        <i class="fas fa-home w-6 text-primary-400 group-hover:text-primary-600"></i>
                        <span class="ml-3">Beranda</span>
                        <i class="fas fa-arrow-right ml-auto opacity-0 group-hover:opacity-100 transition-all text-primary-400 group-hover:translate-x-1"></i>
                    </a>
                    
                    <div class="pt-4">
                        <p class="text-xs uppercase tracking-wider text-primary-300 mb-2 px-4 font-semibold slide-in-left">Manajemen Barang</p>
                        <a href="total_barang.php" class="flex items-center px-4 py-3 text-primary-700 hover:bg-primary-50 rounded-xl transition-all duration-200 group">
                            <i class="fas fa-boxes w-6 text-primary-400 group-hover:text-primary-600"></i>
                            <span class="ml-3">Total Barang</span>
                            <i class="fas fa-arrow-right ml-auto opacity-0 group-hover:opacity-100 transition-all text-primary-400 group-hover:translate-x-1"></i>
                        </a>
                        <a href="data_barang.php" class="flex items-center px-4 py-3 text-primary-700 hover:bg-primary-50 rounded-xl transition-all duration-200 group mt-1">
                            <i class="fas fa-box w-6 text-primary-400 group-hover:text-primary-600"></i>
                            <span class="ml-3">Data Barang</span>
                            <i class="fas fa-arrow-right ml-auto opacity-0 group-hover:opacity-100 transition-all text-primary-400 group-hover:translate-x-1"></i>
                        </a>
                    </div>
                    
                    <div class="pt-4">
                        <p class="text-xs uppercase tracking-wider text-primary-300 mb-2 px-4 font-semibold slide-in-left">Manajemen Lelang</p>
                        <a href="total_lelang.php" class="flex items-center px-4 py-3 text-primary-700 hover:bg-primary-50 rounded-xl transition-all duration-200 group">
                            <i class="fas fa-gavel w-6 text-primary-400 group-hover:text-primary-600"></i>
                            <span class="ml-3">Total Lelang</span>
                            <i class="fas fa-arrow-right ml-auto opacity-0 group-hover:opacity-100 transition-all text-primary-400 group-hover:translate-x-1"></i>
                        </a>
                        <a href="detail_lelang.php?id=<?php echo $id_lelang; ?>" class="flex items-center px-4 py-3 gradient-bg text-white rounded-xl shadow-md transition-all duration-200 group mt-1">
                            <i class="fas fa-clipboard-list w-6 text-white"></i>
                            <span class="ml-3 font-semibold">Detail Lelang</span>
                            <span class="ml-auto text-xs bg-white/20 px-2 py-0.5 rounded-full">Aktif</span>
                        </a>
                        <a href="pembayaran.php" class="flex items-center px-4 py-3 text-primary-700 hover:bg-primary-50 rounded-xl transition-all duration-200 group mt-1">
                            <i class="fas fa-credit-card w-6 text-primary-400 group-hover:text-primary-600"></i>
                            <span class="ml-3">Pembayaran</span>
                            <i class="fas fa-arrow-right ml-auto opacity-0 group-hover:opacity-100 transition-all text-primary-400 group-hover:translate-x-1"></i>
                        </a>
                        <a href="laporan.php" class="flex items-center px-4 py-3 text-primary-700 hover:bg-primary-50 rounded-xl transition-all duration-200 group mt-1">
                            <i class="fas fa-chart-bar w-6 text-primary-400 group-hover:text-primary-600"></i>
                            <span class="ml-3">Laporan</span>
                            <i class="fas fa-arrow-right ml-auto opacity-0 group-hover:opacity-100 transition-all text-primary-400 group-hover:translate-x-1"></i>
                        </a>
                    </div>
                    
                    <div class="pt-4">
                        <p class="text-xs uppercase tracking-wider text-primary-300 mb-2 px-4 font-semibold slide-in-left">Pengaturan</p>
                        <a href="data_user.php" class="flex items-center px-4 py-3 text-primary-700 hover:bg-primary-50 rounded-xl transition-all duration-200 group">
                            <i class="fas fa-users-cog w-6 text-primary-400 group-hover:text-primary-600"></i>
                            <span class="ml-3">Data User</span>
                            <i class="fas fa-arrow-right ml-auto opacity-0 group-hover:opacity-100 transition-all text-primary-400 group-hover:translate-x-1"></i>
                        </a>
                    </div>
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
            <!-- Breadcrumb -->
            <div class="breadcrumb" data-aos="fade-down">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <i class="fas fa-chevron-right"></i>
                <a href="total_lelang.php"><i class="fas fa-gavel"></i> Total Lelang</a>
                <i class="fas fa-chevron-right"></i>
                <span class="text-primary-800 font-medium">Detail Lelang</span>
            </div>

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-primary-800 slide-in-left">Detail Lelang</h1>
                    <p class="text-primary-500 mt-1 slide-in-left">Informasi lengkap lelang <?php echo htmlspecialchars($lelang['nama_barang']); ?></p>
                </div>
                <div class="mt-4 md:mt-0 slide-in-right">
                    <a href="total_lelang.php" class="btn-outline-primary">
                        <i class="fas fa-arrow-left mr-2"></i>Kembali
                    </a>
                </div>
            </div>

            <!-- Error/Success Messages -->
            <?php if(isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-2xl mb-6 flex items-center animate__animated animate__fadeIn">
                    <i class="fas fa-exclamation-circle text-red-700 mr-3 text-xl"></i>
                    <?php 
                        echo $_SESSION['error']; 
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-2xl mb-6 flex items-center animate__animated animate__fadeIn">
                    <i class="fas fa-check-circle text-green-700 mr-3 text-xl"></i>
                    <?php 
                        echo $_SESSION['success']; 
                        unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Status Banner -->
            <div class="status-banner mb-6 <?php echo $lelang['status'] == 'dibuka' ? 'bg-primary-50 border border-primary-200' : 'bg-amber-50 border border-amber-200'; ?>" data-aos="fade-up">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center">
                        <?php if($lelang['status'] == 'dibuka'): ?>
                            <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse mr-3"></div>
                            <span class="font-semibold text-primary-800">Status Lelang: <span class="text-green-600">DIBUKA - Sedang Berlangsung</span></span>
                        <?php else: ?>
                            <div class="w-3 h-3 bg-amber-600 rounded-full mr-3"></div>
                            <span class="font-semibold text-primary-800">Status Lelang: <span class="text-amber-700">DITUTUP - Lelang Selesai</span></span>
                        <?php endif; ?>
                    </div>
                    <?php if($lelang['status'] == 'dibuka' && $is_admin): ?>
                        <a href="tutup_lelang.php?id=<?php echo $id_lelang; ?>" 
                           onclick="return confirm('Yakin ingin menutup lelang ini?')"
                           class="btn-primary text-sm">
                            <i class="fas fa-lock mr-2"></i>Tutup Lelang
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if(!empty($history_error)): ?>
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-6 py-4 rounded-2xl mb-6 flex items-center" data-aos="fade-up">
                    <i class="fas fa-exclamation-triangle text-yellow-600 mr-3 text-xl"></i>
                    Info: <?php echo $history_error; ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column - Barang Info -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Image Card -->
                    <div class="detail-card" data-aos="fade-right">
                        <div class="image-container mb-4">
                            <?php if(!empty($lelang['gambar']) && file_exists('../uploads/barang/' . $lelang['gambar'])): ?>
                                <img src="../uploads/barang/<?php echo htmlspecialchars($lelang['gambar']); ?>" 
                                     alt="<?php echo htmlspecialchars($lelang['nama_barang']); ?>"
                                     class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center">
                                    <div class="text-center">
                                        <i class="fas fa-image text-primary-300 text-5xl mb-2"></i>
                                        <p class="text-primary-400 text-sm">Tidak ada gambar</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Status Badge -->
                            <div class="absolute top-4 right-4">
                                <?php if($lelang['status_barang'] == 'dibuka'): ?>
                                    <span class="badge badge-success">
                                        <i class="fas fa-circle text-[6px] mr-1 animate-pulse"></i>
                                        Dibuka
                                    </span>
                                <?php elseif($lelang['status_barang'] == 'ditutup'): ?>
                                    <span class="badge badge-danger">
                                        <i class="fas fa-times-circle mr-1"></i>Ditutup
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-warning">
                                        <i class="fas fa-clock mr-1"></i>Pending
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <h2 class="text-2xl font-bold text-primary-800 font-serif mb-4"><?php echo htmlspecialchars($lelang['nama_barang']); ?></h2>
                        
                        <div class="space-y-2">
                            <!-- ID Barang -->
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-tag"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-primary-400">ID Barang</p>
                                    <p class="font-semibold text-primary-800">#<?php echo $lelang['id_barang']; ?></p>
                                </div>
                            </div>

                            <!-- Harga Awal -->
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-primary-400">Harga Awal</p>
                                    <p class="text-xl font-bold text-primary-800"><?php echo formatRupiah($lelang['harga_awal']); ?></p>
                                </div>
                            </div>

                            <!-- Harga Akhir -->
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-trophy"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-primary-400">Harga Akhir</p>
                                    <p class="text-2xl font-bold text-amber-600"><?php echo formatRupiah($lelang['harga_akhir']); ?></p>
                                </div>
                            </div>

                            <!-- Tanggal Input Barang -->
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-calendar-plus"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-primary-400">Tanggal Input</p>
                                    <p class="font-medium text-primary-700"><?php echo formatTanggal($lelang['tanggal_input_barang']); ?></p>
                                </div>
                            </div>

                            <!-- Tanggal Lelang -->
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-primary-400">Tanggal Lelang</p>
                                    <p class="font-medium text-primary-700"><?php echo formatTanggal($lelang['tgl_lelang']); ?></p>
                                </div>
                            </div>

                            <!-- Petugas -->
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-primary-400">Petugas</p>
                                    <p class="font-medium text-primary-700">
                                        <?php 
                                        if(!empty($lelang['petugas_nama'])) {
                                            echo htmlspecialchars($lelang['petugas_nama']);
                                        } elseif(!empty($lelang['petugas_username'])) {
                                            echo htmlspecialchars($lelang['petugas_username']);
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Pemenang -->
                            <?php if(!empty($lelang['pemenang'])): ?>
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-crown"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-primary-400">Pemenang</p>
                                    <p class="font-medium text-primary-700"><?php echo htmlspecialchars($lelang['pemenang']); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Deskripsi -->
                        <div class="mt-6 pt-6 border-t border-primary-100">
                            <h3 class="font-bold text-primary-800 mb-3 flex items-center">
                                <i class="fas fa-align-left text-primary-500 mr-2"></i>
                                Deskripsi Barang
                            </h3>
                            <p class="text-gray-600 text-sm leading-relaxed">
                                <?php echo nl2br(htmlspecialchars($lelang['deskripsi_barang'] ?? 'Tidak ada deskripsi')); ?>
                            </p>
                        </div>

                        <!-- Payment Info if exists -->
                        <?php if($pembayaran): ?>
                        <div class="mt-6 pt-6 border-t border-primary-100">
                            <h3 class="font-bold text-primary-800 mb-3 flex items-center">
                                <i class="fas fa-credit-card text-primary-500 mr-2"></i>
                                Informasi Pembayaran
                            </h3>
                            <div class="bg-primary-50 rounded-xl p-4">
                                <div class="flex justify-between items-center mb-3">
                                    <span class="text-sm text-primary-600">Status:</span>
                                    <?php if($pembayaran['status_pembayaran'] == 'selesai'): ?>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check-circle mr-1"></i>Selesai
                                        </span>
                                    <?php elseif($pembayaran['status_pembayaran'] == 'dibayar'): ?>
                                        <span class="badge badge-info">
                                            <i class="fas fa-hourglass-half mr-1"></i>Dibayar
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">
                                            <i class="fas fa-clock mr-1"></i>Pending
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-primary-600">Metode:</span>
                                    <span class="font-semibold text-primary-800"><?php echo htmlspecialchars($pembayaran['metode_pembayaran'] ?? 'Transfer Bank'); ?></span>
                                </div>
                                <?php if(!empty($pembayaran['bukti_pembayaran'])): ?>
                                <div class="mt-3">
                                    <a href="../uploads/bukti/<?php echo urlencode($pembayaran['bukti_pembayaran']); ?>" target="_blank" 
                                       class="text-primary-600 hover:text-primary-800 text-sm flex items-center">
                                        <i class="fas fa-image mr-2"></i>Lihat Bukti Pembayaran
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Column - Bidding History -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- History Card -->
                    <div class="detail-card" data-aos="fade-left">
                        <div class="table-header flex items-center justify-between">
                            <h3 class="text-xl font-bold">
                                <i class="fas fa-history mr-2 text-primary-500"></i>
                                Riwayat Penawaran
                            </h3>
                            <span class="bg-primary-600 text-white px-4 py-2 rounded-xl text-sm font-semibold">
                                <i class="fas fa-users mr-2"></i><?php echo $history_available && $history ? mysqli_num_rows($history) : 0; ?> Penawaran
                            </span>
                        </div>

                        <?php if($history_available && $history && mysqli_num_rows($history) > 0): ?>
                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Penawar</th>
                                        <th>Penawaran Harga</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    $max_bid = 0;
                                    mysqli_data_seek($history, 0);
                                    while($row = mysqli_fetch_assoc($history)): 
                                        if($no == 1) $max_bid = $row['penawaran_harga'];
                                    ?>
                                    <tr class="<?php echo $no == 1 && $lelang['status'] == 'ditutup' ? 'bg-primary-50' : ''; ?>">
                                        <td class="font-medium text-primary-800"><?php echo $no++; ?></td>
                                        <td>
                                            <div class="flex items-center">
                                                <?php if($no == 2 && $lelang['status'] == 'ditutup' && $row['penawaran_harga'] == $max_bid): ?>
                                                    <i class="fas fa-crown text-yellow-500 mr-2" title="Pemenang"></i>
                                                <?php endif; ?>
                                                <span class="font-medium text-primary-800"><?php echo htmlspecialchars($row['nama_lengkap']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="<?php echo ($row['penawaran_harga'] == $max_bid && $lelang['status'] == 'ditutup') ? 'text-amber-600 font-bold text-lg' : 'text-primary-600 font-semibold'; ?>">
                                                <?php echo formatRupiah($row['penawaran_harga']); ?>
                                                <?php if($row['penawaran_harga'] == $max_bid && $lelang['status'] == 'ditutup'): ?>
                                                    <span class="ml-2 text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full">Tertinggi</span>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td class="text-gray-500 text-sm">
                                            <i class="far fa-clock mr-1"></i>
                                            <?php echo formatTanggalWaktu($row['created_at']); ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Winner Summary if closed -->
                        <?php if($lelang['status'] == 'ditutup' && !empty($lelang['id_user'])): ?>
                        <div class="mt-6 pt-6 border-t border-primary-100">
                            <div class="winner-card">
                                <div class="flex items-center justify-between flex-wrap gap-4">
                                    <div>
                                        <p class="text-primary-600 text-sm mb-1">Pemenang Lelang</p>
                                        <p class="text-2xl font-bold text-primary-800 font-serif"><?php echo htmlspecialchars($lelang['pemenang']); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-primary-600 text-sm mb-1">Harga Akhir</p>
                                        <p class="text-2xl font-bold text-amber-600"><?php echo formatRupiah($lelang['harga_akhir']); ?></p>
                                    </div>
                                </div>
                                
                                <?php if(!$pembayaran && $is_admin): ?>
                                <div class="mt-4">
                                    <a href="pembayaran.php?add=<?php echo $id_lelang; ?>" 
                                       class="btn-primary">
                                        <i class="fas fa-credit-card mr-2"></i>Buat Pembayaran
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php else: ?>
                        <div class="p-12 text-center">
                            <div class="w-24 h-24 bg-primary-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-gavel text-primary-300 text-4xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-primary-800 mb-2">Belum Ada Penawaran</h3>
                            <p class="text-primary-500">Belum ada penawar untuk lelang ini</p>
                            
                            <?php if(!$history_available): ?>
                            <p class="text-xs text-primary-400 mt-4">
                                <i class="fas fa-info-circle mr-1"></i>
                                Sistem sedang dalam mode terbatas (tabel history tidak tersedia)
                            </p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Additional Info Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Info Lelang -->
                        <div class="detail-card" data-aos="fade-up" data-aos-delay="100">
                            <h3 class="font-bold text-primary-800 mb-4 flex items-center">
                                <i class="fas fa-info-circle mr-2 text-primary-500"></i>
                                Informasi Lelang
                            </h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center py-2 border-b border-primary-100">
                                    <span class="text-primary-600">ID Lelang</span>
                                    <span class="font-semibold text-primary-800">#<?php echo $lelang['id_lelang']; ?></span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-primary-100">
                                    <span class="text-primary-600">Dibuat Pada</span>
                                    <span class="font-semibold text-primary-800"><?php echo formatTanggalWaktu($lelang['created_at'] ?? $lelang['tgl_lelang']); ?></span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-primary-100">
                                    <span class="text-primary-600">Total Penawaran</span>
                                    <span class="font-semibold text-primary-800"><?php echo $history_available && $history ? mysqli_num_rows($history) : 0; ?>x penawaran</span>
                                </div>
                                <?php if(!empty($lelang['id_user'])): ?>
                                <div class="flex justify-between items-center py-2 border-b border-primary-100">
                                    <span class="text-primary-600">ID Pemenang</span>
                                    <span class="font-semibold text-primary-800">#<?php echo $lelang['id_user']; ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="detail-card" data-aos="fade-up" data-aos-delay="200">
                            <h3 class="font-bold text-primary-800 mb-4 flex items-center">
                                <i class="fas fa-bolt mr-2 text-primary-500"></i>
                                Aksi Cepat
                            </h3>
                            <div class="space-y-3">
                                <a href="data_barang.php?edit=<?php echo $lelang['id_barang']; ?>" 
                                   class="btn-outline-primary w-full justify-center">
                                    <i class="fas fa-edit mr-2"></i>Edit Barang
                                </a>
                                <?php if($lelang['status'] == 'dibuka' && $is_admin): ?>
                                <a href="tutup_lelang.php?id=<?php echo $id_lelang; ?>" 
                                   onclick="return confirm('Yakin ingin menutup lelang ini?')"
                                   class="btn-primary w-full justify-center">
                                    <i class="fas fa-lock mr-2"></i>Tutup Lelang
                                </a>
                                <?php endif; ?>
                                <a href="laporan.php?dari=<?php echo date('Y-m-d', strtotime('-30 days')); ?>&sampai=<?php echo date('Y-m-d'); ?>" 
                                   class="btn-outline-primary w-full justify-center">
                                    <i class="fas fa-chart-line mr-2"></i>Lihat Laporan
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- AOS Animation Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: false,
            mirror: true,
            offset: 50,
            easing: 'ease-out-cubic'
        });

        // Particle Background
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            if(particlesContainer) {
                for (let i = 0; i < 20; i++) {
                    const particle = document.createElement('div');
                    particle.className = 'particle';
                    particle.style.left = Math.random() * 100 + '%';
                    particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
                    particle.style.animationDelay = Math.random() * 5 + 's';
                    particle.style.width = (Math.random() * 6 + 2) + 'px';
                    particle.style.height = particle.style.width;
                    particlesContainer.appendChild(particle);
                }
            }
        }
        createParticles();

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

        // Loading Overlay
        function showLoading() {
            document.getElementById('loadingOverlay').classList.remove('hidden');
            document.getElementById('loadingOverlay').classList.add('flex');
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').classList.add('hidden');
            document.getElementById('loadingOverlay').classList.remove('flex');
        }

        // Hover Effects
        document.querySelectorAll('.hover-scale').forEach(el => {
            el.addEventListener('mouseenter', () => {
                el.style.transform = 'scale(1.05)';
            });
            el.addEventListener('mouseleave', () => {
                el.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>