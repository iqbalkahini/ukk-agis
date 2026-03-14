<?php
session_start();
require_once('../config/config.php');
checkLevel([1]); // Hanya admin yang bisa akses halaman ini

// Handle delete
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Jangan hapus jika masih punya data pembayaran/lelang
    $check = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_pembayaran WHERE id_user = $id");
    $cek = mysqli_fetch_assoc($check);
    if($cek['total'] > 0) {
        echo "<script>alert('User tidak dapat dihapus karena memiliki riwayat pembayaran!'); window.location='data_user.php';</script>";
        exit;
    }
    mysqli_query($conn, "DELETE FROM tb_user WHERE id_user = $id");
    header('Location: data_user.php'); exit;
}

// Handle tambah user
if(isset($_POST['tambah_user'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $level = (int)$_POST['id_level'];
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'aktif');

    // Cek username duplikat
    $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_user FROM tb_user WHERE username='$username'"));
    if($cek) {
        echo "<script>alert('Username sudah digunakan!'); window.location='data_user.php';</script>";
        exit;
    }
    mysqli_query($conn, "INSERT INTO tb_user (nama_lengkap, username, email, no_hp, password, id_level, status) VALUES ('$nama','$username','$email','$no_hp','$password','$level','$status')");
    header('Location: data_user.php'); exit;
}

// Handle update user
if(isset($_POST['update_user'])) {
    $id = (int)$_POST['id_user'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $level = (int)$_POST['id_level'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $pass_query = "";
    if(!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $pass_query = ", password='$password'";
    }
    mysqli_query($conn, "UPDATE tb_user SET nama_lengkap='$nama', username='$username', email='$email', no_hp='$no_hp', id_level='$level', status='$status'$pass_query WHERE id_user=$id");
    header('Location: data_user.php'); exit;
}

// Edit mode
$edit_data = null;
if(isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $edit_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tb_user WHERE id_user=$id"));
}

// Search & Filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_level = isset($_GET['level']) ? (int)$_GET['level'] : 0;
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

$where = "WHERE 1=1";
if($search) $where .= " AND (nama_lengkap LIKE '%$search%' OR username LIKE '%$search%' OR email LIKE '%$search%')";
if($filter_level) $where .= " AND id_level = $filter_level";
if($filter_status) $where .= " AND status = '$filter_status'";

$users = mysqli_query($conn, "SELECT * FROM tb_user $where ORDER BY id_user DESC");

// Statistics
$total_user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_user"))['total'];
$total_peserta = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_user WHERE id_level = 3"))['total'];
$total_petugas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_user WHERE id_level = 2"))['total'];
$total_lelang = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_lelang"))['total'];
$lelang_aktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_lelang WHERE status='dibuka'"))['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data User - Sistem Lelang Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary-50:#eef2f8; --primary-100:#d9e2f0; --primary-200:#b3c5e1;
            --primary-300:#8da8d2; --primary-400:#678bc3; --primary-500:#416eb4;
            --primary-600:#2a4f8c; --primary-700:#1e3a66; --primary-800:#132a4a;
        }
        body { background: linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%); min-height:100vh; overflow-x:hidden; }
        .gradient-bg { background:linear-gradient(135deg,var(--primary-700),var(--primary-600),var(--primary-500)); background-size:200% 200%; animation:gradientShift 8s ease infinite; }
        @keyframes gradientShift{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
        .glass { background:rgba(255,255,255,0.85); backdrop-filter:blur(12px); -webkit-backdrop-filter:blur(12px); border:1px solid rgba(65,110,180,0.15); }
        .stat-card { background:white; border-radius:24px; padding:1.5rem; box-shadow:0 10px 25px -5px rgba(30,58,102,0.08); border:1px solid var(--primary-100); transition:all 0.4s cubic-bezier(0.175,0.885,0.32,1.275); position:relative; overflow:hidden; }
        .stat-card::before { content:''; position:absolute; top:0; left:0; right:0; height:4px; background:linear-gradient(90deg,var(--primary-400),var(--primary-600)); transform:scaleX(0); transition:transform 0.4s ease; }
        .stat-card:hover::before { transform:scaleX(1); }
        .stat-card:hover { transform:translateY(-8px) scale(1.02); box-shadow:0 25px 35px -8px rgba(30,58,102,0.2); }
        .animate-float { animation:float 3s ease-in-out infinite; }
        @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
        .animate-pulse-soft { animation:pulseSoft 2s infinite; }
        @keyframes pulseSoft{0%,100%{opacity:1;transform:scale(1)}50%{opacity:0.7;transform:scale(1.05)}}
        .slide-in-left { animation:slideInLeft 0.5s ease-out; }
        @keyframes slideInLeft{from{opacity:0;transform:translateX(-30px)}to{opacity:1;transform:translateX(0)}}
        .typing-effect { overflow:hidden; white-space:nowrap; animation:typing 3s steps(40,end); }
        @keyframes typing{from{width:0}to{width:100%}}
        .spin-slow { animation:spin 8s linear infinite; }
        @keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
        .counter { animation:countUp 2s ease-out; }
        @keyframes countUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
        .modern-table { border-collapse:separate; border-spacing:0 10px; }
        .modern-table tbody tr { background:white; border-radius:18px; transition:all 0.3s ease; box-shadow:0 4px 12px -2px rgba(30,58,102,0.06); animation:slideInRow 0.5s ease-out; animation-fill-mode:both; }
        @keyframes slideInRow{from{opacity:0;transform:translateX(-20px)}to{opacity:1;transform:translateX(0)}}
        .modern-table tbody tr:hover { transform:scale(1.01) translateY(-2px); box-shadow:0 20px 25px -8px rgba(30,58,102,0.15); background:linear-gradient(90deg,white,var(--primary-50)); }
        .hover-glow { transition:all 0.3s ease; }
        .hover-glow:hover { box-shadow:0 0 20px rgba(65,110,180,0.3); transform:translateY(-2px); }
        .badge { padding:5px 12px; border-radius:100px; font-size:0.72rem; font-weight:600; display:inline-flex; align-items:center; transition:all 0.3s ease; }
        .badge-success { background:#e6f7e6; color:#0a5e0a; border:1px solid #a3e0a3; }
        .badge-danger { background:#ffe6e6; color:#a12323; border:1px solid #ffb8b8; }
        .badge-warning { background:#fff8e1; color:#7a5500; border:1px solid #ffe082; }
        .badge-info { background:#e0f0ff; color:#0a4fa0; border:1px solid #90c8f8; }
        .btn-primary { background:var(--primary-600); color:white; padding:0.65rem 1.4rem; border-radius:12px; font-weight:600; transition:all 0.3s ease; position:relative; overflow:hidden; }
        .btn-primary:hover { transform:translateY(-2px); box-shadow:0 12px 20px -8px rgba(30,58,102,0.4); }
        .modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,0.5); backdrop-filter:blur(4px); z-index:100; display:none; align-items:center; justify-content:center; }
        .modal-backdrop.active { display:flex; }
        .modal-box { background:white; border-radius:24px; padding:2rem; width:100%; max-width:520px; max-height:90vh; overflow-y:auto; box-shadow:0 30px 60px -15px rgba(30,58,102,0.4); animation:modalIn 0.3s ease; }
        @keyframes modalIn{from{opacity:0;transform:scale(0.92) translateY(20px)}to{opacity:1;transform:scale(1) translateY(0)}}
        .form-input { width:100%; border:1.5px solid var(--primary-200); border-radius:12px; padding:0.6rem 1rem; font-size:0.9rem; transition:all 0.2s; outline:none; }
        .form-input:focus { border-color:var(--primary-500); box-shadow:0 0 0 3px rgba(65,110,180,0.15); }
        .form-label { font-size:0.82rem; font-weight:600; color:var(--primary-700); margin-bottom:4px; display:block; }
        .progress-bar { width:0; animation:progressFill 1.5s ease-out forwards; }
        @keyframes progressFill{to{width:var(--target-width)}}
        .particle { position:fixed; width:4px; height:4px; background:var(--primary-300); border-radius:50%; opacity:0.3; animation:particleFloat 15s infinite linear; }
        @keyframes particleFloat{from{transform:translateY(100vh) rotate(0deg)}to{transform:translateY(-100vh) rotate(360deg)}}
        ::-webkit-scrollbar { width:8px; }
        ::-webkit-scrollbar-track { background:var(--primary-50); border-radius:6px; }
        ::-webkit-scrollbar-thumb { background:var(--primary-300); border-radius:6px; }
        ::-webkit-scrollbar-thumb:hover { background:var(--primary-400); }
        .avatar-circle { width:40px; height:40px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1rem; color:white; }
        select.form-input option { background:white; color:#1e3a66; }
    </style>
</head>
<body class="antialiased text-gray-800">
    <div id="particles"></div>

    <!-- Toast Container -->
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
                            <p class="text-xs flex items-center justify-end" style="color:var(--primary-500)">
                                <i class="fas fa-circle text-green-500 text-[6px] mr-1 animate-pulse"></i>Petugas
                            </p>
                        </div>
                        <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center text-white font-bold shadow-md hover:scale-110 transition-transform">
                            <?php echo strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)); ?>
                        </div>
                    </div>
                    <a href="../auth/logout.php" onclick="return confirm('Yakin ingin keluar?')" class="text-sm py-2 px-4 flex items-center space-x-2 rounded-xl border transition-all hover:scale-105" style="color:var(--primary-600);border-color:var(--primary-200)">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="hidden sm:inline">Keluar</span>
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
                <nav class="space-y-2">
                    <p class="text-xs uppercase tracking-wider text-primary-300 mb-4 px-4 font-semibold" data-aos="fade-right">Menu Utama</p>
                    
                    <a href="dashboard.php" class="flex items-center px-4 py-3 text-primary-700 hover:bg-primary-50 rounded-xl transition-all duration-200 group relative overflow-hidden">
                        <i class="fas fa-home w-6 text-primary-400 group-hover:text-primary-600 group-hover:scale-110 transition-transform"></i>
                        <span class="ml-3 group-hover:translate-x-1 transition-transform">Beranda</span>
                        <i class="fas fa-chevron-right ml-auto text-sm text-primary-600"></i>
                    </a>
                    <a href="data_barang.php" class="flex items-center px-4 py-3 bg-primary-50 text-primary-700 rounded-xl font-medium group relative overflow-hidden">
                        <i class="fas fa-box w-6 text-primary-600"></i>
                        <span class="ml-3">Data Barang</span>
                    </a>

                    <a href="laporan.php" class="flex items-center px-4 py-3 text-primary-700 hover:bg-primary-50 rounded-xl transition-all duration-200 group relative overflow-hidden">
                        <i class="fas fa-chart-bar w-6 text-primary-400 group-hover:text-primary-600 group-hover:scale-110 transition-transform"></i>
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

            <!-- Page Header Banner -->
            <div class="mb-8 relative overflow-hidden rounded-3xl" data-aos="fade-down">
                <div class="gradient-bg rounded-3xl p-8 text-white shadow-xl relative">
                    <div class="absolute top-0 right-0 w-80 h-80 bg-white opacity-5 rounded-full -mr-20 -mt-20 animate-float"></div>
                    <div class="absolute bottom-0 left-0 w-64 h-64 bg-white opacity-5 rounded-full -ml-16 -mb-16 animate-float" style="animation-delay:1s"></div>
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
                        <div class="slide-in-left">
                            <h1 class="text-3xl lg:text-4xl font-bold mb-2 typing-effect">Data User</h1>
                            <p class="text-blue-100 flex items-center text-sm">
                                <i class="fas fa-users mr-2"></i>Kelola seluruh akun pengguna sistem lelang
                            </p>
                            <p class="text-blue-100 text-sm mt-2 flex items-center">
                                <i class="fas fa-clock mr-2 animate-spin-slow"></i>
                                <span id="liveClock"><?php echo date('H:i:s'); ?> WIB</span>
                            </p>
                        </div>
                        <div class="mt-4 md:mt-0">
                            <button onclick="openModal('modalTambah')" class="bg-white/20 hover:bg-white/30 backdrop-blur border border-white/30 text-white font-semibold px-6 py-3 rounded-2xl flex items-center space-x-2 transition-all hover:scale-105 shadow-lg">
                                <i class="fas fa-user-plus"></i>
                                <span>Tambah User</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:var(--primary-50)">
                            <i class="fas fa-users text-2xl" style="color:var(--primary-600)"></i>
                        </div>
                        <div class="text-right">
                            <span class="text-3xl font-bold counter" style="color:var(--primary-800)" data-target="<?php echo $total_user; ?>">0</span>
                            <span class="text-xs text-green-600 block mt-1">Total</span>
                        </div>
                    </div>
                    <h3 class="font-medium text-gray-600">Total User</h3>
                    <div class="w-full rounded-full h-1.5 mt-3" style="background:var(--primary-100)">
                        <div class="h-1.5 rounded-full progress-bar" style="--target-width:100%;background:var(--primary-600)"></div>
                    </div>
                </div>

                <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:#e0f0ff">
                            <i class="fas fa-user-tie text-2xl" style="color:#0a4fa0"></i>
                        </div>
                        <div class="text-right">
                            <span class="text-3xl font-bold counter" style="color:#0a4fa0" data-target="<?php echo $total_petugas; ?>">0</span>
                            <span class="text-xs block mt-1" style="color:#0a4fa0">Petugas</span>
                        </div>
                    </div>
                    <h3 class="font-medium text-gray-600">Total Petugas</h3>
                    <div class="w-full rounded-full h-1.5 mt-3" style="background:#e0f0ff">
                        <div class="h-1.5 rounded-full progress-bar" style="--target-width:<?php echo $total_user > 0 ? round($total_petugas/$total_user*100) : 0; ?>%;background:#0a4fa0"></div>
                    </div>
                </div>

                <div class="stat-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:#e6f7e6">
                            <i class="fas fa-user text-2xl" style="color:#0a5e0a"></i>
                        </div>
                        <div class="text-right">
                            <span class="text-3xl font-bold counter" style="color:#0a5e0a" data-target="<?php echo $total_peserta; ?>">0</span>
                            <span class="text-xs block mt-1" style="color:#0a5e0a">Peserta</span>
                        </div>
                    </div>
                    <h3 class="font-medium text-gray-600">Total Peserta</h3>
                    <div class="w-full rounded-full h-1.5 mt-3" style="background:#e6f7e6">
                        <div class="h-1.5 rounded-full progress-bar" style="--target-width:<?php echo $total_user > 0 ? round($total_peserta/$total_user*100) : 0; ?>%;background:#0a5e0a"></div>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border hover-glow" style="border-color:var(--primary-100)" data-aos="zoom-in">
                <!-- Table Header -->
                <div class="p-6 border-b" style="border-color:var(--primary-100);background:linear-gradient(to right,var(--primary-50),white)">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold" style="color:var(--primary-800)">
                                <i class="fas fa-users mr-2 animate-pulse-soft" style="color:var(--primary-500)"></i>Daftar User
                            </h2>
                            <p class="text-sm mt-1" style="color:var(--primary-500)">Manajemen seluruh pengguna sistem</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Search -->
                            <form method="GET" class="flex items-center gap-2 flex-wrap">
                                <div class="relative">
                                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-sm" style="color:var(--primary-400)"></i>
                                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari user..." class="pl-9 pr-4 py-2 border rounded-xl text-sm focus:outline-none transition-all" style="border-color:var(--primary-200)">
                                </div>
                                <select name="level" class="py-2 px-3 border rounded-xl text-sm focus:outline-none bg-white" style="border-color:var(--primary-200);color:var(--primary-700)">
                                    <option value="">Semua Level</option>
                                    <option value="2" <?php echo $filter_level==2?'selected':''; ?>>Petugas</option>
                                    <option value="3" <?php echo $filter_level==3?'selected':''; ?>>Peserta</option>
                                </select>
                                <select name="status" class="py-2 px-3 border rounded-xl text-sm focus:outline-none bg-white" style="border-color:var(--primary-200);color:var(--primary-700)">
                                    <option value="">Semua Status</option>
                                    <option value="aktif" <?php echo $filter_status=='aktif'?'selected':''; ?>>Aktif</option>
                                    <option value="nonaktif" <?php echo $filter_status=='nonaktif'?'selected':''; ?>>Nonaktif</option>
                                </select>
                                <button type="submit" class="btn-primary text-sm px-4 py-2">
                                    <i class="fas fa-filter mr-1"></i>Filter
                                </button>
                                <?php if($search || $filter_level || $filter_status): ?>
                                <a href="data_user.php" class="text-sm py-2 px-3 rounded-xl border transition-all hover:scale-105" style="color:var(--primary-600);border-color:var(--primary-200)">
                                    <i class="fas fa-times mr-1"></i>Reset
                                </a>
                                <?php endif; ?>
                            </form>
                            <button onclick="openModal('modalTambah')" class="btn-primary text-sm flex items-center space-x-2">
                                <i class="fas fa-user-plus"></i>
                                <span>Tambah User</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full modern-table">
                        <thead>
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">User</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Username</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Email</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">No. HP</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Level</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $colors = ['#2a4f8c','#0a5e0a','#0a4fa0','#7a5500','#a12323','#6b21a8'];
                            $delay = 0.1;
                            if(mysqli_num_rows($users) > 0):
                                while($row = mysqli_fetch_assoc($users)):
                                    $color = $colors[ord(strtolower($row['nama_lengkap'][0])) % count($colors)];
                            ?>
                            <tr style="animation-delay:<?php echo $delay; ?>s">
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="avatar-circle" style="background:<?php echo $color; ?>">
                                            <?php echo strtoupper(substr($row['nama_lengkap'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-sm" style="color:var(--primary-800)"><?php echo htmlspecialchars($row['nama_lengkap']); ?></p>
                                            <p class="text-xs" style="color:var(--primary-400)">ID: #<?php echo $row['id_user']; ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-medium text-gray-700">@<?php echo htmlspecialchars($row['username']); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-600"><?php echo htmlspecialchars($row['email'] ?? '-'); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-600"><?php echo htmlspecialchars($row['no_hp'] ?? '-'); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if($row['id_level'] == 1): ?>
                                        <span class="badge" style="background:#f3e8ff;color:#6b21a8;border:1px solid #d8b4fe"><i class="fas fa-crown mr-1"></i>Admin</span>
                                    <?php elseif($row['id_level'] == 2): ?>
                                        <span class="badge badge-info"><i class="fas fa-user-tie mr-1"></i>Petugas</span>
                                    <?php else: ?>
                                        <span class="badge badge-success"><i class="fas fa-user mr-1"></i>Peserta</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if(($row['status'] ?? 'aktif') == 'aktif'): ?>
                                        <span class="badge badge-success"><span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5 animate-pulse-soft inline-block"></span>Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><i class="fas fa-ban mr-1"></i>Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <button onclick='openEditModal(<?php echo json_encode($row); ?>)' class="w-9 h-9 flex items-center justify-center rounded-xl border transition-all hover:scale-110 hover:shadow-md" style="color:var(--primary-600);border-color:var(--primary-200);background:var(--primary-50)" title="Edit">
                                            <i class="fas fa-edit text-sm"></i>
                                        </button>
                                        <?php if($row['id_user'] != $_SESSION['id_user']): ?>
                                        <a href="data_user.php?delete=<?php echo $row['id_user']; ?>" onclick="return confirm('Yakin ingin menghapus user <?php echo htmlspecialchars($row['nama_lengkap']); ?>?')" class="w-9 h-9 flex items-center justify-center rounded-xl border transition-all hover:scale-110 hover:shadow-md" style="color:#a12323;border-color:#ffb8b8;background:#ffe6e6" title="Hapus">
                                            <i class="fas fa-trash text-sm"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php $delay += 0.07; endwhile;
                            else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-20 h-20 rounded-full flex items-center justify-center mb-4 animate-float" style="background:var(--primary-50)">
                                            <i class="fas fa-users text-3xl" style="color:var(--primary-300)"></i>
                                        </div>
                                        <p class="font-semibold text-lg" style="color:var(--primary-600)">Belum ada data user</p>
                                        <p class="text-sm mt-1" style="color:var(--primary-400)">Tambahkan user pertama sekarang</p>
                                        <button onclick="openModal('modalTambah')" class="btn-primary text-sm mt-5 px-6 py-2.5">
                                            <i class="fas fa-user-plus mr-2"></i>Tambah User
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Table Footer -->
                <div class="p-4 border-t flex items-center justify-between" style="background:var(--primary-50);border-color:var(--primary-100)">
                    <p class="text-sm" style="color:var(--primary-600)">
                        <i class="fas fa-info-circle mr-2 animate-pulse"></i>
                        Menampilkan <?php echo mysqli_num_rows($users); ?> dari <?php echo $total_user; ?> user
                    </p>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs px-3 py-1 rounded-full" style="background:var(--primary-100);color:var(--primary-700)">
                            <i class="fas fa-users mr-1"></i><?php echo $total_user; ?> total user
                        </span>
                    </div>
                </div>
            </div>

            <!-- Footer Note -->
            <div class="mt-6 text-center text-sm" style="color:var(--primary-400)">
                <i class="fas fa-sync-alt mr-1 animate-spin-slow"></i>
                Data diperbarui secara real-time &bull; <?php echo date('H:i:s'); ?> WIB
            </div>
        </main>
    </div>

    <!-- Modal Tambah User -->
    <div id="modalTambah" class="modal-backdrop">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-bold" style="color:var(--primary-800)">
                        <i class="fas fa-user-plus mr-2" style="color:var(--primary-500)"></i>Tambah User Baru
                    </h3>
                    <p class="text-xs mt-1" style="color:var(--primary-400)">Isi data user dengan lengkap</p>
                </div>
                <button onclick="closeModal('modalTambah')" class="w-9 h-9 flex items-center justify-center rounded-xl hover:scale-110 transition-all" style="background:var(--primary-50);color:var(--primary-600)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="tambah_user" value="1">
                <div class="space-y-4">
                    <div>
                        <label class="form-label">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_lengkap" class="form-input" placeholder="Masukkan nama lengkap" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Username <span class="text-red-500">*</span></label>
                            <input type="text" name="username" class="form-input" placeholder="username" required>
                        </div>
                        <div>
                            <label class="form-label">No. HP</label>
                            <input type="text" name="no_hp" class="form-input" placeholder="08xx-xxxx-xxxx">
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" placeholder="email@contoh.com">
                    </div>
                    <div>
                        <label class="form-label">Password <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" name="password" id="passNew" class="form-input pr-10" placeholder="Minimal 6 karakter" required minlength="6">
                            <button type="button" onclick="togglePass('passNew','eyeNew')" class="absolute right-3 top-1/2 -translate-y-1/2 text-sm" style="color:var(--primary-400)">
                                <i id="eyeNew" class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Level <span class="text-red-500">*</span></label>
                            <select name="id_level" class="form-input" required>
                                <option value="">Pilih Level</option>
                                <option value="2">Petugas</option>
                                <option value="3">Peserta</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Status</label>
                            <select name="status" class="form-input">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 mt-6 pt-5 border-t" style="border-color:var(--primary-100)">
                    <button type="button" onclick="closeModal('modalTambah')" class="flex-1 py-2.5 rounded-xl border font-medium transition-all hover:scale-105" style="color:var(--primary-600);border-color:var(--primary-200)">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 btn-primary py-2.5 flex items-center justify-center space-x-2">
                        <i class="fas fa-save"></i>
                        <span>Simpan User</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit User -->
    <div id="modalEdit" class="modal-backdrop">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-bold" style="color:var(--primary-800)">
                        <i class="fas fa-user-edit mr-2" style="color:var(--primary-500)"></i>Edit User
                    </h3>
                    <p class="text-xs mt-1" style="color:var(--primary-400)">Perbarui data user</p>
                </div>
                <button onclick="closeModal('modalEdit')" class="w-9 h-9 flex items-center justify-center rounded-xl hover:scale-110 transition-all" style="background:var(--primary-50);color:var(--primary-600)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="update_user" value="1">
                <input type="hidden" name="id_user" id="editId">
                <div class="space-y-4">
                    <div>
                        <label class="form-label">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_lengkap" id="editNama" class="form-input" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Username <span class="text-red-500">*</span></label>
                            <input type="text" name="username" id="editUsername" class="form-input" required>
                        </div>
                        <div>
                            <label class="form-label">No. HP</label>
                            <input type="text" name="no_hp" id="editNoHp" class="form-input">
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="editEmail" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Password Baru <small style="color:var(--primary-400);font-weight:400">(Kosongkan jika tidak diubah)</small></label>
                        <div class="relative">
                            <input type="password" name="password" id="passEdit" class="form-input pr-10" placeholder="Password baru...">
                            <button type="button" onclick="togglePass('passEdit','eyeEdit')" class="absolute right-3 top-1/2 -translate-y-1/2 text-sm" style="color:var(--primary-400)">
                                <i id="eyeEdit" class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Level <span class="text-red-500">*</span></label>
                            <select name="id_level" id="editLevel" class="form-input" required>
                                <option value="2">Petugas</option>
                                <option value="3">Peserta</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Status</label>
                            <select name="status" id="editStatus" class="form-input">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 mt-6 pt-5 border-t" style="border-color:var(--primary-100)">
                    <button type="button" onclick="closeModal('modalEdit')" class="flex-1 py-2.5 rounded-xl border font-medium transition-all hover:scale-105" style="color:var(--primary-600);border-color:var(--primary-200)">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 btn-primary py-2.5 flex items-center justify-center space-x-2">
                        <i class="fas fa-save"></i>
                        <span>Update User</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration:800, once:false, mirror:true, offset:50, easing:'ease-out-cubic' });

        // Particles
        const pc = document.getElementById('particles');
        for(let i=0;i<18;i++){
            const p=document.createElement('div'); p.className='particle';
            p.style.left=Math.random()*100+'%';
            p.style.animationDuration=(Math.random()*10+10)+'s';
            p.style.animationDelay=Math.random()*5+'s';
            p.style.width=p.style.height=(Math.random()*5+2)+'px';
            pc.appendChild(p);
        }

        // Counters
        document.querySelectorAll('.counter').forEach(el=>{
            const target=parseInt(el.getAttribute('data-target')||0);
            const step=Math.max(target/100,1); let curr=0;
            const u=()=>{ curr+=step; if(curr<target){el.textContent=Math.floor(curr);requestAnimationFrame(u);}else{el.textContent=target;} };
            u();
        });

        // Progress bars
        document.querySelectorAll('.progress-bar').forEach(b=>{ b.style.width=b.style.getPropertyValue('--target-width'); });

        // Clock
        function updateClock(){ document.getElementById('liveClock').textContent=new Date().toLocaleTimeString('id-ID')+' WIB'; }
        setInterval(updateClock,1000);

        // Modal
        function openModal(id){ document.getElementById(id).classList.add('active'); document.body.style.overflow='hidden'; }
        function closeModal(id){ document.getElementById(id).classList.remove('active'); document.body.style.overflow=''; }
        document.querySelectorAll('.modal-backdrop').forEach(m=>{ m.addEventListener('click',e=>{ if(e.target===m) closeModal(m.id); }); });

        // Open edit modal & fill data
        function openEditModal(row){
            document.getElementById('editId').value = row.id_user;
            document.getElementById('editNama').value = row.nama_lengkap;
            document.getElementById('editUsername').value = row.username;
            document.getElementById('editEmail').value = row.email || '';
            document.getElementById('editNoHp').value = row.no_hp || '';
            document.getElementById('editLevel').value = row.id_level;
            document.getElementById('editStatus').value = row.status || 'aktif';
            openModal('modalEdit');
        }

        // Toggle password visibility
        function togglePass(inputId, iconId){
            const inp=document.getElementById(inputId); const ico=document.getElementById(iconId);
            if(inp.type==='password'){ inp.type='text'; ico.classList.replace('fa-eye','fa-eye-slash'); }
            else { inp.type='password'; ico.classList.replace('fa-eye-slash','fa-eye'); }
        }

        <?php if(isset($_GET['edit']) && $edit_data): ?>
        openEditModal(<?php echo json_encode($edit_data); ?>);
        <?php endif; ?>
    </script>
</body>
</html>