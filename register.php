<?php
require_once __DIR__ . "/inc/init.php";
require_once __DIR__ . "/inc/user_repo.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name = trim($_POST["name"] ?? "");
  $email = trim($_POST["email"] ?? "");
  $pass = $_POST["password"] ?? "";
  $pass2 = $_POST["password2"] ?? "";

  if ($name === "" || $email === "" || $pass === "" || $pass2 === "") {
    $error = "Lütfen tüm alanları doldurun.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Geçerli bir e-posta adresi girin.";
  } elseif ($pass !== $pass2) {
    $error = "Şifreler birbiriyle eşleşmiyor.";
  } elseif (strlen($pass) < 6) {
    $error = "Şifre en az 6 karakter olmalıdır.";
  } else {
    try {
      $user = create_user($name, $email, $pass);
      $_SESSION["user"] = $user;
      redirect("dashboard.php");
    } catch (Throwable $e) {
      $error = "Bu e-posta adresi zaten kullanımda olabilir.";
    }
  }
}
?>
<!doctype html>
<html lang="tr">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kayıt Ol - AI Tutor</title>
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
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .register-card {
      background: var(--white);
      width: 100%;
      max-width: 450px;
      padding: 2.5rem;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
    }

    .register-header {
      text-align: center;
      margin-bottom: 2rem;
    }

    .register-header h2 {
      font-size: 1.75rem;
      font-weight: 800;
      color: var(--primary);
      letter-spacing: -1px;
    }

    .register-header p {
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
      margin-top: 1rem;
    }

    button:hover {
      background: var(--primary-hover);
    }

    .register-footer {
      margin-top: 1.5rem;
      text-align: center;
      font-size: 0.85rem;
    }

    .register-footer a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 600;
    }

    .register-footer a:hover {
      text-decoration: underline;
    }
  </style>
</head>

<body>

  <div class="register-card">
    <div class="register-header">
      <h2>YENİ HESAP</h2>
      <p>AI Tutor ile öğrenmeye bugün başla</p>
    </div>

    <?php if ($error): ?>
      <div class="error-box"><?php echo h($error); ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="form-group">
        <label>Tam Adınız</label>
        <input type="text" name="name" value="<?php echo h($_POST["name"] ?? ""); ?>" placeholder="Ad Soyad" required>
      </div>

      <div class="form-group">
        <label>E-posta Adresi</label>
        <input type="email" name="email" value="<?php echo h($_POST["email"] ?? ""); ?>" placeholder="email@örnek.com" required>
      </div>

      <div class="form-group">
        <label>Şifre</label>
        <input type="password" name="password" placeholder="En az 6 karakter" required>
      </div>

      <div class="form-group">
        <label>Şifre Tekrar</label>
        <input type="password" name="password2" placeholder="••••••••" required>
      </div>

      <button type="submit">Hesap Oluştur</button>
    </form>

    <div class="register-footer">
      <p>Zaten hesabın var mı? <a href="login.php">Giriş Yap</a></p>
    </div>
  </div>

</body>

</html>