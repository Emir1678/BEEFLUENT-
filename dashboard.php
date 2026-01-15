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
  <title>Dashboard - AI Tutor</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #6366f1;
      --primary-light: #818cf8;
      --secondary: #a855f7;
      --bg-dark: #0f172a;
      --bg-main: #f8fafc;
      --white: #ffffff;
      --text-dark: #1e293b;
      --text-muted: #64748b;
      --radius-lg: 24px;
      --radius-md: 16px;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--bg-main);
      background-image:
        radial-gradient(circle at 0% 0%, rgba(99, 102, 241, 0.05) 0%, transparent 30%),
        radial-gradient(circle at 100% 100%, rgba(168, 85, 247, 0.05) 0%, transparent 30%);
      color: var(--text-dark);
      line-height: 1.6;
      min-height: 100vh;
    }

    /* Navbar */
    header {
      background: rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(12px);
      padding: 1.25rem 2rem;
      position: sticky;
      top: 0;
      z-index: 100;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid rgba(226, 232, 240, 0.8);
    }

    .logo {
      font-size: 1.5rem;
      font-weight: 800;
      text-decoration: none;
      color: #6366f1;
    }

    @supports (background-clip: text) or (-webkit-background-clip: text) {
      .logo {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        -webkit-text-fill-color: transparent;
      }
    }

    /* Main Container */
    .container {
      max-width: 1100px;
      margin: 4rem auto;
      padding: 0 1.5rem;
    }

    .welcome-section {
      margin-bottom: 3.5rem;
      text-align: center;
    }

    .welcome-section h2 {
      font-size: 2.5rem;
      font-weight: 800;
      letter-spacing: -0.025em;
      margin-bottom: 0.75rem;
      color: var(--bg-dark);
    }

    .user-badge {
      display: inline-flex;
      align-items: center;
      background: var(--white);
      border: 1px solid var(--border);
      padding: 6px 16px;
      border-radius: 100px;
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--primary);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    /* Cards */
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 2rem;
    }

    .card {
      background: rgba(255, 255, 255, 0.7);
      backdrop-filter: blur(10px);
      padding: 2.5rem 2rem;
      border-radius: var(--radius-lg);
      border: 1px solid rgba(255, 255, 255, 0.5);
      text-decoration: none;
      color: inherit;
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    }

    .card:hover {
      transform: translateY(-12px);
      background: var(--white);
      border-color: var(--primary-light);
      box-shadow: 0 25px 50px -12px rgba(99, 102, 241, 0.15);
    }

    .card-icon {
      width: 64px;
      height: 64px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.75rem;
      color: var(--white);
      margin-bottom: 1.5rem;
      box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
    }

    .card h3 {
      font-size: 1.3rem;
      font-weight: 700;
      margin-bottom: 0.75rem;
      color: var(--bg-dark);
    }

    .card p {
      color: var(--text-muted);
      font-size: 0.95rem;
    }

    .admin-link {
      margin-top: 4rem;
      text-align: center;
    }

    .btn-admin {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: var(--bg-dark);
      color: var(--white);
      padding: 12px 24px;
      border-radius: 12px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn-logout {
      color: var(--text-muted);
      text-decoration: none;
      font-weight: 600;
      font-size: 0.9rem;
      padding: 8px 16px;
      border-radius: 8px;
    }

    .btn-logout:hover {
      background: #fee2e2;
      color: #ef4444;
    }
  </style>
</head>

<body>

  <header>
    <a href="dashboard.php" class="logo">AI TUTOR</a>
    <a href="logout.php" class="btn-logout">Logout</a>
  </header>

  <div class="container">
    <div class="welcome-section">
      <div class="user-badge">✨ Personal Learning Space</div>
      <h2 style="margin-top: 1rem;">Welcome back, <?php echo h($user["name"]); ?> 👋</h2>
      <p style="color: var(--text-muted); font-weight: 500;">Ready to practice your language skills today?</p>
    </div>

    <div class="grid">
      <a href="chat.php" class="card">
        <div class="card-icon">💬</div>
        <h3>AI Tutor</h3>
        <p>Start a conversation with AI to improve your speaking and grammar skills.</p>
      </a>

      <a href="placement.php" class="card">
        <div class="card-icon">📝</div>
        <h3>Placement Test</h3>
        <p>Take a quick test to determine your current language level and track progress.</p>
      </a>

      <a href="profile.php" class="card">
        <div class="card-icon">👤</div>
        <h3>My Profile</h3>
        <p>Manage your account settings and view your personal achievements.</p>
      </a>

      <a href="history.php" class="card">
        <div class="card-icon">🕒</div>
        <h3>Chat History</h3>
        <p>Review your past conversations and learn from your previous mistakes.</p>
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