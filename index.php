<?php
session_start();
require_once('config/config.php');

$active_lelang = 0;
$r = mysqli_query($conn, "SELECT COUNT(*) as t FROM tb_lelang WHERE status='dibuka'");
if($r) { $active_lelang = mysqli_fetch_assoc($r)['t'] ?? 0; mysqli_free_result($r); }

$featured_items = mysqli_query($conn, "SELECT l.*, b.nama_barang, b.harga_awal, b.gambar,
  (SELECT MAX(penawaran_harga) FROM history_lelang WHERE id_lelang=l.id_lelang) as ht,
  (SELECT COUNT(*) FROM history_lelang WHERE id_lelang=l.id_lelang) as tb 
  FROM tb_lelang l JOIN tb_barang b ON l.id_barang=b.id_barang 
  WHERE l.status='dibuka' ORDER BY l.created_at DESC LIMIT 6");

$total_users = 1250; $total_sold = 345; $total_value = 85000000;
$ct = mysqli_query($conn, "SHOW TABLES");
if($ct) {
  $tables = [];
  while($rr = mysqli_fetch_array($ct)) $tables[] = $rr[0];
  mysqli_free_result($ct);
  if(in_array('tb_user',$tables)){$r=mysqli_query($conn,"SELECT COUNT(*) as t FROM tb_user");if($r){$v=mysqli_fetch_assoc($r)['t']??0;if($v>100)$total_users=$v;mysqli_free_result($r);}}
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>LelangOnline — Platform Lelang Modern</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{
  --p50:#eef2f8;--p100:#d9e2f0;--p200:#b3c5e1;--p300:#8da8d2;
  --p400:#678bc3;--p500:#416eb4;--p600:#2a4f8c;--p700:#1e3a66;
  --p800:#132a4a;--p900:#0a1a30;
  --s50:#f8fafc;--s100:#f1f5f9;--s200:#e2e8f0;--s300:#cbd5e1;
  --s400:#94a3b8;--s500:#64748b;--s700:#334155;--s900:#0f172a;
  --white:#ffffff;--red:#ef4444;--green:#22c55e;--amber:#f59e0b;
}
html{scroll-behavior:smooth;}
body{font-family:'Plus Jakarta Sans',sans-serif;color:var(--s900);background:var(--s50);overflow-x:hidden;}
.container{max-width:1200px;margin:0 auto;padding:0 1.5rem;}

/* ===== NAVBAR ===== */
.navbar{position:fixed;top:0;left:0;right:0;z-index:1000;padding:.875rem 0;transition:all .3s;background:transparent;}
.navbar.scrolled{background:rgba(255,255,255,0.95);backdrop-filter:blur(12px);box-shadow:0 1px 0 var(--s200);padding:.6rem 0;}
.nav-inner{display:flex;align-items:center;justify-content:space-between;gap:1rem;}
.logo{display:flex;align-items:center;gap:10px;text-decoration:none;}
.logo-icon{width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,var(--p700),var(--p500));display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1rem;box-shadow:0 4px 12px -2px rgba(30,58,102,0.4);}
.navbar:not(.scrolled) .logo-icon{background:rgba(255,255,255,0.2);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.25);}
.logo-name{font-size:1.15rem;font-weight:800;color:var(--p900);letter-spacing:-.02em;}
.navbar:not(.scrolled) .logo-name{color:#fff;}
.logo-name span{color:var(--p500);}
.navbar:not(.scrolled) .logo-name span{color:#93c5fd;}
.nav-links{display:flex;align-items:center;gap:1.75rem;}
.nav-links a{text-decoration:none;font-size:.875rem;font-weight:600;color:var(--s500);transition:color .2s;}
.navbar:not(.scrolled) .nav-links a{color:rgba(255,255,255,0.75);}
.nav-links a:hover{color:var(--p600);}
.navbar:not(.scrolled) .nav-links a:hover{color:#fff;}
.nav-actions{display:flex;align-items:center;gap:.6rem;}
.btn-nav{padding:.5rem 1.1rem;border-radius:10px;font-family:inherit;font-size:.82rem;font-weight:700;text-decoration:none;transition:all .2s;cursor:pointer;border:none;}
.btn-nav-ghost{background:transparent;color:var(--s700);border:1.5px solid var(--s200);}
.navbar:not(.scrolled) .btn-nav-ghost{color:#fff;border-color:rgba(255,255,255,0.3);}
.btn-nav-ghost:hover{border-color:var(--p400);background:var(--p50);color:var(--p600);}
.navbar:not(.scrolled) .btn-nav-ghost:hover{background:rgba(255,255,255,0.15);border-color:rgba(255,255,255,0.5);}
.btn-nav-solid{background:linear-gradient(135deg,var(--p700),var(--p500));color:#fff;box-shadow:0 4px 12px -2px rgba(30,58,102,0.4);}
.navbar:not(.scrolled) .btn-nav-solid{background:rgba(255,255,255,0.2);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.3);box-shadow:none;}
.btn-nav-solid:hover{background:linear-gradient(135deg,var(--p600),var(--p400));transform:translateY(-1px);box-shadow:0 8px 20px -4px rgba(30,58,102,0.45);}
.hamburger{display:none;background:none;border:none;font-size:1.4rem;color:var(--s700);cursor:pointer;padding:4px;}
.navbar:not(.scrolled) .hamburger{color:#fff;}
.mobile-menu{display:none;background:#fff;border-top:1px solid var(--s200);padding:1rem 0;}
.mobile-menu.open{display:block;}
.mobile-menu a{display:block;padding:.65rem 1.5rem;font-size:.9rem;font-weight:600;color:var(--s700);text-decoration:none;transition:all .15s;}
.mobile-menu a:hover{color:var(--p600);background:var(--p50);}
.mobile-menu .mobile-actions{padding:.75rem 1.5rem;display:flex;gap:.5rem;}
@media(max-width:768px){.nav-links,.nav-actions{display:none;}.hamburger{display:block;}}

/* ===== HERO ===== */
.hero{min-height:100vh;background:linear-gradient(145deg,var(--p900) 0%,var(--p800) 30%,var(--p700) 60%,var(--p600) 85%,var(--p500) 100%);display:flex;align-items:center;padding:6rem 0 4rem;position:relative;overflow:hidden;}
.hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.025'%3E%3Ccircle cx='40' cy='40' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");pointer-events:none;}
.hero-orb{position:absolute;border-radius:50%;filter:blur(90px);pointer-events:none;animation:orbF 14s ease-in-out infinite;}
.hero-orb-1{width:700px;height:700px;background:rgba(103,139,195,0.18);top:-300px;right:-200px;}
.hero-orb-2{width:500px;height:500px;background:rgba(30,58,102,0.25);bottom:-200px;left:-150px;animation-delay:-7s;}
.hero-orb-3{width:300px;height:300px;background:rgba(141,168,210,0.15);top:30%;right:20%;animation-delay:-3.5s;}
@keyframes orbF{0%,100%{transform:translateY(0) scale(1);}50%{transform:translateY(-28px) scale(1.04);}}

.hero-content{position:relative;z-index:2;display:grid;grid-template-columns:1fr 1fr;gap:4rem;align-items:center;}
@media(max-width:900px){.hero-content{grid-template-columns:1fr;text-align:center;gap:2.5rem;}}

.hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.12);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.2);border-radius:100px;padding:.4rem .9rem .4rem .5rem;margin-bottom:1.5rem;}
.hero-badge .dot{width:8px;height:8px;border-radius:50%;background:#4ade80;box-shadow:0 0 0 3px rgba(74,222,128,.25);animation:pulse 2s infinite;}
@keyframes pulse{0%,100%{box-shadow:0 0 0 3px rgba(74,222,128,.25);}50%{box-shadow:0 0 0 6px rgba(74,222,128,.1);}}
.hero-badge span{font-size:.75rem;font-weight:700;color:rgba(255,255,255,0.85);letter-spacing:.04em;}

.hero-title{font-size:clamp(2rem,5vw,3.5rem);font-weight:800;color:#fff;line-height:1.1;letter-spacing:-.035em;margin-bottom:1.2rem;}
.hero-title .accent{color:#93c5fd;display:block;}
.hero-desc{font-size:1.05rem;color:rgba(255,255,255,0.65);line-height:1.75;margin-bottom:2rem;max-width:500px;}
@media(max-width:900px){.hero-desc{margin-left:auto;margin-right:auto;}}

.hero-actions{display:flex;gap:.75rem;flex-wrap:wrap;}
@media(max-width:900px){.hero-actions{justify-content:center;}}
.btn-hero-primary{display:inline-flex;align-items:center;gap:9px;background:#fff;color:var(--p700);padding:.875rem 1.75rem;border-radius:14px;font-family:inherit;font-size:.9rem;font-weight:800;text-decoration:none;transition:all .25s;box-shadow:0 8px 24px -4px rgba(10,26,48,0.35);}
.btn-hero-primary:hover{transform:translateY(-2px);box-shadow:0 14px 32px -6px rgba(10,26,48,0.45);}
.btn-hero-ghost{display:inline-flex;align-items:center;gap:9px;background:rgba(255,255,255,0.12);backdrop-filter:blur(8px);color:#fff;border:1.5px solid rgba(255,255,255,0.25);padding:.875rem 1.75rem;border-radius:14px;font-family:inherit;font-size:.9rem;font-weight:700;text-decoration:none;transition:all .25s;}
.btn-hero-ghost:hover{background:rgba(255,255,255,0.2);border-color:rgba(255,255,255,0.45);transform:translateY(-2px);}

.hero-stats{display:flex;gap:1.5rem;margin-top:2.5rem;}
@media(max-width:900px){.hero-stats{justify-content:center;}}
.hstat{text-align:left;}
.hstat-num{font-size:1.5rem;font-weight:800;color:#fff;letter-spacing:-.03em;}
.hstat-lbl{font-size:.7rem;color:rgba(255,255,255,0.5);font-weight:500;text-transform:uppercase;letter-spacing:.06em;margin-top:1px;}
.hstat-div{width:1px;background:rgba(255,255,255,0.15);align-self:stretch;}

/* Hero visual */
.hero-visual{position:relative;display:flex;justify-content:center;animation:floatY 4s ease-in-out infinite;}
@keyframes floatY{0%,100%{transform:translateY(0);}50%{transform:translateY(-12px);}}
.hero-card-main{background:rgba(255,255,255,0.1);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,0.2);border-radius:24px;padding:24px;width:280px;box-shadow:0 24px 64px -12px rgba(10,26,48,0.5);}
.hero-card-img{width:100%;height:160px;object-fit:cover;border-radius:16px;background:rgba(255,255,255,0.08);}
.hero-card-body{margin-top:16px;}
.hc-title{font-size:.9rem;font-weight:700;color:#fff;margin-bottom:8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.hc-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;}
.hc-label{font-size:.68rem;color:rgba(255,255,255,0.5);font-weight:500;}
.hc-value{font-size:.8rem;font-weight:700;color:#93c5fd;}
.hc-bid-btn{display:flex;align-items:center;justify-content:center;gap:6px;width:100%;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);border-radius:10px;padding:9px;font-family:inherit;font-size:.8rem;font-weight:700;color:#fff;cursor:pointer;transition:all .2s;}
.hc-bid-btn:hover{background:rgba(255,255,255,0.25);}
.hero-card-float{position:absolute;background:rgba(255,255,255,0.95);border-radius:16px;padding:12px 16px;box-shadow:0 12px 32px -4px rgba(10,26,48,0.3);border:1px solid rgba(255,255,255,0.8);}
.hcf-1{top:-16px;right:-40px;display:flex;align-items:center;gap:10px;animation:floatY 3.5s ease-in-out 1s infinite;}
.hcf-2{bottom:0;left:-48px;display:flex;align-items:center;gap:10px;animation:floatY 4.5s ease-in-out .5s infinite;}
.hcf-icon{width:32px;height:32px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.hcf-icon.green{background:#dcfce7;color:#16a34a;}
.hcf-icon.amber{background:#fef9c3;color:#ca8a04;}
.hcf-text .top{font-size:.72rem;font-weight:700;color:var(--s900);}
.hcf-text .bot{font-size:.65rem;color:var(--s500);}
@media(max-width:900px){.hero-visual{display:none;}}

/* ===== STATS BAR ===== */
.stats-bar{background:#fff;border-top:1px solid var(--s200);border-bottom:1px solid var(--s200);}
.stats-bar-inner{display:grid;grid-template-columns:repeat(4,1fr);divide-x:1px solid var(--s200);}
.sbar-item{padding:1.5rem 2rem;display:flex;align-items:center;gap:14px;border-right:1px solid var(--s200);}
.sbar-item:last-child{border-right:none;}
.sbar-icon{width:44px;height:44px;border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.sbar-num{font-size:1.4rem;font-weight:800;color:var(--p900);letter-spacing:-.03em;}
.sbar-lbl{font-size:.75rem;color:var(--s400);font-weight:500;margin-top:1px;}
@media(max-width:768px){.stats-bar-inner{grid-template-columns:1fr 1fr;}.sbar-item{border-bottom:1px solid var(--s200);}}
@media(max-width:480px){.stats-bar-inner{grid-template-columns:1fr;}}

/* ===== SECTIONS ===== */
.section{padding:5rem 0;}
.section-alt{background:var(--s100);}
.section-head{text-align:center;margin-bottom:3.5rem;}
.section-badge{display:inline-block;background:var(--p50);color:var(--p600);font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;padding:.3rem .9rem;border-radius:100px;border:1px solid var(--p100);margin-bottom:.9rem;}
.section-title{font-size:clamp(1.6rem,3vw,2.3rem);font-weight:800;color:var(--p900);letter-spacing:-.03em;margin-bottom:.65rem;}
.section-sub{font-size:.95rem;color:var(--s500);max-width:520px;margin:0 auto;line-height:1.7;}

/* ===== FEATURES ===== */
.features-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;}
@media(max-width:900px){.features-grid{grid-template-columns:1fr 1fr;}}
@media(max-width:560px){.features-grid{grid-template-columns:1fr;}}
.feat-card{background:#fff;border-radius:22px;padding:28px;border:1px solid var(--s200);transition:all .3s;position:relative;overflow:hidden;}
.feat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--p400),var(--p600));transform:scaleX(0);transition:transform .35s;transform-origin:left;}
.feat-card:hover::before{transform:scaleX(1);}
.feat-card:hover{transform:translateY(-6px);box-shadow:0 20px 40px -8px rgba(30,58,102,0.12);border-color:var(--p200);}
.feat-icon{width:52px;height:52px;border-radius:16px;display:flex;align-items:center;justify-content:center;margin-bottom:18px;font-size:1.2rem;}
.feat-icon.blue{background:var(--p50);color:var(--p600);}
.feat-icon.indigo{background:#eef2ff;color:#4f46e5;}
.feat-icon.teal{background:#f0fdfa;color:#0d9488;}
.feat-icon.amber{background:#fffbeb;color:#d97706;}
.feat-icon.green{background:#f0fdf4;color:#16a34a;}
.feat-icon.rose{background:#fff1f2;color:#e11d48;}
.feat-title{font-size:1rem;font-weight:700;color:var(--p900);margin-bottom:8px;}
.feat-desc{font-size:.85rem;color:var(--s500);line-height:1.65;}

/* ===== AUCTION CARDS ===== */
.auctions-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;}
@media(max-width:900px){.auctions-grid{grid-template-columns:1fr 1fr;}}
@media(max-width:560px){.auctions-grid{grid-template-columns:1fr;}}
.auction-card{background:#fff;border-radius:22px;border:1px solid var(--s200);overflow:hidden;transition:all .3s;display:flex;flex-direction:column;}
.auction-card:hover{transform:translateY(-6px);box-shadow:0 20px 40px -8px rgba(30,58,102,0.14);border-color:var(--p200);}
.auction-img{height:180px;overflow:hidden;position:relative;background:var(--p50);}
.auction-img img{width:100%;height:100%;object-fit:cover;transition:transform .4s;}
.auction-card:hover .auction-img img{transform:scale(1.06);}
.auction-badge{position:absolute;top:10px;left:10px;background:linear-gradient(135deg,var(--p700),var(--p500));color:#fff;font-size:.65rem;font-weight:700;padding:.3rem .7rem;border-radius:100px;text-transform:uppercase;letter-spacing:.06em;}
.auction-body{padding:18px;flex:1;display:flex;flex-direction:column;}
.auction-name{font-size:.95rem;font-weight:700;color:var(--p900);margin-bottom:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.auction-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;}
.auction-lbl{font-size:.72rem;color:var(--s400);font-weight:500;}
.auction-val{font-size:.8rem;font-weight:700;color:var(--s700);}
.auction-hot{font-size:.82rem;font-weight:800;color:var(--p600);}
.auction-footer{display:flex;justify-content:space-between;align-items:center;margin-top:14px;padding-top:14px;border-top:1px solid var(--s200);}
.auction-bids{font-size:.75rem;color:var(--s400);font-weight:500;display:flex;align-items:center;gap:5px;}
.btn-detail{font-size:.78rem;font-weight:700;color:var(--p600);text-decoration:none;display:flex;align-items:center;gap:5px;padding:.35rem .85rem;border-radius:9px;border:1.5px solid var(--p200);transition:all .2s;}
.btn-detail:hover{background:var(--p50);border-color:var(--p400);color:var(--p700);}

/* ===== HOW IT WORKS ===== */
.how-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1.5rem;position:relative;}
@media(max-width:900px){.how-grid{grid-template-columns:1fr 1fr;}}
@media(max-width:480px){.how-grid{grid-template-columns:1fr;}}
.how-grid::before{content:'';position:absolute;top:28px;left:10%;right:10%;height:2px;background:repeating-linear-gradient(90deg,var(--p200) 0,var(--p200) 8px,transparent 8px,transparent 16px);z-index:0;}
@media(max-width:900px){.how-grid::before{display:none;}}
.how-card{text-align:center;position:relative;z-index:1;}
.how-num{width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,var(--p700),var(--p500));color:#fff;font-size:1.1rem;font-weight:800;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;box-shadow:0 8px 20px -4px rgba(30,58,102,0.4);border:4px solid var(--s100);}
.how-title{font-size:.95rem;font-weight:700;color:var(--p900);margin-bottom:8px;}
.how-desc{font-size:.83rem;color:var(--s500);line-height:1.6;}

/* ===== TESTIMONIALS ===== */
.testi-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:1.5rem;}
@media(max-width:640px){.testi-grid{grid-template-columns:1fr;}}
.testi-card{background:#fff;border-radius:22px;padding:28px;border:1px solid var(--s200);transition:all .3s;}
.testi-card:hover{transform:translateY(-4px);box-shadow:0 16px 32px -6px rgba(30,58,102,0.1);border-color:var(--p200);}
.testi-stars{color:#fbbf24;font-size:.85rem;margin-bottom:12px;display:flex;gap:3px;}
.testi-text{font-size:.875rem;color:var(--s500);line-height:1.7;margin-bottom:16px;font-style:italic;}
.testi-author{display:flex;align-items:center;gap:12px;}
.testi-author img{width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid var(--p100);}
.testi-name{font-size:.85rem;font-weight:700;color:var(--p900);}
.testi-role{font-size:.72rem;color:var(--s400);margin-top:1px;}

/* ===== CTA ===== */
.cta-section{background:linear-gradient(135deg,var(--p900) 0%,var(--p700) 50%,var(--p600) 100%);border-radius:28px;padding:4rem;text-align:center;position:relative;overflow:hidden;margin:0 1.5rem;}
.cta-section::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");pointer-events:none;}
.cta-section>*{position:relative;z-index:1;}
.cta-title{font-size:clamp(1.5rem,3vw,2.2rem);font-weight:800;color:#fff;letter-spacing:-.03em;margin-bottom:.75rem;}
.cta-desc{font-size:.95rem;color:rgba(255,255,255,0.6);max-width:460px;margin:0 auto 2rem;line-height:1.7;}
.btn-cta{display:inline-flex;align-items:center;gap:9px;background:#fff;color:var(--p700);padding:.9rem 2.2rem;border-radius:14px;font-family:inherit;font-size:.9rem;font-weight:800;text-decoration:none;transition:all .25s;box-shadow:0 8px 24px -4px rgba(10,26,48,.35);}
.btn-cta:hover{transform:translateY(-2px);box-shadow:0 14px 32px -6px rgba(10,26,48,.45);}

/* ===== FOOTER ===== */
.footer{background:var(--p900);padding:4rem 0 2rem;}
.footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:3rem;margin-bottom:3rem;}
@media(max-width:900px){.footer-grid{grid-template-columns:1fr 1fr;gap:2rem;}}
@media(max-width:540px){.footer-grid{grid-template-columns:1fr;}}
.footer-logo{display:flex;align-items:center;gap:10px;margin-bottom:12px;}
.footer-logo .icon{width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.95rem;}
.footer-logo .name{font-size:1.05rem;font-weight:800;color:#fff;letter-spacing:-.02em;}
.footer-logo .name span{color:#93c5fd;}
.footer-desc{font-size:.82rem;color:rgba(255,255,255,0.4);line-height:1.7;margin-bottom:20px;}
.footer-socials{display:flex;gap:8px;}
.footer-social{width:34px;height:34px;border-radius:10px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.5);font-size:.8rem;text-decoration:none;transition:all .2s;}
.footer-social:hover{background:var(--p500);border-color:var(--p400);color:#fff;}
.footer-title{font-size:.7rem;font-weight:800;color:rgba(255,255,255,0.3);text-transform:uppercase;letter-spacing:.1em;margin-bottom:14px;}
.footer-links{list-style:none;}
.footer-links li{margin-bottom:8px;}
.footer-links a{font-size:.82rem;color:rgba(255,255,255,0.45);text-decoration:none;transition:color .2s;display:flex;align-items:center;gap:6px;}
.footer-links a:hover{color:rgba(255,255,255,0.85);}
.footer-bottom{border-top:1px solid rgba(255,255,255,0.08);padding-top:1.75rem;text-align:center;font-size:.75rem;color:rgba(255,255,255,0.25);}
.footer-bottom i{color:#ef4444;}

/* Back to top */
.btt{position:fixed;bottom:2rem;right:2rem;width:44px;height:44px;border-radius:14px;background:linear-gradient(135deg,var(--p700),var(--p500));color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 20px -4px rgba(30,58,102,0.45);opacity:0;transform:translateY(10px);transition:all .3s;z-index:500;font-size:.9rem;}
.btt.show{opacity:1;transform:none;}
.btt:hover{transform:translateY(-2px);box-shadow:0 12px 28px -4px rgba(30,58,102,0.55);}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
  <div class="container">
    <div class="nav-inner">
      <a href="#" class="logo">
        <div class="logo-icon"><i class="fas fa-gavel"></i></div>
        <span class="logo-name">Lelang<span>Online</span></span>
      </a>
      <div class="nav-links">
        <a href="#features">Fitur</a>
        <a href="#how">Cara Kerja</a>
        <a href="#auctions">Lelang Aktif</a>
        <a href="#testimonials">Testimoni</a>
      </div>
      <div class="nav-actions">
        <a href="auth/login.php" class="btn-nav btn-nav-ghost">Masuk</a>
        <a href="auth/register.php" class="btn-nav btn-nav-solid">Daftar Gratis</a>
      </div>
      <button class="hamburger" id="hamburger"><i class="fas fa-bars"></i></button>
    </div>
    <div class="mobile-menu" id="mobileMenu">
      <a href="#features">Fitur</a>
      <a href="#how">Cara Kerja</a>
      <a href="#auctions">Lelang Aktif</a>
      <a href="#testimonials">Testimoni</a>
      <div class="mobile-actions">
        <a href="auth/login.php" class="btn-nav btn-nav-ghost" style="flex:1;text-align:center;padding:.65rem;">Masuk</a>
        <a href="auth/register.php" class="btn-nav btn-nav-solid" style="flex:1;text-align:center;padding:.65rem;">Daftar</a>
      </div>
    </div>
  </div>
</nav>

<!-- HERO -->
<section class="hero" id="home">
  <div class="hero-orb hero-orb-1"></div>
  <div class="hero-orb hero-orb-2"></div>
  <div class="hero-orb hero-orb-3"></div>
  <div class="container">
    <div class="hero-content">
      <div>
        <div class="hero-badge">
          <span class="dot"></span>
          <span><?php echo $active_lelang > 0 ? $active_lelang.' Lelang Sedang Berlangsung' : 'Platform Lelang #1 Indonesia'; ?></span>
        </div>
        <h1 class="hero-title">
          Platform Lelang<br>
          <span class="accent">Modern &amp; Terpercaya</span>
        </h1>
        <p class="hero-desc">Temukan barang eksklusif dengan harga terbaik. Proses transparan, aman, dan mudah digunakan untuk semua kalangan.</p>
        <div class="hero-actions">
          <a href="auth/register.php" class="btn-hero-primary"><i class="fas fa-rocket"></i> Mulai Gratis</a>
          <a href="#auctions" class="btn-hero-ghost"><i class="fas fa-gavel"></i> Lihat Lelang</a>
        </div>
        <div class="hero-stats">
          <div class="hstat">
            <div class="hstat-num"><?php echo number_format($total_users,0,',','.'); ?>+</div>
            <div class="hstat-lbl">Pengguna Aktif</div>
          </div>
          <div class="hstat-div"></div>
          <div class="hstat">
            <div class="hstat-num"><?php echo number_format($total_sold,0,',','.'); ?>+</div>
            <div class="hstat-lbl">Item Terjual</div>
          </div>
          <div class="hstat-div"></div>
          <div class="hstat">
            <div class="hstat-num">99%</div>
            <div class="hstat-lbl">Kepuasan</div>
          </div>
        </div>
      </div>
      <div class="hero-visual">
        <div class="hero-card-main">
          <div class="hero-card-img" style="background:linear-gradient(135deg,rgba(255,255,255,0.08),rgba(255,255,255,0.04));display:flex;align-items:center;justify-content:center;">
            <i class="fas fa-gavel" style="font-size:4rem;color:rgba(255,255,255,0.2);"></i>
          </div>
          <div class="hero-card-body">
            <div class="hc-title">Rolex Submariner 2023</div>
            <div class="hc-row">
              <span class="hc-label">Harga Awal</span>
              <span class="hc-value">Rp 12.800.000</span>
            </div>
            <div class="hc-row">
              <span class="hc-label">Penawaran</span>
              <span class="hc-value" style="color:#4ade80;">Rp 14.200.000</span>
            </div>
            <button class="hc-bid-btn"><i class="fas fa-gavel"></i> Ajukan Penawaran</button>
          </div>
        </div>
        <div class="hero-card-float hcf-1">
          <div class="hcf-icon green"><i class="fas fa-check"></i></div>
          <div class="hcf-text"><div class="top">Bid Diterima!</div><div class="bot">+Rp 500.000</div></div>
        </div>
        <div class="hero-card-float hcf-2">
          <div class="hcf-icon amber"><i class="fas fa-users"></i></div>
          <div class="hcf-text"><div class="top">128 Penawar</div><div class="bot">Aktif sekarang</div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- STATS BAR -->
<div class="stats-bar">
  <div class="container">
    <div class="stats-bar-inner">
      <div class="sbar-item" data-aos="fade-up" data-aos-delay="0">
        <div class="sbar-icon" style="background:var(--p50);color:var(--p600);"><i class="fas fa-users"></i></div>
        <div><div class="sbar-num"><?php echo number_format($total_users,0,',','.'); ?>+</div><div class="sbar-lbl">Pengguna Terdaftar</div></div>
      </div>
      <div class="sbar-item" data-aos="fade-up" data-aos-delay="100">
        <div class="sbar-icon" style="background:#f0fdf4;color:#16a34a;"><i class="fas fa-box-open"></i></div>
        <div><div class="sbar-num"><?php echo number_format($total_sold,0,',','.'); ?>+</div><div class="sbar-lbl">Item Terjual</div></div>
      </div>
      <div class="sbar-item" data-aos="fade-up" data-aos-delay="200">
        <div class="sbar-icon" style="background:#fffbeb;color:#d97706;"><i class="fas fa-gavel"></i></div>
        <div><div class="sbar-num"><?php echo $active_lelang > 0 ? $active_lelang : '24+'; ?></div><div class="sbar-lbl">Lelang Aktif</div></div>
      </div>
      <div class="sbar-item" data-aos="fade-up" data-aos-delay="300">
        <div class="sbar-icon" style="background:#fdf4ff;color:#9333ea;"><i class="fas fa-shield-halved"></i></div>
        <div><div class="sbar-num">100%</div><div class="sbar-lbl">Transaksi Aman</div></div>
      </div>
    </div>
  </div>
</div>

<!-- FEATURES -->
<section class="section" id="features">
  <div class="container">
    <div class="section-head" data-aos="fade-up">
      <div class="section-badge">Fitur Unggulan</div>
      <h2 class="section-title">Kenapa LelangOnline?</h2>
      <p class="section-sub">Dirancang untuk kemudahan dan keamanan transaksi lelang Anda</p>
    </div>
    <div class="features-grid">
      <?php
      $feats=[
        ['blue','fas fa-bolt','Real-time Bidding','Proses penawaran berlangsung secara langsung dengan pembaruan harga otomatis tanpa perlu refresh halaman.'],
        ['indigo','fas fa-shield-halved','Keamanan Terjamin','Setiap transaksi dienkripsi dan diproteksi dengan sistem keamanan berlapis untuk melindungi data Anda.'],
        ['teal','fas fa-mobile-screen','Akses Dari Mana Saja','Tampilan responsif memungkinkan Anda mengikuti lelang dari perangkat apa pun kapan saja.'],
        ['amber','fas fa-star','Barang Terverifikasi','Setiap barang melewati proses verifikasi ketat sebelum diiklankan untuk menjamin keaslian.'],
        ['green','fas fa-headset','Dukungan 24/7','Tim kami siap membantu Anda sepanjang waktu melalui berbagai saluran komunikasi.'],
        ['rose','fas fa-chart-line','Histori Transparan','Riwayat penawaran lengkap dapat dilihat semua peserta untuk memastikan proses yang adil.'],
      ];
      foreach($feats as $f): ?>
      <div class="feat-card" data-aos="fade-up">
        <div class="feat-icon <?php echo $f[0]; ?>"><i class="<?php echo $f[1]; ?>"></i></div>
        <div class="feat-title"><?php echo $f[2]; ?></div>
        <div class="feat-desc"><?php echo $f[3]; ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="section section-alt" id="how">
  <div class="container">
    <div class="section-head" data-aos="fade-up">
      <div class="section-badge">Cara Kerja</div>
      <h2 class="section-title">Mudah dalam 4 Langkah</h2>
      <p class="section-sub">Bergabung dan mulai ikut lelang dalam hitungan menit</p>
    </div>
    <div class="how-grid">
      <?php
      $steps=[
        ['1','Daftar Akun','Buat akun gratis dengan email dan informasi dasar Anda.'],
        ['2','Telusuri Lelang','Temukan barang favorit dari ribuan item yang tersedia.'],
        ['3','Ajukan Penawaran','Berikan penawaran terbaik Anda dan pantau secara real-time.'],
        ['4','Menangkan &amp; Bayar','Jika menang, lakukan pembayaran aman dan terima barang.'],
      ];
      foreach($steps as $i=>$s): ?>
      <div class="how-card" data-aos="fade-up" data-aos-delay="<?php echo $i*100; ?>">
        <div class="how-num"><?php echo $s[0]; ?></div>
        <div class="how-title"><?php echo $s[1]; ?></div>
        <div class="how-desc"><?php echo $s[2]; ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- AUCTION ITEMS -->
<section class="section" id="auctions">
  <div class="container">
    <div class="section-head" data-aos="fade-up">
      <div class="section-badge">Lelang Aktif</div>
      <h2 class="section-title">Item Unggulan Saat Ini</h2>
      <p class="section-sub">Jangan lewatkan kesempatan mendapatkan barang eksklusif</p>
    </div>
    <div class="auctions-grid">
      <?php
      $placeholders=[
        ['Nike Air Max 270','https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&auto=format',2500000,2750000,45,'TRENDING'],
        ['Rolex Submariner','https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400&auto=format',12800000,13500000,1200,'HOT'],
        ['Sony WH-1000XM5','https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&auto=format',3200000,3500000,89,'POPULER'],
        ['Apple Watch Ultra','https://images.unsplash.com/photo-1546868871-7041f2a55e12?w=400&auto=format',5500000,6100000,320,'BARU'],
        ['Kamera Canon R6','https://images.unsplash.com/photo-1583394838336-acd977736f90?w=400&auto=format',8900000,9400000,67,'EKSKLUSIF'],
        ['iPhone 15 Pro Max','https://images.unsplash.com/photo-1579586337278-3befd40fd17a?w=400&auto=format',11500000,12200000,540,'HOT'],
      ];
      $i=0;
      if($featured_items && mysqli_num_rows($featured_items)>0):
        while($item=mysqli_fetch_assoc($featured_items)):
          $ht=$item['ht']??$item['harga_awal'];
          $link=isset($_SESSION['id_user'])?'masyarakat/detail_lelang.php?id='.$item['id_lelang']:'auth/login.php';
      ?>
      <div class="auction-card" data-aos="fade-up" data-aos-delay="<?php echo ($i%3)*100; ?>">
        <div class="auction-img">
          <img src="uploads/<?php echo htmlspecialchars($item['gambar']); ?>" alt="<?php echo htmlspecialchars($item['nama_barang']); ?>" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&auto=format'">
          <span class="auction-badge">Aktif</span>
        </div>
        <div class="auction-body">
          <div class="auction-name"><?php echo htmlspecialchars($item['nama_barang']); ?></div>
          <div class="auction-row"><span class="auction-lbl">Harga Awal</span><span class="auction-val">Rp <?php echo number_format($item['harga_awal'],0,',','.'); ?></span></div>
          <div class="auction-row"><span class="auction-lbl">Penawaran Tertinggi</span><span class="auction-hot">Rp <?php echo number_format($ht,0,',','.'); ?></span></div>
          <div class="auction-footer">
            <span class="auction-bids"><i class="fas fa-users"></i> <?php echo $item['tb']; ?> penawar</span>
            <a href="<?php echo $link; ?>" class="btn-detail">Detail <i class="fas fa-arrow-right"></i></a>
          </div>
        </div>
      </div>
      <?php $i++; endwhile; else:
      foreach($placeholders as $i=>$p): ?>
      <div class="auction-card" data-aos="fade-up" data-aos-delay="<?php echo ($i%3)*100; ?>">
        <div class="auction-img">
          <img src="<?php echo $p[1]; ?>" alt="<?php echo $p[0]; ?>" loading="lazy">
          <span class="auction-badge"><?php echo $p[5]; ?></span>
        </div>
        <div class="auction-body">
          <div class="auction-name"><?php echo $p[0]; ?></div>
          <div class="auction-row"><span class="auction-lbl">Harga Awal</span><span class="auction-val">Rp <?php echo number_format($p[2],0,',','.'); ?></span></div>
          <div class="auction-row"><span class="auction-lbl">Penawaran Tertinggi</span><span class="auction-hot">Rp <?php echo number_format($p[3],0,',','.'); ?></span></div>
          <div class="auction-footer">
            <span class="auction-bids"><i class="fas fa-users"></i> <?php echo $p[4]; ?> penawar</span>
            <a href="auth/login.php" class="btn-detail">Detail <i class="fas fa-arrow-right"></i></a>
          </div>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
    <div style="text-align:center;margin-top:2.5rem;" data-aos="fade-up">
      <a href="<?php echo isset($_SESSION['id_user'])?'masyarakat/lelang.php':'auth/login.php';?>" class="btn-hero-primary" style="display:inline-flex;">Lihat Semua Lelang <i class="fas fa-arrow-right"></i></a>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="section section-alt" id="testimonials">
  <div class="container">
    <div class="section-head" data-aos="fade-up">
      <div class="section-badge">Testimoni</div>
      <h2 class="section-title">Kata Pengguna Kami</h2>
      <p class="section-sub">Pengalaman nyata dari ribuan pengguna yang telah mempercayai kami</p>
    </div>
    <div class="testi-grid">
      <?php
      $testis=[
        ['Budi Santoso','https://randomuser.me/api/portraits/men/32.jpg','Kolektor','Saya sangat puas! Prosesnya transparan dan barang berkualitas. Harga jauh lebih murah dari pasaran!'],
        ['Siti Rahma','https://randomuser.me/api/portraits/women/44.jpg','Ibu Rumah Tangga','Baru pertama ikut lelang, tapi pengalamannya menyenangkan. Sistemnya mudah dipahami dan dipercaya!'],
        ['Ahmad Fauzi','https://randomuser.me/api/portraits/men/46.jpg','Pengusaha','Sudah 3 kali menang lelang. Barang original dan pengiriman cepat. Sangat direkomendasikan!'],
        ['Dewi Lestari','https://randomuser.me/api/portraits/women/68.jpg','Designer','Platform sangat user-friendly. Bisa ikut lelang dari mana saja. Terpercaya dan profesional!'],
      ];
      foreach($testis as $i=>$t): ?>
      <div class="testi-card" data-aos="fade-up" data-aos-delay="<?php echo ($i%2)*100; ?>">
        <div class="testi-stars"><?php for($s=0;$s<5;$s++) echo '<i class="fas fa-star"></i>'; ?></div>
        <p class="testi-text">"<?php echo $t[3]; ?>"</p>
        <div class="testi-author">
          <img src="<?php echo $t[1]; ?>" alt="<?php echo $t[0]; ?>">
          <div><div class="testi-name"><?php echo $t[0]; ?></div><div class="testi-role"><?php echo $t[2]; ?></div></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="section">
  <div class="container">
    <div class="cta-section" data-aos="zoom-in">
      <h2 class="cta-title">Siap Memulai Lelang?</h2>
      <p class="cta-desc">Bergabunglah dengan ribuan pengguna dan dapatkan pengalaman lelang modern Anda hari ini</p>
      <?php if(!isset($_SESSION['id_user'])): ?>
        <a href="auth/register.php" class="btn-cta"><i class="fas fa-user-plus"></i> Daftar Gratis Sekarang</a>
      <?php else: ?>
        <a href="#auctions" class="btn-cta"><i class="fas fa-gavel"></i> Mulai Lelang</a>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="footer-logo">
          <div class="icon"><i class="fas fa-gavel"></i></div>
          <span class="name">Lelang<span>Online</span></span>
        </div>
        <p class="footer-desc">Platform lelang online terpercaya. Transparan, aman, dan mudah digunakan untuk semua kalangan.</p>
        <div class="footer-socials">
          <a href="#" class="footer-social"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="footer-social"><i class="fab fa-twitter"></i></a>
          <a href="#" class="footer-social"><i class="fab fa-instagram"></i></a>
          <a href="#" class="footer-social"><i class="fab fa-linkedin-in"></i></a>
        </div>
      </div>
      <div>
        <div class="footer-title">Menu</div>
        <ul class="footer-links">
          <li><a href="#home"><i class="fas fa-chevron-right" style="font-size:.6rem;opacity:.4;"></i>Beranda</a></li>
          <li><a href="#features"><i class="fas fa-chevron-right" style="font-size:.6rem;opacity:.4;"></i>Fitur</a></li>
          <li><a href="#auctions"><i class="fas fa-chevron-right" style="font-size:.6rem;opacity:.4;"></i>Lelang</a></li>
          <li><a href="#testimonials"><i class="fas fa-chevron-right" style="font-size:.6rem;opacity:.4;"></i>Testimoni</a></li>
        </ul>
      </div>
      <div>
        <div class="footer-title">Bantuan</div>
        <ul class="footer-links">
          <li><a href="#"><i class="fas fa-chevron-right" style="font-size:.6rem;opacity:.4;"></i>FAQ</a></li>
          <li><a href="#"><i class="fas fa-chevron-right" style="font-size:.6rem;opacity:.4;"></i>Panduan</a></li>
          <li><a href="#"><i class="fas fa-chevron-right" style="font-size:.6rem;opacity:.4;"></i>Kebijakan Privasi</a></li>
          <li><a href="#"><i class="fas fa-chevron-right" style="font-size:.6rem;opacity:.4;"></i>Syarat &amp; Ketentuan</a></li>
        </ul>
      </div>
      <div>
        <div class="footer-title">Kontak</div>
        <ul class="footer-links">
          <li><a href="#"><i class="fas fa-envelope" style="font-size:.7rem;opacity:.5;"></i>support@lelangonline.id</a></li>
          <li><a href="#"><i class="fas fa-phone" style="font-size:.7rem;opacity:.5;"></i>+62 812-3456-7890</a></li>
          <li><a href="#"><i class="fas fa-map-marker-alt" style="font-size:.7rem;opacity:.5;"></i>Jakarta, Indonesia</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">&copy; <?php echo date('Y'); ?> LelangOnline. Semua hak dilindungi. Dibuat dengan <i class="fas fa-heart"></i> di Indonesia</div>
  </div>
</footer>

<button class="btt" id="btt" onclick="window.scrollTo({top:0,behavior:'smooth'})"><i class="fas fa-arrow-up"></i></button>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init({once:true,duration:600,offset:40});

// Navbar scroll
window.addEventListener('scroll',function(){
  document.getElementById('navbar').classList.toggle('scrolled',window.scrollY>60);
  document.getElementById('btt').classList.toggle('show',window.scrollY>300);
});

// Mobile menu
document.getElementById('hamburger').addEventListener('click',function(){
  document.getElementById('mobileMenu').classList.toggle('open');
});

// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(a=>{
  a.addEventListener('click',function(e){
    const t=document.querySelector(this.getAttribute('href'));
    if(t){e.preventDefault();window.scrollTo({top:t.offsetTop-70,behavior:'smooth'});document.getElementById('mobileMenu').classList.remove('open');}
  });
});
</script>
</body>
</html>