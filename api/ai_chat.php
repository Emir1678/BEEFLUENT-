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

// Save user message first
chat_save_message($userId, "user", $message);

$user = $_SESSION["user"];
$dbUser = find_user_by_id($userId);
$level = $dbUser["language_level"] ?? ($user["level"] ?? null);
if (!$level || strtolower((string)$level) === "null") $level = "Beginner";

// load key from env first (like your Java)
$apiKey = getenv("GROQ_API_KEY");
if (!$apiKey) $apiKey = GROQ_API_KEY;

if (!$apiKey || trim($apiKey) === "") {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Missing Groq API key. Set GROQ_API_KEY or inc/config.php"]);
    exit;
}

$system = "You are an English tutor. Student level: {$level}. "
        . "Be concise and helpful. "
        . "Correct mistakes gently. "
        . "Give 1 short example. "
        . "Ask 1 short follow-up question.";

$payload = [
    "model" => GROQ_MODEL,
    "temperature" => 0.2,
    "top_p" => 1,
    "max_tokens" => 300,
    "messages" => [
        ["role" => "system", "content" => $system],
        ["role" => "user", "content" => $message],
    ],
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
    CURLOPT_TIMEOUT => 15,
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

// Save assistant reply
chat_save_message($userId, "assistant", $reply);

echo json_encode(["ok" => true, "reply" => $reply], JSON_UNESCAPED_UNICODE);
