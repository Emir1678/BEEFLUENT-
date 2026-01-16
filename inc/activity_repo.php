<?php
// inc/activity_repo.php
declare(strict_types=1);

require_once __DIR__ . "/db.php";

/**
 * Tries to get current admin id from session user.
 * If not logged in, returns 0 (logging is skipped).
 */
function activity_current_admin_id(): int
{
    if (!isset($_SESSION)) return 0;
    if (!isset($_SESSION["user"])) return 0;
    $id = (int)($_SESSION["user"]["id"] ?? 0);
    return $id > 0 ? $id : 0;
}

/**
 * Insert an admin activity log row.
 * $meta can be array/string. Stored as JSON text.
 */
function activity_log_add(string $action, ?int $targetUserId = null, $meta = null): void
{
    global $conn;

    $adminId = activity_current_admin_id();
    if ($adminId <= 0) return; // skip if not logged in

    $ip = $_SERVER["REMOTE_ADDR"] ?? null;
    $ua = $_SERVER["HTTP_USER_AGENT"] ?? null;

    $metaJson = null;
    if (is_array($meta)) {
        $metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE);
    } elseif (is_string($meta) && trim($meta) !== "") {
        $metaJson = $meta;
    }

    // activity_log table may not exist yet; fail silently
    try {
        $stmt = $conn->prepare(
            "INSERT INTO activity_log (admin_id, action, target_user_id, meta_json, ip_address, user_agent)
             VALUES (?,?,?,?,?,?)"
        );
        $stmt->bind_param(
            "isisss",
            $adminId,
            $action,
            $targetUserId,
            $metaJson,
            $ip,
            $ua
        );
        $stmt->execute();
    } catch (Throwable $e) {
        // ignore (table missing etc.)
    }
}
