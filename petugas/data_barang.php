<?php
session_start();
require_once('../config/config.php');
checkLevel([2]);

if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $check = mysqli_query($conn, "SELECT * FROM tb_lelang WHERE id_barang = $id AND status = 'dibuka'");
    if(mysqli_num_rows($check) > 0) {
        echo "<script>alert('Barang sedang dalam lelang aktif!'); window.location='data_barang.php';</script>";
        exit;
    }
    $barang = mysqli_fetch_assoc(mysqli_query($conn, "SELECT gambar FROM tb_barang WHERE id_barang = $id"));
    if($barang['gambar'] && file_exists('../uploads/' . $barang['gambar'])) unlink('../uploads/' . $barang['gambar']);
    mysqli_query($conn, "DELETE FROM tb_barang WHERE id_barang = $id");
    header('Location: data_barang.php'); exit;
}

if(isset($_POST['tambah_barang'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $tgl = $_POST['tgl']; $harga = $_POST['harga_awal'];
    $desk = mysqli_real_escape_string($conn, $_POST['deskripsi_barang']);
    $gambar_value = "";
    if(isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, ['jpg','jpeg','png','gif'])) {
            if(!is_dir('../uploads')) mkdir('../uploads', 0777, true);
            $gambar = time() . '_' . $_FILES['gambar']['name']; $gambar_value = $gambar;
            move_uploaded_file($_FILES['gambar']['tmp_name'], '../uploads/' . $gambar);
        }
    }
    if($gambar_value)
        mysqli_query($conn, "INSERT INTO tb_barang (nama_barang,tgl,harga_awal,deskripsi_barang,gambar,status_barang) VALUES ('$nama','$tgl','$harga','$desk','$gambar_value','pending')");
    else
        mysqli_query($conn, "INSERT INTO tb_barang (nama_barang,tgl,harga_awal,deskripsi_barang,status_barang) VALUES ('$nama','$tgl','$harga','$desk','pending')");
    header('Location: data_barang.php'); exit;
}

if(isset($_POST['update_barang'])) {
    $id = $_POST['id_barang']; $nama = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $tgl = $_POST['tgl']; $harga = $_POST['harga_awal'];
    $desk = mysqli_real_escape_string($conn, $_POST['deskripsi_barang']);
    $gambar_query = "";
    if(isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, ['jpg','jpeg','png','gif'])) {
            if(!is_dir('../uploads')) mkdir('../uploads', 0777, true);
            $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT gambar FROM tb_barang WHERE id_barang=$id"));
            if($old['gambar'] && file_exists('../uploads/' . $old['gambar'])) unlink('../uploads/' . $old['gambar']);
            $gambar = time() . '_' . $_FILES['gambar']['name'];
            move_uploaded_file($_FILES['gambar']['tmp_name'], '../uploads/' . $gambar);
            $gambar_query = ", gambar='$gambar'";
        }
    }
    mysqli_query($conn, "UPDATE tb_barang SET nama_barang='$nama',tgl='$tgl',harga_awal='$harga',deskripsi_barang='$desk'$gambar_query WHERE id_barang=$id");
    header('Location: data_barang.php'); exit;
}

$edit_data = null;
if(isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $edit_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tb_barang WHERE id_barang=$id"));
}

$barang = mysqli_query($conn, "SELECT * FROM tb_barang ORDER BY id_barang DESC");
$total_barang = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_barang"))['total'];
$lelang_aktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_lelang WHERE status='dibuka'"))['total'];
$total_lelang = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_lelang"))['total'];
$pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM tb_barang WHERE status_barang='pending'"))['t'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang - Sistem Lelang Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary-50:#eef2f8;--primary-100:#d9e2f0;--primary-200:#b3c5e1;--primary-300:#8da8d2;--primary-400:#678bc3;--primary-500:#416eb4;--primary-600:#2a4f8c;--primary-700:#1e3a66;--primary-800:#132a4a; }
        body { background: linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%); min-height:100vh; overflow-x:hidden; }
        .gradient-bg { background:linear-gradient(135deg,var(--primary-700),var(--primary-600),var(--primary-500));background-size:200% 200%;animation:gradientShift 8s ease infinite; }
        @keyframes gradientShift{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
        .glass { background:rgba(255,255,255,0.85);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border:1px solid rgba(65,110,180,0.15); }
        .stat-card { background:white;border-radius:24px;padding:1.5rem;box-shadow:0 10px 25px -5px rgba(30,58,102,0.08);border:1px solid var(--primary-100);transition:all 0.4s cubic-bezier(0.175,0.885,0.32,1.275);position:relative;overflow:hidden; }
        .stat-card::before { content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,var(--primary-400),var(--primary-600));transform:scaleX(0);transition:transform 0.4s ease; }
        .stat-card:hover::before { transform:scaleX(1); }
        .stat-card:hover { transform:translateY(-8px) scale(1.02);box-shadow:0 25px 35px -8px rgba(30,58,102,0.2); }
        .animate-float { animation:float 3s ease-in-out infinite; }
        @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
        .animate-pulse-soft { animation:pulseSoft 2s infinite; }
        @keyframes pulseSoft{0%,100%{opacity:1;transform:scale(1)}50%{opacity:0.7;transform:scale(1.05)}}
        .slide-in-left { animation:slideInLeft 0.5s ease-out; }
        @keyframes slideInLeft{from{opacity:0;transform:translateX(-30px)}to{opacity:1;transform:translateX(0)}}
        .typing-effect { overflow:hidden;white-space:nowrap;animation:typing 3s steps(40,end); }
        @keyframes typing{from{width:0}to{width:100%}}
        .spin-slow { animation:spin 8s linear infinite; }
        @keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
        .hover-glow { transition:all 0.3s ease; }
        .hover-glow:hover { box-shadow:0 0 20px rgba(65,110,180,0.3);transform:translateY(-2px); }
        .counter { animation:countUp 2s ease-out; }
        @keyframes countUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
        .progress-bar { width:0;animation:progressFill 1.5s ease-out forwards; }
        @keyframes progressFill{to{width:var(--target-width)}}
        .particle { position:fixed;width:4px;height:4px;background:var(--primary-300);border-radius:50%;opacity:0.3;animation:particleFloat 15s infinite linear; }
        @keyframes particleFloat{from{transform:translateY(100vh) rotate(0deg)}to{transform:translateY(-100vh) rotate(360deg)}}
        .badge{padding:6px 14px;border-radius:100px;font-size:0.75rem;font-weight:600;display:inline-flex;align-items:center;transition:all 0.3s ease}
        .badge-success{background:#e6f7e6;color:#0a5e0a;border:1px solid #a3e0a3}
        .badge-danger{background:#ffe6e6;color:#a12323;border:1px solid #ffb8b8}
        .badge-warning{background:#fff8e1;color:#7a5500;border:1px solid #ffe082}
        .modern-table{border-collapse:separate;border-spacing:0 12px}
        .modern-table tbody tr{background:white;border-radius:18px;transition:all 0.3s ease;box-shadow:0 4px 12px -2px rgba(30,58,102,0.06);animation:slideInRow 0.5s ease-out;animation-fill-mode:both}
        @keyframes slideInRow{from{opacity:0;transform:translateX(-20px)}to{opacity:1;transform:translateX(0)}}
        .modern-table tbody tr:hover{transform:scale(1.02) translateY(-2px);box-shadow:0 20px 25px -8px rgba(30,58,102,0.15);background:linear-gradient(90deg,white,var(--primary-50))}
        .form-input{width:100%;padding:0.625rem 1rem;border:1px solid var(--primary-200);border-radius:0.75rem;transition:all 0.3s;background:white}
        .form-input:focus{outline:none;border-color:var(--primary-500);box-shadow:0 0 0 3px rgba(65,110,180,0.1)}
        ::-webkit-scrollbar{width:8px;height:8px}
        ::-webkit-scrollbar-track{background:var(--primary-50);border-radius:6px}
        ::-webkit-scrollbar-thumb{background:var(--primary-300);border-radius:6px}
        ::-webkit-scrollbar-thumb:hover{background:var(--primary-400)}
    </style>
</head>
<body class="antialiased text-gray-800">
<div id="particles"></div>

<!-- Navbar identik admin -->
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
                <a href="../auth/logout.php" onclick="return confirm('Yakin ingin keluar?')" class="text-sm py-2 px-4 flex items-center space-x-2 rounded-xl border transition-all hover:scale-105" style="color:var(--primary-600);border-color:var(--primary-200)">
                    <i class="fas fa-sign-out-alt"></i><span class="hidden sm:inline">Keluar</span>
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
                        <p class="text-white/80 text-xs mt-1 flex items-center"><i class="fas fa-circle text-green-300 text-[6px] mr-2 animate-pulse"></i>Sedang Aktif</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 mt-4 pt-4 border-t border-white/20">
                    <div class="text-center transform group-hover:scale-110 transition-transform">
                        <p class="text-2xl font-bold counter" data-target="<?php echo $total_lelang; ?>">0</p>
                        <p class="text-xs text-white/80">Total Lelang</p>
                    </div>
                    <div class="text-center transform group-hover:scale-110 transition-transform">
                        <p class="text-2xl font-bold counter" data-target="<?php echo $lelang_aktif; ?>">0</p>
                        <p class="text-xs text-white/80">Aktif</p>
                    </div>
                </div>
            </div>
            <nav class="space-y-1">
                <p class="text-xs uppercase tracking-wider mb-3 px-4 font-semibold slide-in-left" style="color:var(--primary-300)">Menu Utama</p>
                <a href="dashboard.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group hover:bg-blue-50" style="color:var(--primary-700)">
                    <i class="fas fa-home w-6 group-hover:scale-110 transition-transform" style="color:var(--primary-400)"></i>
                    <span class="ml-3 group-hover:translate-x-1 transition-transform">Beranda</span>
                </a>
                <a href="data_barang.php" class="flex items-center px-4 py-3.5 gradient-bg text-white rounded-xl shadow-lg transition-all duration-200 group relative overflow-hidden">
                    <i class="fas fa-box w-6 text-white"></i>
                    <span class="ml-3 font-medium">Data Barang</span>
                    <i class="fas fa-chevron-right ml-auto text-sm opacity-0 group-hover:opacity-100 transition-all"></i>
                    <span class="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 transition-opacity"></span>
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
    <main class="flex-1 p-8" style="background:#f8fafc">
        <!-- Header Banner -->
        <div class="mb-8 gradient-bg rounded-3xl p-8 text-white shadow-xl relative overflow-hidden" data-aos="fade-down">
            <div class="absolute top-0 right-0 w-80 h-80 bg-white opacity-5 rounded-full -mr-20 -mt-20 animate-float"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-white opacity-5 rounded-full -ml-16 -mb-16 animate-float" style="animation-delay:1s"></div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl lg:text-4xl font-bold mb-2"><i class="fas fa-box mr-3"></i>Data Barang Lelang</h1>
                    <p class="text-blue-100 flex items-center"><i class="fas fa-calendar-alt mr-2"></i><?php
                $hari_id = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
                $bulan_id = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April','May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus','September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
                $tgl_id = date('l, d F Y');
                $tgl_id = str_replace(array_keys($hari_id), array_values($hari_id), $tgl_id);
                $tgl_id = str_replace(array_keys($bulan_id), array_values($bulan_id), $tgl_id);
                echo $tgl_id;
              ?></p>
                    <p class="text-blue-100 text-sm mt-2">Kelola data barang yang akan dilelang</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <div class="bg-white/10 backdrop-blur rounded-2xl px-6 py-4 border border-white/20 hover:bg-white/20 transition-all hover:scale-105">
                        <p class="text-sm text-blue-100">Total Barang</p>
                        <p class="text-3xl font-bold counter" data-target="<?php echo $total_barang; ?>">0</p>
                        <p class="text-xs text-blue-100 mt-1"><?php echo $lelang_aktif; ?> sedang dilelang</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:var(--primary-50)"><i class="fas fa-box text-2xl" style="color:var(--primary-600)"></i></div>
                    <span class="text-3xl font-bold counter" style="color:var(--primary-800)" data-target="<?php echo $total_barang; ?>">0</span>
                </div>
                <h3 class="text-gray-600 font-medium">Total Barang</h3>
                <div class="w-full rounded-full h-1.5 mt-3" style="background:var(--primary-100)">
                    <div class="h-1.5 rounded-full progress-bar" style="--target-width:70%;background:var(--primary-600)"></div>
                </div>
                <p class="text-xs mt-2" style="color:var(--primary-600)">Semua barang terdaftar</p>
            </div>
            <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:#dcfce7"><i class="fas fa-gavel text-2xl text-green-600"></i></div>
                    <span class="text-3xl font-bold counter text-green-700" data-target="<?php echo $lelang_aktif; ?>">0</span>
                </div>
                <h3 class="text-gray-600 font-medium">Sedang Dilelang</h3>
                <span class="text-xs px-3 py-1.5 rounded-full text-green-700 mt-3 inline-flex items-center" style="background:#dcfce7">
                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>Aktif berlangsung
                </span>
            </div>
            <div class="stat-card" data-aos="fade-up" data-aos-delay="300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:#fff8e1"><i class="fas fa-clock text-2xl text-yellow-600"></i></div>
                    <span class="text-3xl font-bold text-yellow-700"><?php echo $pending; ?></span>
                </div>
                <h3 class="text-gray-600 font-medium">Menunggu Lelang</h3>
                <span class="text-xs px-3 py-1.5 rounded-full mt-3 inline-block" style="background:#fff8e1;color:#7a5500">Pending</span>
            </div>
        </div>

        <!-- Form Tambah/Edit -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8 border hover-glow" style="border-color:var(--primary-100)" data-aos="fade-up">
            <h2 class="text-xl font-bold mb-5 flex items-center" style="color:var(--primary-800)">
                <i class="fas <?php echo $edit_data ? 'fa-edit' : 'fa-plus-circle'; ?> mr-2" style="color:var(--primary-500)"></i>
                <?php echo $edit_data ? 'Edit Barang' : 'Tambah Barang Baru'; ?>
            </h2>
            <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <?php if($edit_data): ?><input type="hidden" name="id_barang" value="<?php echo $edit_data['id_barang']; ?>"><?php endif; ?>
                <div>
                    <label class="block text-sm font-semibold mb-2" style="color:var(--primary-700)">Nama Barang</label>
                    <input type="text" name="nama_barang" required value="<?php echo $edit_data['nama_barang'] ?? ''; ?>" class="form-input" placeholder="Masukkan nama barang">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2" style="color:var(--primary-700)">Tanggal</label>
                    <input type="date" name="tgl" required value="<?php echo $edit_data['tgl'] ?? ''; ?>" class="form-input">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2" style="color:var(--primary-700)">Harga Awal (Rp)</label>
                    <input type="number" name="harga_awal" required value="<?php echo $edit_data['harga_awal'] ?? ''; ?>" class="form-input" placeholder="0">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2" style="color:var(--primary-700)">Gambar Barang</label>
                    <input type="file" name="gambar" accept="image/*" class="form-input">
                    <?php if($edit_data && $edit_data['gambar']): ?>
                        <div class="mt-2 flex items-center space-x-3">
                            <img src="../uploads/<?php echo $edit_data['gambar']; ?>" alt="Preview" class="w-16 h-16 object-cover rounded-xl border" style="border-color:var(--primary-200)">
                            <p class="text-xs" style="color:var(--primary-500)">Gambar saat ini</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2" style="color:var(--primary-700)">Deskripsi Barang</label>
                    <textarea name="deskripsi_barang" rows="3" required class="form-input" placeholder="Deskripsi barang..."><?php echo $edit_data['deskripsi_barang'] ?? ''; ?></textarea>
                </div>
                <div class="md:col-span-2 flex gap-3">
                    <button type="submit" name="<?php echo $edit_data ? 'update_barang' : 'tambah_barang'; ?>"
                            class="gradient-bg text-white px-6 py-3 rounded-xl font-semibold transition-all hover:scale-105 hover:shadow-lg flex items-center">
                        <i class="fas <?php echo $edit_data ? 'fa-save' : 'fa-plus'; ?> mr-2"></i><?php echo $edit_data ? 'Perbarui Barang' : 'Tambah Barang'; ?>
                    </button>
                    <?php if($edit_data): ?>
                        <a href="data_barang.php" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-6 py-3 rounded-xl font-semibold transition-all hover:scale-105 flex items-center">
                            <i class="fas fa-times mr-2"></i>Batal
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tabel Barang -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border hover-glow" style="border-color:var(--primary-100)" data-aos="zoom-in">
            <div class="p-6 border-b flex flex-col md:flex-row md:items-center md:justify-between" style="border-color:var(--primary-100);background:linear-gradient(to right,var(--primary-50),white)">
                <div>
                    <h2 class="text-xl font-bold" style="color:var(--primary-800)">
                        <i class="fas fa-list mr-2 animate-spin-slow" style="color:var(--primary-500)"></i>Daftar Barang
                    </h2>
                    <p class="text-sm mt-1" style="color:var(--primary-500)">Semua barang yang terdaftar dalam sistem</p>
                </div>
                <div class="relative mt-3 md:mt-0 group">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-sm" style="color:var(--primary-400)"></i>
                    <input type="text" id="searchBarang" placeholder="Cari barang..." class="pl-9 pr-4 py-2 border rounded-xl text-sm focus:outline-none transition-all" style="border-color:var(--primary-200)">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full modern-table">
                    <thead>
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">No</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Gambar</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Nama Barang</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Tanggal</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Harga Awal</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="barangTableBody">
                        <?php $no = 1; while($row = mysqli_fetch_assoc($barang)): ?>
                        <tr style="animation-delay:<?php echo ($no-1)*0.05; ?>s">
                            <td class="px-6 py-4 font-medium" style="color:var(--primary-700)"><?php echo $no++; ?></td>
                            <td class="px-6 py-4">
                                <?php if($row['gambar']): ?>
                                    <img src="../uploads/<?php echo $row['gambar']; ?>" alt="" class="w-14 h-14 object-cover rounded-xl border" style="border-color:var(--primary-200)">
                                <?php else: ?>
                                    <div class="w-14 h-14 rounded-xl flex items-center justify-center" style="background:var(--primary-50)"><i class="fas fa-image" style="color:var(--primary-400)"></i></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-semibold" style="color:var(--primary-800)"><?php echo htmlspecialchars($row['nama_barang']); ?></p>
                                <p class="text-xs text-gray-500 mt-0.5"><?php echo mb_substr($row['deskripsi_barang'] ?? '', 0, 40); ?>...</p>
                            </td>
                            <td class="px-6 py-4 text-gray-600"><?php echo formatTanggal($row['tgl']); ?></td>
                            <td class="px-6 py-4 font-semibold" style="color:var(--primary-700)"><?php echo formatRupiah($row['harga_awal']); ?></td>
                            <td class="px-6 py-4">
                                <?php if($row['status_barang'] == 'dibuka'): ?>
                                    <span class="badge badge-success"><span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>Dibuka</span>
                                <?php elseif($row['status_barang'] == 'ditutup'): ?>
                                    <span class="badge badge-danger"><i class="fas fa-lock mr-1"></i>Ditutup</span>
                                <?php else: ?>
                                    <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-2">
                                    <a href="?edit=<?php echo $row['id_barang']; ?>" class="w-9 h-9 rounded-xl flex items-center justify-center transition-all hover:scale-110 hover:shadow-md" style="background:var(--primary-100);color:var(--primary-700)" title="Edit">
                                        <i class="fas fa-edit text-sm"></i>
                                    </a>
                                    <a href="?delete=<?php echo $row['id_barang']; ?>" onclick="return confirm('Yakin ingin menghapus?')" class="w-9 h-9 rounded-xl flex items-center justify-center transition-all hover:scale-110 hover:shadow-md" style="background:#fee2e2;color:#dc2626" title="Hapus">
                                        <i class="fas fa-trash text-sm"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t text-center" style="background:var(--primary-50);border-color:var(--primary-100)">
                <p class="text-sm" style="color:var(--primary-400)"><i class="fas fa-sync-alt mr-1 animate-spin-slow"></i>Menampilkan semua <?php echo $total_barang; ?> barang tercatat</p>
            </div>
        </div>
    </main>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 800, once: false, mirror: true, offset: 50, easing: 'ease-out-cubic' });
    function createParticles() {
        const c = document.getElementById('particles');
        for (let i = 0; i < 20; i++) {
            const p = document.createElement('div'); p.className = 'particle';
            p.style.left = Math.random() * 100 + '%';
            p.style.animationDuration = (Math.random() * 10 + 10) + 's';
            p.style.animationDelay = Math.random() * 5 + 's';
            p.style.width = p.style.height = (Math.random() * 6 + 2) + 'px';
            c.appendChild(p);
        }
    }
    createParticles();
    document.querySelectorAll('.counter').forEach(el => {
        const t = parseInt(el.getAttribute('data-target')); const step = Math.max(t / 125, 1); let curr = 0;
        const u = () => { curr += step; if (curr < t) { el.textContent = Math.floor(curr).toLocaleString('id-ID'); requestAnimationFrame(u); } else { el.textContent = t.toLocaleString('id-ID'); } }; u();
    });
    document.querySelectorAll('.progress-bar').forEach(b => { b.style.width = b.style.getPropertyValue('--target-width'); });
    document.getElementById('searchBarang')?.addEventListener('input', function() {
        const s = this.value.toLowerCase();
        document.querySelectorAll('#barangTableBody tr').forEach(r => { r.style.display = r.textContent.toLowerCase().includes(s) ? '' : 'none'; });
    });
</script>
</body>
</html>