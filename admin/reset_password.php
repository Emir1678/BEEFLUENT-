<?php
require_once __DIR__ . "/../inc/admin_guard.php";
require_once __DIR__ . "/../inc/user_repo.php";
require_once __DIR__ . "/../inc/db.php";
require_admin();

$userId = (int)($_GET["user_id"] ?? 0);
if ($userId <= 0) { echo "Missing user_id"; exit; }

// Load target user
$stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$target = $stmt->get_result()->fetch_assoc();
if (!$target) { echo "User not found."; exit; }

$error = "";
$ok = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $p1 = (string)($_POST["pass1"] ?? "");
    $p2 = (string)($_POST["pass2"] ?? "");

    try {
        if ($p1 === "" || $p2 === "") throw new RuntimeException("Please fill both fields.");
        if ($p1 !== $p2) throw new RuntimeException("Passwords do not match.");

        admin_set_user_password($userId, $p1);
        $ok = "Password updated successfully.";
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Reset Password - Admin</title>
</head>
<body>
  <h2>Reset Password</h2>
  <p>User: <strong><?php echo h($target["name"]); ?></strong> (<?php echo h($target["email"]); ?>)</p>

  <?php if ($error): ?>
    <p style="color:red;"><?php echo h($error); ?></p>
  <?php endif; ?>
  <?php if ($ok): ?>
    <p style="color:green;"><?php echo h($ok); ?></p>
  <?php endif; ?>

  <form method="post" style="max-width:420px;">
    <div style="margin:10px 0;">
      <label>New password</label><br>
      <input type="password" name="pass1" style="width:100%;">
    </div>

    <div style="margin:10px 0;">
      <label>Confirm password</label><br>
      <input type="password" name="pass2" style="width:100%;">
    </div>

    <button type="submit" onclick="return confirm('Reset password for this user?');">Reset Password</button>
  </form>

  <p><a href="users.php">Back to users</a> | <a href="index.php">Admin Home</a></p>
</body>
</html>
