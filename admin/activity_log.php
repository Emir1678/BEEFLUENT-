<?php
// admin/activity_log.php
declare(strict_types=1);

require_once __DIR__ . "/../inc/admin_guard.php";
require_once __DIR__ . "/../inc/db.php";
require_once __DIR__ . "/../inc/activity_repo.php";

require_admin();

// Try to log that admin viewed this page (will safely do nothing if table is missing)
if (function_exists("activity_log_add")) {
  activity_log_add("view_activity_log", null, ["page" => "admin/activity_log.php"]);
}

$rows = [];
$tableExists = false;
$currentDb = "";
$columns = [];
$sqlError = "";

// 1) What DB are we connected to?
try {
  $r = $conn->query("SELECT DATABASE() AS db");
  $currentDb = (string)(($r && ($row = $r->fetch_assoc())) ? ($row["db"] ?? "") : "");
} catch (Throwable $e) {
  $sqlError = "Could not read current database: " . $e->getMessage();
}

// 2) Does the table exist in THIS DB?
try {
  $stmt = $conn->prepare("SHOW TABLES LIKE 'activity_log'");
  $stmt->execute();
  $res = $stmt->get_result();
  $tableExists = (bool)$res->fetch_row();
} catch (Throwable $e) {
  $sqlError = "Could not check activity_log table: " . $e->getMessage();
  $tableExists = false;
}

// 3) If exists, read its real columns, then query safely
if ($tableExists) {
  try {
    // Read columns from information_schema
    $stmt = $conn->prepare("
      SELECT COLUMN_NAME
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'activity_log'
    ");
    $stmt->execute();
    $res = $stmt->get_result();
    while ($res && ($r = $res->fetch_assoc())) {
      $columns[] = (string)$r["COLUMN_NAME"];
    }

    $has = fn(string $c): bool => in_array($c, $columns, true);

    // Build SELECT with only existing columns
    $select = [
      "al.id",
      ($has("action") ? "al.action" : "NULL AS action"),
      ($has("created_at") ? "al.created_at" : "NULL AS created_at"),
      ($has("admin_id") ? "al.admin_id" : "NULL AS admin_id"),
      ($has("target_user_id") ? "al.target_user_id" : "NULL AS target_user_id"),
      ($has("meta_json") ? "al.meta_json" : "NULL AS meta_json"),
      ($has("ip_address") ? "al.ip_address" : "NULL AS ip_address"),
      ($has("user_agent") ? "al.user_agent" : "NULL AS user_agent"),
      "a.name AS admin_name",
      "t.name AS target_name"
    ];

    $sql = "
      SELECT " . implode(",\n      ", $select) . "
      FROM activity_log al
      LEFT JOIN users a ON a.id = " . ($has("admin_id") ? "al.admin_id" : "NULL") . "
      LEFT JOIN users t ON t.id = " . ($has("target_user_id") ? "al.target_user_id" : "NULL") . "
      ORDER BY al.id DESC
      LIMIT 200
    ";

    $res = $conn->query($sql);
    while ($res && ($r = $res->fetch_assoc())) $rows[] = $r;

  } catch (Throwable $e) {
    $sqlError = $e->getMessage();
  }
}

function _fmt_dt(?string $dt): string {
  if (!$dt) return "—";
  $ts = strtotime($dt);
  if (!$ts) return $dt;
  return date("d.m.Y H:i", $ts);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Activity Log - BeeFluent Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --primary:#6366f1; --primary-dark:#0f172a; --bg:#f1f5f9; --white:#fff;
      --text:#1e293b; --muted:#64748b; --border:#e2e8f0; --shadow:0 4px 6px -1px rgb(0 0 0 / 0.1);
      --radius:12px;
    }
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh}
    aside{width:260px;background:var(--primary-dark);color:#fff;padding:2rem 1.5rem;position:fixed;height:100vh}
    aside h2{color:var(--primary);margin-bottom:2rem;font-weight:800;font-size:1.2rem}
    .side-nav{list-style:none}
    .side-nav a{text-decoration:none;color:#94a3b8;padding:.75rem 1rem;display:block;border-radius:8px;transition:.2s;font-size:.9rem}
    .side-nav a:hover,.side-nav a.active{background:rgba(255,255,255,.05);color:#fff}
    main{flex:1;margin-left:260px;padding:3rem}
    .header{display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:1.25rem}
    .header h1{font-size:1.75rem;font-weight:900}
    .muted{color:var(--muted);font-size:.9rem}
    .card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden}
    .alert{padding:1rem;border-radius:10px;margin-bottom:1rem;border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;font-weight:700}
    .ok{border:1px solid #bbf7d0;background:#f0fdf4;color:#166534}
    table{width:100%;border-collapse:collapse;font-size:.9rem}
    th{background:#f8fafc;text-align:left;padding:1rem;border-bottom:1px solid var(--border);color:var(--muted);font-weight:700;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em}
    td{padding:1rem;border-bottom:1px solid var(--border);vertical-align:top}
    tr:last-child td{border-bottom:none}
    .badge{display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;background:#eef2ff;color:#3730a3;font-weight:800;font-size:.75rem;border:1px solid #e0e7ff}
    .meta{color:var(--muted);font-size:.8rem;margin-top:6px;word-break:break-word}
    code{background:#f1f5f9;padding:2px 6px;border-radius:6px}
    .empty{padding:2.5rem;text-align:center;color:var(--muted);font-style:italic}
  </style>
</head>
<body>
  <aside>
    <h2>BEEFLUENT ADMIN</h2>
    <nav>
      <ul class="side-nav">
        <li><a href="index.php">🏠 Dashboard</a></li>
        <li><a href="users.php">👥 User Management</a></li>
        <li><a href="stats.php">📊 Overall Statistics</a></li>
        <li><a href="activity_log.php" class="active">🧾 Activity Log</a></li>
        <li><a href="../dashboard.php">🌐 Back to Site</a></li>
      </ul>
    </nav>
  </aside>

  <main>
    <div class="header">
      <div>
        <h1>Activity Log</h1>
        <div class="muted">Latest 200 actions (admin activity)</div>
      </div>
      <div class="muted">
        Current DB from PHP: <strong><?php echo h($currentDb ?: "—"); ?></strong>
      </div>
    </div>

    <?php if (!$tableExists): ?>
      <div class="alert">
        Table <code>activity_log</code> was NOT found in the DB PHP is connected to.
        <div class="meta">If you are sure it exists, then PHP is connected to a different DB or the name is different.</div>
      </div>
    <?php elseif ($sqlError): ?>
      <div class="alert">
        SQL error while reading the activity log:
        <div class="meta"><code><?php echo h($sqlError); ?></code></div>
        <div class="meta">Detected columns: <code><?php echo h(implode(", ", $columns)); ?></code></div>
      </div>
    <?php else: ?>
      <div class="alert ok">
        activity_log table found ✅
        <div class="meta">Detected columns: <code><?php echo h(implode(", ", $columns)); ?></code></div>
      </div>
    <?php endif; ?>

    <div class="card">
      <?php if (!$rows): ?>
        <div class="empty">No activity yet.</div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Time</th>
              <th>Action</th>
              <th>Admin</th>
              <th>Target User</th>
              <th>Meta</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td>
                  <strong><?php echo h(_fmt_dt((string)($r["created_at"] ?? ""))); ?></strong>
                  <div class="meta">IP: <?php echo h((string)($r["ip_address"] ?? "—")); ?></div>
                </td>
                <td><span class="badge"><?php echo h((string)($r["action"] ?? "—")); ?></span></td>
                <td>
                  <?php echo h((string)($r["admin_name"] ?? "Unknown")); ?>
                  <div class="meta">#<?php echo (int)($r["admin_id"] ?? 0); ?></div>
                </td>
                <td>
                  <?php if (!empty($r["target_user_id"])): ?>
                    <?php echo h((string)($r["target_name"] ?? "")); ?>
                    <div class="meta">#<?php echo (int)$r["target_user_id"]; ?></div>
                  <?php else: ?>
                    <span class="meta">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php
                    $meta = (string)($r["meta_json"] ?? "");
                    if ($meta !== "") {
                      echo "<code>" . h(mb_strimwidth($meta, 0, 160, "...", "UTF-8")) . "</code>";
                    } else {
                      echo "<span class='meta'>—</span>";
                    }
                  ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>
