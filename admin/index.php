
<?php
require_once __DIR__ . "/../inc/admin_guard.php";
require_admin();
?>
<!doctype html>
<html lang="tr">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Yönetim Paneli - AI Tutor</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #6366f1;
      --primary-dark: #0f172a;
      /* Admin için daha koyu bir lacivert */
      --bg-main: #f1f5f9;
      --white: #ffffff;
      --text-dark: #1e293b;
      --text-muted: #64748b;
      --border: #e2e8f0;
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
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar Tasarımı */
    aside {
      width: 260px;
      background-color: var(--primary-dark);
      color: white;
      padding: 2rem 1.5rem;
      display: flex;
      flex-direction: column;
      position: fixed;
      height: 100vh;
    }

    aside h2 {
      font-size: 1.25rem;
      font-weight: 800;
      margin-bottom: 2.5rem;
      color: var(--primary);
      letter-spacing: -0.5px;
    }

    .side-nav {
      list-style: none;
      flex-grow: 1;
    }

    .side-nav li {
      margin-bottom: 0.5rem;
    }

    .side-nav a {
      text-decoration: none;
      color: #94a3b8;
      padding: 0.75rem 1rem;
      display: block;
      border-radius: 8px;
      font-weight: 500;
      transition: all 0.2s;
    }

    .side-nav a:hover,
    .side-nav a.active {
      background: rgba(255, 255, 255, 0.05);
      color: white;
    }

    /* Ana İçerik Alanı */
    main {
      flex: 1;
      margin-left: 260px;
      padding: 3rem;
    }

    .header-section {
      margin-bottom: 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .header-section h1 {
      font-size: 1.75rem;
      font-weight: 700;
    }

    .admin-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
    }

    .admin-card {
      background: var(--white);
      padding: 2rem;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      text-decoration: none;
      color: inherit;
      transition: transform 0.2s;
    }

    .admin-card:hover {
      transform: translateY(-5px);
      border-color: var(--primary);
    }

    .icon-box {
      width: 50px;
      height: 50px;
      background: #f1f5f9;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin-bottom: 1.25rem;
      color: var(--primary);
    }

    .admin-card h3 {
      font-size: 1.1rem;
      margin-bottom: 0.5rem;
    }

    .admin-card p {
      font-size: 0.85rem;
      color: var(--text-muted);
    }

    .logout-link {
      margin-top: auto;
      color: #ef4444;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 600;
      padding: 1rem;
    }
  </style>
</head>

<body>

  <aside>
    <h2>AI TUTOR ADMIN</h2>
    <ul class="side-nav">
      <li><a href="index.php" class="active">🏠 Dashboard</a></li>
      <li><a href="users.php">👥 Kullanıcı Yönetimi</a></li>
      <li><a href="../dashboard.php">🌐 Siteye Dön</a></li>
    </ul>
    <a href="../logout.php" class="logout-link">Çıkış Yap</a>
  </aside>

  <main>
    <div class="header-section">
      <h1>Yönetim Paneli</h1>
    </div>

    <div class="admin-grid">
      <a href="users.php" class="admin-card">
        <div class="icon-box">👥</div>
        <h3>Kullanıcıları Yönet</h3>
        <p>Kullanıcı listesini gör, seviyelerini incele ve düzenleme yap.</p>
      </a>

      <div class="admin-card">
        <div class="icon-box">📊</div>
        <h3>Genel İstatistikler</h3>
        <p>Toplam sohbet sayısı ve aktif öğrenci verilerini yakında buradan izleyebilirsin.</p>
      </div>

      <div class="admin-card">
        <div class="icon-box">⚙️</div>
        <h3>Sistem Ayarları</h3>
        <p>AI Tutor parametrelerini ve genel site ayarlarını yapılandır.</p>
      </div>
    </div>
  </main>

</body>

</html>
