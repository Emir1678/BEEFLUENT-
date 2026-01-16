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

$sessions = function_exists("chat_list_sessions") ? chat_list_sessions($userId, 50) : [];
$messages = [];
if (!$sessions) {
  // fallback to legacy messages until session_id is implemented + saved
  $messages = chat_get_messages($userId, 500);
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>History - BeeFluent</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="page-history">

  <header>
    <a href="dashboard.php" class="logo">BeeFluent</a>
    <nav class="nav-links">
      <a href="chat.php">Tutor</a>
      <a href="dashboard.php">Dashboard</a>
      <a href="logout.php" style="color: #ef4444;">Log out</a>
    </nav>
  </header>

  <div class="container">
    <div class="history-header">
      <h2>Chat History</h2>
      <?php if ($sessions || $messages): ?>
        <form method="post" onsubmit="return confirm('Are you sure you want to delete all history?');">
          <input type="hidden" name="action" value="clear">
          <button type="submit" class="btn-clear">Clear History</button>
        </form>
      <?php endif; ?>
    </div>

    <?php if (!$sessions && !$messages): ?>
      <div class="empty-state">
        <p>No saved messages yet. 👋</p>
        <a href="chat.php" style="color: var(--primary); text-decoration: none; font-weight: 600; display: block; margin-top: 10px;">Start Your First Chat</a>
      </div>

    <?php elseif ($sessions): ?>
      <!-- Sessions list (Option A) -->
      <div class="history-list">
        <?php foreach ($sessions as $s): ?>
          <?php
          $sid = (string)($s["session_id"] ?? "");
          $started = (string)($s["started_at"] ?? "");
          $lastAt  = (string)($s["last_message_at"] ?? "");
          $count   = (int)($s["message_count"] ?? 0);
          $preview = trim((string)($s["preview"] ?? ""));

          if (mb_strlen($preview) > 140) $preview = mb_substr($preview, 0, 140) . "…";

          $startedLabel = $started ? date("d M Y, H:i", strtotime($started)) : "—";
          $lastLabel    = $lastAt ? date("d M Y, H:i", strtotime($lastAt)) : "—";
          ?>
          <a class="history-item" href="<?php echo h('chat_session.php?session_id=' . urlencode($sid)); ?>" style="text-decoration:none; color: inherit;">
            <div class="meta">
              <span class="role-ai">💬 Session</span>
              <span class="date"><?php echo h($startedLabel); ?></span>
            </div>
            <div class="message-text" style="display:flex; justify-content:space-between; gap:12px;">
              <div style="flex:1; min-width:0;">
                <div style="font-weight:700; margin-bottom:6px;"><?php echo h($preview !== "" ? $preview : "No preview"); ?></div>
                <div style="color: var(--text-muted); font-size: 0.9rem;">Last message: <?php echo h($lastLabel); ?></div>
              </div>
              <div style="white-space:nowrap; color: var(--text-muted); font-weight:600;"><?php echo $count; ?> msg</div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>

    <?php else: ?>
      <!-- Legacy messages (old view) -->
      <div class="history-list">
        <?php foreach ($messages as $m): ?>
          <div class="history-item">
            <div class="meta">
              <span class="<?php echo $m["role"] === "user" ? "role-user" : "role-ai"; ?>">
                <?php echo $m["role"] === "user" ? "👤 YOU" : "🤖 AI"; ?>
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
