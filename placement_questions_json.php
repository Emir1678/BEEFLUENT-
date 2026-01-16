
<?php
declare(strict_types=1);

require_once __DIR__ . "/inc/init.php";

header("Content-Type: application/json; charset=utf-8");
ini_set("display_errors", "0");

if (!isset($_SESSION["user"])) {
    http_response_code(401);
    echo json_encode(["ok" => false, "error" => "Not logged in"], JSON_UNESCAPED_UNICODE);
    exit;
}

$questions = $_SESSION["placement_questions"] ?? null;
if (!$questions || !is_array($questions)) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "No questions in session"], JSON_UNESCAPED_UNICODE);
    exit;
}

$safe = [];
foreach ($questions as $q) {
    $safe[] = [
        "prompt" => $q["prompt"] ?? "",
        "options" => $q["options"] ?? [],
        "diff" => $q["diff"] ?? "",
    ];
}

echo json_encode(["ok" => true, "questions" => $safe], JSON_UNESCAPED_UNICODE);
exit;
