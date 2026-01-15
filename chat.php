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
  <style>
    :root {
      --primary: #6366f1;
      --primary-dark: #4f46e5;
      --secondary: #a855f7;
      --bg-main: #f8fafc;
      --white: #ffffff;
      --text-dark: #1e293b;
      --text-muted: #64748b;
      --border: #e2e8f0;
      --radius-lg: 24px;
      --radius-sm: 12px;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--bg-main);
      /* Dashboard ile uyumlu derinlik katan gradient arka plan */
      background-image:
        radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.05) 0%, transparent 20%),
        radial-gradient(circle at 90% 80%, rgba(168, 85, 247, 0.05) 0%, transparent 20%);
      color: var(--text-dark);
      height: 100vh;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    /* Modern Cam Efektli Navbar */
    header {
      background: rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(12px);
      padding: 1rem 2rem;
      border-bottom: 1px solid rgba(226, 232, 240, 0.8);
      display: flex;
      justify-content: space-between;
      align-items: center;
      z-index: 100;
    }

    .logo {
      font-size: 1.5rem;
      font-weight: 800;
      text-decoration: none;
      /* Önce düz bir renk veriyoruz (Yedek plan) */
      color: #6366f1;
    }

    /* Tarayıcı gradient destekliyorsa bunu uygula (Hata almanı engeller) */
    @supports (background-clip: text) or (-webkit-background-clip: text) {
      .logo {
        background: linear-gradient(135deg, #6366f1, #a855f7);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        /* Bazı tarayıcılar için ek güvenlik */
        -webkit-text-fill-color: transparent;
      }
    }

    .nav-links a {
      text-decoration: none;
      color: var(--text-muted);
      font-weight: 600;
      margin-left: 1.5rem;
      font-size: 0.85rem;
      transition: 0.2s;
    }

    .nav-links a:hover {
      color: var(--primary);
    }

    /* Sohbet Alanı */
    main {
      flex: 1;
      display: flex;
      justify-content: center;
      padding: 1.5rem;
      overflow: hidden;
      /* Dışarı taşmayı engelle */
    }

    .chat-wrapper {
      width: 100%;
      max-width: 1000px;
      background: rgba(255, 255, 255, 0.7);
      backdrop-filter: blur(10px);
      border-radius: var(--radius-lg);
      border: 1px solid rgba(255, 255, 255, 0.5);
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05);
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    /* Mesaj Penceresi */
    #chatBox {
      flex: 1;
      padding: 2rem;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 1.25rem;
      /* Mesaj balonları arası boşluk */
      background-image: radial-gradient(#cbd5e1 0.5px, transparent 0.5px);
      background-size: 30px 30px;
    }

    /* Mesaj Balonları (WhatsApp Stili) */
    .message {
      max-width: 70%;
      padding: 1rem 1.25rem;
      font-size: 0.95rem;
      line-height: 1.5;
      position: relative;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    /* KULLANICI MESAJI (SAĞA) */
    .user-message {
      align-self: flex-end;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: white;
      border-radius: 20px 20px 4px 20px;
      /* Sağ alt köşe sivri */
    }

    /* AI MESAJI (SOLA) */
    .ai-message {
      align-self: flex-start;
      background: white;
      color: var(--text-dark);
      border: 1px solid var(--border);
      border-radius: 20px 20px 20px 4px;
      /* Sol alt köşe sivri */
    }

    .welcome-msg {
      align-self: center;
      background: rgba(226, 232, 240, 0.8);
      color: var(--text-muted);
      font-size: 0.75rem;
      font-weight: 600;
      padding: 8px 16px;
      border-radius: 100px;
      margin-bottom: 1rem;
    }

    /* Mesaj Yazma Çubuğu */
    .input-area {
      padding: 1.5rem 2rem;
      background: white;
      border-top: 1px solid var(--border);
      display: flex;
      gap: 1rem;
      align-items: center;
    }

    #msg {
      flex: 1;
      padding: 0.85rem 1.25rem;
      border: 1px solid var(--border);
      border-radius: 100px;
      outline: none;
      font-size: 0.95rem;
      background: var(--bg-main);
      transition: all 0.3s;
    }

    #msg:focus {
      border-color: var(--primary);
      background: white;
      box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    button#sendBtn {
      background: var(--primary);
      color: white;
      border: none;
      width: 48px;
      height: 48px;
      border-radius: 50%;
      /* Yuvarlak buton */
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s;
      box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }

    button#sendBtn:hover {
      transform: scale(1.1) rotate(-5deg);
      background: var(--primary-dark);
    }

    /* "Yazıyor..." Durumu */
    #status {
      padding: 0.5rem 2.5rem;
      font-size: 0.8rem;
      font-style: italic;
      color: var(--primary);
      height: 20px;
    }
  </style>
</head>

<body>

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
    // PWA ve JS entegrasyonu buraya...
    if ("serviceWorker" in navigator) {
      navigator.serviceWorker.register("pwa/service-worker.js");
    }
  </script>
</body>

</html>