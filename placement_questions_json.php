
<?php
declare(strict_types=1);

require_once __DIR__ . "/inc/init.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["user"])) {
    http_response_code(401);
    echo json_encode(["ok" => false, "error" => "Not logged in"]);
    exit;
}

$questions = $_SESSION["placement_questions"] ?? null;
if (!$questions || !is_array($questions)) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "No questions in session"]);
    exit;
}

// Send without correct answers to the client
$safe = [];
foreach ($questions as $q) {
    $safe[] = [
        "prompt" => $q["prompt"],
        "options" => $q["options"],
        "diff" => $q["diff"],
    ];
}

echo json_encode(["ok" => true, "questions" => $safe], JSON_UNESCAPED_UNICODE);
