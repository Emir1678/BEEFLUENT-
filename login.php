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
  <title>Giriş Yap - AI Tutor</title>
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
    }

    .login-card {
      background: var(--white);
      width: 100%;
      max-width: 400px;
      padding: 2.5rem;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
    }

    .login-header {
      text-align: center;
      margin-bottom: 2rem;
    }

    .login-header h2 {
      font-size: 1.75rem;
      font-weight: 800;
      color: var(--primary);
      letter-spacing: -1px;
    }

    .login-header p {
      color: var(--text-muted);
      font-size: 0.9rem;
      margin-top: 0.5rem;
    }

    .form-group {
      margin-bottom: 1.25rem;
    }

    .form-group label {
      display: block;
      font-size: 0.85rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
      color: var(--text-dark);
    }

    .form-group input {
      width: 100%;
      padding: 0.75rem 1rem;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 1rem;
      transition: all 0.2s;
      outline: none;
    }

    .form-group input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .error-box {
      background: #fef2f2;
      color: #ef4444;
      padding: 0.75rem;
      border-radius: 8px;
      font-size: 0.85rem;
      margin-bottom: 1.5rem;
      text-align: center;
      border: 1px solid #fee2e2;
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

    .login-footer {
      margin-top: 1.5rem;
      text-align: center;
      font-size: 0.85rem;
    }

    .login-footer a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 600;
    }

    .login-footer a:hover {
      text-decoration: underline;
    }

    .forgot-pass {
      display: block;
      margin-top: 10px;
      color: var(--text-muted) !important;
      font-weight: 400 !important;
    }
  </style>
</head>

<body>

  <div class="login-card">
    <div class="login-header">
      <h2>AI TUTOR</h2>
      <p>Öğrenme yolculuğuna devam et</p>
    </div>

    <?php if ($error): ?>
      <div class="error-box"><?php echo h($error); ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="form-group">
        <label>E-posta Adresi</label>
        <input type="email" name="email" value="<?php echo h($_POST["email"] ?? ""); ?>" placeholder="email@örnek.com" required>
      </div>

      <div class="form-group">
        <label>Şifre</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>

      <button type="submit">Giriş Yap</button>
    </form>

    <div class="login-footer">
      <p>Hesabın yok mu? <a href="register.php">Hemen Kaydol</a></p>
      <a href="forgot_password.php" class="forgot-pass">Şifremi Unuttum</a>
    </div>
  </div>

</body>

</html>