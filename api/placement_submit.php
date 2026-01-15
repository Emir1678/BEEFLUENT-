
<?php
// api/placement_submit.php
declare(strict_types=1);

require_once __DIR__ . "/../inc/init.php";
require_once __DIR__ . "/../inc/user_repo.php";
require_once __DIR__ . "/../inc/db.php";

header("Content-Type: application/json; charset=utf-8");

// 1️⃣ Must be logged in
if (!isset($_SESSION["user"])) {
    http_response_code(401);
    echo json_encode(["ok" => false, "error" => "Not logged in"]);
    exit;
}

$userId = (int)$_SESSION["user"]["id"];

// 2️⃣ Placement questions must exist in session
$questions = $_SESSION["placement_questions"] ?? null;
if (!$questions || !is_array($questions) || count($questions) < 5) {
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "error" => "Placement test not loaded. Please generate the test first."
    ]);
    exit;
}

// 3️⃣ Read submitted answers
// ans[0] = selected option index for Q1, etc.
$answers = $_POST["ans"] ?? [];

// 4️⃣ Weight function (same logic as JavaFX)
function weight(string $difficulty): int {
    $d = strtoupper($difficulty);
    if ($d === "EASY") return 1;
    if ($d === "MEDIUM") return 2;
    return 3; // HARD
}

$totalWeighted = 0;
$earnedWeighted = 0;

// 5️⃣ Calculate score
foreach ($questions as $i => $q) {
    $w = weight((string)($q["diff"] ?? "EASY"));
    $totalWeighted += $w;

    $selected = isset($answers[$i]) ? (int)$answers[$i] : -1;
    $correct  = (int)($q["correct"] ?? -999);

    if ($selected === $correct) {
        $earnedWeighted += $w;
    }
}

// 6️⃣ Percentage
$percentage = ($totalWeighted > 0)
    ? ($earnedWeighted * 100.0 / $totalWeighted)
    : 0.0;

// 7️⃣ Map percentage → level (same as JavaFX)
if ($percentage < 50) {
    $level = "Beginner";
} elseif ($percentage < 80) {
    $level = "Intermediate";
} else {
    $level = "Advanced";
}

// 8️⃣ Update user level in DB
update_user_level($userId, $level);
$_SESSION["user"]["level"] = $level;

// 9️⃣ Save placement attempt to test_results table
try {
    $stmt = $conn->prepare(
        "INSERT INTO test_results (user_id, percentage, level)
         VALUES (?, ?, ?)"
    );
    $p = round($percentage, 2);
    $stmt->bind_param("ids", $userId, $p, $level);
    $stmt->execute();
} catch (Throwable $e) {
    // If something goes wrong, we still continue
    // (level update already succeeded)
}

// 🔟 Return result to frontend
echo json_encode([
    "ok" => true,
    "percentage" => round($percentage, 0),
    "level" => $level
], JSON_UNESCAPED_UNICODE);
