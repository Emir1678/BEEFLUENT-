<?php
// api/placement_submit.php
declare(strict_types=1);

require_once __DIR__ . "/../inc/init.php";
require_once __DIR__ . "/../inc/user_repo.php";
require_once __DIR__ . "/../inc/db.php";

header("Content-Type: application/json; charset=utf-8");
ini_set("display_errors", "0");

// Must be logged in
if (!isset($_SESSION["user"])) {
    http_response_code(401);
    echo json_encode(["ok" => false, "error" => "Not logged in"], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)($_SESSION["user"]["id"] ?? 0);
if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Invalid user"], JSON_UNESCAPED_UNICODE);
    exit;
}

// Placement questions must exist in session (expect 10)
$questions = $_SESSION["placement_questions"] ?? null;
if (!$questions || !is_array($questions) || count($questions) !== 10) {
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "error" => "Placement test not loaded. Please generate the test first."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Submitted answers: ans[0]..ans[9]
$answers = $_POST["ans"] ?? [];
if (!is_array($answers)) $answers = [];

// Require all answers
for ($i = 0; $i < 10; $i++) {
    if (!array_key_exists($i, $answers)) {
        http_response_code(400);
        echo json_encode([
            "ok" => false,
            "error" => "Please answer all questions before submitting."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $v = (int)$answers[$i];
    if ($v < 0 || $v > 3) {
        http_response_code(400);
        echo json_encode([
            "ok" => false,
            "error" => "Invalid answer submitted."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function weight(string $difficulty): int {
    $d = strtoupper(trim($difficulty));
    if ($d === "EASY") return 1;
    if ($d === "MEDIUM") return 2;
    if ($d === "HARD") return 3;
    return 1; // safe default
}

$totalWeighted = 0;
$earnedWeighted = 0;

// Calculate score
foreach ($questions as $i => $q) {
    $w = weight((string)($q["diff"] ?? "EASY"));
    $totalWeighted += $w;

    $selected = (int)$answers[$i];
    $correct  = (int)($q["correct"] ?? -999);

    if ($selected === $correct) {
        $earnedWeighted += $w;
    }
}

$percentage = ($totalWeighted > 0)
    ? ($earnedWeighted * 100.0 / $totalWeighted)
    : 0.0;

// Map percentage → level
if ($percentage < 50) {
    $level = "Beginner";
} elseif ($percentage < 80) {
    $level = "Intermediate";
} else {
    $level = "Advanced";
}

// Update user level in DB + session
update_user_level($userId, $level);
$_SESSION["user"]["level"] = $level;

// Save attempt (best-effort)
try {
    $stmt = $conn->prepare(
        "INSERT INTO test_results (user_id, percentage, level)
         VALUES (?, ?, ?)"
    );
    $p = round($percentage, 2);
    $stmt->bind_param("ids", $userId, $p, $level);
    $stmt->execute();
} catch (Throwable $e) {
    // ignore logging failure
}

// IMPORTANT: clear questions so old tests don't keep reappearing
unset($_SESSION["placement_questions"]);

echo json_encode([
    "ok" => true,
    "percentage" => (int)round($percentage, 0),
    "level" => $level
], JSON_UNESCAPED_UNICODE);
exit;

