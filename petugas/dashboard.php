<?php
session_start();
require_once('../config/config.php');
checkLevel([2]); // Hanya petugas

// Get statistics
$total_barang = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_barang"))['total'];
$total_lelang = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_lelang"))['total'];
$lelang_aktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_lelang WHERE status = 'dibuka'"))['total'];
$total_transaksi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_pembayaran WHERE status_pembayaran = 'selesai'"))['total'];
$pending_bayar = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_pembayaran WHERE status_pembayaran = 'pending'"))['total'];
$total_nilai = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah) as total FROM tb_pembayaran WHERE status_pembayaran = 'selesai'"))['total'];

// Get recent lelang
$recent_lelang = mysqli_query($conn, "SELECT l.*, b.nama_barang FROM tb_lelang l JOIN tb_barang b ON l.id_barang = b.id_barang ORDER BY l.id_lelang DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda Petugas - Sistem Lelang Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary-50: #eef2f8; --primary-100: #d9e2f0; --primary-200: #b3c5e1;
            --primary-300: #8da8d2; --primary-400: #678bc3; --primary-500: #416eb4;
            --primary-600: #2a4f8c; --primary-700: #1e3a66; --primary-800: #132a4a; --primary-900: #0a1a30;
            --secondary-50: #f8fafc; --secondary-100: #f1f5f9;
        }
        body { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); min-height: 100vh; overflow-x: hidden; }
        .bg-primary { background-color: var(--primary-600); }
        .text-primary { color: var(--primary-700); }
        .gradient-bg { background: linear-gradient(135deg, var(--primary-700), var(--primary-600), var(--primary-500)); background-size: 200% 200%; animation: gradientShift 8s ease infinite; }
        @keyframes gradientShift { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        .glass { background: rgba(255,255,255,0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(65,110,180,0.15); transition: all 0.3s ease; }
        .glass:hover { background: rgba(255,255,255,0.95); border-color: rgba(65,110,180,0.3); }
        .stat-card { background: white; border-radius: 24px; padding: 1.5rem; box-shadow: 0 10px 25px -5px rgba(30,58,102,0.08); border: 1px solid var(--primary-100); transition: all 0.4s cubic-bezier(0.175,0.885,0.32,1.275); position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, var(--primary-400), var(--primary-600)); transform: scaleX(0); transition: transform 0.4s ease; }
        .stat-card:hover::before { transform: scaleX(1); }
        .stat-card:hover { transform: translateY(-8px) scale(1.02); box-shadow: 0 25px 35px -8px rgba(30,58,102,0.2); }
        .modern-table { border-collapse: separate; border-spacing: 0 12px; }
        .modern-table tbody tr { background: white; border-radius: 18px; transition: all 0.3s ease; box-shadow: 0 4px 12px -2px rgba(30,58,102,0.06); animation: slideInRow 0.5s ease-out; animation-fill-mode: both; }
        @keyframes slideInRow { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }
        .modern-table tbody tr:hover { transform: scale(1.02) translateY(-2px); box-shadow: 0 20px 25px -8px rgba(30,58,102,0.15); background: linear-gradient(90deg, white, var(--primary-50)); }
        .animate-pulse-soft { animation: pulseSoft 2s infinite; }
        @keyframes pulseSoft { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.7; transform: scale(1.05); } }
        .animate-float { animation: float 3s ease-in-out infinite; }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        .slide-in-left { animation: slideInLeft 0.5s ease-out; }
        @keyframes slideInLeft { from { opacity: 0; transform: translateX(-30px); } to { opacity: 1; transform: translateX(0); } }
        .slide-in-right { animation: slideInRight 0.5s ease-out; }
        @keyframes slideInRight { from { opacity: 0; transform: translateX(30px); } to { opacity: 1; transform: translateX(0); } }
        .fade-in-up { animation: fadeInUp 0.6s ease-out; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .typing-effect { overflow: hidden; white-space: nowrap; animation: typing 3s steps(40, end); }
        @keyframes typing { from { width: 0; } to { width: 100%; } }
        .spin-slow { animation: spin 8s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .counter { animation: countUp 2s ease-out; }
        @keyframes countUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .progress-bar { width: 0; animation: progressFill 1.5s ease-out forwards; }
        @keyframes progressFill { to { width: var(--target-width); } }
        .activity-item { animation: slideInRight 0.5s ease-out; animation-fill-mode: both; }
        .activity-item:nth-child(1) { animation-delay: 0.1s; }
        .activity-item:nth-child(2) { animation-delay: 0.2s; }
        .activity-item:nth-child(3) { animation-delay: 0.3s; }
        .badge { padding: 6px 14px; border-radius: 100px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; transition: all 0.3s ease; }
        .badge:hover { transform: scale(1.05); filter: brightness(1.1); }
        .badge-success { background: #e6f7e6; color: #0a5e0a; border: 1px solid #a3e0a3; }
        .badge-danger { background: #ffe6e6; color: #a12323; border: 1px solid #ffb8b8; }
        .badge-warning { background: #fff8e1; color: #7a5500; border: 1px solid #ffe082; }
        .btn-primary { background: var(--primary-600); color: white; padding: 0.75rem 1.5rem; border-radius: 14px; font-weight: 600; transition: all 0.3s ease; position: relative; overflow: hidden; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 20px -8px rgba(30,58,102,0.4); }
        .hover-glow { transition: all 0.3s ease; }
        .hover-glow:hover { box-shadow: 0 0 20px rgba(65,110,180,0.3); transform: translateY(-2px); }
        .particle { position: fixed; width: 4px; height: 4px; background: var(--primary-300); border-radius: 50%; opacity: 0.3; animation: particleFloat 15s infinite linear; }
        @keyframes particleFloat { from { transform: translateY(100vh) rotate(0deg); } to { transform: translateY(-100vh) rotate(360deg); } }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--primary-50); border-radius: 6px; }
        ::-webkit-scrollbar-thumb { background: var(--primary-300); border-radius: 6px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary-400); }
        .spinner { width: 40px; height: 40px; border: 3px solid var(--primary-200); border-top-color: var(--primary-600); border-radius: 50%; animation: spin 1s linear infinite; }
        .neon-glow { animation: neonPulse 2s infinite; }
        @keyframes neonPulse { 0%, 100% { box-shadow: 0 0 5px rgba(65,110,180,0.3); } 50% { box-shadow: 0 0 20px rgba(65,110,180,0.6); } }
        .ripple { position: absolute; background: rgba(255,255,255,0.3); border-radius: 50%; transform: scale(0); animation: rippleAnim 0.6s linear; pointer-events: none; }
        @keyframes rippleAnim { to { transform: scale(4); opacity: 0; } }
    </style>
</head>
<body class="antialiased text-gray-800">
    <div id="particles"></div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
        <div class="bg-white rounded-2xl p-8 flex flex-col items-center">
            <div class="spinner mb-4"></div>
            <p style="color:var(--primary-600)" class="font-medium">Memuat data...</p>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <!-- Navbar - Full Width identik admin -->
    <nav class="glass sticky top-0 z-30 border-b shadow-sm" style="border-color:var(--primary-100)">
        <div class="w-full px-0 flex">
            <div class="flex justify-between h-16 w-full">
                <div class="flex items-center" style="width:288px;min-width:288px;padding-left:1.5rem">
                    <div class="gradient-bg p-2.5 rounded-xl shadow-lg animate-float flex items-center justify-center" style="min-width:40px;min-height:40px">
                        <i class="fas fa-gavel text-white text-lg"></i>
                    </div>
                    <div class="flex flex-col leading-tight ml-3">
                        <span class="font-extrabold text-lg tracking-tight" style="background:linear-gradient(135deg,var(--primary-700),var(--primary-500));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;line-height:1.2">Sistem Lelang</span>
                        <span class="text-xs font-semibold tracking-widest uppercase" style="color:var(--primary-400);letter-spacing:0.18em;line-height:1.2">Online<span class="inline-block w-1.5 h-1.5 rounded-full bg-green-400 ml-1.5 mb-0.5 animate-pulse align-middle"></span></span>
                    </div>
                </div>
                <div class="flex items-center space-x-4 pr-6">
                    <div class="flex items-center space-x-3 ml-2 pl-2 border-l-2" style="border-color:var(--primary-100)">
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-semibold" style="color:var(--primary-800)"><?php echo $_SESSION['nama_lengkap']; ?></p>
                            <p class="text-xs flex items-center justify-end" style="color:var(--primary-500)">
                                <i class="fas fa-circle text-green-500 text-[6px] mr-1 animate-pulse"></i>Petugas
                            </p>
                        </div>
                        <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center text-white font-bold shadow-md ring-2 hover:scale-110 transition-transform" style="ring-color:var(--primary-100)">
                            <?php echo strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)); ?>
                        </div>
                    </div>
                    <a href="../auth/logout.php" onclick="return confirm('Yakin ingin keluar?')" class="relative overflow-hidden group text-sm py-2 px-4 flex items-center space-x-2 rounded-xl border transition-all hover:scale-105" style="color:var(--primary-600);border-color:var(--primary-200)">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="hidden sm:inline">Keluar</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex relative">
        <!-- Sidebar identik admin -->
        <aside class="w-72 bg-white border-r min-h-screen shadow-xl relative overflow-hidden" style="border-color:var(--primary-100)">
            <div class="absolute top-0 left-0 w-full h-1 gradient-bg"></div>
            <div class="p-6 relative z-10">
                <!-- Profile Card -->
                <div class="gradient-bg rounded-2xl p-6 mb-6 text-white shadow-xl relative overflow-hidden group" data-aos="fade-right">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-white opacity-5 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-700"></div>
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-5 rounded-full -ml-8 -mb-8 group-hover:scale-150 transition-transform duration-700"></div>
                    <div class="flex items-center space-x-4 relative z-10">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center text-2xl font-bold border-2 border-white/30 group-hover:scale-110 transition-transform">
                            <?php echo strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)); ?>
                        </div>
                        <div>
                            <p class="text-white/80 text-sm font-light">Selamat datang,</p>
                            <p class="text-xl font-bold group-hover:translate-x-1 transition-transform"><?php echo $_SESSION['nama_lengkap']; ?></p>
                            <p class="text-white/80 text-xs mt-1 flex items-center">
                                <i class="fas fa-circle text-green-300 text-[6px] mr-2 animate-pulse"></i>Sedang Aktif
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="space-y-1">
                    <p class="text-xs uppercase tracking-wider mb-3 px-4 font-semibold slide-in-left" style="color:var(--primary-300)">Menu Utama</p>
                    <a href="dashboard.php" class="flex items-center px-4 py-3.5 gradient-bg text-white rounded-xl shadow-lg transition-all duration-200 group relative overflow-hidden">
                        <i class="fas fa-home w-6 text-white"></i>
                        <span class="ml-3 font-medium">Beranda</span>
                        <i class="fas fa-chevron-right ml-auto text-sm opacity-0 group-hover:opacity-100 transition-all"></i>
                        <span class="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 transition-opacity"></span>
                    </a>
                    <a href="data_barang.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group hover:bg-blue-50" style="color:var(--primary-700)">
                        <i class="fas fa-box w-6 group-hover:scale-110 transition-transform" style="color:var(--primary-400)"></i>
                        <span class="ml-3 group-hover:translate-x-1 transition-transform">Data Barang</span>
                    </a>
                    <a href="kelola_lelang.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group hover:bg-blue-50" style="color:var(--primary-700)">
                        <i class="fas fa-gavel w-6 group-hover:rotate-12 transition-transform" style="color:var(--primary-400)"></i>
                        <span class="ml-3 group-hover:translate-x-1 transition-transform">Kelola Lelang</span>
                    </a>
                    <a href="pembayaran.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group hover:bg-blue-50" style="color:var(--primary-700)">
                        <i class="fas fa-credit-card w-6 group-hover:scale-110 transition-transform" style="color:var(--primary-400)"></i>
                        <span class="ml-3 group-hover:translate-x-1 transition-transform">Pembayaran</span>
                    </a>
                    <a href="data_user.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group hover:bg-blue-50" style="color:var(--primary-700)">
                        <i class="fas fa-users w-6 group-hover:scale-110 transition-transform" style="color:var(--primary-400)"></i>
                        <span class="ml-3 group-hover:translate-x-1 transition-transform">Data User</span>
                    </a>
                    <a href="laporan.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group hover:bg-blue-50" style="color:var(--primary-700)">
                        <i class="fas fa-chart-bar w-6 group-hover:scale-110 transition-transform" style="color:var(--primary-400)"></i>
                        <span class="ml-3 group-hover:translate-x-1 transition-transform">Laporan</span>
                    </a>
                </nav>
            </div>

            <!-- Sidebar Footer -->
            <div class="absolute bottom-6 left-6 right-6">
                <div class="rounded-xl p-4 border hover:shadow-lg transition-all hover:scale-105 group" style="background:var(--primary-50);border-color:var(--primary-100)">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm group-hover:rotate-12 transition-transform">
                            <i class="fas fa-headset text-lg" style="color:var(--primary-500)"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold" style="color:var(--primary-800)">Butuh Bantuan?</p>
                            <p class="text-xs flex items-center" style="color:var(--primary-500)"><i class="fas fa-envelope mr-1"></i>admin@lelang.com</p>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t" style="border-color:var(--primary-100)">
                        <p class="text-xs text-center" style="color:var(--primary-400)"><i class="fas fa-circle text-green-500 text-[6px] mr-1 animate-pulse"></i>Dukungan 24/7</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8" style="background:var(--secondary-50)">
            <!-- Welcome Banner -->
            <div class="mb-8 relative overflow-hidden rounded-3xl" data-aos="fade-down">
                <div class="gradient-bg rounded-3xl p-8 text-white shadow-xl relative">
                    <div class="absolute top-0 right-0 w-80 h-80 bg-white opacity-5 rounded-full -mr-20 -mt-20 animate-float"></div>
                    <div class="absolute bottom-0 left-0 w-64 h-64 bg-white opacity-5 rounded-full -ml-16 -mb-16 animate-float" style="animation-delay:1s"></div>
                    <div class="absolute top-1/2 left-1/2 w-40 h-40 bg-white opacity-5 rounded-full animate-ping"></div>
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
                        <div class="slide-in-left">
                            <h1 class="text-3xl lg:text-4xl font-bold mb-2 typing-effect">Beranda Petugas</h1>
                            <p class="text-blue-100 flex items-center"><i class="fas fa-calendar-alt mr-2"></i><?php
                $hari_id = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
                $bulan_id = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April','May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus','September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
                $tgl_id = date('l, d F Y');
                $tgl_id = str_replace(array_keys($hari_id), array_values($hari_id), $tgl_id);
                $tgl_id = str_replace(array_keys($bulan_id), array_values($bulan_id), $tgl_id);
                echo $tgl_id;
              ?></p>
                            <p class="text-blue-100 text-sm mt-2 flex items-center">
                                <i class="fas fa-clock mr-2 animate-spin-slow"></i>
                                <span id="liveClock"><?php echo date('H:i:s'); ?> WIB</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:var(--primary-50)">
                            <i class="fas fa-box text-2xl" style="color:var(--primary-600)"></i>
                        </div>
                        <div class="text-right">
                            <span class="text-3xl font-bold counter" style="color:var(--primary-800)" data-target="<?php echo $total_barang; ?>">0</span>
                            <span class="text-xs text-green-600 block flex items-center justify-end mt-1"><i class="fas fa-arrow-up mr-1 animate-bounce"></i>Total</span>
                        </div>
                    </div>
                    <h3 class="font-medium text-gray-600">Total Barang</h3>
                    <div class="w-full rounded-full h-1.5 mt-3" style="background:var(--primary-100)">
                        <div class="h-1.5 rounded-full progress-bar" style="--target-width:70%;background:var(--primary-600)"></div>
                    </div>
                    <p class="text-xs mt-2" style="color:var(--primary-600)"><?php echo $lelang_aktif; ?> sedang dilelang</p>
                </div>

                <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:var(--primary-50)">
                            <i class="fas fa-gavel text-2xl" style="color:var(--primary-600)"></i>
                        </div>
                        <div class="text-right">
                            <span class="text-3xl font-bold counter" style="color:var(--primary-800)" data-target="<?php echo $total_lelang; ?>">0</span>
                            <span class="text-xs text-green-600 block flex items-center justify-end mt-1"><i class="fas fa-arrow-up mr-1 animate-bounce"></i>8%</span>
                        </div>
                    </div>
                    <h3 class="font-medium text-gray-600">Total Lelang</h3>
                    <div class="flex items-center mt-3 text-sm">
                        <span class="flex items-center px-3 py-1.5 rounded-full hover:scale-105 transition-transform text-green-700" style="background:#dcfce7">
                            <i class="fas fa-play-circle mr-2 animate-pulse"></i><?php echo $lelang_aktif; ?> berlangsung
                        </span>
                    </div>
                </div>

                <div class="stat-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:var(--primary-50)">
                            <i class="fas fa-credit-card text-2xl" style="color:var(--primary-600)"></i>
                        </div>
                        <div class="text-right">
                            <span class="text-3xl font-bold counter" style="color:var(--primary-800)" data-target="<?php echo $total_transaksi; ?>">0</span>
                            <span class="text-xs text-green-600 block mt-1">Selesai</span>
                        </div>
                    </div>
                    <h3 class="font-medium text-gray-600">Transaksi Selesai</h3>
                    <div class="flex items-center mt-3 text-sm">
                        <span class="flex items-center px-3 py-1.5 rounded-full hover:scale-105 transition-transform text-green-700" style="background:#dcfce7">
                            <i class="fas fa-check-circle mr-2 animate-pulse"></i>100% verified
                        </span>
                    </div>
                </div>

                <div class="stat-card" data-aos="fade-up" data-aos-delay="400">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:#fff8e1">
                            <i class="fas fa-clock text-2xl text-yellow-600"></i>
                        </div>
                        <div class="text-right">
                            <span class="text-3xl font-bold counter text-yellow-700" data-target="<?php echo $pending_bayar; ?>">0</span>
                            <span class="text-xs text-yellow-600 block mt-1">Menunggu</span>
                        </div>
                    </div>
                    <h3 class="font-medium text-gray-600">Pembayaran Pending</h3>
                    <div class="flex items-center mt-3 text-sm">
                        <a href="pembayaran.php" class="flex items-center px-3 py-1.5 rounded-full hover:scale-105 transition-transform" style="background:#fff8e1;color:#7a5500">
                            <i class="fas fa-arrow-right mr-2 animate-bounce"></i>Proses sekarang
                        </a>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg p-6 border hover-glow" style="border-color:var(--primary-100)" data-aos="flip-left">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-xl font-bold" style="color:var(--primary-800)">Aktivitas Lelang</h2>
                            <p class="text-sm mt-1" style="color:var(--primary-500)">Grafik penawaran 7 hari terakhir</p>
                        </div>
                        <div class="flex space-x-2">
                            <button class="px-4 py-2 text-white rounded-xl text-sm font-medium hover:shadow-lg active:scale-95 transition-all" style="background:var(--primary-600)">Harian</button>
                            <button class="px-4 py-2 rounded-xl text-sm font-medium transition-all hover:scale-105 hover:bg-blue-50" style="color:var(--primary-600)">Mingguan</button>
                            <button class="px-4 py-2 rounded-xl text-sm font-medium transition-all hover:scale-105 hover:bg-blue-50" style="color:var(--primary-600)">Bulanan</button>
                        </div>
                    </div>
                    <div class="h-80 relative">
                        <canvas id="auctionChart"></canvas>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="bg-white rounded-2xl shadow-lg p-6 border hover-glow" style="border-color:var(--primary-100)" data-aos="flip-right">
                    <h2 class="text-xl font-bold mb-6 flex items-center" style="color:var(--primary-800)">
                        <i class="fas fa-bell mr-2 animate-pulse" style="color:var(--primary-500)"></i>
                        Aktivitas Terkini
                        <span class="ml-auto text-white text-xs px-2 py-1 rounded-full animate-pulse-soft" style="background:var(--primary-600)">Live</span>
                    </h2>
                    <div class="space-y-4">
                        <div class="activity-item flex items-start space-x-3 p-3 rounded-xl hover:shadow-lg transition-all" style="background:var(--primary-50)">
                            <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center text-white shadow-md">
                                <i class="fas fa-gavel"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium" style="color:var(--primary-800)">Lelang aktif berjalan</p>
                                <p class="text-xs mt-1" style="color:var(--primary-500)"><?php echo $lelang_aktif; ?> lelang sedang berlangsung</p>
                                <p class="text-xs text-gray-400 mt-1 flex items-center"><i class="far fa-clock mr-1"></i>Saat ini</p>
                            </div>
                        </div>
                        <div class="activity-item flex items-start space-x-3 p-3 rounded-xl hover:shadow-lg transition-all" style="background:var(--primary-50)">
                            <div class="w-10 h-10 bg-green-600 rounded-xl flex items-center justify-center text-white shadow-md">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium" style="color:var(--primary-800)">Pembayaran selesai</p>
                                <p class="text-xs mt-1" style="color:var(--primary-500)"><?php echo $total_transaksi; ?> transaksi terkonfirmasi</p>
                                <p class="text-xs text-gray-400 mt-1 flex items-center"><i class="far fa-clock mr-1"></i>Total</p>
                            </div>
                        </div>
                        <div class="activity-item flex items-start space-x-3 p-3 rounded-xl hover:shadow-lg transition-all" style="background:var(--primary-50)">
                            <div class="w-10 h-10 bg-yellow-600 rounded-xl flex items-center justify-center text-white shadow-md">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium" style="color:var(--primary-800)">Pembayaran pending</p>
                                <p class="text-xs mt-1" style="color:var(--primary-500)"><?php echo $pending_bayar; ?> menunggu konfirmasi</p>
                                <p class="text-xs text-gray-400 mt-1 flex items-center"><i class="far fa-clock mr-1"></i>Perlu tindakan</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 pt-6 border-t" style="border-color:var(--primary-100)">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center p-3 rounded-xl hover:scale-105 transition-transform hover:shadow-lg" style="background:var(--primary-50)">
                                <p class="text-2xl font-bold counter" style="color:var(--primary-700)" data-target="<?php echo $lelang_aktif; ?>">0</p>
                                <p class="text-xs" style="color:var(--primary-600)">Lelang Aktif</p>
                            </div>
                            <div class="text-center p-3 rounded-xl hover:scale-105 transition-transform hover:shadow-lg" style="background:var(--primary-50)">
                                <p class="text-2xl font-bold counter" style="color:var(--primary-700)" data-target="<?php echo $total_barang; ?>">0</p>
                                <p class="text-xs" style="color:var(--primary-600)">Total Barang</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Lelang Table -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border hover-glow" style="border-color:var(--primary-100)" data-aos="zoom-in">
                <div class="p-6 border-b" style="border-color:var(--primary-100);background:linear-gradient(to right,var(--primary-50),white)">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-xl font-bold" style="color:var(--primary-800)">
                                <i class="fas fa-history mr-2 animate-spin-slow" style="color:var(--primary-500)"></i>Lelang Terbaru
                            </h2>
                            <p class="text-sm mt-1" style="color:var(--primary-500)">5 lelang terakhir yang ditambahkan</p>
                        </div>
                        <div class="flex items-center space-x-3 mt-4 md:mt-0">
                            <div class="relative group">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-sm" style="color:var(--primary-400)"></i>
                                <input type="text" id="searchLelang" placeholder="Cari lelang..." class="pl-9 pr-4 py-2 border rounded-xl text-sm focus:outline-none transition-all" style="border-color:var(--primary-200)">
                            </div>
                            <a href="kelola_lelang.php" class="text-sm py-2 px-4 flex items-center group relative overflow-hidden rounded-xl border transition-all hover:scale-105" style="color:var(--primary-600);border-color:var(--primary-200)">
                                <span>Lihat Semua</span>
                                <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full modern-table">
                        <thead>
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Barang</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Tanggal Lelang</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Harga Awal</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Harga Akhir</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($recent_lelang) > 0): ?>
                                <?php $delay = 0.1; while($row = mysqli_fetch_assoc($recent_lelang)): ?>
                                <tr style="animation-delay:<?php echo $delay; ?>s">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold mr-3" style="background:var(--primary-100);color:var(--primary-700)">
                                                <i class="fas fa-box"></i>
                                            </div>
                                            <div>
                                                <p class="font-semibold" style="color:var(--primary-800)"><?php echo htmlspecialchars($row['nama_barang']); ?></p>
                                                <p class="text-xs" style="color:var(--primary-500)">ID: #<?php echo $row['id_lelang']; ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-gray-700"><?php echo date('d/m/Y', strtotime($row['tgl_lelang'])); ?></div>
                                        <p class="text-xs mt-1" style="color:var(--primary-400)"><?php echo date('H:i', strtotime($row['created_at'])); ?> WIB</p>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600"><?php echo 'Rp ' . number_format($row['harga_awal'] ?? 0, 0, ',', '.'); ?></td>
                                    <td class="px-6 py-4 font-semibold" style="color:var(--primary-700)"><?php echo 'Rp ' . number_format($row['harga_akhir'] ?? $row['harga_awal'], 0, ',', '.'); ?></td>
                                    <td class="px-6 py-4">
                                        <?php if($row['status'] == 'dibuka'): ?>
                                            <span class="badge badge-success">
                                                <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse-soft"></span>Dibuka
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-danger"><i class="fas fa-lock mr-1"></i>Ditutup</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <a href="detail_lelang_petugas.php?id=<?php echo $row['id_lelang']; ?>" class="font-medium inline-flex items-center group" style="color:var(--primary-600)">
                                            Detail <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform text-sm"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php $delay += 0.1; endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-20 h-20 rounded-full flex items-center justify-center mb-4 animate-float" style="background:var(--primary-50)">
                                                <i class="fas fa-gavel text-3xl" style="color:var(--primary-300)"></i>
                                            </div>
                                            <p class="font-medium text-lg" style="color:var(--primary-600)">Belum ada data lelang</p>
                                            <a href="data_barang.php" class="btn-primary text-sm mt-4 px-6 py-2.5">
                                                <i class="fas fa-plus mr-2"></i>Tambah Barang
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t flex flex-col sm:flex-row items-center justify-between gap-4" style="background:var(--primary-50);border-color:var(--primary-100)">
                    <p class="text-sm" style="color:var(--primary-600)">
                        <i class="fas fa-info-circle mr-2 animate-pulse"></i>
                        Menampilkan 5 dari <?php echo $total_lelang; ?> lelang
                    </p>
                    <div class="flex items-center space-x-3">
                        <select class="text-sm border rounded-lg px-3 py-2 bg-white transition-all" style="border-color:var(--primary-200);color:var(--primary-700)">
                            <option>10 per halaman</option>
                            <option>25 per halaman</option>
                            <option>50 per halaman</option>
                        </select>
                        <div class="flex space-x-2">
                            <button class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border hover:scale-110 active:scale-95 transition-all" style="color:var(--primary-600);border-color:var(--primary-200)">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <span class="w-9 h-9 flex items-center justify-center rounded-xl text-white font-medium" style="background:var(--primary-600)">1</span>
                            <button class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border hover:scale-110 transition-all" style="color:var(--primary-600);border-color:var(--primary-200)">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Note -->
            <div class="mt-6 text-center text-sm" style="color:var(--primary-400)">
                <i class="fas fa-sync-alt mr-1 animate-spin-slow"></i>
                Data diperbarui setiap 5 menit • Terakhir: <span id="lastUpdate"><?php echo date('H:i:s'); ?></span> WIB
            </div>
        </main>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 800, once: false, mirror: true, offset: 50, easing: 'ease-out-cubic' });

        // Particles
        function createParticles() {
            const c = document.getElementById('particles');
            for (let i = 0; i < 20; i++) {
                const p = document.createElement('div');
                p.className = 'particle';
                p.style.left = Math.random() * 100 + '%';
                p.style.animationDuration = (Math.random() * 10 + 10) + 's';
                p.style.animationDelay = Math.random() * 5 + 's';
                p.style.width = p.style.height = (Math.random() * 6 + 2) + 'px';
                c.appendChild(p);
            }
        }
        createParticles();

        // Counter
        function animateCounter(el) {
            const target = parseInt(el.getAttribute('data-target'));
            const step = Math.max(target / 125, 1);
            let curr = 0;
            const u = () => {
                curr += step;
                if (curr < target) { el.textContent = Math.floor(curr).toLocaleString('id-ID'); requestAnimationFrame(u); }
                else { el.textContent = target.toLocaleString('id-ID'); }
            };
            u();
        }
        document.querySelectorAll('.counter').forEach(animateCounter);

        // Progress bars
        document.querySelectorAll('.progress-bar').forEach(b => { b.style.width = b.style.getPropertyValue('--target-width'); });

        // Live Clock
        function updateClock() {
            const now = new Date();
            const t = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
            document.getElementById('liveClock').textContent = t;
            document.getElementById('lastUpdate').textContent = t.replace(' WIB','');
        }
        setInterval(updateClock, 1000);

        // Chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('auctionChart').getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(65,110,180,0.25)');
            gradient.addColorStop(1, 'rgba(65,110,180,0)');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Sen','Sel','Rab','Kam','Jum','Sab','Min'],
                    datasets: [{ data: [8,14,11,16,20,25,18], borderColor: '#416eb4', backgroundColor: gradient, borderWidth: 2.5, tension: 0.3, fill: true, pointBackgroundColor: '#fff', pointBorderColor: '#416eb4', pointBorderWidth: 2, pointRadius: 4, pointHoverRadius: 8 }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    animation: { duration: 2000, easing: 'easeInOutQuart' },
                    plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1e3a66', padding: 10, cornerRadius: 8 } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(65,110,180,0.06)' }, ticks: { color: '#64748b' } },
                        x: { grid: { display: false }, ticks: { color: '#64748b' } }
                    }
                }
            });
        });

        // Search
        document.getElementById('searchLelang')?.addEventListener('input', function() {
            const s = this.value.toLowerCase();
            document.querySelectorAll('.modern-table tbody tr').forEach(r => { r.style.display = r.textContent.toLowerCase().includes(s) ? '' : 'none'; });
        });

        // Auto Refresh
        setInterval(() => { document.getElementById('loadingOverlay').classList.remove('hidden'); document.getElementById('loadingOverlay').classList.add('flex'); setTimeout(() => location.reload(), 1000); }, 300000);
    </script>
</body>
</html>