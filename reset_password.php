<?php
require_once __DIR__ . "/inc/init.php";
require_once __DIR__ . "/inc/user_repo.php";

$token = trim($_GET["token"] ?? "");
$error = "";
$msg = "";
$u = null;

if ($token === "") {
  $error = "Missing reset token.";
} else {
  $u = find_user_by_reset_token($token);
  if (!$u) {
    $error = "Invalid or expired reset token.";
  }
}

if (!$error && $_SERVER["REQUEST_METHOD"] === "POST") {
  $pass = $_POST["password"] ?? "";
  $pass2 = $_POST["password2"] ?? "";

  if ($pass === "" || $pass2 === "") {
    $error = "Please fill in both password fields.";
  } elseif ($pass !== $pass2) {
    $error = "Passwords do not match.";
  } elseif (strlen($pass) < 6) {
    $error = "Password must be at least 6 characters long.";
  } else {
    update_password_by_user_id((int)$u["id"], $pass);
    $msg = "Your password has been updated successfully. You can now log in.";
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reset Password - BeeFluent</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-full-bg">

  <div class="auth-wrapper">
    <div class="auth-floating-panel">
      <div class="login-header-left">
        <h2 class="bee-logo">Bee<span>Fluent</span></h2>
        <p>Choose a new password 🐝</p>
      </div>

      <?php if ($error): ?>
        <div class="error-box"><?php echo h($error); ?></div>
      <?php endif; ?>

      <?php if ($msg): ?>
        <div class="error-box" style="background:#f0fdf4; color:#16a34a; border:1px solid #dcfce7;">
          <?php echo h($msg); ?>
        </div>
        <div class="login-footer">
          <a href="login.php">Go to login</a>
        </div>
      <?php endif; ?>

      <?php if (!$error && !$msg): ?>
        <form method="post">
          <div class="form-group">
            <label>New Password</label>
            <input type="password" name="password" placeholder="••••••••" required>
          </div>

          <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="password2" placeholder="••••••••" required>
          </div>

          <button type="submit" class="btn-bee">Update Password</button>
        </form>
      <?php elseif ($error && !$u): ?>
        <div class="login-footer">
          <a href="forgot_password.php">Request a new link</a>
        </div>
      <?php endif; ?>
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
