<?php
require_once __DIR__ . "/inc/init.php";
require_login();
$user = current_user();
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Chat - BeeFluent</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    /* Sadece yazı tipini eşitleyen ve temayı bozmayan eklemeler */
    body.page-chat,
    #chatBox,
    #msg,
    .welcome-container,
    .q-card p,
    .user-info {
      font-family: 'Inter', sans-serif !important;
      -webkit-font-smoothing: antialiased;
    }

    #msg::placeholder {
      font-family: 'Inter', sans-serif;
      opacity: 0.6;
    }

    /* Mesajların içindeki yazıların da yumuşak fontla gelmesini garanti eder */
    #chatBox div,
    #chatBox p,
    #chatBox span {
      font-family: 'Inter', sans-serif !important;
    }
  </style>
</head>

<body class="page-chat">

  <header>
    <a href="dashboard.php" class="logo">BeeFluent</a>
    <div class="user-info" style="font-size: 0.85rem; color: var(--text-muted);">
      Level: <span style="color: var(--primary); font-weight: 800;"><?php echo h($user["level"] ?? 'A1'); ?></span> |
      User: <span style="color: var(--text-dark); font-weight: 700;"><?php echo h($user["name"]); ?></span>
    </div>
    <nav class="nav-links">
      <a href="dashboard.php">Dashboard</a>
      <a href="history.php">History</a>
      <a href="logout.php" style="color: #ef4444;">Log out</a>
    </nav>
  </header>

  <main>
    <div class="chat-wrapper">
      <div id="chatBox">
        <div class="welcome-container" id="quick-actions">
          <div class="welcome-header">
            <h2 style="color:#e07e23" class="bee-logo">BeeFluent</h2>
            <p>How would you like to proceed today? ✨</p>
          </div>

          <div class="quick-cards">
            <div class="q-card" onclick="sendQuickAction('I want to learn the word of the day.')">
              <span class="icon">🐝</span>
              <p>Word of the Day</p>
            </div>

            <div class="q-card" onclick="sendQuickAction('Can you tell me a short story in English?')">
              <span class="icon">📖</span>
              <p>Short Story</p>
            </div>

            <div class="q-card" onclick="sendQuickAction('Let\'s practice grammar at <?php echo h($user['level'] ?? 'A1'); ?> level.')">
              <span class="icon">⚖️</span>
              <p><span><?php echo h($user['level'] ?? 'A1'); ?></span> Practice</p>
            </div>
          </div>
        </div>
      </div>

      <div id="status"></div>

      <div class="input-area">
        <input id="msg" placeholder="Type your message here…" autocomplete="off">
        <button id="sendBtn" aria-label="Send message">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="22" y1="2" x2="11" y2="13"></line>
            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
          </svg>
        </button>
      </div>
    </div>
  </main>

  <script src="assets/js/chat.js"></script>

  <script>
    // Quick Action Function
    function sendQuickAction(text) {
      const msgInput = document.getElementById("msg");
      const sendBtn = document.getElementById("sendBtn");
      const quickActions = document.getElementById("quick-actions");

      // Set input value
      msgInput.value = text;

      // Hide cards visually
      if (quickActions) {
        quickActions.style.display = "none";
      }

      // Trigger the send logic in existing chat.js
      sendBtn.click();
    }

    if ("serviceWorker" in navigator) {
      navigator.serviceWorker.register("pwa/service-worker.js");
    }
  </script>
</body>

</html>
