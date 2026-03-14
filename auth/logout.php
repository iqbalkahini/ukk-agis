<?php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Logout — LelangOnline</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{
  --p50:#eef2f8;--p100:#d9e2f0;--p200:#b3c5e1;--p300:#8da8d2;
  --p400:#678bc3;--p500:#416eb4;--p600:#2a4f8c;--p700:#1e3a66;
  --p800:#132a4a;--p900:#0a1a30;
  --s50:#f8fafc;--s100:#f1f5f9;--s200:#e2e8f0;
  --s400:#94a3b8;--s500:#64748b;--s900:#0f172a;
}
html{height:100%;}
body{
  font-family:'Plus Jakarta Sans',sans-serif;
  min-height:100vh;display:flex;align-items:center;justify-content:center;
  padding:1.5rem;
  background:linear-gradient(145deg,var(--p900) 0%,var(--p800) 30%,var(--p700) 60%,var(--p600) 85%,var(--p500) 100%);
  position:relative;overflow:hidden;
}
body::before{
  content:'';position:fixed;inset:0;
  background:url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.025'%3E%3Ccircle cx='40' cy='40' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
  pointer-events:none;
}
.orb{position:fixed;border-radius:50%;filter:blur(90px);pointer-events:none;animation:orbF 14s ease-in-out infinite;}
.orb-1{width:600px;height:600px;background:rgba(103,139,195,0.18);top:-250px;right:-150px;}
.orb-2{width:450px;height:450px;background:rgba(30,58,102,0.25);bottom:-180px;left:-120px;animation-delay:-7s;}
.orb-3{width:280px;height:280px;background:rgba(141,168,210,0.15);top:40%;left:20%;animation-delay:-3.5s;}
@keyframes orbF{0%,100%{transform:translateY(0) scale(1);}50%{transform:translateY(-25px) scale(1.04);}}

.wrap{position:relative;z-index:10;display:flex;flex-direction:column;align-items:center;}

.card{
  background:rgba(255,255,255,0.97);border-radius:28px;
  padding:44px 36px;text-align:center;
  box-shadow:0 32px 80px -12px rgba(10,26,48,0.5),0 0 0 1px rgba(255,255,255,0.1);
  width:100%;max-width:380px;
  animation:slideUp .5s cubic-bezier(.16,1,.3,1) both;
}
@keyframes slideUp{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:none;}}

.icon-wrap{
  width:76px;height:76px;border-radius:50%;
  background:var(--p50);border:2px solid var(--p100);
  display:flex;align-items:center;justify-content:center;
  margin:0 auto 20px;position:relative;
}
.icon-wrap i{font-size:1.9rem;color:var(--p700);}
.badge{
  position:absolute;bottom:0;right:0;
  width:24px;height:24px;border-radius:50%;
  background:#22c55e;border:2.5px solid #fff;
  display:flex;align-items:center;justify-content:center;
}
.badge i{font-size:.5rem;color:#fff;}

h2{font-size:1.3rem;font-weight:800;color:var(--s900);margin-bottom:6px;}
.sub{font-size:.85rem;color:var(--s400);line-height:1.6;margin-bottom:22px;}

.pill{
  display:inline-flex;align-items:center;gap:8px;
  background:var(--p50);border:1px solid var(--p100);
  border-radius:100px;padding:.4rem .95rem;
  font-size:.78rem;color:var(--p700);font-weight:600;margin-bottom:22px;
}
.pill .num{font-weight:800;color:var(--p500);}

.btn{
  display:flex;align-items:center;justify-content:center;gap:8px;
  padding:12px;border-radius:13px;
  font-family:inherit;font-size:.875rem;font-weight:700;
  text-decoration:none;transition:all .2s;
  border:none;cursor:pointer;width:100%;
}
.btn+.btn{margin-top:8px;}
.btn-primary{
  background:linear-gradient(135deg,var(--p700),var(--p500));
  color:#fff;box-shadow:0 6px 20px -4px rgba(30,58,102,0.4);
}
.btn-primary:hover{opacity:.9;transform:translateY(-1px);box-shadow:0 10px 26px -4px rgba(30,58,102,0.45);}
.btn-ghost{
  background:transparent;color:var(--p700);
  border:1.5px solid var(--p200);
}
.btn-ghost:hover{background:var(--p50);border-color:var(--p300);}

.pbar{height:3px;background:var(--p100);border-radius:3px;margin-top:26px;overflow:hidden;}
.pbar-fill{
  height:100%;
  background:linear-gradient(90deg,var(--p700),var(--p500));
  transform-origin:left;
  animation:shrink 4s linear forwards;
}
@keyframes shrink{from{transform:scaleX(1);}to{transform:scaleX(0);}}

.page-footer{margin-top:1.25rem;text-align:center;font-size:.7rem;color:rgba(255,255,255,0.35);letter-spacing:.03em;}
</style>
</head>
<body>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="wrap">
  <div class="card">
    <div class="icon-wrap">
      <i class="fas fa-sign-out-alt"></i>
      <div class="badge"><i class="fas fa-check"></i></div>
    </div>

    <h2>Berhasil Keluar</h2>
    <p class="sub">Anda telah logout dengan aman.<br>Sampai jumpa kembali!</p>

    <div class="pill">
      <i class="fas fa-clock" style="font-size:.7rem;opacity:.6;"></i>
      kembali dalam <span class="num" id="cn">4</span> detik
    </div>

    <a href="login.php" class="btn btn-primary">
      <i class="fas fa-arrow-right-to-bracket"></i> Login Kembali
    </a>
    <a href="../index.php" class="btn btn-ghost">
      <i class="fas fa-house"></i> Ke Beranda
    </a>

    <div class="pbar"><div class="pbar-fill"></div></div>
  </div>



<script>
var n=4,el=document.getElementById('cn');
setInterval(function(){n--;if(n<=0){location.href='login.php';return;}el.textContent=n;},1000);
</script>
</body>
</html>