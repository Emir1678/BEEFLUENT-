<?php
require_once __DIR__ . "/../inc/admin_guard.php";
require_once __DIR__ . "/../inc/db.php";
require_admin();

$userId = (int)($_GET["user_id"] ?? 0);
if ($userId <= 0) {
  echo "Kullanıcı ID eksik.";
  exit;
}

// Kullanıcı bilgilerini al
$stmtU = $conn->prepare("SELECT id, name, email FROM users WHERE id=?");
$stmtU->bind_param("i", $userId);
$stmtU->execute();
$target = $stmtU->get_result()->fetch_assoc();
if (!$target) {
  echo "Kullanıcı bulunamadı.";
  exit;
}

$attempts = [];
$tableMissing = false;

try {
  $stmt = $conn->prepare(
    "SELECT percentage, level, created_at
         FROM test_results
         WHERE user_id=?
         ORDER BY id DESC
         LIMIT 50"
  );
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) $attempts[] = $r;
} catch (Throwable $e) {
  $tableMissing = true;
}
?>
<!doctype html>
<html lang="tr">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Test Geçmişi - Admin</title>
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
      margin-bottom: 2rem;
    }

    .header-box h1 {
      font-size: 1.75rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }

    .user-info-bar {
      color: var(--text-muted);
      font-size: 0.9rem;
    }

    /* Table Style */
    .table-container {
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      overflow: hidden;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th {
      background: #f8fafc;
      text-align: left;
      padding: 1rem;
      border-bottom: 1px solid var(--border);
      color: var(--text-muted);
      font-weight: 600;
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    td {
      padding: 1rem;
      border-bottom: 1px solid var(--border);
      font-size: 0.9rem;
    }

    tr:last-child td {
      border-bottom: none;
    }

    /* Level Badges */
    .badge {
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .badge-beginner {
      background: #dcfce7;
      color: #166534;
    }

    .badge-intermediate {
      background: #fef9c3;
      color: #854d0e;
    }

    .badge-advanced {
      background: #e0e7ff;
      color: #3730a3;
    }

    .score-text {
      font-weight: 700;
      color: var(--primary);
    }

    .alert {
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
      font-size: 0.9rem;
      border: 1px solid;
    }

    .alert-error {
      background: #fef2f2;
      color: #ef4444;
      border-color: #fee2e2;
    }

    .empty-state {
      text-align: center;
      padding: 3rem;
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
      <h1>Sınav Geçmişi</h1>
      <div class="user-info-bar">
        Kullanıcı: <strong><?php echo h($target["name"]); ?></strong> (<?php echo h($target["email"]); ?>)
      </div>
    </div>

    <?php if ($tableMissing): ?>
      <div class="alert alert-error">
        <strong>Hata:</strong> test_results tablosu bulunamadı. Lütfen phpMyAdmin üzerinden tabloyu oluşturun.
      </div>
    <?php elseif (!$attempts): ?>
      <div class="table-container">
        <div class="empty-state">Bu kullanıcı henüz seviye belirleme testi yapmamış.</div>
      </div>
    <?php else: ?>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Tarih / Saat</th>
              <th>Başarı Oranı</th>
              <th>Atanan Seviye</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($attempts as $a): ?>
              <tr>
                <td><?php echo date("d.m.Y - H:i", strtotime($a["created_at"])); ?></td>
                <td class="score-text">%<?php echo h((string)$a["percentage"]); ?></td>
                <td>
                  <?php
                  $lvl = strtolower($a["level"]);
                  $badgeClass = "badge-beginner";
                  if (strpos($lvl, 'intermediate') !== false) $badgeClass = "badge-intermediate";
                  if (strpos($lvl, 'advanced') !== false) $badgeClass = "badge-advanced";
                  ?>
                  <span class="badge <?php echo $badgeClass; ?>">
                    <?php echo h((string)$a["level"]); ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <div style="margin-top: 2rem;">
      <a href="users.php" style="color: var(--primary); text-decoration: none; font-weight: 600; font-size: 0.9rem;">← Kullanıcı Listesine Dön</a>
    </div>
  </main>

</body>

</html>
