
<?php
require_once __DIR__ . "/inc/init.php";
require_once __DIR__ . "/inc/user_repo.php";

require_login();

$user = current_user();

$dbUser = find_user_by_id((int)$user["id"]);
if ($dbUser) {
  $_SESSION["user"]["level"] = $dbUser["language_level"];
  $_SESSION["user"]["avatar"] = $dbUser["avatar_path"];
  $user = current_user();
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard - BeeFluent</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="page-dashboard">

  <header>
    <a href="dashboard.php" class="logo">BeeFluent</a>
    <a href="logout.php" class="btn-logout">Log out</a>
  </header>

  <div class="container">
    <div class="welcome-section">
      <div class="user-badge">✨ Your Learning Space</div>
      <h2 style="margin-top: 1rem;">Welcome, <?php echo h($user["name"]); ?> 👋</h2>
      <p style="color: var(--text-muted); font-weight: 500;">Ready to learn something new today?</p>
    </div>

    <div class="grid">
      <a href="chat.php" class="card">
        <div class="card-icon">💬</div>
        <h3>AI Tutor</h3>
        <p>Practice English in a natural chat and get instant feedback.</p>
      </a>

      <a href="placement.php" class="card">
        <div class="card-icon">📝</div>
        <h3>Placement Test</h3>
        <p>Find your current level with AI-powered questions.</p>
      </a>

      <a href="profile.php" class="card">
        <div class="card-icon">👤</div>
        <h3>My Profile</h3>
        <p>Manage your preferences and view your learning info.</p>
      </a>

      <a href="history.php" class="card">
        <div class="card-icon">🕒</div>
        <h3>Chat History</h3>
        <p>Review past conversations and learn from your mistakes.</p>
      </a>
    </div>

    <?php if (($_SESSION["user"]["role"] ?? "user") === "admin"): ?>
      <div class="admin-link">
        <a href="admin/index.php" class="btn-admin">
          <span>⚙️ Admin Panel</span>
        </a>
      </div>
    <?php endif; ?>
  </div>

</body>

</html>
