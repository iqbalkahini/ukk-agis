<?php
session_start();
require_once('../config/config.php');
checkLevel([3]);

$id_lelang = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$id_user   = $_SESSION['id_user'];

// Handle penawaran
if(isset($_POST['submit_penawaran'])) {
    $penawaran_harga = (int)$_POST['penawaran_harga'];
    $id_barang       = (int)$_POST['id_barang'];
    $current = mysqli_fetch_assoc(mysqli_query($conn, "SELECT harga_akhir FROM tb_lelang WHERE id_lelang = $id_lelang"));
    if($penawaran_harga > $current['harga_akhir']) {
        mysqli_query($conn, "INSERT INTO history_lelang (id_lelang, id_barang, id_user, penawaran_harga) VALUES ($id_lelang, $id_barang, $id_user, $penawaran_harga)");
        mysqli_query($conn, "UPDATE tb_lelang SET harga_akhir = $penawaran_harga, id_user = $id_user WHERE id_lelang = $id_lelang");
        $success = "Penawaran berhasil diajukan!";
    } else {
        $error = "Penawaran harus lebih tinggi dari harga saat ini!";
    }
}

$lelang = mysqli_fetch_assoc(mysqli_query($conn, "SELECT l.*, b.* FROM tb_lelang l JOIN tb_barang b ON l.id_barang = b.id_barang WHERE l.id_lelang = $id_lelang"));
$history = mysqli_query($conn, "SELECT h.*, u.nama_lengkap FROM history_lelang h JOIN tb_user u ON h.id_user = u.id_user WHERE h.id_lelang = $id_lelang ORDER BY h.penawaran_harga DESC");
$user_bid = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM history_lelang WHERE id_lelang = $id_lelang AND id_user = $id_user ORDER BY penawaran_harga DESC LIMIT 1"));

$total_penawaran = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM history_lelang WHERE id_user=$id_user"))['t'];
$total_menang    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM tb_lelang WHERE id_user=$id_user AND status='ditutup'"))['t'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detail Lelang - <?php echo htmlspecialchars($lelang['nama_barang'] ?? ''); ?></title>
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
.spin-slow{animation:spinSlow 8s linear infinite;}
@keyframes spinSlow{from{transform:rotate(0deg);}to{transform:rotate(360deg);}}
.animate-pulse-soft{animation:pulseSoft 2s infinite;}
@keyframes pulseSoft{0%,100%{opacity:1;transform:scale(1);}50%{opacity:0.7;transform:scale(1.05);}}
.slide-in-left{animation:slideInLeft 0.5s ease-out;}
@keyframes slideInLeft{from{opacity:0;transform:translateX(-30px);}to{opacity:1;transform:translateX(0);}}
.hover-glow{transition:all 0.3s ease;}
.hover-glow:hover{box-shadow:0 0 20px rgba(65,110,180,0.3);transform:translateY(-2px);}
.btn-primary{background:var(--primary-600);color:white;padding:.75rem 1.5rem;border-radius:14px;font-weight:600;transition:all 0.3s ease;display:inline-flex;align-items:center;gap:.5rem;width:100%;justify-content:center;}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 12px 20px -8px rgba(30,58,102,0.4);}
.particle{position:fixed;width:4px;height:4px;background:var(--primary-300);border-radius:50%;opacity:0.3;animation:particleFloat 15s infinite linear;}
@keyframes particleFloat{from{transform:translateY(100vh) rotate(0deg);}to{transform:translateY(-100vh) rotate(360deg);}}
.rank-badge{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;}
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
        <a href="pembayaran.php" class="flex items-center px-4 py-3 rounded-xl transition-all" style="color:var(--primary-700)" onmouseover="this.style.background='var(--primary-50)'" onmouseout="this.style.background=''">
          <i class="fas fa-credit-card w-6" style="color:var(--primary-400)"></i><span class="ml-3">Pembayaran</span>
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
  <!-- Breadcrumb -->
  <div class="mb-6 flex items-center text-sm" data-aos="fade-right">
    <a href="barang_lelang.php" class="flex items-center font-semibold hover:underline" style="color:var(--primary-600)">
      <i class="fas fa-arrow-left mr-2"></i>Kembali ke Barang Lelang
    </a>
    <span class="mx-3 text-gray-400">/</span>
    <span class="text-gray-500"><?php echo htmlspecialchars($lelang['nama_barang'] ?? 'Detail'); ?></span>
  </div>

  <!-- Alert Messages -->
  <?php if(isset($success)): ?>
  <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-xl flex items-center" data-aos="fade-down">
    <i class="fas fa-check-circle text-green-500 text-2xl mr-3"></i>
    <p class="text-green-700 font-semibold"><?php echo $success; ?></p>
  </div>
  <?php endif; ?>
  <?php if(isset($error)): ?>
  <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-xl flex items-center" data-aos="fade-down">
    <i class="fas fa-exclamation-circle text-red-500 text-2xl mr-3"></i>
    <p class="text-red-700 font-semibold"><?php echo $error; ?></p>
  </div>
  <?php endif; ?>

  <?php if(!$lelang): ?>
  <div class="bg-white rounded-2xl shadow-lg p-16 text-center border" style="border-color:var(--primary-100)">
    <i class="fas fa-exclamation-triangle text-5xl mb-4" style="color:var(--primary-300)"></i>
    <h3 class="text-2xl font-bold mb-2" style="color:var(--primary-800)">Lelang Tidak Ditemukan</h3>
    <a href="barang_lelang.php" class="mt-4 inline-flex items-center gap-2 btn-primary" style="width:auto">
      <i class="fas fa-arrow-left"></i>Kembali
    </a>
  </div>
  <?php else: ?>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left: Detail Barang -->
    <div class="lg:col-span-2">
      <!-- Image & Status -->
      <div class="bg-white rounded-2xl shadow-lg overflow-hidden border hover-glow mb-6" style="border-color:var(--primary-100)" data-aos="fade-right">
        <div class="h-72 flex items-center justify-center relative" style="background:linear-gradient(135deg,var(--primary-50),var(--primary-100))">
          <div class="w-28 h-28 rounded-2xl flex items-center justify-center animate-float gradient-bg shadow-2xl">
            <i class="fas fa-box text-white text-5xl"></i>
          </div>
          <div class="absolute top-4 right-4">
            <?php if($lelang['status'] == 'dibuka'): ?>
            <span class="px-4 py-2 bg-green-500 text-white rounded-full font-semibold flex items-center shadow-lg text-sm">
              <span class="w-2 h-2 bg-white rounded-full mr-2 animate-pulse"></span>Sedang Berlangsung
            </span>
            <?php else: ?>
            <span class="px-4 py-2 bg-red-500 text-white rounded-full font-semibold flex items-center shadow-lg text-sm">
              <i class="fas fa-times-circle mr-2"></i>Ditutup
            </span>
            <?php endif; ?>
          </div>
          <?php if($user_bid): ?>
          <div class="absolute top-4 left-4 px-3 py-2 rounded-xl text-white text-sm font-semibold shadow-lg" style="background:rgba(30,58,102,0.85)">
            <i class="fas fa-check-circle mr-2"></i>Bid Anda: <?php echo 'Rp '.number_format($user_bid['penawaran_harga'],0,',','.'); ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="p-8">
          <h1 class="text-3xl font-bold mb-2" style="color:var(--primary-800)"><?php echo htmlspecialchars($lelang['nama_barang']); ?></h1>
          <p class="flex items-center mb-6" style="color:var(--primary-500)">
            <i class="fas fa-calendar mr-2"></i>Tanggal Lelang: <?php echo formatTanggal($lelang['tgl_lelang']); ?>
          </p>
          <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="rounded-xl p-4" style="background:var(--primary-50)">
              <p class="text-sm text-gray-500 mb-1">Harga Awal</p>
              <p class="text-2xl font-bold" style="color:var(--primary-700)"><?php echo formatRupiah($lelang['harga_awal']); ?></p>
            </div>
            <div class="rounded-xl p-4 bg-green-50">
              <p class="text-sm text-green-600 mb-1">Harga Saat Ini</p>
              <p class="text-2xl font-bold text-green-600"><?php echo formatRupiah($lelang['harga_akhir']); ?></p>
            </div>
          </div>
          <div>
            <h3 class="text-xl font-bold mb-4 flex items-center" style="color:var(--primary-800)">
              <i class="fas fa-align-left mr-2" style="color:var(--primary-500)"></i>Deskripsi Barang
            </h3>
            <p class="text-gray-600 leading-relaxed whitespace-pre-line"><?php echo nl2br(htmlspecialchars($lelang['deskripsi_barang'])); ?></p>
          </div>
          <div class="mt-6 pt-6 border-t grid grid-cols-2 gap-4 text-sm" style="border-color:var(--primary-100)">
            <div class="flex items-center text-gray-600"><i class="fas fa-box mr-2" style="color:var(--primary-500)"></i>Kondisi: Baru</div>
            <div class="flex items-center text-gray-600"><i class="fas fa-shield-alt mr-2" style="color:var(--primary-500)"></i>Garansi Resmi</div>
          </div>
        </div>
      </div>

      <!-- Leaderboard -->
      <div class="bg-white rounded-2xl shadow-lg p-6 border hover-glow" style="border-color:var(--primary-100)" data-aos="fade-up">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl font-bold flex items-center" style="color:var(--primary-800)">
            <i class="fas fa-list-ol mr-2" style="color:var(--primary-500)"></i>Leaderboard Penawaran
          </h2>
          <span class="text-sm px-3 py-1.5 rounded-xl" style="background:var(--primary-50);color:var(--primary-600)">
            <?php echo mysqli_num_rows($history); ?> penawaran
          </span>
        </div>
        <?php if(mysqli_num_rows($history) > 0): ?>
        <div class="space-y-3">
          <?php $rank=1; while($h=mysqli_fetch_assoc($history)): $is_me=($h['id_user']==$id_user); ?>
          <div class="flex items-center justify-between p-4 rounded-xl transition-all" style="background:<?php echo $is_me ? 'var(--primary-50)' : '#f8fafc'; ?>;border:<?php echo $is_me ? '2px solid var(--primary-300)' : '1px solid var(--primary-100)'; ?>">
            <div class="flex items-center space-x-4">
              <div class="rank-badge <?php echo $rank==1 ? 'bg-yellow-100 text-yellow-600' : ($rank==2 ? 'bg-gray-200 text-gray-600' : ($rank==3 ? 'bg-orange-100 text-orange-600' : '')); ?>" style="<?php echo $rank>3 ? 'background:var(--primary-100);color:var(--primary-600)' : ''; ?>">
                <?php echo $rank<=3 ? ['🥇','🥈','🥉'][$rank-1] : $rank; ?>
              </div>
              <div>
                <p class="font-semibold" style="color:var(--primary-800)">
                  <?php echo htmlspecialchars($h['nama_lengkap']); ?>
                  <?php if($is_me): ?><span class="ml-2 text-xs px-2 py-0.5 rounded-full text-white" style="background:var(--primary-600)">Anda</span><?php endif; ?>
                </p>
                <p class="text-xs text-gray-500"><i class="fas fa-clock mr-1"></i><?php echo date('d/m/Y H:i', strtotime($h['created_at'])); ?></p>
              </div>
            </div>
            <div class="text-right">
              <p class="text-lg font-bold" style="color:<?php echo $is_me ? 'var(--primary-600)' : '#16a34a'; ?>"><?php echo formatRupiah($h['penawaran_harga']); ?></p>
              <?php if($rank==1 && $lelang['status']=='dibuka'): ?><p class="text-xs text-yellow-600 font-semibold">Memimpin </p><?php endif; ?>
            </div>
          </div>
          <?php $rank++; endwhile; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-8">
          <i class="fas fa-inbox text-5xl mb-3" style="color:var(--primary-200)"></i>
          <p class="text-gray-500">Belum ada penawaran untuk barang ini</p>
          <p class="text-sm text-gray-400 mt-1">Jadilah yang pertama menawar!</p>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Right: Bid Form -->
    <div class="lg:col-span-1">
      <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-24 border" style="border-color:var(--primary-100)" data-aos="fade-left">
        <h2 class="text-2xl font-bold mb-6 flex items-center" style="color:var(--primary-800)">
          <i class="fas fa-gavel mr-2" style="color:var(--primary-500)"></i>Buat Penawaran
        </h2>

        <?php if($lelang['status'] == 'dibuka'): ?>
        <form method="POST" class="space-y-5">
          <input type="hidden" name="id_barang" value="<?php echo $lelang['id_barang']; ?>">
          <!-- Min Price -->
          <div class="gradient-bg rounded-xl p-5 text-white">
            <p class="text-sm text-white/80 mb-1">Harga Minimum Penawaran</p>
            <p class="text-3xl font-bold"><?php echo formatRupiah($lelang['harga_akhir'] + 50000); ?></p>
            <p class="text-xs text-white/60 mt-2"><i class="fas fa-info-circle mr-1"></i>Kelipatan Rp 50.000</p>
          </div>
          <!-- Input -->
          <div>
            <label class="block font-semibold mb-2 text-sm" style="color:var(--primary-700)">
              <i class="fas fa-money-bill-wave mr-2" style="color:var(--primary-500)"></i>Masukkan Nominal
            </label>
            <div class="relative">
              <span class="absolute left-4 top-3.5 text-gray-500 font-semibold">Rp</span>
              <input type="text" id="rupiah" class="w-full pl-12 pr-4 py-3 border rounded-xl text-lg font-semibold focus:outline-none focus:ring-2 focus:ring-blue-300 transition-all" style="border-color:var(--primary-200)" placeholder="0" onkeyup="formatRupiah(this)">
              <input type="hidden" name="penawaran_harga" id="penawaran_harga">
            </div>
          </div>
          <!-- Quick Bid -->
          <div class="grid grid-cols-2 gap-2">
            <?php $base = $lelang['harga_akhir'] + 50000; ?>
            <button type="button" onclick="setBid(50000)" class="px-3 py-2 rounded-xl text-sm font-semibold transition-all hover:scale-105" style="background:var(--primary-50);color:var(--primary-700)">+ Rp 50rb</button>
            <button type="button" onclick="setBid(100000)" class="px-3 py-2 rounded-xl text-sm font-semibold transition-all hover:scale-105" style="background:var(--primary-50);color:var(--primary-700)">+ Rp 100rb</button>
            <button type="button" onclick="setBid(250000)" class="px-3 py-2 rounded-xl text-sm font-semibold transition-all hover:scale-105" style="background:var(--primary-50);color:var(--primary-700)">+ Rp 250rb</button>
            <button type="button" onclick="setBid(500000)" class="px-3 py-2 rounded-xl text-sm font-semibold transition-all hover:scale-105" style="background:var(--primary-50);color:var(--primary-700)">+ Rp 500rb</button>
          </div>
          <!-- Submit -->
          <button type="submit" name="submit_penawaran" class="btn-primary py-4 text-base hover:scale-105">
            <i class="fas fa-hand-holding-usd"></i>Ajukan Penawaran
          </button>
        </form>

        <?php if($lelang['status']=='dibuka'): ?>
        <div class="mt-5 p-4 rounded-xl bg-blue-50">
          <div class="flex items-center justify-between">
            <span class="text-blue-700 font-semibold text-sm">Sisa Waktu</span>
            <span class="text-blue-800 font-bold text-sm" id="countdown">-</span>
          </div>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="text-center py-8">
          <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4" style="background:var(--primary-50)">
            <i class="fas fa-lock text-3xl" style="color:var(--primary-300)"></i>
          </div>
          <p class="font-semibold text-lg" style="color:var(--primary-800)">Lelang Telah Ditutup</p>
          <p class="text-sm text-gray-400 mt-2">Tidak dapat mengajukan penawaran</p>
          <?php
          $winner_check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tb_lelang WHERE id_lelang=$id_lelang AND id_user=$id_user"));
          if($winner_check):
          ?>
          <div class="mt-6 p-4 bg-green-50 rounded-xl border border-green-200">
            <i class="fas fa-trophy text-green-500 text-3xl mb-2"></i>
            <p class="text-green-700 font-semibold">Selamat! Anda pemenangnya </p>
            <a href="pembayaran.php" class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-semibold transition-all">
              <i class="fas fa-credit-card"></i>Lanjut ke Pembayaran
            </a>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Info Penting -->
        <div class="mt-6 pt-6 border-t" style="border-color:var(--primary-100)">
          <h3 class="font-bold mb-4 flex items-center" style="color:var(--primary-700)">
            <i class="fas fa-info-circle mr-2" style="color:var(--primary-500)"></i>Informasi Penting
          </h3>
          <ul class="space-y-2 text-sm text-gray-600">
            <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mr-2 mt-0.5"></i>Penawaran bersifat mengikat</li>
            <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mr-2 mt-0.5"></i>Bayar dalam 3x24 jam setelah menang</li>
            <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mr-2 mt-0.5"></i>Pastikan dana tersedia sebelum menawar</li>
            <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mr-2 mt-0.5"></i>Barang dikirim setelah pembayaran dikonfirmasi</li>
          </ul>
        </div>
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

function formatRupiah(input){
  let v=input.value.replace(/[^,\d]/g,'').toString();
  let split=v.split(',');let sisa=split[0].length%3;
  let r=split[0].substr(0,sisa);let ribuan=split[0].substr(sisa).match(/\d{3}/gi);
  if(ribuan){let sep=sisa?'.':'';r+=sep+ribuan.join('.');}
  r=split[1]!=undefined?r+','+split[1]:r;input.value=r;
  document.getElementById('penawaran_harga').value=v.replace(/\./g,'');
}

function setBid(increment){
  let base=<?php echo (int)($lelang['harga_akhir'] ?? 0)+50000; ?>;
  let cur=parseInt(document.getElementById('penawaran_harga').value)||base;
  let newBid=cur+increment;
  document.getElementById('rupiah').value=newBid.toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.');
  document.getElementById('penawaran_harga').value=newBid;
}

window.onload=function(){
  let init=<?php echo (int)($lelang['harga_akhir'] ?? 0)+50000; ?>;
  document.getElementById('rupiah').value=init.toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.');
  document.getElementById('penawaran_harga').value=init;
};

// Countdown
const endDate=new Date();endDate.setDate(endDate.getDate()+7);
function updateCountdown(){
  const diff=endDate-new Date();
  if(diff<=0){const el=document.getElementById('countdown');if(el)el.textContent='Berakhir';return;}
  const d=Math.floor(diff/(1000*60*60*24));const h=Math.floor((diff%(1000*60*60*24))/(1000*60*60));const m=Math.floor((diff%(1000*60*60))/(1000*60));
  const el=document.getElementById('countdown');if(el)el.textContent=d+'h '+h+'j '+m+'m';
}
updateCountdown();setInterval(updateCountdown,60000);
</script>
</body>
</html>