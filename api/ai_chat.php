<?php
// api/ai_chat.php
declare(strict_types=1);

require_once __DIR__ . "/../inc/init.php";
require_once __DIR__ . "/../inc/user_repo.php";
require_once __DIR__ . "/../inc/chat_repo.php";
require_once __DIR__ . "/../inc/config.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["user"])) {
    http_response_code(401);
    echo json_encode(["ok" => false, "error" => "Not logged in"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$message = trim((string)($input["message"] ?? ""));

if ($message === "") {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Empty message"]);
    exit;
}

$userId = (int)$_SESSION["user"]["id"];

/**
 * SESSION SUPPORT (Option A)
 */
$clientSessionId = trim((string)($input["session_id"] ?? ""));
$sessionId = "";

if ($clientSessionId !== "") {
    $sessionId = $clientSessionId;
    $_SESSION["chat_session_id"] = $sessionId;
} else {
    if (
        !isset($_SESSION["chat_session_id"]) ||
        !is_string($_SESSION["chat_session_id"]) ||
        trim($_SESSION["chat_session_id"]) === ""
    ) {
        $_SESSION["chat_session_id"] = chat_new_session_id();
    }
    $sessionId = (string)$_SESSION["chat_session_id"];
}

$user = $_SESSION["user"];
$dbUser = find_user_by_id($userId);
$level = $dbUser["language_level"] ?? ($user["level"] ?? null);
if (!$level || strtolower((string)$level) === "null") $level = "Beginner";

// load key from env first
$apiKey = getenv("GROQ_API_KEY");
if (!$apiKey) $apiKey = GROQ_API_KEY;

if (!$apiKey || trim($apiKey) === "") {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Missing Groq API key. Set GROQ_API_KEY or inc/config.php"]);
    exit;
}

/**
 * MEMORY: pull recent messages from THIS session and send as context
 * Keep it small to avoid token bloat.
 */
$history = [];
$maxContextMessages = 14; // ~7 turns (user+assistant). Adjust if needed.

if (function_exists("chat_get_session_messages") && $sessionId !== "") {
    // Fetch more than needed, then take the last N
    $all = chat_get_session_messages($userId, $sessionId, 2000);
    if ($all) {
        $history = array_slice($all, -$maxContextMessages);
    }
} else {
    // Fallback (legacy / no session_id column): use last messages overall
    $all = chat_get_messages($userId, 200);
    if ($all) {
        $history = array_slice($all, -$maxContextMessages);
    }
}

// Save user message first (with sessionId) AFTER we read old history
chat_save_message($userId, "user", $message, $sessionId);

$system = "You are BeeFluent, an English tutor. Student level: {$level}. "
        . "IMPORTANT: Use the conversation context to stay consistent and remember what the student just said. "
        . "Be concise and helpful. Correct mistakes gently. "
        . "Give 1 short example. Ask 1 short follow-up question. "
        . "Do not re-introduce yourself every message.";

// Build messages payload: system + history + current user message
$messagesPayload = [
    ["role" => "system", "content" => $system],
];

// Add prior session history (if any)
foreach ($history as $h) {
    $r = (string)($h["role"] ?? "");
    $c = (string)($h["message"] ?? "");

    // Only allow roles Groq expects
    if ($r !== "user" && $r !== "assistant") continue;
    if (trim($c) === "") continue;

    $messagesPayload[] = ["role" => $r, "content" => $c];
}

// Add the NEW user message at the end (so model responds to it)
$messagesPayload[] = ["role" => "user", "content" => $message];

$payload = [
    "model" => GROQ_MODEL,
    "temperature" => 0.25,
    "top_p" => 1,
    "max_tokens" => 350,
    "messages" => $messagesPayload,
];

$ch = curl_init(GROQ_API_URL);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . trim($apiKey),
        "Content-Type: application/json; charset=UTF-8",
    ],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_TIMEOUT => 20,
]);

$raw = curl_exec($ch);
$curlErr = curl_error($ch);
$http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($raw === false) {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "cURL error: " . $curlErr]);
    exit;
}

if ($http < 200 || $http >= 300) {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Groq HTTP $http", "raw" => $raw]);
    exit;
}

$data = json_decode($raw, true);
$reply = $data["choices"][0]["message"]["content"] ?? null;

if (!$reply) {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Unexpected response", "raw" => $raw]);
    exit;
}

// Save assistant reply (with sessionId)
chat_save_message($userId, "assistant", $reply, $sessionId);

echo json_encode(
    ["ok" => true, "reply" => $reply, "session_id" => $sessionId],
    JSON_UNESCAPED_UNICODE
);


