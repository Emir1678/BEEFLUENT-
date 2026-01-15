<?php
require_once __DIR__ . "/inc/init.php";
require_login();
$user = current_user();
?>
<!doctype html>
<html lang="tr">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Chat - AI Tutor</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="page-chat">


  <header>
    <a href="dashboard.php" class="logo">AI TUTOR</a>
    <div class="user-info" style="font-size: 0.85rem; color: var(--text-muted);">
      Oturum: <span style="color: var(--text-dark); font-weight: 700;"><?php echo h($user["name"]); ?></span>
    </div>
    <nav class="nav-links">
      <a href="dashboard.php">Panel</a>
      <a href="history.php">Geçmiş</a>
      <a href="logout.php" style="color: #ef4444;">Çıkış</a>
    </nav>
  </header>

  <main>
    <div class="chat-wrapper">
      <div id="chatBox">
        <div class="welcome-msg">Sohbet şifrelendi ve başlatıldı ✨</div>
      </div>

      <div id="status"></div>

      <div class="input-area">
        <input id="msg" placeholder="Bir şeyler yazın..." autocomplete="off">
        <button id="sendBtn">
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
    if ("serviceWorker" in navigator) {
      navigator.serviceWorker.register("pwa/service-worker.js");
    }
  </script>
</body>

</html>
