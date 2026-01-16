<?php
// inc/activity_log_repo.php
declare(strict_types=1);

require_once __DIR__ . "/db.php";

function activity_log_add(
    int $adminUserId,
    string $action,
    ?string $targetTable = null,
    ?int $targetId = null,
    ?string $details = null
): void {
    global $conn;

    $ip = $_SERVER["REMOTE_ADDR"] ?? null;
    $ua = $_SERVER["HTTP_USER_AGENT"] ?? null;

    $stmt = $conn->prepare(
        "INSERT INTO activity_log (admin_user_id, action, target_table, target_id, details, ip_address, user_agent)
         VALUES (?,?,?,?,?,?,?)"
    );
    $stmt->bind_param(
        "ississs",
        $adminUserId,
        $action,
        $targetTable,
        $targetId,
        $details,
        $ip,
        $ua
    );
    $stmt->execute();
}
