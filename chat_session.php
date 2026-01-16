<?php
// chat_session.php
declare(strict_types=1);

require_once __DIR__ . "/inc/init.php";
require_once __DIR__ . "/inc/chat_repo.php";

require_login();

$user = current_user();
$userId = (int)$user["id"];

$sessionId = trim((string)($_GET["session_id"] ?? ""));

// Basic validation (we generate 32-hex with bin2hex(random_bytes(16)))
if ($sessionId === "" || !preg_match('/^[a-f0-9]{16,64}$/i', $sessionId)) {
  http_response_code(400);
  echo "Invalid or missing session_id.";
  exit;
}

// Handle actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $action = (string)($_POST["action"] ?? "");

  if ($action === "clear_session") {
    if (function_exists("chat_clear_session")) {
      chat_clear_session($userId, $sessionId);
    }
    redirect("history.php");
  }

  if ($action === "new_session") {
    $newSid = chat_new_session_id();
    $_SESSION["chat_session_id"] = $newSid;
    redirect("chat_session.php?session_id=" . urlencode($newSid));
  }
}

// Load messages for this session
$messages = function_exists("chat_get_session_messages")
  ? chat_get_session_messages($userId, $sessionId, 2000)
  : [];

// Keep server session aligned so continuing the chat uses this session
$_SESSION["chat_session_id"] = $sessionId;

// For header info
$lastAt = "";
if ($messages) {
  $lastAt = (string)($messages[count($messages) - 1]["created_at"] ?? "");
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Chat - BeeFluent</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">

  <meta name="chat-session-id" content="<?php echo h($sessionId); ?>">
</head>

<body class="page-chat" data-session-id="<?php echo h($sessionId); ?>">

  <header>
    <a href="dashboard.php" class="logo">BeFluent</a>

    <div class="user-info" style="font-size: 0.85rem; color: var(--text-muted); display:flex; gap:14px; align-items:center;">
      <span>
        Session: <span style="color: var(--text-dark); font-weight: 700;"><?php echo h($user["name"]); ?></span>
      </span>
      <?php if ($lastAt): ?>
        <span style="color: var(--text-muted);">
          Last used:
          <span style="color: var(--text-dark); font-weight: 700;">
            <?php echo h(date("d M Y, H:i", strtotime($lastAt))); ?>
          </span>
        </span>
      <?php endif; ?>
    </div>

    <nav class="nav-links">
      <a href="dashboard.php">Dashboard</a>
      <a href="history.php">History</a>
      <a href="logout.php" style="color: #ef4444;">Log out</a>
    </nav>
  </header>

  <main>
    <div class="chat-wrapper">

      <!-- Centered session actions (match BeFluent style) -->
      <div style="display:flex; justify-content:center; gap:12px; padding: 14px 16px; border-bottom: 1px solid rgba(245, 158, 11, 0.14); background: rgba(255,255,255,0.35);">
        <!-- New Chat (like placement button) -->
        <form method="post" style="margin:0;" onsubmit="return confirm('Start a new chat session?');">
          <input type="hidden" name="action" value="new_session">
          <button type="submit"
            style="
              background: linear-gradient(135deg, #F59E0B, #D97706);
              color: #0F172A;
              border: none;
              padding: 10px 18px;
              border-radius: 14px;
              font-weight: 900;
              cursor: pointer;
              transition: 0.2s;
              box-shadow: 0 14px 28px rgba(245, 158, 11, 0.22);
            "
            onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 18px 34px rgba(217, 119, 6, 0.28)';"
            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 14px 28px rgba(245, 158, 11, 0.22)';"
          >
            New Chat
          </button>
        </form>

        <!-- Delete Session (like history clear button) -->
        <form method="post" style="margin:0;" onsubmit="return confirm('Delete this chat session permanently?');">
          <input type="hidden" name="action" value="clear_session">
          <button type="submit"
            style="
              background: rgba(255,255,255,0.75);
              border: 1px solid rgba(225, 29, 72, 0.25);
              color: #E11D48;
              padding: 10px 18px;
              border-radius: 14px;
              font-weight: 900;
              cursor: pointer;
              transition: 0.2s;
              backdrop-filter: blur(10px);
            "
            onmouseover="this.style.background='#E11D48'; this.style.color='#fff'; this.style.transform='translateY(-1px)';"
            onmouseout="this.style.background='rgba(255,255,255,0.75)'; this.style.color='#E11D48'; this.style.transform='translateY(0)';"
          >
            Delete Session
          </button>
        </form>
      </div>

      <div id="chatBox">
        <?php if (!$messages): ?>
          <div class="welcome-msg">This session is empty. Send a message to start. ✨</div>
        <?php else: ?>
          <?php foreach ($messages as $m): ?>
            <?php
              $role = (string)($m["role"] ?? "");
              $text = (string)($m["message"] ?? "");
              $cls = ($role === "user") ? "message user-message" : "message ai-message";
            ?>
            <div class="<?php echo h($cls); ?>"><?php echo h($text); ?></div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div id="status"></div>

      <div class="input-area">
        <input id="msg" placeholder="Type something…" autocomplete="off">
        <button id="sendBtn" aria-label="Send message">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="22" y1="2" x2="11" y2="13"></line>
            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
          </svg>
        </button>
      </div>
    </div>
  </main>

  <script>
    window.__CHAT_SESSION_ID = <?php echo json_encode($sessionId, JSON_UNESCAPED_UNICODE); ?>;
    window.addEventListener("load", () => {
      const box = document.getElementById("chatBox");
      if (box) box.scrollTop = box.scrollHeight;
    });
  </script>

  <script src="assets/js/chat.js"></script>

  <script>
    if ("serviceWorker" in navigator) {
      navigator.serviceWorker.register("pwa/service-worker.js");
    }
  </script>
</body>

</html>
