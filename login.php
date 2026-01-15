<?php
require_once __DIR__ . "/inc/init.php";
require_once __DIR__ . "/inc/user_repo.php";
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = trim($_POST["email"] ?? "");
  $pass = $_POST["password"] ?? "";
  if ($email === "" || $pass === "") {
    $error = "Lütfen e-posta ve şifrenizi girin.";
  } else {
    $user = authenticate_user($email, $pass);
    if ($user) {
      $_SESSION["user"] = $user;
      redirect("dashboard.php");
    } else {
      $error = "Geçersiz e-posta veya şifre.";
    }
  }
}
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Giriş Yap - BeeFluent</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-full-bg">

  <div class="auth-wrapper">

    <!-- SOL: Login Panel (SENİN MEVCUT KODUN, DEĞİŞMİYOR) -->
    <div class="auth-floating-panel">
      <div class="login-header-left">
        <h2 class="bee-logo">BEE<span>FLUENT</span></h2>
        <p>Öğrenme yolculuğuna devam et 🐝</p>
      </div>

      <?php if ($error): ?>
        <div class="error-box"><?php echo h($error); ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="form-group">
          <label>E-posta Adresi</label>
          <input type="email" name="email"
            value="<?php echo h($_POST["email"] ?? ""); ?>"
            placeholder="email@örnek.com" required>
        </div>

        <div class="form-group">
          <label>Şifre</label>
          <input type="password" name="password" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-bee">Giriş Yap</button>
      </form>

      <div class="login-footer">
        <p>Hesabın yok mu? <a href="register.php">Hemen Kaydol</a></p>
        <a href="forgot_password.php" class="forgot-pass">Şifremi Unuttum</a>
      </div>
    </div>

    <!-- SAĞ: GÖRSEL + YAZI (YENİ EKLENEN KISIM) -->
    <div class="auth-right-visual">
      <img src="assets/img/hero.png" alt="BeeFluent">
      <h1>
        Don't just learn,<br>
        <span>BeeFluent.</span>
      </h1>
    </div>

  </div>

</body>
