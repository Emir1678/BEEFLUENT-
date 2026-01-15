
<?php
require_once __DIR__ . "/../inc/admin_guard.php";
require_once __DIR__ . "/../inc/user_repo.php";
require_once __DIR__ . "/../inc/chat_repo.php";
require_once __DIR__ . "/../inc/db.php";
require_admin();

$ok = "";
$error = "";
$allowedLevels = ["Beginner", "Intermediate", "Advanced"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $action = (string)($_POST["action"] ?? "");
  $userId = (int)($_POST["user_id"] ?? 0);

  try {
    if ($userId <= 0) throw new RuntimeException("Geçersiz kullanıcı ID.");

    if ($action === "set_level") {
      $level = (string)($_POST["level"] ?? "");
      if (!in_array($level, $allowedLevels, true)) throw new RuntimeException("Geçersiz seviye.");
      update_user_level($userId, $level);
      $ok = "Kullanıcı seviyesi güncellendi.";
    } elseif ($action === "clear_chats") {
      chat_clear_user($userId);
      $ok = "Sohbet geçmişi temizlendi.";
    }
  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
  // Formun tekrar gönderilmesini engellemek için redirect yerine mesajları session'da taşıyabilirsin, 
  // ama basitlik için şimdilik akışı bozmayalım.
}

$stmt = $conn->prepare("SELECT id, name, email, role, language_level, created_at FROM users ORDER BY id ASC");
$stmt->execute();
$res = $stmt->get_result();
$users = [];
while ($row = $res->fetch_assoc()) $users[] = $row;
?>
<!doctype html>
<html lang="tr">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kullanıcı Yönetimi - AI Tutor</title>
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

    /* Sidebar Sidebar (Aynı Tema) */
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
    }

    .side-nav a:hover,
    .side-nav a.active {
      background: rgba(255, 255, 255, 0.05);
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
      align-items: center;
      margin-bottom: 2rem;
    }

    /* Table Design */
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
      font-size: 0.9rem;
    }

    th {
      background: #f8fafc;
      text-align: left;
      padding: 1rem;
      border-bottom: 1px solid var(--border);
      color: var(--text-muted);
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.05em;
    }

    td {
      padding: 1rem;
      border-bottom: 1px solid var(--border);
      vertical-align: middle;
    }

    tr:last-child td {
      border-bottom: none;
    }

    /* Badges */
    .badge {
      padding: 4px 8px;
      border-radius: 6px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .badge-role {
      background: #f1f5f9;
      color: var(--text-dark);
    }

    .badge-level {
      background: #e0e7ff;
      color: var(--primary);
    }

    /* Form Elements */
    select {
      padding: 5px 10px;
      border-radius: 6px;
      border: 1px solid var(--border);
      outline: none;
    }

    button {
      padding: 6px 12px;
      border-radius: 6px;
      border: none;
      cursor: pointer;
      font-weight: 600;
      font-size: 0.8rem;
      transition: 0.2s;
    }

    .btn-update {
      background: var(--primary);
      color: white;
      margin-left: 5px;
    }

    .btn-clear {
      background: #fee2e2;
      color: var(--danger);
    }

    .btn-clear:hover {
      background: var(--danger);
      color: white;
    }

    .action-links a {
      text-decoration: none;
      color: var(--primary);
      font-weight: 600;
      font-size: 0.8rem;
      margin-right: 10px;
    }

    .action-links a:hover {
      text-decoration: underline;
    }

    .alert {
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
      font-size: 0.9rem;
    }

    .alert-success {
      background: #f0fdf4;
      color: #16a34a;
      border: 1px solid #dcfce7;
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
      <h1>Kullanıcı Yönetimi</h1>
    </div>

    <?php if ($ok): ?><div class="alert alert-success"><?php echo h($ok); ?></div><?php endif; ?>

    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Kullanıcı Bilgisi</th>
            <th>Rol / Seviye</th>
            <th>Seviye Güncelle</th>
            <th>Yönetim</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><strong>#<?php echo (int)$u["id"]; ?></strong></td>
              <td>
                <div style="font-weight: 700;"><?php echo h($u["name"]); ?></div>
                <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo h($u["email"]); ?></div>
              </td>
              <td>
                <span class="badge badge-role"><?php echo h($u["role"]); ?></span>
                <span class="badge badge-level"><?php echo h((string)($u["language_level"] ?? "N/A")); ?></span>
              </td>
              <td>
                <form method="post" style="display: flex; align-items: center;">
                  <input type="hidden" name="action" value="set_level">
                  <input type="hidden" name="user_id" value="<?php echo (int)$u["id"]; ?>">
                  <select name="level">
                    <?php foreach ($allowedLevels as $lv): ?>
                      <option value="<?php echo h($lv); ?>" <?php echo (($u["language_level"] ?? "") === $lv) ? "selected" : ""; ?>>
                        <?php echo h($lv); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn-update">Güncelle</button>
                </form>
              </td>
              <td>
                <div class="action-links" style="margin-bottom: 8px;">
                  <a href="user_chats.php?user_id=<?php echo (int)$u["id"]; ?>">Sohbetler</a>
                  <a href="user_tests.php?user_id=<?php echo (int)$u["id"]; ?>">Testler</a>
                  <a href="reset_password.php?user_id=<?php echo (int)$u["id"]; ?>">Şifre</a>
                </div>
                <form method="post" onsubmit="return confirm('Tüm geçmiş silinsin mi?');">
                  <input type="hidden" name="action" value="clear_chats">
                  <input type="hidden" name="user_id" value="<?php echo (int)$u["id"]; ?>">
                  <button type="submit" class="btn-clear">Geçmişi Temizle</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>

</body>

</html>
