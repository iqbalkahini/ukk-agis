<?php
session_start();
require_once('../config/config.php');

if(!function_exists('formatRupiah')){function formatRupiah($a){return'Rp '.number_format($a,0,',','.');}}
if(!function_exists('formatTanggal')){function formatTanggal($t){$b=[1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];$p=explode('-',date('Y-m-d',strtotime($t)));return $p[2].' '.$b[(int)$p[1]].' '.$p[0];}}
if(!function_exists('checkLevel')){function checkLevel($al=[]){if(!isset($_SESSION['level'])||!in_array($_SESSION['level'],$al)){header('Location: ../auth/login.php');exit();}}}

checkLevel([3]);

if(!isset($_SESSION['id_user'])){header('Location: ../auth/login.php');exit();}

$id_user = $_SESSION['id_user'];

if(!isset($_GET['id'])){header('Location: pembayaran.php');exit();}

$id_lelang = mysqli_real_escape_string($conn, $_GET['id']);

$result = mysqli_query($conn, "SELECT l.*, b.nama_barang, b.deskripsi_barang, b.harga_awal FROM tb_lelang l JOIN tb_barang b ON l.id_barang=b.id_barang WHERE l.id_lelang='$id_lelang' AND l.id_user='$id_user' AND l.status='ditutup'");
if(!$result || mysqli_num_rows($result)==0){echo "<script>alert('Lelang tidak ditemukan!');window.location='pembayaran.php';</script>";exit();}
$lelang = mysqli_fetch_assoc($result);

$cek_bayar = mysqli_query($conn, "SELECT * FROM tb_pembayaran WHERE id_lelang='$id_lelang' AND id_user='$id_user'");
$pembayaran_ada = mysqli_fetch_assoc($cek_bayar);

$upload_success = false;
$upload_message = '';
$upload_dir = dirname(__DIR__) . '/uploads/bukti_bayar/';
$upload_dir_db = 'bukti_bayar/';

if(isset($_POST['submit_bayar'])){
  $metode_bayar = mysqli_real_escape_string($conn, $_POST['metode_bayar']);
  $uploadOk = 1;

  if(!isset($_FILES['bukti_bayar']) || $_FILES['bukti_bayar']['error'] !== UPLOAD_ERR_OK){
    $upload_message = 'File bukti pembayaran wajib dipilih.';
    $uploadOk = 0;
  }

  if($uploadOk == 1 && !is_dir($upload_dir) && !mkdir($upload_dir, 0777, true)){
    $upload_message = 'Folder upload bukti pembayaran tidak dapat dibuat.';
    $uploadOk = 0;
  }

  if($uploadOk == 1){
    @chmod(dirname($upload_dir), 0777);
    @chmod($upload_dir, 0777);
  }

  if($uploadOk == 1 && !is_writable($upload_dir)){
    $upload_message = 'Folder upload bukti pembayaran tidak bisa ditulis.';
    $uploadOk = 0;
  }

  if($uploadOk == 1){
    $original_name = basename($_FILES['bukti_bayar']['name']);
    $safe_name = preg_replace('/[^A-Za-z0-9._-]/', '_', $original_name);
    $file_ext = strtolower(pathinfo($safe_name, PATHINFO_EXTENSION));
    $file_name = 'bukti_' . $id_user . '_' . $id_lelang . '_' . time() . '.' . $file_ext;
    $target_file = $upload_dir . $file_name;
    $check = getimagesize($_FILES['bukti_bayar']['tmp_name']);

    if($check === false){$upload_message='File bukan gambar!';$uploadOk=0;}
    if($_FILES['bukti_bayar']['size'] > 5000000){$upload_message='File terlalu besar! Maksimal 5MB';$uploadOk=0;}
    if(!in_array($file_ext,['jpg','jpeg','png','gif'])){$upload_message='Hanya file JPG, JPEG, PNG & GIF!';$uploadOk=0;}
  }

  if($uploadOk == 1){
    if(move_uploaded_file($_FILES["bukti_bayar"]["tmp_name"], $target_file)){
      if($pembayaran_ada){
        $q=mysqli_query($conn,"UPDATE tb_pembayaran SET jumlah='{$lelang['harga_akhir']}',metode_pembayaran='$metode_bayar',bukti_pembayaran='" . $upload_dir_db . $file_name . "',status_pembayaran='dibayar' WHERE id_pembayaran='{$pembayaran_ada['id_pembayaran']}'");
        if($q && !empty($pembayaran_ada['bukti_pembayaran'])){
          $old_file = dirname(__DIR__) . '/uploads/' . ltrim($pembayaran_ada['bukti_pembayaran'], '/');
          if(is_file($old_file) && $old_file !== $target_file){
            unlink($old_file);
          }
        }
        $upload_success=$q;$upload_message=$q?'Pembayaran berhasil diupload!':'Gagal update: '.mysqli_error($conn);
      } else {
        $q=mysqli_query($conn,"INSERT INTO tb_pembayaran (id_lelang,id_user,jumlah,metode_pembayaran,bukti_pembayaran,status_pembayaran) VALUES ('$id_lelang','$id_user','{$lelang['harga_akhir']}','$metode_bayar','" . $upload_dir_db . $file_name . "','dibayar')");
        $upload_success=$q;$upload_message=$q?'Pembayaran berhasil diupload! Menunggu konfirmasi admin.':'Gagal simpan: '.mysqli_error($conn);
      }
      if(!$q && is_file($target_file)){
        unlink($target_file);
      }
    } else {$upload_message='Terjadi kesalahan saat upload file ke folder uploads/bukti_bayar!';}
  }
}

$total_penawaran = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM history_lelang WHERE id_user=$id_user"))['t'];
$total_menang    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM tb_lelang WHERE id_user=$id_user AND status='ditutup'"))['t'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Proses Pembayaran - Sistem Lelang Online</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
*{font-family:'Plus Jakarta Sans',sans-serif;margin:0;padding:0;box-sizing:border-box;}
:root{
  --primary-50:#eef2f8;--primary-100:#d9e2f0;--primary-200:#b3c5e1;--primary-300:#8da8d2;
  --primary-400:#678bc3;--primary-500:#416eb4;--primary-600:#2a4f8c;--primary-700:#1e3a66;
  --primary-800:#132a4a;--primary-900:#0a1a30;
}
body{background:linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%);min-height:100vh;overflow-x:hidden;}
.gradient-bg{background:linear-gradient(135deg,var(--primary-700),var(--primary-600),var(--primary-500));background-size:200% 200%;animation:gradientShift 8s ease infinite;}
@keyframes gradientShift{0%{background-position:0% 50%;}50%{background-position:100% 50%;}100%{background-position:0% 50%;}}
.glass{background:rgba(255,255,255,0.85);backdrop-filter:blur(12px);border:1px solid rgba(65,110,180,0.15);transition:all 0.3s ease;}
.animate-float{animation:float 3s ease-in-out infinite;}
@keyframes float{0%,100%{transform:translateY(0);}50%{transform:translateY(-8px);}}
.animate-pulse-soft{animation:pulseSoft 2s infinite;}
@keyframes pulseSoft{0%,100%{opacity:1;transform:scale(1);}50%{opacity:0.7;transform:scale(1.05);}}
.slide-in-left{animation:slideInLeft 0.5s ease-out;}
@keyframes slideInLeft{from{opacity:0;transform:translateX(-30px);}to{opacity:1;transform:translateX(0);}}
.hover-glow{transition:all 0.3s ease;}
.hover-glow:hover{box-shadow:0 0 20px rgba(65,110,180,0.3);}
.btn-primary{background:var(--primary-600);color:white;padding:.75rem 1.5rem;border-radius:14px;font-weight:600;transition:all 0.3s ease;display:inline-flex;align-items:center;gap:.5rem;}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 12px 20px -8px rgba(30,58,102,0.4);}
.particle{position:fixed;width:4px;height:4px;background:var(--primary-300);border-radius:50%;opacity:0.3;animation:particleFloat 15s infinite linear;}
@keyframes particleFloat{from{transform:translateY(100vh) rotate(0deg);}to{transform:translateY(-100vh) rotate(360deg);}}
.upload-area{border:2px dashed var(--primary-200);border-radius:16px;padding:2rem;text-align:center;transition:all 0.3s ease;cursor:pointer;}
.upload-area:hover{border-color:var(--primary-500);background:var(--primary-50);}
::-webkit-scrollbar{width:8px;}::-webkit-scrollbar-track{background:var(--primary-50);border-radius:6px;}::-webkit-scrollbar-thumb{background:var(--primary-300);border-radius:6px;}
</style>
</head>
<body class="antialiased text-gray-800">
<div id="particles"></div>

<!-- NAVBAR -->
<nav class="glass sticky top-0 z-30 border-b shadow-sm" style="border-color:var(--primary-100)">
  <div class="w-full px-0 flex">
    <div class="flex justify-between h-16 w-full">
      <div class="flex items-center" style="width:288px;min-width:288px;padding-left:1.5rem">
        <div class="gradient-bg p-2.5 rounded-xl shadow-lg animate-float flex items-center justify-center" style="min-width:40px;min-height:40px">
          <i class="fas fa-gavel text-white text-lg"></i>
        </div>
        <div class="flex flex-col leading-tight ml-3">
          <span class="font-extrabold text-lg tracking-tight" style="background:linear-gradient(135deg,var(--primary-700),var(--primary-500));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;line-height:1.2">Sistem Lelang</span>
          <span class="text-xs font-semibold tracking-widest uppercase" style="color:var(--primary-400);letter-spacing:.18em;line-height:1.2">Online<span class="inline-block w-1.5 h-1.5 rounded-full bg-green-400 ml-1.5 mb-0.5 animate-pulse align-middle"></span></span>
        </div>
      </div>
      <div class="flex items-center space-x-4 pr-6">
        <div class="flex items-center space-x-3 ml-2 pl-2 border-l-2" style="border-color:var(--primary-100)">
          <div class="text-right hidden sm:block">
            <p class="text-sm font-semibold" style="color:var(--primary-800)"><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></p>
            <p class="text-xs flex items-center justify-end" style="color:var(--primary-500)"><i class="fas fa-circle text-green-500 text-[6px] mr-1 animate-pulse"></i>Masyarakat</p>
          </div>
          <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center text-white font-bold shadow-md">
            <?php echo strtoupper(substr($_SESSION['nama_lengkap'],0,1)); ?>
          </div>
        </div>
        <a href="../auth/logout.php" onclick="return confirm('Yakin ingin keluar?')" class="text-sm py-2 px-4 flex items-center space-x-2 rounded-xl border transition-all" style="border-color:var(--primary-200);color:var(--primary-600)">
          <i class="fas fa-sign-out-alt"></i><span class="hidden sm:inline">Keluar</span>
        </a>
      </div>
    </div>
  </div>
</nav>

<div class="flex relative">
<!-- SIDEBAR -->
<aside class="w-72 bg-white border-r min-h-screen shadow-xl relative overflow-hidden" style="border-color:var(--primary-100)">
  <div class="absolute top-0 left-0 w-full h-1 gradient-bg"></div>
  <div class="p-6 relative z-10">
    <div class="gradient-bg rounded-2xl p-6 mb-6 text-white shadow-xl relative overflow-hidden group">
      <div class="absolute top-0 right-0 w-40 h-40 bg-white opacity-5 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-700"></div>
      <div class="flex items-center space-x-4 relative z-10">
        <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center text-2xl font-bold border-2 border-white/30">
          <?php echo strtoupper(substr($_SESSION['nama_lengkap'],0,1)); ?>
        </div>
        <div>
          <p class="text-white/80 text-sm">Selamat datang,</p>
          <p class="text-xl font-bold"><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></p>
          <p class="text-white/80 text-xs mt-1 flex items-center"><i class="fas fa-circle text-green-300 text-[6px] mr-2 animate-pulse"></i>Sedang Aktif</p>
        </div>
      </div>
      <div class="grid grid-cols-2 gap-2 mt-4 pt-4 border-t border-white/20">
        <div class="text-center"><p class="text-2xl font-bold"><?php echo $total_penawaran; ?></p><p class="text-xs text-white/80">Penawaran</p></div>
        <div class="text-center"><p class="text-2xl font-bold"><?php echo $total_menang; ?></p><p class="text-xs text-white/80">Kemenangan</p></div>
      </div>
    </div>
    <nav class="space-y-2">
      <p class="text-xs uppercase tracking-wider px-4 mb-4 font-semibold slide-in-left" style="color:var(--primary-300)">Menu Utama</p>
      <a href="dashboard.php" class="flex items-center px-4 py-3 rounded-xl transition-all" style="color:var(--primary-700)" onmouseover="this.style.background='var(--primary-50)'" onmouseout="this.style.background=''">
        <i class="fas fa-home w-6" style="color:var(--primary-400)"></i><span class="ml-3">Beranda</span>
      </a>
      <div class="pt-4">
        <p class="text-xs uppercase tracking-wider px-4 mb-2 font-semibold" style="color:var(--primary-300)">Lelang</p>
        <a href="barang_lelang.php" class="flex items-center px-4 py-3 rounded-xl transition-all" style="color:var(--primary-700)" onmouseover="this.style.background='var(--primary-50)'" onmouseout="this.style.background=''">
          <i class="fas fa-gavel w-6" style="color:var(--primary-400)"></i><span class="ml-3">Barang Lelang</span>
        </a>
        <a href="penawaran_saya.php" class="flex items-center px-4 py-3 rounded-xl transition-all mt-1" style="color:var(--primary-700)" onmouseover="this.style.background='var(--primary-50)'" onmouseout="this.style.background=''">
          <i class="fas fa-history w-6" style="color:var(--primary-400)"></i><span class="ml-3">Penawaran Saya</span>
        </a>
        <a href="pembayaran.php" class="flex items-center px-4 py-3.5 gradient-bg text-white rounded-xl shadow-lg transition-all">
          <i class="fas fa-credit-card w-6"></i><span class="ml-3 font-medium">Pembayaran</span>
        </a>
      </div>
    </nav>
  </div>
  <div class="absolute bottom-6 left-6 right-6">
    <div class="rounded-xl p-4 border" style="background:var(--primary-50);border-color:var(--primary-100)">
      <div class="flex items-center space-x-3">
        <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm">
          <i class="fas fa-headset text-lg" style="color:var(--primary-500)"></i>
        </div>
        <div>
          <p class="text-sm font-semibold" style="color:var(--primary-800)">Butuh Bantuan?</p>
          <p class="text-xs" style="color:var(--primary-500)"><i class="fas fa-envelope mr-1"></i>admin@lelang.com</p>
        </div>
      </div>
    </div>
  </div>
</aside>

<!-- MAIN CONTENT -->
<main class="flex-1 p-8" style="background:#f8fafc">

<?php if($upload_success): ?>
<!-- SUCCESS PAGE -->
<div class="max-w-2xl mx-auto" data-aos="zoom-in">
  <!-- Success Icon -->
  <div class="text-center mb-8">
    <div class="w-28 h-28 rounded-full flex items-center justify-center mx-auto mb-6 animate-float" style="background:var(--primary-50)">
      <i class="fas fa-check-circle text-6xl text-green-500"></i>
    </div>
    <h1 class="text-4xl font-bold mb-4" style="color:var(--primary-800)">Pembayaran Berhasil Dikirim!</h1>
    <p class="text-gray-600 text-lg">Bukti pembayaran Anda telah berhasil diupload dan sedang menunggu konfirmasi admin.</p>
  </div>

  <!-- Detail Card -->
  <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border" style="border-color:var(--primary-100)">
    <h2 class="text-xl font-bold mb-6 flex items-center" style="color:var(--primary-800)"><i class="fas fa-receipt mr-2" style="color:var(--primary-500)"></i>Detail Pembayaran</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="rounded-xl p-4" style="background:var(--primary-50)">
        <p class="text-sm mb-1" style="color:var(--primary-500)">Nama Barang</p>
        <p class="font-bold" style="color:var(--primary-800)"><?php echo htmlspecialchars($lelang['nama_barang']); ?></p>
      </div>
      <div class="rounded-xl p-4" style="background:var(--primary-50)">
        <p class="text-sm mb-1" style="color:var(--primary-500)">Tanggal Lelang</p>
        <p class="font-bold" style="color:var(--primary-800)"><?php echo formatTanggal($lelang['tgl_lelang']); ?></p>
      </div>
      <div class="rounded-xl p-4" style="background:var(--primary-50)">
        <p class="text-sm mb-1" style="color:var(--primary-500)">Total Pembayaran</p>
        <p class="text-2xl font-bold" style="color:var(--primary-700)"><?php echo formatRupiah($lelang['harga_akhir']); ?></p>
      </div>
      <div class="rounded-xl p-4" style="background:var(--primary-50)">
        <p class="text-sm mb-1" style="color:var(--primary-500)">Status</p>
        <span class="inline-flex items-center px-3 py-1.5 rounded-full bg-amber-50 text-amber-700 text-sm font-semibold border border-amber-200">
          <i class="fas fa-clock mr-2 animate-pulse-soft"></i>Menunggu Konfirmasi
        </span>
      </div>
    </div>
  </div>

  <!-- Timeline -->
  <div class="rounded-2xl p-6 mb-8" style="background:var(--primary-700)">
    <h3 class="font-bold text-white mb-4 flex items-center"><i class="fas fa-tasks mr-2"></i>Proses Selanjutnya</h3>
    <div class="space-y-4">
      <div class="flex items-start">
        <div class="w-8 h-8 rounded-full flex items-center justify-center mr-4 flex-shrink-0 bg-green-500"><i class="fas fa-check text-white text-xs"></i></div>
        <div><p class="font-semibold text-white">Bukti Pembayaran Terkirim</p><p class="text-sm text-white/70">Berhasil diupload ke sistem</p></div>
      </div>
      <div class="flex items-start">
        <div class="w-8 h-8 rounded-full flex items-center justify-center mr-4 flex-shrink-0 bg-white/20 animate-pulse"><i class="fas fa-clock text-white text-xs"></i></div>
        <div><p class="font-semibold text-white">Menunggu Verifikasi Admin</p><p class="text-sm text-white/70">Maksimal 1x24 jam</p></div>
      </div>
      <div class="flex items-start">
        <div class="w-8 h-8 rounded-full flex items-center justify-center mr-4 flex-shrink-0 bg-white/10"><i class="fas fa-box text-white text-xs"></i></div>
        <div><p class="font-semibold text-white/70">Proses Pengiriman Barang</p><p class="text-sm text-white/50">Setelah pembayaran dikonfirmasi</p></div>
      </div>
    </div>
  </div>

  <!-- Actions -->
  <div class="flex flex-col sm:flex-row gap-4 justify-center">
    <a href="pembayaran.php" class="btn-primary"><i class="fas fa-list"></i>Lihat Semua Pembayaran</a>
    <a href="dashboard.php" class="btn-primary" style="background:var(--primary-500)"><i class="fas fa-home"></i>Kembali ke Beranda</a>
  </div>
</div>

<?php else: ?>
<!-- FORM PAGE -->
<div class="mb-6 flex items-center text-sm" data-aos="fade-right">
  <a href="pembayaran.php" class="flex items-center font-semibold hover:underline" style="color:var(--primary-600)">
    <i class="fas fa-arrow-left mr-2"></i>Kembali ke Pembayaran
  </a>
</div>

<!-- Header Banner -->
<div class="mb-8 relative overflow-hidden rounded-3xl" data-aos="fade-down">
  <div class="gradient-bg rounded-3xl p-8 text-white shadow-xl relative">
    <div class="absolute top-0 right-0 w-80 h-80 bg-white opacity-5 rounded-full -mr-20 -mt-20 animate-float"></div>
    <div class="relative z-10">
      <h1 class="text-3xl lg:text-4xl font-bold mb-2"><i class="fas fa-money-bill-wave mr-3"></i>Proses Pembayaran</h1>
      <p style="color:rgba(255,255,255,0.8)">Upload bukti pembayaran Anda</p>
    </div>
  </div>
</div>

<?php if(!empty($upload_message)): ?>
<div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-xl flex items-center" data-aos="fade-down">
  <i class="fas fa-exclamation-circle text-red-500 text-xl mr-3"></i>
  <p class="text-red-700 font-semibold"><?php echo $upload_message; ?></p>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
  <!-- Main Form -->
  <div class="lg:col-span-2 space-y-6">
    <!-- Detail Lelang -->
    <div class="bg-white rounded-2xl shadow-lg p-6 border hover-glow" style="border-color:var(--primary-100)" data-aos="fade-up">
      <h2 class="text-xl font-bold mb-6 flex items-center" style="color:var(--primary-800)">
        <i class="fas fa-info-circle mr-2" style="color:var(--primary-500)"></i>Detail Lelang
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="rounded-xl p-4" style="background:var(--primary-50)">
          <p class="text-sm mb-1" style="color:var(--primary-500)">Nama Barang</p>
          <p class="font-bold" style="color:var(--primary-800)"><?php echo htmlspecialchars($lelang['nama_barang']); ?></p>
        </div>
        <div class="rounded-xl p-4" style="background:var(--primary-50)">
          <p class="text-sm mb-1" style="color:var(--primary-500)">Tanggal Lelang</p>
          <p class="font-bold" style="color:var(--primary-800)"><?php echo formatTanggal($lelang['tgl_lelang']); ?></p>
        </div>
        <div class="rounded-xl p-4" style="background:var(--primary-50)">
          <p class="text-sm mb-1" style="color:var(--primary-500)">Harga Awal</p>
          <p class="font-bold" style="color:var(--primary-800)"><?php echo formatRupiah($lelang['harga_awal']); ?></p>
        </div>
        <div class="rounded-xl p-4" style="background:linear-gradient(135deg,var(--primary-600),var(--primary-700))">
          <p class="text-sm mb-1 text-white/80">Total Pembayaran</p>
          <p class="text-2xl font-bold text-white"><?php echo formatRupiah($lelang['harga_akhir']); ?></p>
        </div>
      </div>
    </div>

    <!-- Form Upload -->
    <div class="bg-white rounded-2xl shadow-lg p-6 border hover-glow" style="border-color:var(--primary-100)" data-aos="fade-up" data-aos-delay="100">
      <h2 class="text-xl font-bold mb-6 flex items-center" style="color:var(--primary-800)">
        <i class="fas fa-upload mr-2" style="color:var(--primary-500)"></i>Upload Bukti Pembayaran
      </h2>
      <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <!-- Metode -->
        <div>
          <label class="block font-semibold mb-2 text-sm" style="color:var(--primary-700)">
            Metode Pembayaran <span class="text-red-500">*</span>
          </label>
          <select name="metode_bayar" required class="w-full px-4 py-3 border rounded-xl focus:outline-none focus:ring-2 transition-all bg-white" style="border-color:var(--primary-200)">
            <option value="">-- Pilih Metode Pembayaran --</option>
            <option value="Transfer Bank BCA">Transfer Bank BCA</option>
            <option value="Transfer Bank Mandiri">Transfer Bank Mandiri</option>
            <option value="Transfer Bank BNI">Transfer Bank BNI</option>
            <option value="E-Wallet (OVO)">E-Wallet (OVO)</option>
            <option value="E-Wallet (GoPay)">E-Wallet (GoPay)</option>
            <option value="E-Wallet (Dana)">E-Wallet (Dana)</option>
          </select>
        </div>
        <!-- Upload -->
        <div>
          <label class="block font-semibold mb-2 text-sm" style="color:var(--primary-700)">
            Bukti Pembayaran <span class="text-red-500">*</span>
          </label>
          <div class="upload-area" id="uploadArea" onclick="document.getElementById('bukti_bayar').click()">
            <input type="file" name="bukti_bayar" id="bukti_bayar" required accept="image/*" class="hidden" onchange="previewImage(event)">
            <div id="uploadPlaceholder">
              <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-3 gradient-bg">
                <i class="fas fa-cloud-upload-alt text-white text-2xl"></i>
              </div>
              <p class="font-semibold" style="color:var(--primary-700)">Klik untuk upload gambar</p>
              <p class="text-sm text-gray-400 mt-1">Format: JPG, PNG, GIF (Max 5MB)</p>
            </div>
            <div id="preview" class="hidden">
              <img id="preview-image" class="max-w-full h-64 rounded-xl mx-auto shadow-lg" alt="Preview">
              <p class="text-sm mt-3 font-semibold" style="color:var(--primary-600)"><i class="fas fa-check-circle mr-1"></i>Gambar dipilih. Klik untuk ganti.</p>
            </div>
          </div>
        </div>
        <!-- Buttons -->
        <div class="flex space-x-4">
          <button type="submit" name="submit_bayar" class="btn-primary flex-1 justify-center py-3.5">
            <i class="fas fa-paper-plane"></i>Kirim Bukti Pembayaran
          </button>
          <a href="pembayaran.php" class="flex-1 flex items-center justify-center gap-2 py-3.5 rounded-xl font-semibold border transition-all hover:scale-105 text-center" style="border-color:var(--primary-200);color:var(--primary-600)">
            <i class="fas fa-times"></i>Batal
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- Sidebar Info -->
  <div class="space-y-6">
    <!-- Rekening -->
    <div class="bg-white rounded-2xl shadow-lg p-6 border hover-glow" style="border-color:var(--primary-100)" data-aos="fade-left">
      <h3 class="font-bold mb-4 flex items-center" style="color:var(--primary-800)">
        <i class="fas fa-university mr-2" style="color:var(--primary-500)"></i>Rekening Pembayaran
      </h3>
      <div class="space-y-3">
        <?php $banks=[['BCA','1234567890'],['Mandiri','0987654321'],['BNI','5678901234']]; foreach($banks as $b): ?>
        <div class="rounded-xl p-3" style="background:var(--primary-50)">
          <p class="font-bold text-sm" style="color:var(--primary-800)">Bank <?php echo $b[0]; ?></p>
          <p class="text-xs text-gray-500">No. Rek: <span class="font-semibold"><?php echo $b[1]; ?></span></p>
        </div>
        <?php endforeach; ?>
        <div class="rounded-xl p-3" style="background:var(--primary-50)">
          <p class="font-bold text-sm" style="color:var(--primary-800)">E-Wallet</p>
          <p class="text-xs text-gray-500">No. HP: <span class="font-semibold">081234567890</span></p>
        </div>
      </div>
    </div>

    <!-- Panduan -->
    <div class="rounded-2xl p-6" style="background:var(--primary-700)" data-aos="fade-left" data-aos-delay="100">
      <h3 class="font-bold text-white mb-4 flex items-center">
        <i class="fas fa-exclamation-triangle mr-2"></i>Panduan Pembayaran
      </h3>
      <ol class="text-white/80 space-y-2 text-sm">
        <li class="flex items-start"><span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center text-xs font-bold mr-2 mt-0.5 flex-shrink-0">1</span>Transfer sesuai total pembayaran</li>
        <li class="flex items-start"><span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center text-xs font-bold mr-2 mt-0.5 flex-shrink-0">2</span>Screenshot bukti transfer yang jelas</li>
        <li class="flex items-start"><span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center text-xs font-bold mr-2 mt-0.5 flex-shrink-0">3</span>Upload melalui form di samping</li>
        <li class="flex items-start"><span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center text-xs font-bold mr-2 mt-0.5 flex-shrink-0">4</span>Admin konfirmasi max 1x24 jam</li>
        <li class="flex items-start"><span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center text-xs font-bold mr-2 mt-0.5 flex-shrink-0">5</span>Barang dikirim setelah dikonfirmasi</li>
      </ol>
    </div>
  </div>
</div>
<?php endif; ?>
</main>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init({duration:800,once:false,mirror:true,offset:50,easing:'ease-out-cubic'});
function createParticles(){
  const c=document.getElementById('particles');
  for(let i=0;i<20;i++){
    const p=document.createElement('div');p.className='particle';
    p.style.left=Math.random()*100+'%';
    p.style.animationDuration=(Math.random()*10+10)+'s';
    p.style.animationDelay=Math.random()*5+'s';
    const s=(Math.random()*6+2)+'px';p.style.width=s;p.style.height=s;
    c.appendChild(p);
  }
}
createParticles();

function previewImage(event){
  const file=event.target.files[0];
  if(file){
    const reader=new FileReader();
    reader.onload=function(e){
      document.getElementById('uploadPlaceholder').classList.add('hidden');
      const prev=document.getElementById('preview');
      prev.classList.remove('hidden');
      document.getElementById('preview-image').src=e.target.result;
    };
    reader.readAsDataURL(file);
  }
}
</script>
</body>
</html>
