<?php
// inc/chat_repo.php
declare(strict_types=1);

require_once __DIR__ . "/db.php";


function chat_new_session_id(): string
{
    // 32 hex chars, safe for URLs and DB
    return bin2hex(random_bytes(16));
}

function _chat_has_session_id_column(): bool
{
    static $cached = null;
    if ($cached !== null) return $cached;

    global $conn;

    // Check if chats.session_id exists
    $sql = "
        SELECT COUNT(*) AS c
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'chats'
          AND COLUMN_NAME = 'session_id'
    ";

    $res = $conn->query($sql);
    $row = $res ? $res->fetch_assoc() : null;
    $cached = (int)($row["c"] ?? 0) > 0;

    return $cached;
}

/**
 * Backward-compatible save:
 * - If chats.session_id exists and you pass $sessionId, it will be stored.
 * - If session_id column doesn't exist (yet), it will store messages without it.
 */
function chat_save_message(int $userId, string $role, string $message, ?string $sessionId = null): void
{
    global $conn;

    if ($role !== "user" && $role !== "assistant") {
        throw new RuntimeException("Invalid role");
    }

    $message = trim($message);
    if ($message === "") return;

    $hasSession = _chat_has_session_id_column();

    if ($hasSession && $sessionId !== null && $sessionId !== "") {
        $stmt = $conn->prepare("INSERT INTO chats (user_id, session_id, role, message) VALUES (?,?,?,?)");
        $stmt->bind_param("isss", $userId, $sessionId, $role, $message);
        $stmt->execute();
        return;
    }

    // Legacy insert (no session_id)
    $stmt = $conn->prepare("INSERT INTO chats (user_id, role, message) VALUES (?,?,?)");
    $stmt->bind_param("iss", $userId, $role, $message);
    $stmt->execute();
}

/**
 * NEW: Get messages for a specific session.
 * If session_id column doesn't exist, returns empty (caller can fall back).
 */
function chat_get_session_messages(int $userId, string $sessionId, int $limit = 500): array
{
    global $conn;

    if ($limit < 1) $limit = 1;
    if ($limit > 2000) $limit = 2000;

    $sessionId = trim($sessionId);
    if ($sessionId === "") return [];

    if (!_chat_has_session_id_column()) return [];

    $stmt = $conn->prepare(
        "SELECT id, session_id, role, message, created_at
         FROM chats
         WHERE user_id=? AND session_id=?
         ORDER BY id ASC
         LIMIT ?"
    );
    $stmt->bind_param("isi", $userId, $sessionId, $limit);
    $stmt->execute();

    $rows = [];
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    return $rows;
}

/**
 * Existing function: all messages (legacy / or across sessions).
 */
function chat_get_messages(int $userId, int $limit = 200): array
{
    global $conn;

    if ($limit < 1) $limit = 1;
    if ($limit > 1000) $limit = 1000;

    $stmt = $conn->prepare(
        "SELECT id, role, message, created_at
         FROM chats
         WHERE user_id=?
         ORDER BY id ASC
         LIMIT ?"
    );
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();

    $rows = [];
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    return $rows;
}

/**
 * NEW: List sessions for History page.
 * Returns: session_id, started_at, last_message_at, message_count, preview
 */
function chat_list_sessions(int $userId, int $limit = 50): array
{
    global $conn;

    if ($limit < 1) $limit = 1;
    if ($limit > 200) $limit = 200;

    if (!_chat_has_session_id_column()) return [];

    // Only sessions with a non-null session_id
    $stmt = $conn->prepare(
        "SELECT
            session_id,
            MIN(created_at) AS started_at,
            MAX(created_at) AS last_message_at,
            COUNT(*) AS message_count,
            SUBSTRING_INDEX(
              GROUP_CONCAT(CASE WHEN role='user' THEN message ELSE NULL END ORDER BY id ASC SEPARATOR '\n'),
              '\n',
              1
            ) AS preview
         FROM chats
         WHERE user_id=? AND session_id IS NOT NULL AND session_id <> ''
         GROUP BY session_id
         ORDER BY MAX(id) DESC
         LIMIT ?"
    );
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();

    $rows = [];
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        // ensure predictable keys
        $rows[] = [
            "session_id"      => (string)($r["session_id"] ?? ""),
            "started_at"      => $r["started_at"] ?? null,
            "last_message_at" => $r["last_message_at"] ?? null,
            "message_count"   => (int)($r["message_count"] ?? 0),
            "preview"         => (string)($r["preview"] ?? ""),
        ];
    }
    return $rows;
}

/**
 * Existing clear: removes all chats for user (all sessions too).
 */
function chat_clear_user(int $userId): void
{
    global $conn;
    $stmt = $conn->prepare("DELETE FROM chats WHERE user_id=?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
}

/**
 * NEW: Clear all sessions/messages for a user (alias of chat_clear_user).
 * Kept separate so history.php can call a session-specific name.
 */
function chat_clear_user_sessions(int $userId): void
{
    chat_clear_user($userId);
}

/**
 * NEW: Clear one session only.
 */
function chat_clear_session(int $userId, string $sessionId): void
{
    global $conn;

    $sessionId = trim($sessionId);
    if ($sessionId === "") return;

    if (!_chat_has_session_id_column()) return;

    $stmt = $conn->prepare("DELETE FROM chats WHERE user_id=? AND session_id=?");
    $stmt->bind_param("is", $userId, $sessionId);
    $stmt->execute();
}

