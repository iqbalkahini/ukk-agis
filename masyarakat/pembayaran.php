<?php
session_start();
require_once('../config/config.php');
checkLevel([3]);

$id_user = $_SESSION['id_user'];

$lelang_menang = mysqli_query($conn, "SELECT l.*, b.nama_barang, b.harga_awal,
    (SELECT status_pembayaran FROM tb_pembayaran WHERE id_lelang = l.id_lelang AND id_user = $id_user ORDER BY id_pembayaran DESC LIMIT 1) as status_bayar
    FROM tb_lelang l JOIN tb_barang b ON l.id_barang = b.id_barang
    WHERE l.id_user = $id_user AND l.status = 'ditutup'
    ORDER BY l.created_at DESC");

$total_menang    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM tb_lelang WHERE id_user=$id_user AND status='ditutup'"))['t'];
$total_lunas     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM tb_pembayaran WHERE id_user=$id_user AND status_pembayaran='selesai'"))['t'];
$total_pending   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM tb_pembayaran WHERE id_user=$id_user AND status_pembayaran='dibayar'"))['t'];
$total_penawaran = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM history_lelang WHERE id_user=$id_user"))['t'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pembayaran - Sistem Lelang Online</title>
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
.stat-card{background:white;border-radius:24px;padding:1.5rem;box-shadow:0 10px 25px -5px rgba(30,58,102,0.08);border:1px solid var(--primary-100);transition:all 0.4s cubic-bezier(0.175,0.885,0.32,1.275);position:relative;overflow:hidden;}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,var(--primary-400),var(--primary-600));transform:scaleX(0);transition:transform 0.4s ease;}
.stat-card:hover::before{transform:scaleX(1);}
.stat-card:hover{transform:translateY(-8px) scale(1.02);box-shadow:0 25px 35px -8px rgba(30,58,102,0.2);}
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
.btn-primary{background:var(--primary-600);color:white;padding:.65rem 1.25rem;border-radius:12px;font-weight:600;transition:all 0.3s ease;display:inline-flex;align-items:center;gap:.5rem;}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 12px 20px -8px rgba(30,58,102,0.4);}
.payment-item{border-bottom:1px solid var(--primary-100);transition:all 0.2s ease;}
.payment-item:hover{background:var(--primary-50);}
.payment-item:last-child{border-bottom:none;}
.particle{position:fixed;width:4px;height:4px;background:var(--primary-300);border-radius:50%;opacity:0.3;animation:particleFloat 15s infinite linear;}
@keyframes particleFloat{from{transform:translateY(100vh) rotate(0deg);}to{transform:translateY(-100vh) rotate(360deg);}}
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
          <span class="ml-auto px-2 py-1 rounded-lg text-xs font-bold" style="background:var(--primary-100);color:var(--primary-600)"><?php echo $total_penawaran; ?></span>
        </a>
        <a href="pembayaran.php" class="flex items-center px-4 py-3.5 gradient-bg text-white rounded-xl shadow-lg transition-all">
          <i class="fas fa-credit-card w-6"></i><span class="ml-3 font-medium">Pembayaran</span>
          <?php if($total_pending > 0): ?><span class="ml-auto px-2 py-1 rounded-lg text-xs font-bold bg-white/20 animate-pulse-soft"><?php echo $total_pending; ?></span><?php endif; ?>
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
  <!-- Header Banner -->
  <div class="mb-8 relative overflow-hidden rounded-3xl" data-aos="fade-down">
    <div class="gradient-bg rounded-3xl p-8 text-white shadow-xl relative">
      <div class="absolute top-0 right-0 w-80 h-80 bg-white opacity-5 rounded-full -mr-20 -mt-20 animate-float"></div>
      <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-3xl lg:text-4xl font-bold mb-2"><i class="fas fa-credit-card mr-3"></i>Pembayaran</h1>
          <p style="color:rgba(255,255,255,0.8)">Kelola pembayaran lelang yang Anda menangkan</p>
        </div>
        <div class="mt-4 md:mt-0">
          <div class="bg-white/10 backdrop-blur rounded-2xl px-6 py-4 border border-white/20">
            <p class="text-sm" style="color:rgba(255,255,255,0.8)">Lelang Dimenangkan</p>
            <p class="text-4xl font-bold"><?php echo $total_menang; ?></p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Stat Cards -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
      <div class="flex items-center justify-between mb-4">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:var(--primary-50)">
          <i class="fas fa-trophy text-2xl" style="color:var(--primary-600)"></i>
        </div>
        <span class="text-3xl font-bold" style="color:var(--primary-800)"><?php echo $total_menang; ?></span>
      </div>
      <h3 class="text-gray-600 font-medium">Total Lelang Menang</h3>
      <div class="mt-3 flex items-center text-sm"><span class="flex items-center px-3 py-1.5 rounded-full" style="background:var(--primary-50);color:var(--primary-700)"><i class="fas fa-gavel mr-2"></i>Kemenangan</span></div>
    </div>
    <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
      <div class="flex items-center justify-between mb-4">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:var(--primary-50)">
          <i class="fas fa-check-circle text-2xl text-green-600"></i>
        </div>
        <span class="text-3xl font-bold" style="color:var(--primary-800)"><?php echo $total_lunas; ?></span>
      </div>
      <h3 class="text-gray-600 font-medium">Sudah Lunas</h3>
      <div class="mt-3 flex items-center text-sm"><span class="flex items-center px-3 py-1.5 rounded-full bg-green-50 text-green-700"><i class="fas fa-check-circle mr-2"></i>Selesai</span></div>
    </div>
    <div class="stat-card" data-aos="fade-up" data-aos-delay="300">
      <div class="flex items-center justify-between mb-4">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:var(--primary-50)">
          <i class="fas fa-clock text-2xl text-amber-600"></i>
        </div>
        <span class="text-3xl font-bold" style="color:var(--primary-800)"><?php echo $total_pending; ?></span>
      </div>
      <h3 class="text-gray-600 font-medium">Menunggu Konfirmasi</h3>
      <div class="mt-3 flex items-center text-sm"><span class="flex items-center px-3 py-1.5 rounded-full bg-amber-50 text-amber-700 animate-pulse-soft"><i class="fas fa-clock mr-2"></i>Pending</span></div>
    </div>
  </div>

  <!-- Info Box -->
  <div class="rounded-2xl p-6 mb-8 border shadow-lg" style="background:var(--primary-700);border-color:var(--primary-600)" data-aos="fade-up">
    <div class="flex items-start">
      <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
        <i class="fas fa-info-circle text-white text-xl"></i>
      </div>
      <div>
        <h3 class="font-bold text-white mb-2">Informasi Penting</h3>
        <ul class="text-white/80 space-y-1 text-sm">
          <li>• Selesaikan pembayaran maksimal 3x24 jam setelah lelang ditutup</li>
          <li>• Upload bukti pembayaran yang jelas dan valid</li>
          <li>• Hubungi admin jika ada kendala dalam pembayaran</li>
          <li>• Barang akan dikirim setelah pembayaran dikonfirmasi</li>
        </ul>
      </div>
    </div>
  </div>

  <!-- Daftar Lelang -->
  <div class="bg-white rounded-2xl shadow-lg overflow-hidden border hover-glow mb-8" style="border-color:var(--primary-100)" data-aos="zoom-in">
    <div class="p-6 border-b" style="border-color:var(--primary-100);background:linear-gradient(to right,var(--primary-50),white)">
      <h2 class="text-xl font-bold flex items-center" style="color:var(--primary-800)">
        <i class="fas fa-trophy mr-2" style="color:var(--primary-500)"></i>Lelang yang Anda Menangkan
      </h2>
    </div>
    <div>
      <?php while($row = mysqli_fetch_assoc($lelang_menang)): ?>
      <div class="payment-item p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
          <div class="flex-1 mb-4 lg:mb-0">
            <div class="flex items-start">
              <div class="w-16 h-16 rounded-2xl flex items-center justify-center mr-4 flex-shrink-0" style="background:var(--primary-50)">
                <i class="fas fa-box text-2xl" style="color:var(--primary-500)"></i>
              </div>
              <div>
                <h3 class="font-bold text-xl mb-2" style="color:var(--primary-800)"><?php echo htmlspecialchars($row['nama_barang']); ?></h3>
                <div class="space-y-1 text-sm text-gray-500">
                  <p><i class="fas fa-calendar mr-2" style="color:var(--primary-400)"></i>Tanggal Lelang: <?php echo formatTanggal($row['tgl_lelang']); ?></p>
                  <p><i class="fas fa-tag mr-2" style="color:var(--primary-400)"></i>Harga Awal: <?php echo formatRupiah($row['harga_awal']); ?></p>
                </div>
              </div>
            </div>
          </div>
          <div class="lg:ml-8 lg:text-right">
            <p class="text-sm mb-1" style="color:var(--primary-500)">Total Pembayaran</p>
            <p class="text-3xl font-bold mb-3" style="color:var(--primary-700)"><?php echo formatRupiah($row['harga_akhir']); ?></p>
            <?php $sb=$row['status_bayar']; if($sb=='selesai'): ?>
              <span class="inline-flex items-center px-4 py-2 rounded-full font-semibold bg-green-50 text-green-700 border border-green-200">
                <i class="fas fa-check-circle mr-2"></i>Lunas
              </span>
            <?php elseif($sb=='dibayar'): ?>
              <span class="inline-flex items-center px-4 py-2 rounded-full font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                <i class="fas fa-clock mr-2"></i>Menunggu Konfirmasi
              </span>
            <?php else: ?>
              <a href="proses_bayar.php?id=<?php echo $row['id_lelang']; ?>" class="btn-primary">
                <i class="fas fa-money-bill-wave"></i>Bayar Sekarang
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>

    <?php
    mysqli_data_seek($lelang_menang, 0);
    if(mysqli_num_rows($lelang_menang) == 0):
    ?>
    <div class="p-16 text-center">
      <div class="w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6 animate-float" style="background:var(--primary-50)">
        <i class="fas fa-inbox text-4xl" style="color:var(--primary-300)"></i>
      </div>
      <h3 class="text-2xl font-bold mb-2" style="color:var(--primary-800)">Belum Ada Lelang yang Dimenangkan</h3>
      <p class="text-gray-500 mb-6">Anda belum memenangkan lelang apapun</p>
      <a href="barang_lelang.php" class="btn-primary" style="width:auto;margin:0 auto">
        <i class="fas fa-gavel"></i>Lihat Barang Lelang
      </a>
    </div>
    <?php endif; ?>
  </div>

  <!-- Metode Pembayaran -->
  <div class="bg-white rounded-2xl shadow-lg p-6 border hover-glow" style="border-color:var(--primary-100)" data-aos="fade-up">
    <h3 class="text-xl font-bold mb-6 flex items-center" style="color:var(--primary-800)">
      <i class="fas fa-university mr-2" style="color:var(--primary-500)"></i>Metode Pembayaran Tersedia
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <?php
      $banks = [
        ['icon'=>'fa-university','name'=>'Bank BCA','no'=>'1234567890','label'=>'Transfer Bank BCA'],
        ['icon'=>'fa-university','name'=>'Bank Mandiri','no'=>'0987654321','label'=>'Transfer Bank Mandiri'],
        ['icon'=>'fa-university','name'=>'Bank BNI','no'=>'5678901234','label'=>'Transfer Bank BNI'],
        ['icon'=>'fa-mobile-alt','name'=>'E-Wallet (OVO/GoPay/Dana)','no'=>'081234567890','label'=>'E-Wallet'],
      ];
      foreach($banks as $bank):
      ?>
      <div class="rounded-xl p-4 border transition-all hover:scale-105 hover:shadow-lg" style="background:var(--primary-50);border-color:var(--primary-100)">
        <div class="flex items-center mb-3">
          <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center mr-3">
            <i class="fas <?php echo $bank['icon']; ?> text-white"></i>
          </div>
          <h4 class="font-bold" style="color:var(--primary-800)"><?php echo $bank['label']; ?></h4>
        </div>
        <p class="text-sm text-gray-600 mb-1"><i class="fas fa-credit-card mr-2" style="color:var(--primary-400)"></i>No: <?php echo $bank['no']; ?></p>
        <p class="text-sm text-gray-600"><i class="fas fa-user mr-2" style="color:var(--primary-400)"></i>a.n. Sistem Lelang Online</p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
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
</script>
</body>
</html>