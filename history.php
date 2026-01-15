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
      --danger: #ef4444;
      --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
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
      line-height: 1.6;
    }

    /* Navbar */
    header {
      background: var(--white);
      padding: 1rem 2rem;
      box-shadow: var(--shadow);
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 10;
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
      transition: color 0.2s;
    }

    .nav-links a:hover {
      color: var(--primary);
    }

    .container {
      max-width: 800px;
      margin: 3rem auto;
      padding: 0 1.5rem;
    }

    .history-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }

    .btn-clear {
      background: none;
      border: 1px solid var(--danger);
      color: var(--danger);
      padding: 8px 16px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      font-size: 0.85rem;
      transition: all 0.2s;
    }

    .btn-clear:hover {
      background: var(--danger);
      color: white;
    }

    /* Mesaj Listesi Stili */
    .history-list {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .history-item {
      background: var(--white);
      padding: 1.25rem;
      border-radius: var(--radius);
      border: 1px solid var(--border);
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    }

    .meta {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.75rem;
      font-size: 0.8rem;
      font-weight: 600;
    }

    .role-user {
      color: var(--primary);
    }

    .role-ai {
      color: #10b981;
    }

    /* Yeşil tonu AI için */
    .date {
      color: var(--text-muted);
      font-weight: 400;
    }

    .message-text {
      font-size: 0.95rem;
      white-space: pre-wrap;
      color: #334155;
    }

    .empty-state {
      text-align: center;
      padding: 4rem;
      background: var(--white);
      border-radius: var(--radius);
      border: 2px dashed var(--border);
      color: var(--text-muted);
    }
  </style>
</head>

<body>

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