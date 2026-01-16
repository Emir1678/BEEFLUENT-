<?php
require_once __DIR__ . "/inc/init.php";
require_once __DIR__ . "/inc/user_repo.php";

$msg = "";
$error = "";
$resetLink = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = trim($_POST["email"] ?? "");
  if ($email === "") {
    $error = "Please enter your email address.";
  } else {
    $u = find_user_by_email($email);

    // Security: always show a generic message
    $msg = "If this email exists in our system, a reset link has been generated.";

    if ($u) {
      $token = create_reset_token((int)$u["id"]);
      $resetLink = "http://localhost/ai_tutor/reset_password.php?token=" . urlencode($token);
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Forgot Password - BeeFluent</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-full-bg">

  <div class="auth-wrapper">
    <div class="auth-floating-panel">
      <div class="login-header-left">
        <h2 class="bee-logo">Bee<span>Fluent</span></h2>
        <p>Reset your password securely 🐝</p>
      </div>

      <?php if ($error): ?>
        <div class="error-box"><?php echo h($error); ?></div>
      <?php endif; ?>

      <?php if ($msg): ?>
        <div class="error-box" style="background:#f0fdf4; color:#16a34a; border:1px solid #dcfce7;">
          <?php echo h($msg); ?>
        </div>
      <?php endif; ?>

      <form method="post">
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="email" value="<?php echo h($_POST["email"] ?? ""); ?>" placeholder="email@example.com" required>
        </div>
        <button type="submit" class="btn-bee">Generate Reset Link</button>
      </form>

      <?php if ($resetLink): ?>
        <div style="margin-top: 1rem; padding: 1rem; border-radius: 12px; background: rgba(255, 255, 255, 0.7); border: 1px solid rgba(245, 158, 11, 0.18); font-size: 0.85rem;">
          <strong style="display:block; margin-bottom:6px;">Demo reset link:</strong>
          <a href="<?php echo h($resetLink); ?>" style="word-break: break-all; color: #D97706; font-weight: 800; text-decoration: none;">
            <?php echo h($resetLink); ?>
          </a>
        </div>
      <?php endif; ?>

      <div class="login-footer">
        <a href="login.php">Back to login</a>
      </div>
    </div>

    <div class="auth-right-visual">
      <img src="assets/img/hero.png" alt="BeFluent">
      <h1>
        Don't just learn,<br>
        <span>BeeFluent.</span>
      </h1>
    </div>
  </div>

</body>
</html>

