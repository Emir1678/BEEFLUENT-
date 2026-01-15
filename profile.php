<?php
require_once __DIR__ . "/inc/init.php";
require_once __DIR__ . "/inc/user_repo.php";

require_login();

$userId = (int)current_user()["id"];
$error = "";
$ok = "";

$u = get_user_public($userId);
if (!$u) {
  redirect("logout.php");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name = (string)($_POST["name"] ?? "");
  $avatar = (string)($_POST["avatar"] ?? "");

  try {
    update_user_profile($userId, $name, $avatar);
    $ok = "Profil başarıyla güncellendi.";

    $_SESSION["user"]["name"] = trim($name);
    $_SESSION["user"]["avatar"] = trim($avatar) !== "" ? trim($avatar) : null;

    $u = get_user_public($userId);
  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}
?>
<!doctype html>
<html lang="tr">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Profilim - AI Tutor</title>
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
      --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
      --radius: 16px;
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
      line-height: 1.6;
    }

    header {
      background: var(--white);
      padding: 1rem 2rem;
      box-shadow: var(--shadow);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .logo {
      font-size: 1.25rem;
      font-weight: 800;
      color: var(--primary);
      text-decoration: none;
    }

    .nav-links a {
      text-decoration: none;
      color: var(--text-muted);
      font-weight: 500;
      margin-left: 1.5rem;
      font-size: 0.9rem;
      transition: 0.2s;
    }

    .nav-links a:hover {
      color: var(--primary);
    }

    .container {
      max-width: 600px;
      margin: 3rem auto;
      padding: 0 1.5rem;
    }

    .profile-card {
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      overflow: hidden;
      margin-bottom: 2rem;
    }

    .profile-header {
      background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
      padding: 2rem;
      color: white;
      text-align: center;
    }

    .avatar-preview {
      width: 80px;
      height: 80px;
      background: white;
      border-radius: 50%;
      margin: 0 auto 1rem;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      border: 4px solid rgba(255, 255, 255, 0.3);
    }

    .profile-body {
      padding: 2rem;
    }

    .info-row {
      display: flex;
      justify-content: space-between;
      padding: 0.75rem 0;
      border-bottom: 1px solid var(--border);
    }

    .info-row:last-child {
      border: none;
    }

    .info-label {
      color: var(--text-muted);
      font-weight: 500;
    }

    .info-value {
      font-weight: 600;
    }

    .form-section {
      margin-top: 2rem;
    }

    .form-group {
      margin-bottom: 1.25rem;
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
      transition: border-color 0.2s;
    }

    input:focus {
      border-color: var(--primary);
    }

    button {
      width: 100%;
      padding: 0.75rem;
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: 0.2s;
    }

    button:hover {
      background: var(--primary-hover);
    }

    .alert {
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
      font-size: 0.9rem;
      font-weight: 500;
      text-align: center;
    }

    .alert-error {
      background: #fef2f2;
      color: #ef4444;
      border: 1px solid #fee2e2;
    }

    .alert-success {
      background: #f0fdf4;
      color: #16a34a;
      border: 1px solid #dcfce7;
    }
  </style>
</head>

<body>

  <header>
    <a href="dashboard.php" class="logo">AI TUTOR</a>
    <nav class="nav-links">
      <a href="dashboard.php">Panel</a>
      <a href="chat.php">Eğitmen</a>
      <a href="history.php">Geçmiş</a>
    </nav>
  </header>

  <div class="container">
    <?php if ($error): ?>
      <div class="alert alert-error"><?php echo h($error); ?></div>
    <?php endif; ?>
    <?php if ($ok): ?>
      <div class="alert alert-success"><?php echo h($ok); ?></div>
    <?php endif; ?>

    <div class="profile-card">
      <div class="profile-header">
        <div class="avatar-preview">
          <?php if (!empty($u["avatar"])): ?>
            <img src="<?php echo h($u["avatar"]); ?>" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
          <?php else: ?>
            👤
          <?php endif; ?>
        </div>
        <h3><?php echo h($u["name"]); ?></h3>
        <p style="opacity: 0.8; font-size: 0.9rem;"><?php echo h($u["email"]); ?></p>
      </div>

      <div class="profile-body">
        <div class="info-row">
          <span class="info-label">Dil Seviyesi</span>
          <span class="info-value"><?php echo h((string)($u["level"] ?? "Belirlenmedi")); ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Katılım Tarihi</span>
          <span class="info-value">
            <?php echo !empty($u["created_at"]) ? date("d.m.Y", strtotime($u["created_at"])) : "-"; ?>
          </span>
        </div>

        <div class="form-section">
          <h4 style="margin-bottom: 1rem; font-size: 1rem;">Profili Düzenle</h4>
          <form method="post">
            <div class="form-group">
              <label>Ad Soyad</label>
              <input name="name" value="<?php echo h($u["name"]); ?>" required>
            </div>
            <div class="form-group">
              <label>Avatar Yolu (Opsiyonel)</label>
              <input name="avatar" value="<?php echo h((string)($u["avatar"] ?? "")); ?>" placeholder="/assets/avatar.png">
            </div>
            <button type="submit">Değişiklikleri Kaydet</button>
          </form>
        </div>
      </div>
    </div>

    <div style="text-align: center;">
      <a href="placement.php" style="color: var(--primary); text-decoration: none; font-size: 0.9rem; font-weight: 600;">Seviye Belirleme Testini Tekrarla</a>
    </div>
  </div>

</body>

</html>