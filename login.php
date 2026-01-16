<?php
require_once __DIR__ . "/inc/init.php";
require_once __DIR__ . "/inc/user_repo.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = trim($_POST["email"] ?? "");
  $pass  = (string)($_POST["password"] ?? "");

  if ($email === "" || $pass === "") {
    $error = "Please enter your email and password.";
  } else {
    $user = authenticate_user($email, $pass);

    if ($user) {
      $_SESSION["user"] = $user;
      redirect("dashboard.php");
    } else {
      $error = "Invalid email or password.";
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - BeeFluent</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-full-bg">

  <div class="auth-wrapper">
    <div class="auth-floating-panel">
      <div class="login-header-left">
        <h2 class="bee-logo">Bee<span>Fluent</span></h2>
        <p>Continue your learning journey 🐝</p>
      </div>

      <?php if ($error): ?>
        <div class="error-box"><?php echo h($error); ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="email"
            value="<?php echo h($_POST["email"] ?? ""); ?>"
            placeholder="email@example.com" required>
        </div>

        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-bee">Log in</button>
      </form>

      <div class="login-footer">
        <p>Don’t have an account? <a href="register.php">Create one</a></p>
        <a href="forgot_password.php" class="forgot-pass">Forgot password?</a>
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

