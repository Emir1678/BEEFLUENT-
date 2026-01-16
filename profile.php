<?php
require_once __DIR__ . "/inc/init.php";
require_once __DIR__ . "/inc/user_repo.php";

require_login();

$userId = (int)current_user()["id"];
$error = "";
$ok = "";

$u = get_user_public($userId);
if (!$u) {
  redirect("logout.php");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name = (string)($_POST["name"] ?? "");
  $avatar = (string)($_POST["avatar"] ?? "");

  try {
    update_user_profile($userId, $name, $avatar);
    $ok = "Profile updated successfully.";

    $_SESSION["user"]["name"] = trim($name);
    $_SESSION["user"]["avatar"] = trim($avatar) !== "" ? trim($avatar) : null;

    $u = get_user_public($userId);
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
  <title>My Profile - BeeFluent</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    /* Sadece Font ve Ekran Görüntüsündeki Hizalamayı Sağlayan CSS */
    body,
    .profile-card,
    input,
    button,
    .stat-box {
      font-family: 'Inter', sans-serif !important;
      -webkit-font-smoothing: antialiased;
    }

    /* Ekran görüntüsündeki gibi avatar ve metinleri ortala */
    .profile-header {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 40px 20px;
    }

    .avatar-preview {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      border: 5px solid #fff;
      overflow: hidden;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #fff;
    }

    .profile-header h3 {
      margin: 0;
      font-weight: 800;
      font-size: 1.5rem;
      color: #1a1a1a;
    }

    .profile-header p {
      margin: 5px 0 0 0;
      font-weight: 500;
      opacity: 0.8;
    }

    /* Ekran görüntüsündeki o meşhur kutucuklar */
    .info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      padding: 0 20px;
      margin-top: -30px;
      /* Kartın içine hafif girmesi için */
    }

    .stat-box {
      background: #fff;
      padding: 20px;
      border-radius: 15px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
      text-align: center;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .stat-label {
      font-size: 0.7rem;
      text-transform: uppercase;
      font-weight: 700;
      color: #a0a0a0;
      letter-spacing: 0.05em;
      margin-bottom: 5px;
    }

    .stat-value {
      font-size: 1.1rem;
      font-weight: 800;
      color: #FFB938;
    }

    /* Form elemanları */
    .form-section {
      padding: 30px 20px;
    }

    .form-group label {
      font-weight: 700;
      font-size: 0.85rem;
      margin-bottom: 8px;
      display: block;
    }

    input {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #edf2f7;
      border-radius: 10px;
      background: #f8fafc;
      margin-bottom: 20px;
      font-weight: 500;
    }

    button {
      background: #FFB938;
      color: #fff;
      border: none;
      padding: 14px;
      width: 100%;
      border-radius: 12px;
      font-weight: 800;
      cursor: pointer;
      transition: 0.2s;
    }

    button:hover {
      opacity: 0.9;
    }
  </style>
</head>

<body class="page-profile">

  <header>
    <a href="dashboard.php" class="logo">BeeFluent</a>
    <nav class="nav-links">
      <a href="dashboard.php">Dashboard</a>
      <a href="chat.php">Tutor</a>
      <a href="history.php">History</a>
    </nav>
  </header>

  <div class="container" style="max-width: 700px; margin: 0 auto;">
    <?php if ($error): ?>
      <div class="alert alert-error">⚠️ <?php echo h($error); ?></div>
    <?php endif; ?>
    <?php if ($ok): ?>
      <div class="alert alert-success">🐝 <?php echo h($ok); ?></div>
    <?php endif; ?>

    <div class="profile-card" style="background: #fff; border-radius: 25px; overflow: hidden; margin-top: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
      <div class="profile-header" style="background: #FFB938; color: #fff; padding-bottom: 60px;">
        <div class="avatar-preview">
          <?php if (!empty($u["avatar"])): ?>
            <img src="<?php echo h($u["avatar"]); ?>" style="width:100%; height:100%; object-fit:cover;">
          <?php else: ?>
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($u['name']); ?>&background=ffffff&color=FFB938" style="width:100%; height:100%;">
          <?php endif; ?>
        </div>
        <h3 style="color: #000;"><?php echo h($u["name"]); ?></h3>
        <p style="color: #000;"><?php echo h($u["email"]); ?></p>
      </div>

      <div class="info-grid">
        <div class="stat-box">
          <span class="stat-label">Level</span>
          <span class="stat-value"><?php echo h((string)($u["level"] ?? "Advanced")); ?></span>
        </div>
        <div class="stat-box">
          <span class="stat-label">Since</span>
          <span class="stat-value"><?php echo !empty($u["created_at"]) ? date("M Y", strtotime($u["created_at"])) : "Jan 2026"; ?></span>
        </div>
      </div>

      <div class="form-section">
        <form method="post">
          <div class="form-group">
            <label>Full Name</label>
            <input name="name" value="<?php echo h($u["name"]); ?>" required>
          </div>
          <div class="form-group">
            <label>Avatar URL (Optional)</label>
            <input name="avatar" value="<?php echo h((string)($u["avatar"] ?? "")); ?>" placeholder="https://image.path/photo.jpg">
          </div>
          <button type="submit">Save Changes</button>
        </form>

        <div style="text-align: center; margin-top: 25px;">
          <a href="placement.php" style="color: #FFB938; text-decoration: none; font-size: 0.85rem; font-weight: 700;">
            🔄 Reset Progress & Retake Test
          </a>
        </div>
      </div>
    </div>
  </div>

</body>

</html>
