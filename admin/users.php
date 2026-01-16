<?php
require_once __DIR__ . "/../inc/admin_guard.php";
require_once __DIR__ . "/../inc/user_repo.php";
require_once __DIR__ . "/../inc/chat_repo.php";
require_once __DIR__ . "/../inc/db.php";
require_once __DIR__ . "/../inc/activity_repo.php";
require_admin();

$ok = "";
$error = "";
$allowedLevels = ["Beginner", "Intermediate", "Advanced"];

/**
 * Optional activity log (won't break if table doesn't exist yet).
 * Table we will create later: activity_log
 */
function _activity_log_table_exists(): bool
{
  static $cached = null;
  if ($cached !== null) return $cached;

  global $conn;
  $sql = "
    SELECT COUNT(*) AS c
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'activity_log'
  ";
  $res = $conn->query($sql);
  $row = $res ? $res->fetch_assoc() : null;
  $cached = ((int)($row["c"] ?? 0) > 0);
  return $cached;
}

function activity_log(string $action, ?int $targetUserId = null, array $meta = []): void
{
  if (!_activity_log_table_exists()) return;

  global $conn;

  $adminId = (int)($_SESSION["user"]["id"] ?? 0);
  if ($adminId <= 0) return;

  $metaJson = "";
  if (!empty($meta)) {
    $metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE);
    if (!is_string($metaJson)) $metaJson = "";
  }

  // activity_log schema we will create:
  // id, admin_id, action, target_user_id, meta_json, ip_address, user_agent, created_at
  $ip = (string)($_SERVER["REMOTE_ADDR"] ?? "");
  $ua = (string)($_SERVER["HTTP_USER_AGENT"] ?? "");

  $stmt = $conn->prepare("
    INSERT INTO activity_log (admin_id, action, target_user_id, meta_json, ip_address, user_agent)
    VALUES (?,?,?,?,?,?)
  ");
  $tuid = $targetUserId !== null ? (int)$targetUserId : null;
  $stmt->bind_param("isisss", $adminId, $action, $tuid, $metaJson, $ip, $ua);
  $stmt->execute();
}

/** Fix the reset password filename mismatch safely */
$resetHref = "reset_password.php";
if (!file_exists(__DIR__ . "/reset_password.php") && file_exists(__DIR__ . "/reset_passsword.php")) {
  $resetHref = "reset_passsword.php";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $action = (string)($_POST["action"] ?? "");
  $userId = (int)($_POST["user_id"] ?? 0);

  try {
    if ($userId <= 0) throw new RuntimeException("Invalid user ID.");

    if ($action === "set_level") {
      $level = (string)($_POST["level"] ?? "");
      if (!in_array($level, $allowedLevels, true)) throw new RuntimeException("Invalid level.");

      update_user_level($userId, $level);
  activity_log_add("set_user_level", $userId, ["level" => $level]);
  $ok = "User level updated.";
} elseif ($action === "clear_chats") {
  chat_clear_user($userId);
  activity_log_add("clear_user_chats", $userId, ["scope" => "all"]);
  $ok = "Chat history cleared.";
}
  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}

$stmt = $conn->prepare("SELECT id, name, email, role, language_level, created_at FROM users ORDER BY id ASC");
$stmt->execute();
$res = $stmt->get_result();
$users = [];
while ($row = $res->fetch_assoc()) $users[] = $row;
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>User Management - BeeFluent Admin</title>
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

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--bg-main);
      color: var(--text-dark);
      display: flex;
      min-height: 100vh;
    }

    aside {
      width: 260px;
      background: var(--primary-dark);
      color: white;
      padding: 2rem 1.5rem;
      position: fixed;
      height: 100vh;
      display: flex;
      flex-direction: column;
    }

    aside h2 {
      color: var(--primary);
      margin-bottom: 2rem;
      font-weight: 800;
      letter-spacing: -0.5px;
    }

    .side-nav { list-style: none; }

    .side-nav a {
      text-decoration: none;
      color: #94a3b8;
      padding: 0.75rem 1rem;
      display: block;
      border-radius: 8px;
      transition: 0.2s;
      font-weight: 500;
    }

    .side-nav a:hover,
    .side-nav a.active {
      background: rgba(255, 255, 255, 0.05);
      color: white;
    }

    .logout-link {
      margin-top: auto;
      color: #ef4444;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 700;
      padding: 1rem;
      border-radius: 10px;
      transition: 0.2s;
    }

    .logout-link:hover {
      background: rgba(239, 68, 68, 0.12);
      color: #fecaca;
    }

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

    .header-box h1 {
      font-size: 1.75rem;
      font-weight: 800;
      letter-spacing: -0.5px;
    }

    .table-container {
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      overflow: hidden;
    }

    table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }

    th {
      background: #f8fafc;
      text-align: left;
      padding: 1rem;
      border-bottom: 1px solid var(--border);
      color: var(--text-muted);
      font-weight: 700;
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.05em;
      white-space: nowrap;
    }

    td {
      padding: 1rem;
      border-bottom: 1px solid var(--border);
      vertical-align: middle;
    }

    tr:last-child td { border-bottom: none; }

    .badge {
      padding: 4px 8px;
      border-radius: 6px;
      font-size: 0.75rem;
      font-weight: 700;
      display: inline-block;
      margin-right: 6px;
    }

    .badge-role { background: #f1f5f9; color: var(--text-dark); }
    .badge-level { background: #e0e7ff; color: var(--primary); }

    select {
      padding: 6px 10px;
      border-radius: 8px;
      border: 1px solid var(--border);
      outline: none;
      background: #fff;
    }

    button {
      padding: 7px 12px;
      border-radius: 8px;
      border: none;
      cursor: pointer;
      font-weight: 700;
      font-size: 0.82rem;
      transition: 0.2s;
    }

    .btn-update { background: var(--primary); color: white; margin-left: 8px; }
    .btn-update:hover { filter: brightness(0.95); }

    .btn-clear { background: #fee2e2; color: var(--danger); }
    .btn-clear:hover { background: var(--danger); color: white; }

    .action-links a {
      text-decoration: none;
      color: var(--primary);
      font-weight: 700;
      font-size: 0.82rem;
      margin-right: 10px;
    }

    .action-links a:hover { text-decoration: underline; }

    .alert {
      padding: 1rem;
      border-radius: 10px;
      margin-bottom: 1.5rem;
      font-size: 0.9rem;
      border: 1px solid transparent;
      font-weight: 600;
    }

    .alert-success { background: #f0fdf4; color: #16a34a; border-color: #dcfce7; }
    .alert-error { background: #fef2f2; color: #b91c1c; border-color: #fee2e2; }

    .muted {
      color: var(--text-muted);
      font-size: 0.8rem;
      margin-top: 2px;
    }
  </style>
</head>

<body>

  <aside>
    <h2>BEFLUENT ADMIN</h2>
    <nav>
      <ul class="side-nav">
        <li><a href="index.php">🏠 Dashboard</a></li>
        <li><a href="users.php" class="active">👥 User Management</a></li>
        <li><a href="stats.php">📊 Overall Statistics</a></li>
        <li><a href="activity_log.php">🧾 Activity Log</a></li>
        <li><a href="../dashboard.php">🌐 Back to Site</a></li>
      </ul>
    </nav>
    <a href="../logout.php" class="logout-link">Log out</a>
  </aside>

  <main>
    <div class="header-box">
      <h1>User Management</h1>
    </div>

    <?php if ($ok): ?>
      <div class="alert alert-success"><?php echo h($ok); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-error"><?php echo h($error); ?></div>
    <?php endif; ?>

    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th style="width:90px;">ID</th>
            <th>User</th>
            <th style="width:220px;">Role / Level</th>
            <th style="width:260px;">Update Level</th>
            <th style="width:320px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><strong>#<?php echo (int)$u["id"]; ?></strong></td>

              <td>
                <div style="font-weight: 800;"><?php echo h($u["name"]); ?></div>
                <div class="muted"><?php echo h($u["email"]); ?></div>
                <div class="muted">
                  Joined: <?php echo h(date("d M Y", strtotime((string)$u["created_at"]))); ?>
                </div>
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
                  <button type="submit" class="btn-update">Update</button>
                </form>
              </td>

              <td>
                <div class="action-links" style="margin-bottom: 10px;">
                  <a href="user_chats.php?user_id=<?php echo (int)$u["id"]; ?>">Chats</a>
                  <a href="user_tests.php?user_id=<?php echo (int)$u["id"]; ?>">Tests</a>
                  <a href="<?php echo h($resetHref); ?>?user_id=<?php echo (int)$u["id"]; ?>">Password</a>
                </div>

                <form method="post" onsubmit="return confirm('Clear all chat history for this user?');">
                  <input type="hidden" name="action" value="clear_chats">
                  <input type="hidden" name="user_id" value="<?php echo (int)$u["id"]; ?>">
                  <button type="submit" class="btn-clear">Clear History</button>
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

