<?php
session_start();
require_once('../config/config.php');
checkLevel([2]);

$id_lelang = $_GET['id'] ?? 0;
$lelang = mysqli_fetch_assoc(mysqli_query($conn, "SELECT l.*, b.nama_barang, b.deskripsi_barang, b.harga_awal FROM tb_lelang l JOIN tb_barang b ON l.id_barang = b.id_barang WHERE id_lelang = $id_lelang"));
$history = mysqli_query($conn, "SELECT h.*, u.nama_lengkap FROM history_lelang h JOIN tb_user u ON h.id_user = u.id_user WHERE h.id_lelang = $id_lelang ORDER BY h.penawaran_harga DESC, h.created_at ASC");
$total_penawaran = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM history_lelang WHERE id_lelang=$id_lelang"));

$total_lelang_all = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM tb_lelang"))['t'];
$lelang_aktif = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM tb_lelang WHERE status='dibuka'"))['t'];
$total_barang = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM tb_barang"))['t'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Penawaran Lelang - Sistem Lelang Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        *{font-family:'Plus Jakarta Sans',sans-serif;margin:0;padding:0;box-sizing:border-box;}
        :root{--primary-50:#eef2f8;--primary-100:#d9e2f0;--primary-200:#b3c5e1;--primary-300:#8da8d2;--primary-400:#678bc3;--primary-500:#416eb4;--primary-600:#2a4f8c;--primary-700:#1e3a66;--primary-800:#132a4a;--primary-900:#0a1a30;}
        body{background:linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%);min-height:100vh;overflow-x:hidden;}
        .gradient-bg{background:linear-gradient(135deg,var(--primary-700),var(--primary-600),var(--primary-500));background-size:200% 200%;animation:gradientShift 8s ease infinite;}
        @keyframes gradientShift{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
        .glass{background:rgba(255,255,255,0.85);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border:1px solid rgba(65,110,180,0.15);transition:all 0.3s ease;}
        .glass:hover{background:rgba(255,255,255,0.95);}
        .stat-card{background:white;border-radius:24px;padding:1.5rem;box-shadow:0 10px 25px -5px rgba(30,58,102,0.08);border:1px solid var(--primary-100);transition:all 0.4s cubic-bezier(0.175,0.885,0.32,1.275);position:relative;overflow:hidden;}
        .stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,var(--primary-400),var(--primary-600));transform:scaleX(0);transition:transform 0.4s ease;}
        .stat-card:hover::before{transform:scaleX(1);}
        .stat-card:hover{transform:translateY(-8px) scale(1.02);box-shadow:0 25px 35px -8px rgba(30,58,102,0.2);}
        .animate-float{animation:float 3s ease-in-out infinite;}
        @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
        .animate-pulse-soft{animation:pulseSoft 2s infinite;}
        @keyframes pulseSoft{0%,100%{opacity:1;transform:scale(1)}50%{opacity:0.7;transform:scale(1.05)}}
        .spin-slow{animation:spin 8s linear infinite;}
        @keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
        .slide-in-left{animation:slideInLeft 0.5s ease-out;}
        @keyframes slideInLeft{from{opacity:0;transform:translateX(-30px)}to{opacity:1;transform:translateX(0)}}
        .hover-glow{transition:all 0.3s ease;}
        .hover-glow:hover{box-shadow:0 0 20px rgba(65,110,180,0.3);transform:translateY(-2px);}
        .counter{animation:countUp 2s ease-out;}
        @keyframes countUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
        .particle{position:fixed;width:4px;height:4px;background:var(--primary-300);border-radius:50%;opacity:0.3;animation:particleFloat 15s infinite linear;}
        @keyframes particleFloat{from{transform:translateY(100vh) rotate(0deg)}to{transform:translateY(-100vh) rotate(360deg)}}
        .badge-success{background:#e6f7e6;color:#0a5e0a;border:1px solid #a3e0a3;}
        .badge-danger{background:#ffe6e6;color:#a12323;border:1px solid #ffb8b8;}
        .modern-table{border-collapse:separate;border-spacing:0 12px;}
        .modern-table tbody tr{background:white;border-radius:18px;transition:all 0.3s ease;box-shadow:0 4px 12px -2px rgba(30,58,102,0.06);animation:slideInRow 0.5s ease-out;animation-fill-mode:both;}
        @keyframes slideInRow{from{opacity:0;transform:translateX(-20px)}to{opacity:1;transform:translateX(0)}}
        .modern-table tbody tr:hover{transform:scale(1.02) translateY(-2px);box-shadow:0 20px 25px -8px rgba(30,58,102,0.15);background:linear-gradient(90deg,white,var(--primary-50));}
        .winner-row{background:linear-gradient(90deg,var(--primary-50),white) !important;}
        .winner-badge{background:linear-gradient(135deg,var(--primary-600),var(--primary-500));color:white;padding:4px 12px;border-radius:100px;font-size:0.7rem;font-weight:700;}
        ::-webkit-scrollbar{width:8px;}::-webkit-scrollbar-track{background:var(--primary-50);border-radius:6px;}::-webkit-scrollbar-thumb{background:var(--primary-300);border-radius:6px;}
    </style>
</head>
<body class="antialiased text-gray-800">
<div id="particles"></div>
<div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>

<!-- Navbar -->
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
                        <p class="text-xs flex items-center justify-end" style="color:var(--primary-500)"><i class="fas fa-circle text-green-500 text-[6px] mr-1 animate-pulse"></i>Petugas</p>
                    </div>
                    <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center text-white font-bold shadow-md hover:scale-110 transition-transform">
                        <?php echo strtoupper(substr($_SESSION['nama_lengkap'],0,1)); ?>
                    </div>
                </div>
                <a href="../auth/logout.php" onclick="return confirm('Yakin ingin keluar?')" class="text-sm py-2 px-4 flex items-center space-x-2 rounded-xl border transition-all hover:scale-105" style="color:var(--primary-600);border-color:var(--primary-200)">
                    <i class="fas fa-sign-out-alt"></i><span class="hidden sm:inline">Keluar</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="flex relative">
    <!-- Sidebar -->
    <aside class="w-72 bg-white border-r min-h-screen shadow-xl relative overflow-hidden" style="border-color:var(--primary-100)">
        <div class="absolute top-0 left-0 w-full h-1 gradient-bg"></div>
        <div class="p-6 relative z-10">
            <div class="gradient-bg rounded-2xl p-6 mb-6 text-white shadow-xl relative overflow-hidden group" data-aos="fade-right">
                <div class="absolute top-0 right-0 w-40 h-40 bg-white opacity-5 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-700"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-5 rounded-full -ml-8 -mb-8 group-hover:scale-150 transition-transform duration-700"></div>
                <div class="flex items-center space-x-4 relative z-10">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center text-2xl font-bold border-2 border-white/30 group-hover:scale-110 transition-transform">
                        <?php echo strtoupper(substr($_SESSION['nama_lengkap'],0,1)); ?>
                    </div>
                    <div>
                        <p class="text-white/80 text-sm font-light">Selamat datang,</p>
                        <p class="text-xl font-bold group-hover:translate-x-1 transition-transform"><?php echo $_SESSION['nama_lengkap']; ?></p>
                        <p class="text-white/80 text-xs mt-1 flex items-center"><i class="fas fa-circle text-green-300 text-[6px] mr-2 animate-pulse"></i>Sedang Aktif</p>
                    </div>
                </div>
            </div>
            <nav class="space-y-1">
                <p class="text-xs uppercase tracking-wider mb-3 px-4 font-semibold slide-in-left" style="color:var(--primary-300)">Menu Utama</p>
                <a href="dashboard.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group hover:bg-blue-50" style="color:var(--primary-700)">
                    <i class="fas fa-home w-6 group-hover:scale-110 transition-transform" style="color:var(--primary-400)"></i>
                    <span class="ml-3 group-hover:translate-x-1 transition-transform">Beranda</span>
                </a>
                <a href="data_barang.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group hover:bg-blue-50" style="color:var(--primary-700)">
                    <i class="fas fa-box w-6 group-hover:scale-110 transition-transform" style="color:var(--primary-400)"></i>
                    <span class="ml-3 group-hover:translate-x-1 transition-transform">Data Barang</span>
                </a>
                <a href="kelola_lelang.php" class="flex items-center px-4 py-3.5 gradient-bg text-white rounded-xl shadow-lg transition-all duration-200 group relative overflow-hidden">
                    <i class="fas fa-gavel w-6 text-white"></i>
                    <span class="ml-3 font-medium">Kelola Lelang</span>
                    <i class="fas fa-chevron-right ml-auto text-sm opacity-0 group-hover:opacity-100 transition-all"></i>
                    <span class="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 transition-opacity"></span>
                </a>
                <a href="pembayaran.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group hover:bg-blue-50" style="color:var(--primary-700)">
                    <i class="fas fa-credit-card w-6 group-hover:scale-110 transition-transform" style="color:var(--primary-400)"></i>
                    <span class="ml-3 group-hover:translate-x-1 transition-transform">Pembayaran</span>
                </a>
                <a href="laporan.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group hover:bg-blue-50" style="color:var(--primary-700)">
                    <i class="fas fa-chart-bar w-6 group-hover:scale-110 transition-transform" style="color:var(--primary-400)"></i>
                    <span class="ml-3 group-hover:translate-x-1 transition-transform">Laporan</span>
                </a>
            </nav>
        </div>
        <div class="absolute bottom-6 left-6 right-6">
            <div class="rounded-xl p-4 border hover:shadow-lg transition-all hover:scale-105 group" style="background:var(--primary-50);border-color:var(--primary-100)">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm group-hover:rotate-12 transition-transform"><i class="fas fa-headset text-lg" style="color:var(--primary-500)"></i></div>
                    <div><p class="text-sm font-semibold" style="color:var(--primary-800)">Butuh Bantuan?</p><p class="text-xs flex items-center" style="color:var(--primary-500)"><i class="fas fa-envelope mr-1"></i>admin@lelang.com</p></div>
                </div>
                <div class="mt-3 pt-3" style="border-top:1px solid var(--primary-100)">
                    <p class="text-xs text-center" style="color:var(--primary-400)"><i class="fas fa-circle text-green-500 text-[6px] mr-1 animate-pulse"></i>Dukungan 24/7</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-8" style="background:#f8fafc">
        <!-- Back Button -->
        <a href="kelola_lelang.php" class="inline-flex items-center mb-6 text-sm font-semibold transition-all hover:scale-105 px-4 py-2 rounded-xl group" style="color:var(--primary-600);background:var(--primary-50);border:1px solid var(--primary-200)" data-aos="fade-right">
            <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform"></i>Kembali ke Kelola Lelang
        </a>

        <!-- Header Banner -->
        <div class="mb-8 relative overflow-hidden rounded-3xl" data-aos="fade-down">
            <div class="gradient-bg rounded-3xl p-8 text-white shadow-xl relative">
                <div class="absolute top-0 right-0 w-80 h-80 bg-white opacity-5 rounded-full -mr-20 -mt-20 animate-float"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-white opacity-5 rounded-full -ml-16 -mb-16 animate-float" style="animation-delay:1s"></div>
                <div class="absolute top-1/2 left-1/2 w-40 h-40 bg-white opacity-5 rounded-full animate-ping"></div>
                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
                    <div class="slide-in-left">
                        <p class="text-blue-200 text-sm mb-1"><i class="fas fa-gavel mr-1"></i>Lelang #<?php echo $id_lelang; ?></p>
                        <h1 class="text-3xl lg:text-4xl font-bold mb-2"><?php echo htmlspecialchars($lelang['nama_barang']); ?></h1>
                        <p class="text-blue-100 max-w-xl"><?php echo htmlspecialchars($lelang['deskripsi_barang']); ?></p>
                        <p class="text-blue-100 text-sm mt-2 flex items-center">
                            <i class="fas fa-clock mr-2 spin-slow"></i><span id="liveClock"><?php echo date('H:i:s'); ?> WIB</span>
                        </p>
                    </div>
                    <div class="mt-4 md:mt-0">
                        <?php if($lelang['status']=='dibuka'): ?>
                            <div class="bg-white/10 backdrop-blur rounded-2xl px-6 py-4 border border-white/20 hover:bg-white/20 transition-all hover:scale-105">
                                <p class="text-sm text-blue-100">Status Lelang</p>
                                <div class="flex items-center mt-1"><span class="w-3 h-3 bg-green-400 rounded-full mr-2 animate-pulse"></span><span class="text-xl font-bold">Aktif</span></div>
                            </div>
                        <?php else: ?>
                            <div class="bg-white/10 backdrop-blur rounded-2xl px-6 py-4 border border-white/20">
                                <p class="text-sm text-blue-100">Status Lelang</p>
                                <div class="flex items-center mt-1"><i class="fas fa-lock mr-2"></i><span class="text-xl font-bold">Ditutup</span></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:var(--primary-50)"><i class="fas fa-tag text-2xl" style="color:var(--primary-600)"></i></div>
                    <span class="text-2xl font-bold" style="color:var(--primary-800)"><?php echo formatRupiah($lelang['harga_awal']); ?></span>
                </div>
                <h3 class="text-gray-600 font-medium">Harga Awal</h3>
                <p class="text-xs mt-2" style="color:var(--primary-500)">Harga pembukaan lelang</p>
            </div>
            <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:var(--primary-50)"><i class="fas fa-chart-line text-2xl" style="color:var(--primary-600)"></i></div>
                    <span class="text-2xl font-bold" style="color:var(--primary-700)"><?php echo formatRupiah($lelang['harga_akhir']); ?></span>
                </div>
                <h3 class="text-gray-600 font-medium">Harga Tertinggi</h3>
                <div class="flex items-center mt-3 text-sm"><span class="flex items-center px-3 py-1.5 rounded-full hover:scale-105 transition-transform" style="background:var(--primary-50);color:var(--primary-700)"><i class="fas fa-arrow-up mr-1 animate-bounce"></i>Penawaran tertinggi</span></div>
            </div>
            <div class="stat-card" data-aos="fade-up" data-aos-delay="300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:var(--primary-50)"><i class="fas fa-users text-2xl" style="color:var(--primary-600)"></i></div>
                    <div class="text-right">
                        <span class="text-3xl font-bold counter" style="color:var(--primary-800)" data-target="<?php echo $total_penawaran; ?>">0</span>
                        <span class="text-xs block mt-1 text-green-600 flex items-center justify-end"><i class="fas fa-check-circle mr-1"></i>Terverifikasi</span>
                    </div>
                </div>
                <h3 class="text-gray-600 font-medium">Total Penawaran</h3>
                <div class="flex items-center mt-3 text-sm"><span class="flex items-center px-3 py-1.5 rounded-full hover:scale-105 transition-transform" style="background:#dcfce7;color:#15803d"><span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>Aktif berjalan</span></div>
            </div>
        </div>

        <!-- History Table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border hover-glow" style="border-color:var(--primary-100)" data-aos="zoom-in">
            <div class="p-6 border-b" style="border-color:var(--primary-100);background:linear-gradient(to right,var(--primary-50),white)">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold flex items-center" style="color:var(--primary-800)">
                            <i class="fas fa-history mr-2 spin-slow" style="color:var(--primary-500)"></i>Riwayat Penawaran
                        </h2>
                        <p class="text-sm mt-1" style="color:var(--primary-500)">Diurutkan berdasarkan penawaran tertinggi</p>
                    </div>
                    <span class="text-sm px-3 py-1.5 rounded-full font-semibold animate-pulse-soft" style="background:var(--primary-100);color:var(--primary-600)"><?php echo $total_penawaran; ?> penawaran</span>
                </div>
            </div>
            <div class="overflow-x-auto" style="padding:0 1rem">
                <table class="w-full modern-table">
                    <thead>
                        <tr>
                            <th class="px-4 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Peringkat</th>
                            <th class="px-4 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Nama Penawar</th>
                            <th class="px-4 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Nominal Penawaran</th>
                            <th class="px-4 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no=1; $found=false; while($h=mysqli_fetch_assoc($history)): $found=true; ?>
                        <tr class="<?php echo $no==1?'winner-row':''; ?>" style="animation-delay:<?php echo ($no-1)*0.05; ?>s">
                            <td class="px-4 py-4">
                                <?php if($no==1): ?>
                                    <div class="flex items-center">
                                        <span class="w-9 h-9 gradient-bg rounded-xl flex items-center justify-center text-white font-bold text-sm mr-2">1</span>
                                        <span class="winner-badge animate-pulse-soft">Tertinggi</span>
                                    </div>
                                <?php else: ?>
                                    <span class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-sm inline-flex" style="background:var(--primary-50);color:var(--primary-600)"><?php echo $no; ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 <?php echo $no==1?'gradient-bg':''; ?> rounded-xl flex items-center justify-center font-bold text-sm mr-3 <?php echo $no==1?'text-white':''; ?>" style="<?php echo $no!=1?'background:var(--primary-100);color:var(--primary-700)':''; ?>">
                                        <?php echo strtoupper(substr($h['nama_lengkap'],0,1)); ?>
                                    </div>
                                    <span class="font-semibold" style="color:var(--primary-800)"><?php echo htmlspecialchars($h['nama_lengkap']); ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="font-bold <?php echo $no==1?'text-lg':''; ?>" style="color:var(--primary-700)"><?php echo formatRupiah($h['penawaran_harga']); ?></span>
                                <?php if($no==1): ?><span class="ml-2 text-xs text-green-600 flex items-center inline-flex"><i class="fas fa-crown mr-1"></i>Tertinggi</span><?php endif; ?>
                            </td>
                            <td class="px-4 py-4 text-gray-500 text-sm">
                                <i class="far fa-clock mr-1"></i><?php echo date('d/m/Y H:i', strtotime($h['created_at'])); ?>
                            </td>
                        </tr>
                        <?php $no++; endwhile; ?>
                        <?php if(!$found): ?>
                        <tr>
                            <td colspan="4" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-20 h-20 rounded-full flex items-center justify-center mb-4 animate-float" style="background:var(--primary-50)">
                                        <i class="fas fa-inbox text-4xl" style="color:var(--primary-300)"></i>
                                    </div>
                                    <p class="font-semibold text-lg" style="color:var(--primary-600)">Belum ada penawaran</p>
                                    <p class="text-sm text-gray-500 mt-1">Penawaran akan muncul di sini ketika peserta mulai menawar</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t text-center" style="background:var(--primary-50);border-color:var(--primary-100)">
                <p class="text-sm" style="color:var(--primary-400)">
                    <i class="fas fa-sync-alt mr-1 spin-slow"></i>Data diurutkan berdasarkan penawaran tertinggi • Terakhir: <span id="lastUpdate"><?php echo date('H:i:s'); ?></span> WIB
                </p>
            </div>
        </div>
    </main>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({duration:800,once:false,mirror:true,offset:50,easing:'ease-out-cubic'});
    function createParticles(){const c=document.getElementById('particles');for(let i=0;i<20;i++){const p=document.createElement('div');p.className='particle';p.style.left=Math.random()*100+'%';p.style.animationDuration=(Math.random()*10+10)+'s';p.style.animationDelay=Math.random()*5+'s';p.style.width=p.style.height=(Math.random()*6+2)+'px';c.appendChild(p);}}
    createParticles();
    document.querySelectorAll('.counter').forEach(el=>{const t=parseInt(el.getAttribute('data-target'));const step=Math.max(t/125,1);let curr=0;const u=()=>{curr+=step;if(curr<t){el.textContent=Math.floor(curr).toLocaleString('id-ID');requestAnimationFrame(u);}else{el.textContent=t.toLocaleString('id-ID');}};u();});
    function updateClock(){const now=new Date();const t=now.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit',second:'2-digit'});const el=document.getElementById('liveClock');const lu=document.getElementById('lastUpdate');if(el)el.textContent=t+' WIB';if(lu)lu.textContent=t;}
    setInterval(updateClock,1000);
</script>
</body>
</html>