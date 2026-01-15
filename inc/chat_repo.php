<?php
// inc/chat_repo.php
declare(strict_types=1);

require_once __DIR__ . "/db.php";

function chat_save_message(int $userId, string $role, string $message): void {
    global $conn;

    // role must be 'user' or 'assistant'
    if ($role !== "user" && $role !== "assistant") {
        throw new RuntimeException("Invalid role");
    }

    $message = trim($message);
    if ($message === "") return;

    $stmt = $conn->prepare("INSERT INTO chats (user_id, role, message) VALUES (?,?,?)");
    $stmt->bind_param("iss", $userId, $role, $message);
    $stmt->execute();
}

function chat_get_messages(int $userId, int $limit = 200): array {
    global $conn;

    // Limit safety
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
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    return $rows;
}

function chat_clear_user(int $userId): void {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM chats WHERE user_id=?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
}
