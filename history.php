<?php
require_once __DIR__ . "/inc/init.php";
require_once __DIR__ . "/inc/chat_repo.php";

require_login();

$user = current_user();
$userId = (int)$user["id"];

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "clear") {
  chat_clear_user($userId);
  redirect("history.php");
}

$messages = chat_get_messages($userId, 500);
?>
<!doctype html>
<html lang="tr">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Geçmiş - AI Tutor</title>
  <link rel="stylesheet" href="assets/css/style.css">

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="page-history">

  <header>
    <a href="dashboard.php" class="logo">AI TUTOR</a>
    <nav class="nav-links">
      <a href="chat.php">Eğitmen</a>
      <a href="dashboard.php">Panel</a>
      <a href="logout.php" style="color: var(--danger);">Çıkış</a>
    </nav>
  </header>

  <div class="container">
    <div class="history-header">
      <h2>Sohbet Geçmişi</h2>
      <?php if ($messages): ?>
        <form method="post" onsubmit="return confirm('Tüm geçmişi silmek istediğinize emin misiniz?');">
          <input type="hidden" name="action" value="clear">
          <button type="submit" class="btn-clear">Geçmişi Temizle</button>
        </form>
      <?php endif; ?>
    </div>

    <?php if (!$messages): ?>
      <div class="empty-state">
        <p>Henüz kayıtlı bir mesaj bulunmuyor. 👋</p>
        <a href="chat.php" style="color: var(--primary); text-decoration: none; font-weight: 600; display: block; margin-top: 10px;">İlk Sohbetini Başlat</a>
      </div>
    <?php else: ?>
      <div class="history-list">
        <?php foreach ($messages as $m): ?>
          <div class="history-item">
            <div class="meta">
              <span class="<?php echo $m["role"] === "user" ? "role-user" : "role-ai"; ?>">
                <?php echo $m["role"] === "user" ? "👤 SEN" : "🤖 YAPAY ZEKA"; ?>
              </span>
              <span class="date"><?php echo h($m["created_at"]); ?></span>
            </div>
            <div class="message-text"><?php echo h($m["message"]); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</body>

</html>
