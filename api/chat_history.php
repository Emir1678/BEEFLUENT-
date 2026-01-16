<?php
// api/chat_history.php
declare(strict_types=1);

require_once __DIR__ . "/../inc/init.php";
require_once __DIR__ . "/../inc/chat_repo.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["user"])) {
    http_response_code(401);
    echo json_encode(["ok" => false, "error" => "Not logged in"]);
    exit;
}

$userId = (int)$_SESSION["user"]["id"];
$limit = (int)($_GET["limit"] ?? 50);
if ($limit < 1) $limit = 1;
if ($limit > 300) $limit = 300;

$messages = chat_get_messages($userId, $limit);

// Return as-is (already safe to display with escapeHtml on client)
echo json_encode(["ok" => true, "messages" => $messages], JSON_UNESCAPED_UNICODE);

