<?php
require_once __DIR__ . "/inc/init.php";
require_once __DIR__ . "/inc/user_repo.php";

require_login();

$user = current_user();

$dbUser = find_user_by_id((int)$user["id"]);
if ($dbUser) {
  $_SESSION["user"]["level"] = $dbUser["language_level"];
  $_SESSION["user"]["avatar"] = $dbUser["avatar_path"];
  $user = current_user();
}
?>
<!doctype html>
<html lang="tr">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard - AI Tutor</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="page-dashboard">

  <header>
    <a href="dashboard.php" class="logo">AI TUTOR</a>
    <a href="logout.php" class="btn-logout">Çıkış Yap</a>
  </header>

  <div class="container">
    <div class="welcome-section">
      <div class="user-badge">✨ Kişisel Öğrenme Alanı</div>
      <h2 style="margin-top: 1rem;">Hoş geldin, <?php echo h($user["name"]); ?> 👋</h2>
      <p style="color: var(--text-muted); font-weight: 500;">Bugün yeni bir şeyler öğrenmeye ne dersin?</p>
    </div>

    <div class="grid">
      <a href="chat.php" class="card">
        <div class="card-icon">💬</div>
        <h3>AI Eğitmen</h3>
        <p>Anlık geri bildirimlerle doğal bir sohbet ortamında dil pratiği yap.</p>
      </a>

      <a href="placement.php" class="card">
        <div class="card-icon">📝</div>
        <h3>Seviye Testi</h3>
        <p>Yapay zeka analizli sorularla güncel dil seviyeni hemen belirle.</p>
      </a>

      <a href="profile.php" class="card">
        <div class="card-icon">👤</div>
        <h3>Profilim</h3>
        <p>İstatistiklerini incele ve kişisel tercihlerini yönet.</p>
      </a>

      <a href="history.php" class="card">
        <div class="card-icon">🕒</div>
        <h3>Sohbetler</h3>
        <p>Önceki pratiklerini incele ve hatalarından ders çıkar.</p>
      </a>
    </div>

    <?php if (($_SESSION["user"]["role"] ?? "user") === "admin"): ?>
      <div class="admin-link">
        <a href="admin/index.php" class="btn-admin">
          <span>⚙️ Yönetim Paneli</span>
        </a>
      </div>
    <?php endif; ?>
  </div>

</body>

</html>
