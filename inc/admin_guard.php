
<?php
// inc/admin_guard.php
declare(strict_types=1);

require_once __DIR__ . "/init.php";

function require_admin(): void
{
    if (!isset($_SESSION["user"])) {
        redirect("../login.php");
    }

    $role  = $_SESSION["user"]["role"] ?? "user";
    $email = $_SESSION["user"]["email"] ?? "";

    // STRICT: only the admin email + role can access
    if ($role !== "admin") {
        http_response_code(403);
        echo "<h2>403 Forbidden</h2>";
        echo "<p>Admins only.</p>";
        echo '<p><a href="../dashboard.php">Back</a></p>';
        exit;
    }
}
