
<?php
require_once __DIR__ . "/../inc/admin_guard.php";
require_once __DIR__ . "/../inc/chat_repo.php";
require_once __DIR__ . "/../inc/db.php";
require_admin();

$userId = (int)($_GET["user_id"] ?? 0);
if ($userId <= 0) {
  echo "Kullanıcı ID bulunamadı.";
  exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "clear") {
  chat_clear_user($userId);
  redirect("user_chats.php?user_id=" . $userId);
}

$stmtU = $conn->prepare("SELECT id, name, email FROM users WHERE id=?");
$stmtU->bind_param("i", $userId);
$stmtU->execute();
$target = $stmtU->get_result()->fetch_assoc();
if (!$target) {
  echo "Kullanıcı mevcut değil.";
  exit;
}

$stmt = $conn->prepare("SELECT role, message, created_at FROM chats WHERE user_id=? ORDER BY id ASC LIMIT 1000");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();

$messages = [];
while ($r = $res->fetch_assoc()) $messages[] = $r;
?>
<!doctype html>
<html lang="tr">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kullanıcı Sohbetleri - Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #6366f1;
      --primary-dark: #0f172a;
      --bg-main: #f1f5f9;
      --white: #ffffff;
      --text-dark: #1e293b;
      --text-muted: #64748b;
      --border: #e2e8f0;
      --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
      --radius: 12px;
      --danger: #ef4444;
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
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar */
    aside {
      width: 260px;
      background: var(--primary-dark);
      color: white;
      padding: 2rem 1.5rem;
      position: fixed;
      height: 100vh;
    }

    aside h2 {
      color: var(--primary);
      margin-bottom: 2rem;
      font-weight: 800;
      font-size: 1.2rem;
    }

    .side-nav {
      list-style: none;
    }

    .side-nav a {
      text-decoration: none;
      color: #94a3b8;
      padding: 0.75rem 1rem;
      display: block;
      border-radius: 8px;
      transition: 0.2s;
      font-size: 0.9rem;
    }

    .side-nav a:hover {
      background: rgba(255, 255, 255, 0.05);
      color: white;
    }

    .side-nav a.active {
      background: var(--primary);
      color: white;
    }

    /* Main Content */
    main {
      flex: 1;
      margin-left: 260px;
      padding: 3rem;
    }

    .header-box {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      margin-bottom: 2rem;
    }

    .user-info h1 {
      font-size: 1.75rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }

    .user-info p {
      color: var(--text-muted);
      font-size: 0.9rem;
    }

    /* Chat Log Container */
    .log-container {
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      padding: 1.5rem;
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .chat-entry {
      padding: 1rem;
      border-radius: 8px;
      border-left: 4px solid #cbd5e1;
      background: #f8fafc;
    }

    .chat-entry.user {
      border-left-color: var(--primary);
      background: #f5f7ff;
    }

    .chat-entry.assistant {
      border-left-color: #10b981;
      background: #f0fdf4;
    }

    .entry-meta {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.5rem;
      font-size: 0.75rem;
      font-weight: 700;
    }

    .meta-role {
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .meta-time {
      color: var(--text-muted);
      font-weight: 400;
    }

    .entry-content {
      font-size: 0.95rem;
      white-space: pre-wrap;
      line-height: 1.5;
      color: #334155;
    }

    /* Buttons */
    .btn-clear {
      background: #fee2e2;
      color: var(--danger);
      border: none;
      padding: 10px 18px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: 0.2s;
    }

    .btn-clear:hover {
      background: var(--danger);
      color: white;
    }

    .empty-state {
      text-align: center;
      padding: 4rem;
      color: var(--text-muted);
      font-style: italic;
    }
  </style>
</head>

<body>

  <aside>
    <h2>AI TUTOR ADMIN</h2>
    <nav>
      <ul class="side-nav">
        <li><a href="index.php">🏠 Dashboard</a></li>
        <li><a href="users.php" class="active">👥 Kullanıcı Yönetimi</a></li>
        <li><a href="../dashboard.php">🌐 Siteye Dön</a></li>
      </ul>
    </nav>
  </aside>

  <main>
    <div class="header-box">
      <div class="user-info">
        <h1>Sohbet Kayıtları</h1>
        <p>Kullanıcı: <strong><?php echo h($target["name"]); ?></strong> (<?php echo h($target["email"]); ?>)</p>
      </div>

      <?php if ($messages): ?>
        <form method="post" onsubmit="return confirm('Bu kullanıcının TÜM sohbet geçmişini silmek istediğinize emin misiniz?');">
          <input type="hidden" name="action" value="clear">
          <button type="submit" class="btn-clear">Geçmişi Temizle</button>
        </form>
      <?php endif; ?>
    </div>

    <div class="log-container">
      <?php if (!$messages): ?>
        <div class="empty-state">Bu kullanıcıya ait henüz bir sohbet kaydı bulunmuyor.</div>
      <?php else: ?>
        <?php foreach ($messages as $m): ?>
          <div class="chat-entry <?php echo $m["role"] === "user" ? "user" : "assistant"; ?>">
            <div class="entry-meta">
              <span class="meta-role">
                <?php echo $m["role"] === "user" ? "👤 Kullanıcı" : "🤖 Yapay Zeka"; ?>
              </span>
              <span class="meta-time"><?php echo date("d.m.Y H:i", strtotime($m["created_at"])); ?></span>
            </div>
            <div class="entry-content"><?php echo h($m["message"]); ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div style="margin-top: 2rem;">
      <a href="users.php" style="color: var(--primary); text-decoration: none; font-weight: 600; font-size: 0.9rem;">← Kullanıcı Listesine Dön</a>
    </div>
  </main>

</body>

</html>
