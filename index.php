<?php
session_start();
require_once('config/config.php');

$active_lelang = 0;
$r = mysqli_query($conn, "SELECT COUNT(*) as t FROM tb_lelang WHERE status='dibuka'");
if($r) { $active_lelang = mysqli_fetch_assoc($r)['t'] ?? 0; mysqli_free_result($r); }

$total_users = 3; $total_barang = 0; $total_selesai = 0;
$ct = mysqli_query($conn, "SHOW TABLES");
if($ct) {
    $tables = [];
    while($rr = mysqli_fetch_array($ct)) $tables[] = $rr[0];
    mysqli_free_result($ct);
    if(in_array('tb_user',$tables)){$r=mysqli_query($conn,"SELECT COUNT(*) as t FROM tb_user");if($r){$v=mysqli_fetch_assoc($r)['t']??0;if($v>0)$total_users=$v;mysqli_free_result($r);}}
    if(in_array('tb_barang',$tables)){$r=mysqli_query($conn,"SELECT COUNT(*) as t FROM tb_barang");if($r){$total_barang=mysqli_fetch_assoc($r)['t']??0;mysqli_free_result($r);}}
    if(in_array('tb_lelang',$tables)){$r=mysqli_query($conn,"SELECT COUNT(*) as t FROM tb_lelang WHERE status='ditutup'");if($r){$total_selesai=mysqli_fetch_assoc($r)['t']??0;mysqli_free_result($r);}}
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LelangOnline — Platform Lelang Digital Terpercaya</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}

/* ── PALET — identik dengan login.php & logout.php ────────── */
:root{
  --p50:#eef2f8;--p100:#d9e2f0;--p200:#b3c5e1;--p300:#8da8d2;
  --p400:#678bc3;--p500:#416eb4;--p600:#2a4f8c;--p700:#1e3a66;
  --p800:#132a4a;--p900:#0a1a30;
  --s50:#f8fafc;--s100:#f1f5f9;--s200:#e2e8f0;--s300:#cbd5e1;
  --s400:#94a3b8;--s500:#64748b;--s700:#334155;--s900:#0f172a;
  --white:#ffffff;
}

html{scroll-behavior:smooth;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--white);color:var(--s900);overflow-x:hidden;}
.container{max-width:1200px;margin:0 auto;padding:0 2rem;}

/* ── NAVBAR ─────────────────────────────────────────────── */
.nav{position:fixed;top:0;left:0;right:0;z-index:100;padding:1.25rem 0;transition:all .4s ease;}
.nav.solid{background:rgba(10,26,48,.96);backdrop-filter:blur(20px);border-bottom:1px solid rgba(65,110,180,.14);padding:.85rem 0;box-shadow:0 4px 24px rgba(0,0,0,.38);}
.nav-inner{display:flex;align-items:center;justify-content:space-between;}
.logo{display:flex;align-items:center;gap:.65rem;text-decoration:none;}
.logo-mark{width:38px;height:38px;border-radius:12px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1rem;backdrop-filter:blur(8px);}
.logo-name{font-weight:800;font-size:1.15rem;color:#fff;letter-spacing:-.015em;}
.logo-name span{color:#93c5fd;}
.nav-links{display:flex;gap:2rem;}
.nav-links a{text-decoration:none;color:rgba(255,255,255,.55);font-size:.875rem;font-weight:500;transition:color .2s;}
.nav-links a:hover{color:#fff;}
.nav-cta{display:flex;gap:.55rem;}
.nbtn-ghost{padding:.45rem 1.15rem;border:1px solid rgba(255,255,255,.16);border-radius:8px;text-decoration:none;color:rgba(255,255,255,.7);font-size:.85rem;font-weight:500;transition:all .2s;}
.nbtn-ghost:hover{border-color:rgba(255,255,255,.35);color:#fff;}
.nbtn-primary{padding:.45rem 1.15rem;background:linear-gradient(135deg,var(--p700),var(--p500));border:1px solid rgba(255,255,255,.1);border-radius:8px;text-decoration:none;color:#fff;font-size:.85rem;font-weight:700;display:flex;align-items:center;gap:.4rem;transition:all .2s;}
.nbtn-primary:hover{opacity:.88;transform:translateY(-1px);}
.hamburger{display:none;background:none;border:none;color:#fff;font-size:1.2rem;cursor:pointer;}
@media(max-width:768px){.nav-links,.nav-cta{display:none;}.hamburger{display:block;}}
.mob-menu{display:none;position:fixed;top:64px;left:0;right:0;background:rgba(10,26,48,.98);z-index:99;border-bottom:1px solid rgba(65,110,180,.1);backdrop-filter:blur(16px);}
.mob-menu.open{display:block;}
.mob-menu a{display:block;padding:.85rem 2rem;text-decoration:none;color:rgba(255,255,255,.6);font-size:.9rem;border-bottom:1px solid rgba(255,255,255,.04);transition:all .2s;}
.mob-menu a:hover{color:#fff;background:rgba(65,110,180,.12);}

/* ── ORBS — identik login.php ──────────────────────────── */
.orb{position:absolute;border-radius:50%;filter:blur(90px);pointer-events:none;animation:orbF 14s ease-in-out infinite;}
.orb-1{width:600px;height:600px;background:rgba(103,139,195,0.18);top:-250px;right:-150px;}
.orb-2{width:450px;height:450px;background:rgba(30,58,102,0.25);bottom:-180px;left:-120px;animation-delay:-7s;}
.orb-3{width:280px;height:280px;background:rgba(141,168,210,0.15);top:40%;left:20%;animation-delay:-3.5s;}
@keyframes orbF{0%,100%{transform:translateY(0) scale(1);}50%{transform:translateY(-25px) scale(1.04);}}
.dot-grid{position:absolute;inset:0;pointer-events:none;background:url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.025'%3E%3Ccircle cx='40' cy='40' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");}

/* ── HERO ───────────────────────────────────────────────── */
.hero{
  min-height:100vh;
  background:linear-gradient(145deg,var(--p900) 0%,var(--p800) 30%,var(--p700) 60%,var(--p600) 85%,var(--p500) 100%);
  display:flex;flex-direction:column;justify-content:center;
  position:relative;overflow:hidden;padding:7rem 0 5rem;
}
.hero-inner{position:relative;z-index:2;display:grid;grid-template-columns:1fr 1fr;gap:4rem;align-items:center;}
.hero-eyebrow{display:inline-flex;align-items:center;gap:.55rem;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.18);border-radius:100px;padding:.38rem 1rem;margin-bottom:1.75rem;backdrop-filter:blur(8px);}
.pulse-dot{width:7px;height:7px;background:#4ade80;border-radius:50%;animation:blink 2s infinite;flex-shrink:0;}
@keyframes blink{0%,100%{opacity:1;}50%{opacity:.3;}}
.hero-eyebrow span{font-size:.78rem;color:rgba(255,255,255,.82);font-weight:600;letter-spacing:.05em;text-transform:uppercase;}
.hero h1{font-size:clamp(2.4rem,5vw,3.8rem);font-weight:800;line-height:1.1;color:#fff;margin-bottom:1.5rem;letter-spacing:-.025em;}
.hero h1 em{font-style:normal;-webkit-text-stroke:1.5px rgba(255,255,255,.3);color:transparent;}
.hero-desc{font-size:.975rem;line-height:1.8;color:rgba(255,255,255,.52);max-width:440px;margin-bottom:2.5rem;}
.hero-actions{display:flex;gap:.7rem;flex-wrap:wrap;}
.hbtn-primary{padding:.85rem 2rem;background:linear-gradient(135deg,var(--p700),var(--p500));border:1px solid rgba(255,255,255,.12);border-radius:14px;text-decoration:none;color:#fff;font-weight:700;font-size:.9rem;display:inline-flex;align-items:center;gap:.5rem;box-shadow:0 8px 28px -6px rgba(10,26,48,.5);transition:all .25s;}
.hbtn-primary:hover{opacity:.88;transform:translateY(-2px);}
.hbtn-ghost{padding:.85rem 2rem;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.14);border-radius:14px;text-decoration:none;color:rgba(255,255,255,.78);font-weight:500;font-size:.9rem;display:inline-flex;align-items:center;gap:.5rem;backdrop-filter:blur(8px);transition:all .25s;}
.hbtn-ghost:hover{background:rgba(255,255,255,.13);border-color:rgba(255,255,255,.28);}

/* hero visual */
.hero-right{display:flex;justify-content:center;align-items:center;}
.hero-visual{position:relative;width:360px;height:360px;}
.ring{position:absolute;border-radius:50%;border:1px solid rgba(179,197,225,.12);animation:spin linear infinite;}
.ring-1{inset:0;animation-duration:30s;}
.ring-2{inset:28px;animation-duration:22s;animation-direction:reverse;border-color:rgba(141,168,210,.1);}
.ring-3{inset:64px;animation-duration:16s;}
.ring-dot{position:absolute;width:7px;height:7px;border-radius:50%;background:var(--p300);top:-3.5px;left:50%;margin-left:-3.5px;box-shadow:0 0 10px rgba(141,168,210,.7);}
.ring-2 .ring-dot{background:var(--p400);width:5px;height:5px;top:-2.5px;margin-left:-2.5px;}
.ring-3 .ring-dot{background:rgba(255,255,255,.45);width:4px;height:4px;top:-2px;margin-left:-2px;}
@keyframes spin{to{transform:rotate(360deg);}}
.hero-core{position:absolute;inset:96px;background:rgba(255,255,255,.08);backdrop-filter:blur(12px);border-radius:50%;border:1px solid rgba(255,255,255,.18);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.3rem;box-shadow:0 0 50px rgba(30,58,102,.4),inset 0 1px 0 rgba(255,255,255,.08);}
.hero-core i{font-size:2.4rem;color:#fff;}
.hero-core span{font-size:.65rem;color:rgba(255,255,255,.38);letter-spacing:.14em;text-transform:uppercase;margin-top:.1rem;}
.float-pill{position:absolute;background:rgba(255,255,255,.1);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.16);border-radius:100px;padding:.5rem 1rem;font-size:.78rem;color:rgba(255,255,255,.82);font-weight:500;display:flex;align-items:center;gap:.4rem;white-space:nowrap;animation:floaty 4s ease-in-out infinite alternate;}
.float-pill i{color:var(--p300);font-size:.7rem;}
.fp1{top:12%;left:-8%;animation-delay:0s;}
.fp2{bottom:18%;right:-6%;animation-delay:1.5s;}
.fp3{top:52%;left:-18%;animation-delay:.8s;}
@keyframes floaty{to{transform:translateY(-8px);}}
@media(max-width:900px){.hero-inner{grid-template-columns:1fr;}.hero-right{display:none;}}
@media(max-width:600px){.hero{padding:7rem 0 4rem;}}

/* ── STATS BAR ──────────────────────────────────────────── */
.stats-bar{background:rgba(10,26,48,.97);border-top:1px solid rgba(65,110,180,.1);border-bottom:1px solid rgba(65,110,180,.1);padding:2.25rem 0;}
.stats-inner{display:flex;justify-content:space-around;flex-wrap:wrap;gap:1.5rem;}
.stat{text-align:center;display:flex;flex-direction:column;gap:.2rem;}
.stat-num{font-size:2.2rem;font-weight:800;color:#fff;letter-spacing:-.03em;}
.stat-num sup{font-size:1rem;color:var(--p300);}
.stat-lbl{font-size:.72rem;color:rgba(255,255,255,.3);letter-spacing:.07em;text-transform:uppercase;font-weight:500;}

/* ── SECTIONS ────────────────────────────────────────────── */
.section{padding:6rem 0;}
.section-alt{background:var(--s50);}
.section-dark{background:linear-gradient(145deg,var(--p900) 0%,var(--p800) 30%,var(--p700) 60%,var(--p600) 85%,var(--p500) 100%);position:relative;overflow:hidden;}

.tag{display:inline-block;padding:.22rem .8rem;background:rgba(42,79,140,.1);border:1px solid rgba(42,79,140,.18);border-radius:100px;font-size:.72rem;font-weight:700;color:var(--p600);text-transform:uppercase;letter-spacing:.07em;margin-bottom:.9rem;}
.tag-light{background:rgba(179,197,225,.14);border-color:rgba(179,197,225,.22);color:var(--p200);}
.sec-title{font-size:clamp(1.6rem,3vw,2.15rem);font-weight:800;color:var(--s900);line-height:1.2;margin-bottom:.75rem;letter-spacing:-.02em;}
.sec-title-light{color:#fff;}
.sec-sub{color:var(--s400);font-size:.9rem;max-width:500px;line-height:1.7;}
.sec-sub-light{color:rgba(255,255,255,.42);}
.hdr{margin-bottom:3.5rem;}
.hdr-center{text-align:center;}
.hdr-center .sec-sub{margin:0 auto;}

/* ── FEATURES ────────────────────────────────────────────── */
.features-bento{display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem;}
.feat{background:#fff;border:1px solid rgba(42,79,140,.07);border-radius:20px;padding:2rem;transition:all .3s;position:relative;overflow:hidden;}
.feat::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--p700),var(--p400));transform:scaleX(0);transform-origin:left;transition:transform .4s;}
.feat:hover::before{transform:scaleX(1);}
.feat:hover{transform:translateY(-4px);box-shadow:0 12px 40px rgba(42,79,140,.09);border-color:rgba(42,79,140,.14);}
.feat-wide{grid-column:span 2;}
.feat-icon{width:46px;height:46px;background:var(--p50);border-radius:13px;display:flex;align-items:center;justify-content:center;margin-bottom:1.2rem;color:var(--p600);font-size:1.05rem;transition:all .3s;}
.feat:hover .feat-icon{background:var(--p700);color:#fff;}
.feat h3{font-size:.95rem;font-weight:700;margin-bottom:.45rem;color:var(--s900);}
.feat p{font-size:.84rem;color:var(--s400);line-height:1.65;}
.feat-num{position:absolute;bottom:1.5rem;right:1.75rem;font-size:4rem;font-weight:800;color:rgba(42,79,140,.04);line-height:1;pointer-events:none;}
@media(max-width:900px){.features-bento{grid-template-columns:1fr 1fr;}.feat-wide{grid-column:span 2;}}
@media(max-width:600px){.features-bento{grid-template-columns:1fr;}.feat-wide{grid-column:span 1;}}

/* ── STEPS ───────────────────────────────────────────────── */
.steps-track{display:grid;grid-template-columns:repeat(4,1fr);gap:0;position:relative;}
.steps-track::before{content:'';position:absolute;top:27px;left:12.5%;right:12.5%;height:1px;background:linear-gradient(90deg,rgba(179,197,225,.18),rgba(179,197,225,.4),rgba(179,197,225,.18));z-index:0;}
.step{text-align:center;padding:0 1rem;position:relative;z-index:1;}
.step-num{width:54px;height:54px;background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.16);backdrop-filter:blur(8px);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;font-size:1.05rem;font-weight:800;color:var(--p200);transition:all .3s;}
.step:hover .step-num{background:var(--p600);border-color:var(--p400);color:#fff;transform:scale(1.1);}
.step h3{font-size:.95rem;font-weight:700;color:#fff;margin-bottom:.5rem;}
.step p{font-size:.8rem;color:rgba(255,255,255,.38);line-height:1.6;}
@media(max-width:768px){.steps-track{grid-template-columns:1fr 1fr;}.steps-track::before{display:none;}.step{margin-bottom:2rem;}}
@media(max-width:480px){.steps-track{grid-template-columns:1fr;}}

/* ── ROLES ───────────────────────────────────────────────── */
.roles-stack{display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;}
.role{background:#fff;border:1px solid rgba(42,79,140,.07);border-radius:24px;padding:2.25rem 2rem;transition:all .35s;position:relative;overflow:hidden;}
.role:hover{box-shadow:0 20px 60px rgba(42,79,140,.1);transform:translateY(-6px);border-color:rgba(42,79,140,.15);}
.role-accent{position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--p700),var(--p400));opacity:0;transition:opacity .3s;}
.role:hover .role-accent{opacity:1;}
.role-avatar{width:52px;height:52px;background:linear-gradient(135deg,var(--p800),var(--p600));border-radius:16px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;margin-bottom:1.5rem;}
.role h3{font-size:1.1rem;font-weight:700;margin-bottom:.35rem;color:var(--s900);}
.role-tagline{font-size:.8rem;color:var(--s400);margin-bottom:1.5rem;}
.role-list{list-style:none;margin-bottom:2rem;}
.role-list li{font-size:.84rem;color:var(--s700);padding:.45rem 0;border-bottom:1px solid rgba(42,79,140,.05);display:flex;align-items:center;gap:.55rem;}
.role-list li::before{content:'→';color:var(--p600);font-size:.8rem;}
.role-cta{display:inline-flex;align-items:center;gap:.4rem;font-size:.85rem;font-weight:700;color:var(--p600);text-decoration:none;transition:gap .2s;}
.role-cta:hover{gap:.7rem;}
@media(max-width:900px){.roles-stack{grid-template-columns:1fr;max-width:440px;margin:0 auto;}}

/* ── TESTIMONIALS ────────────────────────────────────────── */
.testi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1.25rem;}
.testi{background:#fff;border:1px solid rgba(42,79,140,.07);border-radius:20px;padding:1.75rem;transition:all .3s;}
.testi:hover{transform:translateY(-4px);box-shadow:0 12px 40px rgba(42,79,140,.09);}
.stars{color:var(--p500);font-size:.75rem;margin-bottom:.85rem;letter-spacing:.1em;}
.testi-text{font-size:.84rem;color:var(--s700);line-height:1.7;margin-bottom:1.25rem;font-style:italic;}
.testi-author{display:flex;align-items:center;gap:.75rem;}
.avatar{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--p800),var(--p600));display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:#fff;flex-shrink:0;}
.author-name{font-size:.875rem;font-weight:600;color:var(--s900);}
.author-role{font-size:.74rem;color:var(--s400);}
@media(max-width:1000px){.testi-grid{grid-template-columns:1fr 1fr;}}
@media(max-width:600px){.testi-grid{grid-template-columns:1fr;}}

/* ── CTA ─────────────────────────────────────────────────── */
.cta-wrap{background:linear-gradient(145deg,var(--p900) 0%,var(--p800) 30%,var(--p700) 60%,var(--p600) 85%,var(--p500) 100%);border-radius:28px;padding:5rem;position:relative;overflow:hidden;text-align:center;}
.cta-wrap .dot-grid{border-radius:28px;}
.cta-orb{position:absolute;width:500px;height:500px;background:radial-gradient(circle,rgba(42,79,140,.3) 0%,transparent 65%);border-radius:50%;top:-200px;left:50%;transform:translateX(-50%);filter:blur(60px);pointer-events:none;}
.cta-inner{position:relative;z-index:1;}
.cta-wrap h2{font-size:clamp(1.75rem,4vw,2.75rem);color:#fff;margin-bottom:.85rem;font-weight:800;letter-spacing:-.02em;}
.cta-wrap p{color:rgba(255,255,255,.48);max-width:440px;margin:0 auto 2.5rem;font-size:.95rem;line-height:1.7;}
.cta-btn{display:inline-flex;align-items:center;gap:.6rem;padding:1rem 2.5rem;background:rgba(255,255,255,.12);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.22);border-radius:14px;text-decoration:none;color:#fff;font-weight:700;font-size:.95rem;transition:all .25s;}
.cta-btn:hover{background:rgba(255,255,255,.2);transform:translateY(-3px);box-shadow:0 14px 40px -8px rgba(0,0,0,.4);}
@media(max-width:600px){.cta-wrap{padding:3rem 1.5rem;}}

/* ── FOOTER ──────────────────────────────────────────────── */
.footer{background:var(--p900);border-top:1px solid rgba(65,110,180,.1);padding:4rem 0 2rem;}
.footer-top{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:3rem;margin-bottom:3rem;}
.footer-brand p{font-size:.85rem;color:rgba(255,255,255,.3);margin:1rem 0;line-height:1.75;}
.socials{display:flex;gap:.55rem;}
.soc{width:34px;height:34px;border:1px solid rgba(179,197,225,.14);border-radius:8px;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.35);text-decoration:none;font-size:.8rem;transition:all .2s;}
.soc:hover{border-color:var(--p400);color:var(--p300);background:rgba(65,110,180,.1);}
.footer-col h4{font-size:.72rem;font-weight:700;color:rgba(255,255,255,.22);text-transform:uppercase;letter-spacing:.1em;margin-bottom:1.2rem;}
.footer-col ul{list-style:none;}
.footer-col ul li{margin-bottom:.6rem;}
.footer-col ul li a{color:rgba(255,255,255,.36);text-decoration:none;font-size:.84rem;transition:color .2s;}
.footer-col ul li a:hover{color:#fff;}
.footer-bottom{border-top:1px solid rgba(255,255,255,.05);padding-top:1.75rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;}
.footer-bottom p{font-size:.76rem;color:rgba(255,255,255,.18);}
@media(max-width:900px){.footer-top{grid-template-columns:1fr 1fr;gap:2rem;}}
@media(max-width:480px){.footer-top{grid-template-columns:1fr;}}

/* ── SCROLL TOP & REVEALS ────────────────────────────────── */
.top-btn{position:fixed;bottom:2rem;right:2rem;z-index:99;width:42px;height:42px;background:linear-gradient(135deg,var(--p700),var(--p500));border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;text-decoration:none;opacity:0;visibility:hidden;transform:translateY(10px);transition:all .3s;box-shadow:0 4px 18px rgba(30,58,102,.45);}
.top-btn.show{opacity:1;visibility:visible;transform:translateY(0);}
.top-btn:hover{opacity:.88;}
.reveal{opacity:0;transform:translateY(24px);transition:opacity .6s ease,transform .6s ease;}
.reveal.visible{opacity:1;transform:translateY(0);}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="nav" id="nav">
  <div class="container">
    <div class="nav-inner">
      <a href="#" class="logo">
        <div class="logo-mark"><i class="fas fa-gavel"></i></div>
        <div class="logo-name">Lelang<span>Online</span></div>
      </a>
      <div class="nav-links">
        <a href="#features">Fitur</a>
        <a href="#how-it-works">Cara Kerja</a>
        <a href="#roles">Level Akses</a>
        <a href="#testimonials">Testimoni</a>
      </div>
      <div class="nav-cta">
        <a href="auth/login.php" class="nbtn-ghost">Masuk</a>
        <a href="auth/register.php" class="nbtn-primary"><i class="fas fa-arrow-right"></i> Daftar</a>
      </div>
      <button class="hamburger" id="ham"><i class="fas fa-bars"></i></button>
    </div>
  </div>
</nav>

<div class="mob-menu" id="mobMenu">
  <a href="#features">Fitur</a>
  <a href="#how-it-works">Cara Kerja</a>
  <a href="#roles">Level Akses</a>
  <a href="#testimonials">Testimoni</a>
  <a href="auth/login.php">Masuk</a>
  <a href="auth/register.php">Daftar Sekarang</a>
</div>

<!-- HERO -->
<section class="hero">
  <div class="dot-grid"></div>
  <div class="orb orb-1"></div>
  <div class="orb orb-2"></div>
  <div class="orb orb-3"></div>
  <div class="container">
    <div class="hero-inner">
      <div>
        <div class="hero-eyebrow">
          <div class="pulse-dot"></div>
          <span><?php echo $active_lelang > 0 ? $active_lelang . ' Lelang Aktif Sekarang' : 'Platform Lelang Digital'; ?></span>
        </div>
        <h1>
          Lelang Digital<br>
          Transparan.<br>
          <em>Terpercaya.</em>
        </h1>
        <p class="hero-desc">Ikuti proses lelang secara online dengan mudah, aman, dan dapat dipantau secara langsung. Platform modern untuk semua kebutuhan Anda.</p>
        <div class="hero-actions">
          <a href="auth/register.php" class="hbtn-primary"><i class="fas fa-rocket"></i> Mulai Sekarang</a>
          <a href="auth/login.php" class="hbtn-ghost"><i class="fas fa-sign-in-alt"></i> Masuk</a>
        </div>
      </div>
      <div class="hero-right">
        <div class="hero-visual">
          <div class="ring ring-1"><div class="ring-dot"></div></div>
          <div class="ring ring-2"><div class="ring-dot"></div></div>
          <div class="ring ring-3"><div class="ring-dot"></div></div>
          <div class="hero-core">
            <i class="fas fa-gavel"></i>
            <span>Live Auction</span>
          </div>
          <div class="float-pill fp1"><i class="fas fa-bolt"></i> Real-time Bidding</div>
          <div class="float-pill fp2"><i class="fas fa-shield-alt"></i> Terverifikasi</div>
          <div class="float-pill fp3"><i class="fas fa-chart-line"></i> Transparan</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- STATS -->
<div class="stats-bar">
  <div class="container">
    <div class="stats-inner">
      <div class="stat"><div class="stat-num"><?php echo number_format($total_users,0,',','.'); ?><sup>+</sup></div><div class="stat-lbl">Pengguna Terdaftar</div></div>
      <div class="stat"><div class="stat-num"><?php echo $total_barang; ?></div><div class="stat-lbl">Total Barang</div></div>
      <div class="stat"><div class="stat-num"><?php echo $active_lelang; ?></div><div class="stat-lbl">Lelang Aktif</div></div>
      <div class="stat"><div class="stat-num"><?php echo $total_selesai; ?></div><div class="stat-lbl">Lelang Selesai</div></div>
    </div>
  </div>
</div>

<!-- FEATURES -->
<section class="section" id="features">
  <div class="container">
    <div class="hdr reveal">
      <div class="tag">Fitur Unggulan</div>
      <h2 class="sec-title">Kenapa LelangOnline?</h2>
      <p class="sec-sub">Dibangun untuk memberikan pengalaman lelang terbaik bagi semua pengguna.</p>
    </div>
    <div class="features-bento">
      <?php
      $feats=[
        ['fa-bolt','Real-time Bidding','Penawaran diperbarui secara langsung. Semua peserta melihat harga tertinggi saat itu juga tanpa delay.',false],
        ['fa-shield-alt','Keamanan Berlapis','Sistem keamanan dengan kontrol akses berdasarkan level pengguna untuk menjamin keamanan data.',false],
        ['fa-mobile-alt','Mobile Friendly','Tampilan responsif yang dapat diakses dari berbagai perangkat — desktop, tablet, maupun HP.',true],
        ['fa-clipboard-list','Verifikasi Barang','Setiap barang diverifikasi oleh petugas sebelum masuk ke daftar lelang resmi.',false],
        ['fa-file-alt','Laporan Lengkap','Cetak laporan lelang dengan filter tanggal dan rekap nilai transaksi secara otomatis.',false],
        ['fa-chart-line','Histori Transparan','Riwayat penawaran dapat dilihat oleh semua peserta untuk memastikan proses yang adil.',false],
      ];
      foreach($feats as $i=>$f): ?>
      <div class="feat <?php echo $f[3]?'feat-wide':''; ?> reveal">
        <div class="feat-icon"><i class="fas <?php echo $f[0]; ?>"></i></div>
        <h3><?php echo $f[1]; ?></h3>
        <p><?php echo $f[2]; ?></p>
        <div class="feat-num">0<?php echo $i+1; ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="section section-dark" id="how-it-works">
  <div class="dot-grid"></div>
  <div class="orb orb-1" style="opacity:.5;"></div>
  <div class="orb orb-2" style="opacity:.4;"></div>
  <div class="container" style="position:relative;z-index:2;">
    <div class="hdr hdr-center reveal">
      <div class="tag tag-light">Cara Kerja</div>
      <h2 class="sec-title sec-title-light">4 Langkah Mudah</h2>
      <p class="sec-sub sec-sub-light">Proses yang simpel untuk semua kalangan.</p>
    </div>
    <div class="steps-track reveal">
      <?php foreach([
        ['01','Buat Akun','Daftar gratis dengan data diri. Langsung aktif tanpa verifikasi tambahan.'],
        ['02','Telusuri Lelang','Cari dan lihat barang yang sedang dilelang oleh petugas.'],
        ['03','Ajukan Penawaran','Masukkan nilai tawaran lebih tinggi dari penawaran sebelumnya.'],
        ['04','Menangkan','Penawar tertinggi menjadi pemenang dan lanjutkan ke pembayaran.'],
      ] as $s): ?>
      <div class="step">
        <div class="step-num"><?php echo $s[0]; ?></div>
        <h3><?php echo $s[1]; ?></h3>
        <p><?php echo $s[2]; ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ROLES -->
<section class="section section-alt" id="roles">
  <div class="container">
    <div class="hdr hdr-center reveal">
      <div class="tag">Level Akses</div>
      <h2 class="sec-title">Tiga Peran Pengguna</h2>
      <p class="sec-sub">Setiap peran memiliki akses dan fungsi berbeda sesuai kebutuhan.</p>
    </div>
    <div class="roles-stack">
      <?php
      $roles=[
        ['fa-crown','Administrator','Kontrol penuh sistem lelang',['Dashboard statistik lengkap','Manajemen semua pengguna','Kelola data barang lelang','Monitor & audit semua lelang','Generate laporan per periode'],'Akses Panel Admin','auth/login.php'],
        ['fa-user-tie','Petugas','Operasional lelang sehari-hari',['Pendataan & input barang','Buka dan tutup sesi lelang','Monitor penawaran real-time','Konfirmasi pembayaran','Cetak laporan lelang'],'Akses Panel Petugas','auth/login.php'],
        ['fa-users','Masyarakat','Peserta lelang umum',['Daftar akun gratis','Browse barang lelang aktif','Ajukan penawaran harga','Lihat riwayat penawaran','Upload bukti pembayaran'],'Daftar Sekarang','auth/register.php'],
      ];
      foreach($roles as $r): ?>
      <div class="role reveal">
        <div class="role-accent"></div>
        <div class="role-avatar"><i class="fas <?php echo $r[0]; ?>"></i></div>
        <h3><?php echo $r[1]; ?></h3>
        <p class="role-tagline"><?php echo $r[2]; ?></p>
        <ul class="role-list"><?php foreach($r[3] as $item): ?><li><?php echo $item; ?></li><?php endforeach; ?></ul>
        <a href="<?php echo $r[5]; ?>" class="role-cta"><?php echo $r[4]; ?> <i class="fas fa-arrow-right"></i></a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="section" id="testimonials">
  <div class="container">
    <div class="hdr hdr-center reveal">
      <div class="tag">Testimoni</div>
      <h2 class="sec-title">Apa Kata Mereka?</h2>
      <p class="sec-sub">Pengalaman nyata dari pengguna LelangOnline.</p>
    </div>
    <div class="testi-grid">
      <?php foreach([
        ['BS','Budi Santoso','Kolektor','Prosesnya sangat transparan. Berhasil mendapatkan barang incaran dengan harga terjangkau.'],
        ['SR','Siti Rahma','Ibu Rumah Tangga','Pertama kali ikut lelang langsung menang! Sistemnya mudah dipahami oleh siapa saja.'],
        ['AF','Ahmad Fauzi','Pengusaha','Sudah tiga kali menang lelang. Proses cepat, barang original, pelayanan responsif.'],
        ['DL','Dewi Lestari','Desainer','User-friendly dan bisa diakses dari HP dengan lancar. Platform yang benar-benar profesional.'],
      ] as $t): ?>
      <div class="testi reveal">
        <div class="stars">★★★★★</div>
        <p class="testi-text">"<?php echo $t[3]; ?>"</p>
        <div class="testi-author">
          <div class="avatar"><?php echo $t[0]; ?></div>
          <div>
            <div class="author-name"><?php echo $t[1]; ?></div>
            <div class="author-role"><?php echo $t[2]; ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="section">
  <div class="container">
    <div class="cta-wrap reveal">
      <div class="dot-grid"></div>
      <div class="cta-orb"></div>
      <div class="cta-inner">
        <h2>Siap Memulai Lelang?</h2>
        <p>Bergabunglah sekarang dan dapatkan barang terbaik melalui proses lelang yang adil dan transparan.</p>
        <?php if(!isset($_SESSION['id_user'])): ?>
          <a href="auth/register.php" class="cta-btn"><i class="fas fa-user-plus"></i> Daftar Gratis</a>
        <?php else: ?>
          <a href="masyarakat/lelang.php" class="cta-btn"><i class="fas fa-gavel"></i> Lihat Lelang Aktif</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer class="footer">
  <div class="container">
    <div class="footer-top">
      <div class="footer-brand">
        <a href="#" class="logo" style="margin-bottom:.5rem;">
          <div class="logo-mark"><i class="fas fa-gavel"></i></div>
          <div class="logo-name">Lelang<span>Online</span></div>
        </a>
        <p>Platform lelang online terpercaya. Transparan, aman, dan mudah untuk semua kalangan.</p>
        <div class="socials">
          <a href="#" class="soc"><i class="fab fa-instagram"></i></a>
          <a href="#" class="soc"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="soc"><i class="fab fa-twitter"></i></a>
          <a href="#" class="soc"><i class="fab fa-linkedin-in"></i></a>
        </div>
      </div>
      <div class="footer-col"><h4>Navigasi</h4><ul><li><a href="#features">Fitur</a></li><li><a href="#how-it-works">Cara Kerja</a></li><li><a href="#roles">Level Akses</a></li><li><a href="#testimonials">Testimoni</a></li></ul></div>
      <div class="footer-col"><h4>Akun</h4><ul><li><a href="auth/login.php">Masuk</a></li><li><a href="auth/register.php">Daftar Baru</a></li><li><a href="auth/login.php">Admin Panel</a></li><li><a href="auth/login.php">Petugas Panel</a></li></ul></div>
      <div class="footer-col"><h4>Kontak</h4><ul><li><a href="#">support@lelangonline.id</a></li><li><a href="#">+62 812-3456-7890</a></li><li><a href="#">Jakarta, Indonesia</a></li></ul></div>
    </div>
 

<a href="#" class="top-btn" id="topBtn"><i class="fas fa-arrow-up"></i></a>

<script>
const nav=document.getElementById('nav'),topBtn=document.getElementById('topBtn');
window.addEventListener('scroll',()=>{
  nav.classList.toggle('solid',window.scrollY>40);
  topBtn.classList.toggle('show',window.scrollY>300);
});
document.getElementById('ham').addEventListener('click',()=>document.getElementById('mobMenu').classList.toggle('open'));
topBtn.addEventListener('click',e=>{e.preventDefault();window.scrollTo({top:0,behavior:'smooth'});});
document.querySelectorAll('a[href^="#"]').forEach(a=>{
  a.addEventListener('click',e=>{
    const t=document.querySelector(a.getAttribute('href'));
    if(t){e.preventDefault();t.scrollIntoView({behavior:'smooth'});document.getElementById('mobMenu').classList.remove('open');}
  });
});
const io=new IntersectionObserver(entries=>{
  entries.forEach((entry,i)=>{
    if(entry.isIntersecting){setTimeout(()=>entry.target.classList.add('visible'),i*80);io.unobserve(entry.target);}
  });
},{threshold:0.1});
document.querySelectorAll('.reveal').forEach(el=>io.observe(el));
</script>
</body>
</html>