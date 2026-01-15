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
  <link rel="stylesheet" href="assets/css/style.css">

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="page-profile">

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
