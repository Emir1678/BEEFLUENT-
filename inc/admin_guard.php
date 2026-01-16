<?php
// inc/admin_guard.php
declare(strict_types=1);

require_once __DIR__ . "/init.php";

/**
 * Require an authenticated admin user.
 * - Redirects to login if not logged in
 * - Returns 403 Forbidden if logged in but not admin
 */
function require_admin(): void
{
    if (!isset($_SESSION["user"]) || !is_array($_SESSION["user"])) {
        redirect("/login.php");
    }

    $role = (string)($_SESSION["user"]["role"] ?? "user");
    if ($role !== "admin") {
        http_response_code(403);
        echo "Forbidden";
        exit;
    }
}


