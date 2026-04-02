<?php
session_start();
require_once('../config/config.php');
checkLevel([2]);

if(isset($_GET['action']) && isset($_GET['id'])) {
    $id=(int)$_GET['id']; $action=$_GET['action'];
    if($action=='buka'){
        // Cek apakah sudah ada pembayaran lunas/selesai untuk lelang ini
        $cek_bayar = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_pembayaran WHERE id_lelang = $id AND (status_pembayaran = 'dibayar' OR status_pembayaran = 'selesai')");
        $lunas = mysqli_fetch_assoc($cek_bayar)['total'];
        
        if($lunas > 0) {
            $_SESSION['error'] = 'Lelang tidak bisa dibuka kembali karena pemenang sudah membayar!';
        } else {
            mysqli_query($conn,"UPDATE tb_lelang SET status='dibuka' WHERE id_lelang=$id");
            mysqli_query($conn,"UPDATE tb_barang SET status_barang='dibuka' WHERE id_barang=(SELECT id_barang FROM tb_lelang WHERE id_lelang=$id)");
            $_SESSION['success'] = 'Lelang berhasil dibuka kembali';
        }
    } elseif($action=='tutup'){
        mysqli_query($conn,"UPDATE tb_lelang SET status='ditutup' WHERE id_lelang=$id");
        mysqli_query($conn,"UPDATE tb_barang SET status_barang='ditutup' WHERE id_barang=(SELECT id_barang FROM tb_lelang WHERE id_lelang=$id)");
        $_SESSION['success'] = 'Lelang berhasil ditutup';
    }
    header('Location: kelola_lelang.php'); exit;
}

if(isset($_POST['create_lelang'])) {
    $id_barang=$_POST['id_barang']; $tgl=$_POST['tgl_lelang']; $id_petugas=$_SESSION['id_user'];
    $barang=mysqli_fetch_assoc(mysqli_query($conn,"SELECT harga_awal FROM tb_barang WHERE id_barang=$id_barang"));
    
    // Logika lelang terjadwal
    $status_baru = ($tgl == date('Y-m-d')) ? 'dibuka' : 'pending';
    
    mysqli_query($conn,"INSERT INTO tb_lelang (id_barang,tgl_lelang,harga_akhir,id_petugas,status) VALUES ($id_barang,'$tgl',{$barang['harga_awal']},$id_petugas,'$status_baru')");
    mysqli_query($conn,"UPDATE tb_barang SET status_barang='$status_baru' WHERE id_barang=$id_barang");
    
    $_SESSION['success'] = ($status_baru == 'dibuka') ? 'Lelang berhasil dibuka hari ini' : 'Lelang berhasil dijadwalkan (Menunggu)';
    header('Location: kelola_lelang.php'); exit;
}

$lelang=mysqli_query($conn,"SELECT l.*,b.nama_barang,b.harga_awal,b.gambar,u.nama_lengkap as pemenang, (SELECT COUNT(*) FROM tb_pembayaran p WHERE p.id_lelang = l.id_lelang AND (p.status_pembayaran = 'dibayar' OR p.status_pembayaran = 'selesai')) as sudah_bayar FROM tb_lelang l JOIN tb_barang b ON l.id_barang=b.id_barang LEFT JOIN tb_user u ON l.id_user=u.id_user ORDER BY l.id_lelang DESC");
$barang_available=mysqli_query($conn,"SELECT * FROM tb_barang WHERE status_barang='pending' OR (status_barang='ditutup' AND id_barang NOT IN (SELECT l.id_barang FROM tb_lelang l JOIN tb_pembayaran p ON l.id_lelang = p.id_lelang WHERE p.status_pembayaran IN ('dibayar', 'selesai'))) ");
$total_lelang=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM tb_lelang"))['t'];
$lelang_aktif=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM tb_lelang WHERE status='dibuka'"))['t'];
$lelang_tutup=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM tb_lelang WHERE status='ditutup'"))['t'];
$total_barang=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM tb_barang"))['t'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Lelang - Sistem Lelang Online</title>
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
        .glass:hover{background:rgba(255,255,255,0.95);border-color:rgba(65,110,180,0.3);}
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
        .progress-bar{width:0;animation:progressFill 1.5s ease-out forwards;}
        @keyframes progressFill{to{width:var(--target-width)}}
        .particle{position:fixed;width:4px;height:4px;background:var(--primary-300);border-radius:50%;opacity:0.3;animation:particleFloat 15s infinite linear;}
        @keyframes particleFloat{from{transform:translateY(100vh) rotate(0deg)}to{transform:translateY(-100vh) rotate(360deg)}}
        .badge{padding:6px 14px;border-radius:100px;font-size:0.75rem;font-weight:600;display:inline-flex;align-items:center;transition:all 0.3s ease;}
        .badge-success{background:#e6f7e6;color:#0a5e0a;border:1px solid #a3e0a3;}
        .badge-danger{background:#ffe6e6;color:#a12323;border:1px solid #ffb8b8;}
        .modern-table{border-collapse:separate;border-spacing:0 12px;}
        .modern-table tbody tr{background:white;border-radius:18px;transition:all 0.3s ease;box-shadow:0 4px 12px -2px rgba(30,58,102,0.06);animation:slideInRow 0.5s ease-out;animation-fill-mode:both;}
        @keyframes slideInRow{from{opacity:0;transform:translateX(-20px)}to{opacity:1;transform:translateX(0)}}
        .modern-table tbody tr:hover{transform:scale(1.02) translateY(-2px);box-shadow:0 20px 25px -8px rgba(30,58,102,0.15);background:linear-gradient(90deg,white,var(--primary-50));}
        .form-input{width:100%;padding:0.625rem 1rem;border:1px solid var(--primary-200);border-radius:0.75rem;transition:all 0.3s;background:white;}
        .form-input:focus{outline:none;border-color:var(--primary-500);box-shadow:0 0 0 3px rgba(65,110,180,0.1);}
        ::-webkit-scrollbar{width:8px;height:8px;}::-webkit-scrollbar-track{background:var(--primary-50);border-radius:6px;}::-webkit-scrollbar-thumb{background:var(--primary-300);border-radius:6px;}::-webkit-scrollbar-thumb:hover{background:var(--primary-400);}
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
            <!-- Profile Card -->
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
            <!-- Nav -->
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
                <div class="mt-3 pt-3" style="border-top:1px solid var(--primary-100)">
                    <p class="text-xs text-center" style="color:var(--primary-400)"><i class="fas fa-circle text-green-500 text-[6px] mr-1 animate-pulse"></i>Dukungan 24/7</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-8" style="background:#f8fafc">
        <!-- Welcome Banner -->
        <div class="mb-8 relative overflow-hidden rounded-3xl" data-aos="fade-down">
            <div class="gradient-bg rounded-3xl p-8 text-white shadow-xl relative">
                <div class="absolute top-0 right-0 w-80 h-80 bg-white opacity-5 rounded-full -mr-20 -mt-20 animate-float"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-white opacity-5 rounded-full -ml-16 -mb-16 animate-float" style="animation-delay:1s"></div>
                <div class="absolute top-1/2 left-1/2 w-40 h-40 bg-white opacity-5 rounded-full animate-ping"></div>
                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
                    <div class="slide-in-left">
                        <h1 class="text-3xl lg:text-4xl font-bold mb-2">Kelola Barang &amp; Penawaran</h1>
                        <p class="text-blue-100 flex items-center"><i class="fas fa-calendar-alt mr-2"></i><?php
                $hari_id = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
                $bulan_id = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April','May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus','September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
                $tgl_id = date('l, d F Y');
                $tgl_id = str_replace(array_keys($hari_id), array_values($hari_id), $tgl_id);
                $tgl_id = str_replace(array_keys($bulan_id), array_values($bulan_id), $tgl_id);
                echo $tgl_id;
              ?></p>
                        <p class="text-blue-100 text-sm mt-2 flex items-center"><i class="fas fa-clock mr-2 spin-slow"></i><span id="liveClock"><?php echo date('H:i:s'); ?> WIB</span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:var(--primary-50)"><i class="fas fa-list text-2xl" style="color:var(--primary-600)"></i></div>
                    <div class="text-right"><span class="text-3xl font-bold counter" style="color:var(--primary-800)" data-target="<?php echo $total_lelang; ?>">0</span><span class="text-xs block mt-1" style="color:var(--primary-500)">Total</span></div>
                </div>
                <h3 class="text-gray-600 font-medium">Total Lelang</h3>
                <div class="w-full rounded-full h-1.5 mt-3" style="background:var(--primary-100)"><div class="h-1.5 rounded-full progress-bar" style="--target-width:80%;background:var(--primary-600)"></div></div>
                <p class="text-xs mt-2" style="color:var(--primary-600)">Semua periode</p>
            </div>
            <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:#dcfce7"><i class="fas fa-play-circle text-2xl text-green-600"></i></div>
                    <div class="text-right"><span class="text-3xl font-bold counter text-green-700" data-target="<?php echo $lelang_aktif; ?>">0</span><span class="text-xs text-green-600 block mt-1 flex items-center justify-end"><i class="fas fa-arrow-up mr-1 animate-bounce"></i>Aktif</span></div>
                </div>
                <h3 class="text-gray-600 font-medium">Lelang Aktif</h3>
                <div class="flex items-center mt-3 text-sm"><span class="text-green-700 flex items-center px-3 py-1.5 rounded-full hover:scale-105 transition-transform" style="background:#dcfce7"><span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>Sedang berlangsung</span></div>
            </div>
            <div class="stat-card" data-aos="fade-up" data-aos-delay="300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:#fee2e2"><i class="fas fa-stop-circle text-2xl text-red-500"></i></div>
                    <div class="text-right"><span class="text-3xl font-bold counter text-red-600" data-target="<?php echo $lelang_tutup; ?>">0</span><span class="text-xs text-red-500 block mt-1">Selesai</span></div>
                </div>
                <h3 class="text-gray-600 font-medium">Lelang Ditutup</h3>
                <div class="flex items-center mt-3 text-sm"><span class="px-3 py-1.5 rounded-full hover:scale-105 transition-transform" style="background:#fee2e2;color:#dc2626">Sudah ditutup</span></div>
            </div>
        </div>

        <!-- Form Buka Lelang -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8 border hover-glow" style="border-color:var(--primary-100)" data-aos="fade-up">
            <h2 class="text-xl font-bold mb-5 flex items-center" style="color:var(--primary-800)">
                <i class="fas fa-plus-circle mr-2" style="color:var(--primary-500)"></i>Buka Lelang Baru
            </h2>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label class="block text-sm font-semibold mb-2" style="color:var(--primary-700)">Pilih Barang</label>
                    <select name="id_barang" required class="form-input">
                        <option value="">-- Pilih Barang --</option>
                        <?php while($b=mysqli_fetch_assoc($barang_available)): ?>
                        <option value="<?php echo $b['id_barang']; ?>"><?php echo htmlspecialchars($b['nama_barang']); ?> (<?php echo formatRupiah($b['harga_awal']); ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2" style="color:var(--primary-700)">Tanggal Lelang</label>
                    <input type="date" name="tgl_lelang" required class="form-input">
                </div>
                <div class="flex items-end">
                    <button type="submit" name="create_lelang" class="w-full gradient-bg text-white px-6 py-3 rounded-xl font-semibold transition-all hover:scale-105 hover:shadow-lg flex items-center justify-center">
                        <i class="fas fa-play mr-2"></i>Buka Lelang
                    </button>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border hover-glow" style="border-color:var(--primary-100)" data-aos="zoom-in">
            <div class="p-6 border-b" style="border-color:var(--primary-100);background:linear-gradient(to right,var(--primary-50),white)">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-xl font-bold flex items-center" style="color:var(--primary-800)">
                            <i class="fas fa-history mr-2 spin-slow" style="color:var(--primary-500)"></i>Daftar Lelang
                        </h2>
                        <p class="text-sm mt-1" style="color:var(--primary-500)">Semua data lelang tercatat dalam sistem</p>
                    </div>
                    <div class="relative mt-3 md:mt-0 group">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-sm group-hover:scale-110 transition-transform" style="color:var(--primary-400)"></i>
                        <input type="text" id="searchLelang" placeholder="Cari lelang..." class="pl-9 pr-4 py-2 border rounded-xl text-sm focus:outline-none transition-all" style="border-color:var(--primary-200)">
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto" style="padding:0 1rem">
                <table class="w-full modern-table">
                    <thead>
                        <tr>
                            <th class="px-4 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">ID</th>
                            <th class="px-4 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Nama Barang</th>
                            <th class="px-4 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Harga Awal</th>
                            <th class="px-4 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Harga Akhir</th>
                            <th class="px-4 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Pemenang</th>
                            <th class="px-4 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Status</th>
                            <th class="px-4 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Aksi</th>
                            <th class="px-4 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Detail</th>
                        </tr>
                    </thead>
                    <tbody id="lelangTableBody">
                        <?php $delay=0.05; while($row=mysqli_fetch_assoc($lelang)): ?>
                        <tr style="animation-delay:<?php echo $delay; ?>s">
                            <td class="px-4 py-4"><span class="font-bold text-sm" style="color:var(--primary-700)">#<?php echo $row['id_lelang']; ?></span></td>
                            <td class="px-4 py-4">
                                <div class="flex items-center">
                                    <?php $image_url = resolveBarangImageUrl($row['gambar'] ?? ''); ?>
                                    <?php if ($image_url): ?>
                                        <img src="<?php echo $image_url; ?>" alt="" class="w-10 h-10 rounded-xl object-cover mr-3 border" style="border-color:var(--primary-100)">
                                    <?php else: ?>
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center mr-3" style="background:var(--primary-100);color:var(--primary-700)"><i class="fas fa-box"></i></div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="font-semibold" style="color:var(--primary-800)"><?php echo htmlspecialchars($row['nama_barang']); ?></p>
                                        <p class="text-xs" style="color:var(--primary-500)">Tgl: <?php echo date('d/m/Y', strtotime($row['tgl_lelang'])); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-gray-600"><?php echo formatRupiah($row['harga_awal']); ?></td>
                            <td class="px-4 py-4 font-semibold" style="color:var(--primary-700)"><?php echo formatRupiah($row['harga_akhir']); ?></td>
                            <td class="px-4 py-4">
                                <?php if($row['pemenang']): ?>
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 gradient-bg rounded-lg flex items-center justify-center text-white text-xs font-bold mr-2"><?php echo strtoupper(substr($row['pemenang'],0,1)); ?></div>
                                        <span class="text-sm" style="color:var(--primary-800)"><?php echo htmlspecialchars($row['pemenang']); ?></span>
                                    </div>
                                <?php else: ?><span class="text-gray-400">-</span><?php endif; ?>
                            </td>
                            <td class="px-4 py-4">
                                <?php if($row['status']=='dibuka'): ?>
                                    <span class="badge badge-success"><span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>Dibuka</span>
                                <?php elseif($row['status']=='pending'): ?>
                                    <span class="badge" style="background:#fff7ed;color:#c2410c;border:1px solid #fdba74"><i class="fas fa-clock mr-1"></i>Menunggu</span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><i class="fas fa-lock mr-1"></i>Ditutup</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4">
                                <?php if($row['status']=='dibuka'): ?>
                                    <a href="?action=tutup&id=<?php echo $row['id_lelang']; ?>" onclick="return confirm('Yakin ingin menutup lelang?')" class="flex items-center text-sm font-semibold transition-all hover:scale-105 px-3 py-1.5 rounded-xl" style="background:#fee2e2;color:#dc2626">
                                        <i class="fas fa-stop-circle mr-1"></i>Tutup
                                    </a>
                                <?php else: ?>
                                    <?php if($row['sudah_bayar'] > 0): ?>
                                        <button disabled class="flex items-center text-sm font-semibold px-3 py-1.5 rounded-xl opacity-60 cursor-not-allowed" style="background:#f1f5f9;color:#64748b" title="Pemenang sudah membayar">
                                            <i class="fas fa-check-double mr-1"></i>Lunas
                                        </button>
                                    <?php else: ?>
                                        <a href="?action=buka&id=<?php echo $row['id_lelang']; ?>" class="flex items-center text-sm font-semibold transition-all hover:scale-105 px-3 py-1.5 rounded-xl" style="background:#dcfce7;color:#15803d">
                                            <i class="fas fa-play-circle mr-1"></i>Buka
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4">
                                <a href="detail_lelang_petugas.php?id=<?php echo $row['id_lelang']; ?>" class="flex items-center text-sm font-semibold transition-all hover:scale-105 px-3 py-1.5 rounded-xl group" style="background:var(--primary-100);color:var(--primary-700)">
                                    <i class="fas fa-eye mr-1"></i>Detail<i class="fas fa-arrow-right ml-1 text-xs group-hover:translate-x-1 transition-transform"></i>
                                </a>
                            </td>
                        </tr>
                        <?php $delay+=0.05; endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t" style="background:var(--primary-50);border-color:var(--primary-100)">
                <p class="text-sm text-center" style="color:var(--primary-400)">
                    <i class="fas fa-sync-alt mr-1 spin-slow"></i>Total <?php echo $total_lelang; ?> lelang tercatat • Terakhir: <span id="lastUpdate"><?php echo date('H:i:s'); ?></span> WIB
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
    document.querySelectorAll('.progress-bar').forEach(b=>{b.style.width=b.style.getPropertyValue('--target-width');});
    function updateClock(){const now=new Date();const t=now.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit',second:'2-digit'});const el=document.getElementById('liveClock');const lu=document.getElementById('lastUpdate');if(el)el.textContent=t+' WIB';if(lu)lu.textContent=t;}
    setInterval(updateClock,1000);
    document.getElementById('searchLelang')?.addEventListener('input',function(){const s=this.value.toLowerCase();document.querySelectorAll('#lelangTableBody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(s)?'':'none';});});

    function showToast(message, type) {
        const container = document.getElementById('toastContainer');
        if (!container) return;
        const toast = document.createElement('div');
        const colors = type === 'error' ? 'bg-red-50 text-red-800 border-red-200' : 'bg-green-50 text-green-800 border-green-200';
        const icon = type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check';
        toast.className = `flex items-center p-4 rounded-xl shadow-lg text-sm border ${colors} animate__animated animate__fadeInRight mb-2`;
        toast.style.minWidth = "300px";
        toast.innerHTML = `<i class="fas ${icon} mr-3 text-lg"></i><span>${message}</span>`;
        container.appendChild(toast);
        setTimeout(() => {
            toast.classList.replace('animate__fadeInRight', 'animate__fadeOutRight');
            setTimeout(() => toast.remove(), 500);
        }, 4000);
    }
</script>

<?php if (isset($_SESSION['success'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast('<?php echo addslashes($_SESSION['success']); ?>', 'success'));</script>
<?php unset($_SESSION['success']); endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast('<?php echo addslashes($_SESSION['error']); ?>', 'error'));</script>
<?php unset($_SESSION['error']); endif; ?>
</body>
</html>
