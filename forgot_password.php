<?php
require_once __DIR__ . "/inc/init.php";
require_once __DIR__ . "/inc/user_repo.php";

$msg = "";
$error = "";
$resetLink = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = trim($_POST["email"] ?? "");
  if ($email === "") {
    $error = "Lütfen e-posta adresinizi girin.";
  } else {
    $u = find_user_by_email($email);
    // Güvenlik için her zaman genel bir mesaj gösteriyoruz
    $msg = "Eğer bu e-posta sistemde kayıtlıysa, bir sıfırlama bağlantısı oluşturuldu.";
    if ($u) {
      $token = create_reset_token((int)$u["id"]);
      // Proje klasör ismin farklıysa burayı düzeltebilirsin
      $resetLink = "http://localhost/ai_tutor/reset_password.php?token=" . urlencode($token);
    }
  }
}
?>
<!doctype html>
<html lang="tr">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Şifremi Unuttum - AI Tutor</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #6366f1;
      --primary-hover: #4f46e5;
      --bg-main: #f8fafc;
      --white: #ffffff;
      --text-dark: #1e293b;
      --text-muted: #64748b;
      --border: #e2e8f0;
      --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
      --radius: 12px;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--bg-main);
      color: var(--text-dark);
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .card {
      background: var(--white);
      width: 100%;
      max-width: 420px;
      padding: 2.5rem;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
    }

    .header {
      text-align: center;
      margin-bottom: 2rem;
    }

    .header h2 {
      font-size: 1.5rem;
      font-weight: 800;
      color: var(--primary);
      letter-spacing: -1px;
      text-transform: uppercase;
    }

    .header p {
      color: var(--text-muted);
      font-size: 0.9rem;
      margin-top: 0.5rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    label {
      display: block;
      font-size: 0.85rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }

    input {
      width: 100%;
      padding: 0.75rem 1rem;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 1rem;
      outline: none;
      transition: all 0.2s;
    }

    input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    button {
      width: 100%;
      padding: 0.75rem;
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }

    button:hover {
      background: var(--primary-hover);
    }

    .alert {
      padding: 0.75rem;
      border-radius: 8px;
      font-size: 0.85rem;
      margin-bottom: 1.5rem;
      text-align: center;
      border: 1px solid;
    }

    .alert-error {
      background: #fef2f2;
      color: #ef4444;
      border-color: #fee2e2;
    }

    .alert-success {
      background: #f0fdf4;
      color: #16a34a;
      border-color: #dcfce7;
    }

    .demo-box {
      margin-top: 1.5rem;
      padding: 1rem;
      background: #fff7ed;
      border: 1px solid #ffedd5;
      border-radius: 8px;
      font-size: 0.8rem;
    }

    .footer {
      margin-top: 1.5rem;
      text-align: center;
      font-size: 0.85rem;
    }

    .footer a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 600;
    }
  </style>
</head>

<body>

  <div class="card">
    <div class="header">
      <h2>Şifre Kurtarma</h2>
      <p>E-posta adresini girerek şifreni sıfırlayabilirsin.</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?php echo h($error); ?></div>
    <?php endif; ?>

    <?php if ($msg): ?>
      <div class="alert alert-success"><?php echo h($msg); ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="form-group">
        <label>E-posta Adresi</label>
        <input type="email" name="email" value="<?php echo h($_POST["email"] ?? ""); ?>" placeholder="Örn: ad@soyad.com" required>
      </div>
      <button type="submit">Sıfırlama Linki Oluştur</button>
    </form>

    <?php if ($resetLink): ?>
      <div class="demo-box">
        <strong style="display:block; color: #9a3412; margin-bottom: 5px;">Demo Sıfırlama Linki:</strong>
        <a href="<?php echo h($resetLink); ?>" style="word-break: break-all; color: var(--primary);"><?php echo h($resetLink); ?></a>
      </div>
    <?php endif; ?>

    <div class="footer">
      <a href="login.php">Giriş Ekranına Dön</a>
    </div>
  </div>

</body>

</html>