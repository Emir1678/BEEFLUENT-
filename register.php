<?php
require_once __DIR__ . "/inc/init.php";
require_once __DIR__ . "/inc/user_repo.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name = trim($_POST["name"] ?? "");
  $email = trim($_POST["email"] ?? "");
  $pass = $_POST["password"] ?? "";
  $pass2 = $_POST["password2"] ?? "";

  if ($name === "" || $email === "" || $pass === "" || $pass2 === "") {
    $error = "Please fill in all fields.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Please enter a valid email address.";
  } elseif ($pass !== $pass2) {
    $error = "Passwords do not match.";
  } elseif (strlen($pass) < 6) {
    $error = "Password must be at least 6 characters.";
  } else {
    try {
      $user = create_user($name, $email, $pass);
      $_SESSION["user"] = $user;
      redirect("dashboard.php");
    } catch (Throwable $e) {
      $error = "This email may already be in use.";
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create Account - BeeFluent</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-full-bg">

  <div class="auth-wrapper">
    <div class="auth-floating-panel">
      <div class="login-header-left">
        <h2 class="bee-logo">Bee<span>Fluent</span></h2>
        <p>Start your learning journey today 🐝</p>
      </div>

      <?php if ($error): ?>
        <div class="error-box"><?php echo h($error); ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" name="name" value="<?php echo h($_POST["name"] ?? ""); ?>" placeholder="Your name" required>
        </div>

        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="email" value="<?php echo h($_POST["email"] ?? ""); ?>" placeholder="email@example.com" required>
        </div>

        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="At least 6 characters" required>
        </div>

        <div class="form-group">
          <label>Confirm Password</label>
          <input type="password" name="password2" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-bee">Create Account</button>
      </form>

      <div class="login-footer">
        <p>Already have an account? <a href="login.php">Log in</a></p>
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
