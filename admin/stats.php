<?php
// admin/stats.php
declare(strict_types=1);

require_once __DIR__ . "/../inc/admin_guard.php";
require_once __DIR__ . "/../inc/db.php";
require_once __DIR__ . "/../inc/activity_repo.php";

require_admin();

// Optional: log page view
activity_log_add("view_stats", null, ["page" => "admin/stats.php"]);

function q_int(string $sql): int {
  global $conn;
  $res = $conn->query($sql);
  $row = $res ? $res->fetch_row() : null;
  return (int)($row[0] ?? 0);
}

function q_float(string $sql): float {
  global $conn;
  $res = $conn->query($sql);
  $row = $res ? $res->fetch_row() : null;
  return (float)($row[0] ?? 0);
}

$totalUsers = q_int("SELECT COUNT(*) FROM users");
$totalAdmins = q_int("SELECT COUNT(*) FROM users WHERE role='admin'");
$totalChats = q_int("SELECT COUNT(*) FROM chats");

$chats24h = q_int("SELECT COUNT(*) FROM chats WHERE created_at >= (NOW() - INTERVAL 1 DAY)");
$activeUsers7d = q_int("SELECT COUNT(DISTINCT user_id) FROM chats WHERE created_at >= (NOW() - INTERVAL 7 DAY)");

$testsTotal = q_int("SELECT COUNT(*) FROM test_results");
$tests24h = q_int("SELECT COUNT(*) FROM test_results WHERE created_at >= (NOW() - INTERVAL 1 DAY)");
$avgScore = q_float("SELECT AVG(percentage) FROM test_results");

$hasSessionCol = false;
try {
  $c = q_int("
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME='chats'
      AND COLUMN_NAME='session_id'
  ");
  $hasSessionCol = $c > 0;
} catch (Throwable $e) {}

$totalSessions = 0;
if ($hasSessionCol) {
  $totalSessions = q_int("SELECT COUNT(DISTINCT session_id) FROM chats WHERE session_id IS NOT NULL AND session_id<>''");
}

$latestChatAt = "";
try {
  $res = $conn->query("SELECT MAX(created_at) AS last_at FROM chats");
  $r = $res ? $res->fetch_assoc() : null;
  $latestChatAt = (string)($r["last_at"] ?? "");
} catch (Throwable $e) {}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Overall Statistics - BeeFluent Admin</title>
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
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', sans-serif; background: var(--bg-main); color: var(--text-dark); display:flex; min-height:100vh; }
    aside {
      width: 260px; background: var(--primary-dark); color:#fff;
      padding: 2rem 1.5rem; position: fixed; height: 100vh;
    }
    aside h2 { color: var(--primary); margin-bottom: 2rem; font-weight: 800; font-size: 1.2rem; }
    .side-nav { list-style:none; }
    .side-nav a {
      text-decoration:none; color:#94a3b8; padding:0.75rem 1rem; display:block;
      border-radius:8px; transition:0.2s; font-size:0.9rem;
    }
    .side-nav a:hover, .side-nav a.active { background: rgba(255,255,255,0.05); color:#fff; }
    main { flex:1; margin-left:260px; padding:3rem; }
    .header {
      display:flex; justify-content:space-between; align-items:flex-end; gap: 12px; flex-wrap:wrap;
      margin-bottom: 1.5rem;
    }
    .header h1 { font-size: 1.75rem; font-weight: 800; }
    .muted { color: var(--text-muted); font-size: 0.9rem; }
    .grid {
      display:grid; gap: 1rem;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      margin-top: 1rem;
    }
    .card {
      background: var(--white); border:1px solid var(--border); border-radius: var(--radius);
      box-shadow: var(--shadow); padding: 1.25rem;
    }
    .kpi { font-size: 2rem; font-weight: 900; letter-spacing: -0.03em; margin-top: 6px; }
    .label { color: var(--text-muted); font-weight: 700; font-size: 0.85rem; }
    .pill {
      display:inline-flex; align-items:center; gap:8px;
      background:#eef2ff; color:#3730a3;
      padding:6px 10px; border-radius: 999px;
      font-weight: 800; font-size: 0.8rem;
      border: 1px solid #e0e7ff;
    }
    .actions { margin-top: 1.25rem; display:flex; gap:10px; flex-wrap:wrap; }
    .btn {
      display:inline-flex; align-items:center; justify-content:center;
      padding: 10px 14px; border-radius: 10px;
      border: 1px solid var(--border);
      text-decoration:none; font-weight: 800; font-size: 0.9rem;
      background: #fff; color: var(--text-dark);
      transition: 0.2s;
    }
    .btn:hover { transform: translateY(-1px); box-shadow: var(--shadow); }
    .btn-primary {
      background: var(--primary); color:#fff; border-color: transparent;
    }
    .btn-primary:hover { filter: brightness(0.95); }
  </style>
</head>
<body>

  <aside>
    <h2>BEEFLUENT ADMIN</h2>
    <nav>
      <ul class="side-nav">
        <li><a href="index.php">🏠 Dashboard</a></li>
        <li><a href="users.php">👥 User Management</a></li>
        <li><a href="stats.php" class="active">📊 Overall Statistics</a></li>
        <li><a href="activity_log.php">🧾 Activity Log</a></li>
        <li><a href="../dashboard.php">🌐 Back to Site</a></li>
      </ul>
    </nav>
  </aside>

  <main>
    <div class="header">
      <div>
        <h1>Overall Statistics</h1>
        <div class="muted">
          <?php if ($latestChatAt): ?>
            Last chat message: <strong><?php echo h(date("d.m.Y H:i", strtotime($latestChatAt))); ?></strong>
          <?php else: ?>
            No chat data yet.
          <?php endif; ?>
        </div>
      </div>
      <span class="pill">Live from database</span>
    </div>

    <div class="grid">
      <div class="card">
        <div class="label">Total Users</div>
        <div class="kpi"><?php echo (int)$totalUsers; ?></div>
        <div class="muted">Admins: <?php echo (int)$totalAdmins; ?></div>
      </div>

      <div class="card">
        <div class="label">Total Chat Messages</div>
        <div class="kpi"><?php echo (int)$totalChats; ?></div>
        <div class="muted">Last 24h: <?php echo (int)$chats24h; ?></div>
      </div>

      <div class="card">
        <div class="label">Active Users (7 days)</div>
        <div class="kpi"><?php echo (int)$activeUsers7d; ?></div>
        <div class="muted">Based on chat activity</div>
      </div>

      <div class="card">
        <div class="label">Placement Tests</div>
        <div class="kpi"><?php echo (int)$testsTotal; ?></div>
        <div class="muted">Last 24h: <?php echo (int)$tests24h; ?></div>
      </div>

      <div class="card">
        <div class="label">Average Test Score</div>
        <div class="kpi"><?php echo $testsTotal ? (int)round($avgScore) : 0; ?>%</div>
        <div class="muted"><?php echo $testsTotal ? "From all attempts" : "No attempts yet"; ?></div>
      </div>

      <div class="card">
        <div class="label">Total Sessions</div>
        <div class="kpi"><?php echo (int)$totalSessions; ?></div>
        <div class="muted">
          <?php echo $hasSessionCol ? "Distinct chat sessions" : "Add chats.session_id to enable"; ?>
        </div>
      </div>
    </div>

    <div class="actions">
      <a class="btn btn-primary" href="activity_log.php">Open Activity Log</a>
      <a class="btn" href="index.php">Back to Admin Dashboard</a>
    </div>

  </main>
</body>
</html>
