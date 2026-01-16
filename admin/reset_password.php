<?php
require_once __DIR__ . "/../inc/admin_guard.php";
require_once __DIR__ . "/../inc/user_repo.php";
require_once __DIR__ . "/../inc/db.php";
require_once __DIR__ . "/../inc/activity_repo.php";

require_admin();

$userId = (int)($_GET["user_id"] ?? 0);
if ($userId <= 0) {
  echo "Missing user_id";
  exit;
}

// Load target user
$stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$target = $stmt->get_result()->fetch_assoc();
if (!$target) {
  echo "User not found.";
  exit;
}

$error = "";
$ok = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $p1 = (string)($_POST["pass1"] ?? "");
  $p2 = (string)($_POST["pass2"] ?? "");

  try {
    if ($p1 === "" || $p2 === "") throw new RuntimeException("Please fill both fields.");
    if (strlen($p1) < 6) throw new RuntimeException("Password must be at least 6 characters.");
    if ($p1 !== $p2) throw new RuntimeException("Passwords do not match.");

    admin_set_user_password($userId, $p1);
    activity_log_add("reset_user_password", $userId, ["by_admin" => true]);
    $ok = "Password updated successfully.";
  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reset Password - Admin</title>
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
      --success: #16a34a;
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

    /* Main */
    main {
      flex: 1;
      margin-left: 260px;
      padding: 3rem;
    }

    .header-box {
      margin-bottom: 1.5rem;
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

    .card {
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      padding: 1.5rem;
      max-width: 520px;
    }

    .field {
      margin-top: 12px;
    }

    .field label {
      display: block;
      font-weight: 600;
      margin-bottom: 6px;
      font-size: 0.9rem;
    }

    .field input {
      width: 100%;
      padding: 10px 12px;
      border-radius: 10px;
      border: 1px solid var(--border);
      outline: none;
      font-size: 0.95rem;
    }

    .actions {
      display: flex;
      gap: 10px;
      margin-top: 14px;
      align-items: center;
    }

    .btn {
      border: none;
      border-radius: 10px;
      padding: 10px 14px;
      font-weight: 700;
      cursor: pointer;
      font-size: 0.9rem;
      transition: 0.2s;
    }

    .btn-primary {
      background: var(--primary);
      color: white;
    }

    .btn-primary:hover {
      filter: brightness(0.95);
    }

    .btn-secondary {
      background: #f1f5f9;
      color: #0f172a;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .btn-secondary:hover {
      filter: brightness(0.98);
    }

    .alert {
      padding: 0.9rem 1rem;
      border-radius: 10px;
      margin: 0 0 1rem 0;
      font-size: 0.9rem;
      border: 1px solid;
      max-width: 520px;
    }

    .alert-error {
      background: #fef2f2;
      color: #b91c1c;
      border-color: #fee2e2;
    }

    .alert-success {
      background: #f0fdf4;
      color: var(--success);
      border-color: #dcfce7;
    }

    .footer-links {
      margin-top: 1.25rem;
      max-width: 520px;
      font-size: 0.9rem;
    }

    .footer-links a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 600;
    }

    .footer-links a:hover {
      text-decoration: underline;
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
        <li><a href="../dashboard.php">🌐 Back to Site</a></li>
      </ul>
    </nav>
  </aside>

  <main>
    <div class="header-box">
      <h1>Reset Password</h1>
      <div class="user-info-bar">
        User: <strong><?php echo h($target["name"]); ?></strong> (<?php echo h($target["email"]); ?>)
      </div>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?php echo h($error); ?></div>
    <?php endif; ?>

    <?php if ($ok): ?>
      <div class="alert alert-success"><?php echo h($ok); ?></div>
    <?php endif; ?>

    <div class="card">
      <form method="post" onsubmit="return confirm('Reset password for this user?');">
        <div class="field">
          <label for="pass1">New password</label>
          <input id="pass1" type="password" name="pass1" autocomplete="new-password" required>
        </div>

        <div class="field">
          <label for="pass2">Confirm password</label>
          <input id="pass2" type="password" name="pass2" autocomplete="new-password" required>
        </div>

        <div class="actions">
          <button class="btn btn-primary" type="submit">Update Password</button>
          <a class="btn btn-secondary" href="users.php">Cancel</a>
        </div>
      </form>
    </div>

    <div class="footer-links">
      <a href="users.php">← Back to users</a> &nbsp;|&nbsp; <a href="index.php">Admin Home</a>
    </div>
  </main>

</body>
</html>


