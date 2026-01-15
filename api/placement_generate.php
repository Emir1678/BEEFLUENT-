
<?php
// api/placement_generate.php
declare(strict_types=1);

require_once __DIR__ . "/../inc/init.php";
require_once __DIR__ . "/../inc/user_repo.php";
require_once __DIR__ . "/../inc/config.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["user"])) {
    http_response_code(401);
    echo json_encode(["ok" => false, "error" => "Not logged in"]);
    exit;
}

$userId = (int)$_SESSION["user"]["id"];
$dbUser = find_user_by_id($userId);
$levelHint = $dbUser["language_level"] ?? "Beginner";
if (!$levelHint || strtolower((string)$levelHint) === "null") $levelHint = "Beginner";

// Key like your Java: env first then config fallback
$apiKey = getenv("GROQ_API_KEY");
if (!$apiKey) $apiKey = GROQ_API_KEY;

function fixed_test(): array {
    return [
        [
            "prompt" => "Choose the correct sentence.",
            "options" => [
                "She don't like coffee.",
                "She doesn’t like coffee.",
                "She doesn’t likes coffee.",
                "She not like coffee."
            ],
            "correct" => 1,
            "diff" => "EASY"
        ],
        [
            "prompt" => "I ___ to the gym every Monday.",
            "options" => ["go", "goes", "going", "gone"],
            "correct" => 0,
            "diff" => "EASY"
        ],
        [
            "prompt" => "Which sentence is correct?",
            "options" => [
                "He is married with a doctor.",
                "He is married to a doctor.",
                "He married to a doctor.",
                "He married with a doctor."
            ],
            "correct" => 1,
            "diff" => "EASY"
        ],
        [
            "prompt" => "If I ___ more time, I would travel more.",
            "options" => ["have", "had", "will have", "would have"],
            "correct" => 1,
            "diff" => "MEDIUM"
        ],
        [
            "prompt" => "I’m looking ___ my keys.",
            "options" => ["at", "for", "to", "on"],
            "correct" => 1,
            "diff" => "MEDIUM"
        ],
        [
            "prompt" => "Choose the correct sentence:",
            "options" => [
                "I have been to Ankara last year.",
                "I went to Ankara last year.",
                "I have went to Ankara last year.",
                "I was going to Ankara last year."
            ],
            "correct" => 1,
            "diff" => "MEDIUM"
        ],
        [
            "prompt" => "This is the book ___ I told you about.",
            "options" => ["who", "where", "that", "what"],
            "correct" => 2,
            "diff" => "MEDIUM"
        ],
        [
            "prompt" => "Not only ___ late, but he also forgot the meeting.",
            "options" => ["he was", "was he", "he is", "is he"],
            "correct" => 1,
            "diff" => "HARD"
        ],
        [
            "prompt" => "She turned down the offer.",
            "options" => ["accepted", "refused", "explained", "improved"],
            "correct" => 1,
            "diff" => "HARD"
        ],
        [
            "prompt" => "Hardly had I arrived when it started raining.",
            "options" => [
                "Hardly had I arrived when it started raining.",
                "Hardly I had arrived when it started raining.",
                "Hardly did I arrived when it started raining.",
                "Hardly had I arrive when it started raining."
            ],
            "correct" => 0,
            "diff" => "HARD"
        ],
    ];
}

function build_prompt(string $levelHint): string {
    return
        "Generate exactly 10 English placement test multiple-choice questions.\n"
        . "Focus on grammar, vocabulary, prepositions, verb tenses.\n"
        . "Mix difficulty: 3 EASY, 4 MEDIUM, 3 HARD.\n"
        . "User level hint: {$levelHint}\n\n"
        . "STRICT FORMAT:\n"
        . "Q1: question text\n"
        . "A) option\n"
        . "B) option\n"
        . "C) option\n"
        . "D) option\n"
        . "ANSWER: A/B/C/D\n"
        . "DIFF: EASY/MEDIUM/HARD\n"
        . "---\n"
        . "Repeat exactly this format. No extra text.\n";
}

function parse_questions(string $raw): array {
    $raw = trim($raw);
    if ($raw === "") return [];

    // Split by lines that are "---"
    $blocks = preg_split("/(?m)^(?:-{3,})\\s*$/", $raw);
    if (!$blocks) return [];

    $out = [];

    foreach ($blocks as $b) {
        $block = trim($b);
        if ($block === "") continue;

        // Q line
        if (!preg_match("/(?m)^Q\\s*\\d{1,2}[:\\.]\\s*(.+)$/", $block, $mQ)) continue;
        $prompt = trim($mQ[1]);

        // Options
        $opts = [];
        foreach (["A","B","C","D"] as $L) {
            if (!preg_match("/(?m)^{$L}\\s*[\\)\\.:\\-]\\s*(.+)$/", $block, $mO)) {
                $opts = [];
                break;
            }
            $opts[] = trim($mO[1]);
        }
        if (count($opts) !== 4) continue;

        // Answer
        if (!preg_match("/ANSWER\\s*:\\s*([ABCD])/i", $block, $mA)) continue;
        $ansLetter = strtoupper($mA[1]);
        $correct = strpos("ABCD", $ansLetter);
        if ($correct === false) continue;

        // Difficulty
        if (!preg_match("/(DIFF|DIFFICULTY)\\s*:\\s*(EASY|MEDIUM|HARD)/i", $block, $mD)) continue;
        $diff = strtoupper($mD[2]);

        $out[] = [
            "prompt" => $prompt,
            "options" => $opts,
            "correct" => (int)$correct,
            "diff" => $diff
        ];
    }

    return $out;
}

$questions = [];

// If missing key, just use fixed
if (!$apiKey || trim($apiKey) === "") {
    $questions = fixed_test();
} else {
    $system = "You generate English placement tests. Follow the format EXACTLY. No extra text.";
    $prompt = build_prompt($levelHint);

    $payload = [
        "model" => GROQ_MODEL,
        "temperature" => 0.2,
        "top_p" => 1,
        "max_tokens" => 1200,
        "messages" => [
            ["role" => "system", "content" => $system],
            ["role" => "user", "content" => $prompt],
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
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
    ]);

    $raw = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw && $http >= 200 && $http < 300) {
        $data = json_decode($raw, true);
        $content = $data["choices"][0]["message"]["content"] ?? "";
        $questions = parse_questions((string)$content);
    }

    // Fallback if parse failed
    if (count($questions) < 8) {
        $questions = fixed_test();
    }
}

// Store questions in session for grading later
$_SESSION["placement_questions"] = $questions;

echo json_encode(["ok" => true, "count" => count($questions)], JSON_UNESCAPED_UNICODE);
