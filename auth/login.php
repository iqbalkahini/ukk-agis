<?php
ob_start();
session_start();
require_once('../config/config.php');

if (isset($_SESSION['id_user'])) {
    ob_end_clean();
    if ($_SESSION['id_level'] == 1)     header('Location: ../admin/dashboard.php');
    elseif ($_SESSION['id_level'] == 2) header('Location: ../petugas/dashboard.php');
    else                                header('Location: ../masyarakat/dashboard.php');
    exit;
}

$error = '';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);
    $query  = "SELECT u.*, l.level FROM tb_user u 
               JOIN tb_level l ON u.id_level = l.id_level 
               WHERE u.username = '$username' AND u.password = '$password'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) == 1) {
        $data = mysqli_fetch_assoc($result);
        $_SESSION['id_user']      = $data['id_user'];
        $_SESSION['username']     = $data['username'];
        $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
        $_SESSION['id_level']     = $data['id_level'];
        $_SESSION['level']        = $data['level'];
        ob_end_clean();
        if ($data['id_level'] == 1)      header('Location: ../admin/dashboard.php');
        elseif ($data['id_level'] == 2)  header('Location: ../petugas/dashboard.php');
        else                             header('Location: ../masyarakat/dashboard.php');
        exit;
    } else {
        $error = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — LelangOnline</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{
  --p50:#eef2f8;--p100:#d9e2f0;--p200:#b3c5e1;--p300:#8da8d2;
  --p400:#678bc3;--p500:#416eb4;--p600:#2a4f8c;--p700:#1e3a66;
  --p800:#132a4a;--p900:#0a1a30;
  --s50:#f8fafc;--s100:#f1f5f9;--s200:#e2e8f0;--s300:#cbd5e1;
  --s400:#94a3b8;--s500:#64748b;--s700:#334155;--s900:#0f172a;
  --white:#ffffff;--red:#ef4444;--green:#22c55e;
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

.wrap{position:relative;z-index:10;width:100%;max-width:420px;animation:slideUp .6s cubic-bezier(.16,1,.3,1) both;}
@keyframes slideUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:none;}}

/* Brand */
.brand{text-align:center;margin-bottom:2rem;}
.brand-icon{
  width:64px;height:64px;border-radius:20px;
  background:rgba(255,255,255,0.15);backdrop-filter:blur(12px);
  border:1px solid rgba(255,255,255,0.2);
  display:inline-flex;align-items:center;justify-content:center;
  box-shadow:0 8px 32px rgba(0,0,0,0.2);
  margin-bottom:14px;
}
.brand-icon i{font-size:1.7rem;color:#fff;}
.brand-name{font-size:1.7rem;font-weight:800;color:#fff;letter-spacing:-.025em;}
.brand-name span{color:#93c5fd;}
.brand-tag{font-size:.6rem;color:rgba(255,255,255,0.4);font-weight:500;letter-spacing:.14em;text-transform:uppercase;margin-top:3px;}

/* Card */
.card{
  background:rgba(255,255,255,0.97);
  border-radius:28px;
  padding:36px 34px 30px;
  box-shadow:0 32px 80px -12px rgba(10,26,48,0.5), 0 0 0 1px rgba(255,255,255,0.1);
}
.card-title{font-size:1.45rem;font-weight:800;color:var(--p900);letter-spacing:-.025em;margin-bottom:4px;}
.card-sub{font-size:.83rem;color:var(--s500);margin-bottom:26px;}
.card-sub a{color:var(--p600);font-weight:700;text-decoration:none;border-bottom:1.5px solid var(--p200);}
.card-sub a:hover{color:var(--p700);}

/* Alert */
.alert{display:flex;align-items:center;gap:10px;border-radius:14px;padding:11px 14px;margin-bottom:20px;}
.alert-err{background:#fef2f2;border:1.5px solid #fca5a5;animation:shake .35s ease;}
.alert-err i{color:var(--red);font-size:.82rem;flex-shrink:0;}
.alert-err p{font-size:.82rem;font-weight:600;color:#b91c1c;}
@keyframes shake{0%,100%{transform:translateX(0);}25%{transform:translateX(-5px);}75%{transform:translateX(4px);}}

/* Fields */
.field{margin-bottom:18px;}
.field-label{display:block;font-size:.68rem;font-weight:700;color:var(--s700);text-transform:uppercase;letter-spacing:.1em;margin-bottom:7px;}
.fw{position:relative;}
.fi{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--s400);font-size:.8rem;pointer-events:none;transition:color .2s;}
.fw:focus-within .fi{color:var(--p500);}
.finput{
  width:100%;padding:12px 14px 12px 40px;
  border:1.5px solid var(--s200);border-radius:13px;
  font-family:inherit;font-size:.875rem;font-weight:500;
  color:var(--s900);background:var(--s50);outline:none;
  transition:all .2s;
}
.finput::placeholder{color:var(--s300);font-weight:400;}
.finput:focus{border-color:var(--p400);background:#fff;box-shadow:0 0 0 4px rgba(65,110,180,0.1);}
.feye{
  position:absolute;right:12px;top:50%;transform:translateY(-50%);
  background:none;border:none;cursor:pointer;
  color:var(--s400);font-size:.8rem;padding:4px 6px;
  transition:color .2s;border-radius:6px;
}
.feye:hover{color:var(--p500);background:var(--p50);}

/* Button */
.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:9px;
  width:100%;padding:13px 24px;border-radius:14px;
  font-family:inherit;font-size:.9rem;font-weight:700;
  cursor:pointer;border:none;text-decoration:none;
  transition:all .25s;position:relative;overflow:hidden;
}
.btn-primary{
  background:linear-gradient(135deg,var(--p700),var(--p500));
  color:#fff;
  box-shadow:0 6px 20px -4px rgba(30,58,102,0.45);
}
.btn-primary::before{
  content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,var(--p600),var(--p400));
  opacity:0;transition:opacity .25s;
}
.btn-primary:hover::before{opacity:1;}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 12px 28px -5px rgba(30,58,102,0.5);}
.btn span,.btn i{position:relative;z-index:1;}

.divider{display:flex;align-items:center;gap:12px;margin:20px 0;}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--s200);}
.divider span{font-size:.72rem;color:var(--s400);font-weight:600;white-space:nowrap;}

.foot-link{text-align:center;font-size:.83rem;color:var(--s400);margin-top:16px;}
.foot-link a{color:var(--p600);font-weight:700;text-decoration:none;border-bottom:1.5px solid var(--p200);}
.foot-link a:hover{color:var(--p700);}

.dots{display:flex;justify-content:center;gap:6px;margin-top:24px;}
.dot{width:6px;height:6px;border-radius:50%;background:var(--s200);}
.dot.on{width:20px;border-radius:3px;background:var(--p500);}

.page-footer{text-align:center;margin-top:1.25rem;font-size:.7rem;color:rgba(255,255,255,0.35);letter-spacing:.03em;}
</style>
</head>
<body>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="wrap">
  <div class="brand">
    <div class="brand-icon"><i class="fas fa-gavel"></i></div>
    <div class="brand-name">Lelang<span>Online</span></div>
    <div class="brand-tag">Platform Lelang Terpercaya</div>
  </div>

  <div class="card">
    <h2 class="card-title">Selamat Datang </h2>
    <p class="card-sub">Belum punya akun? <a href="register.php">Daftar sekarang</a></p>

    <?php if($error):?>
    <div class="alert alert-err">
      <i class="fas fa-exclamation-circle"></i>
      <p><?php echo htmlspecialchars($error);?></p>
    </div>
    <?php endif;?>

    <form method="POST" action="">
      <div class="field">
        <label class="field-label">Nama Pengguna</label>
        <div class="fw">
          <i class="fi fas fa-user"></i>
          <input type="text" name="username" class="finput" placeholder="Masukkan  nama  pengguna" required
            value="<?php echo htmlspecialchars($_POST['username'] ?? '');?>">
        </div>
      </div>

      <div class="field">
        <label class="field-label">Kata Sandi</label>
        <div class="fw">
          <i class="fi fas fa-lock"></i>
          <input type="password" name="password" id="pwd" class="finput" placeholder="Masukkan  kata  sandi" required>
          <button type="button" class="feye" onclick="tog()"><i class="fas fa-eye" id="eyeIcon"></i></button>
        </div>
      </div>

      <button type="submit" name="login" class="btn btn-primary">
        <i class="fas fa-arrow-right-to-bracket"></i><span>Masuk</span>
      </button>
    </form>

    <div class="divider"><span>atau</span></div>
    <div class="foot-link">Belum punya akun? <a href="register.php">Daftar Sekarang</a></div>

    <div class="dots">
      <div class="dot on"></div>
      <div class="dot"></div>
    </div>
  </div>

  
<script>
function tog(){
  var p=document.getElementById('pwd'),i=document.getElementById('eyeIcon');
  p.type=p.type==='password'?'text':'password';
  i.className=p.type==='text'?'fas fa-eye-slash':'fas fa-eye';
}
</script>
</body>
</html>