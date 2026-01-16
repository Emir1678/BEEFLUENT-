<?php
// api/placement_generate.php
declare(strict_types=1);

require_once __DIR__ . "/../inc/init.php";
require_once __DIR__ . "/../inc/user_repo.php";
require_once __DIR__ . "/../inc/config.php";

header("Content-Type: application/json; charset=utf-8");
ini_set("display_errors", "0");

if (!isset($_SESSION["user"])) {
    http_response_code(401);
    echo json_encode(["ok" => false, "error" => "Not logged in"], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Force NEW test each click
 */
unset($_SESSION["placement_questions"]);

$userId = (int)$_SESSION["user"]["id"];
$dbUser = find_user_by_id($userId);

$levelHint = $dbUser["language_level"] ?? "Beginner";
if (!$levelHint || strtolower((string)$levelHint) === "null") {
    $levelHint = "Beginner";
}

// env first, then config fallback
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

function build_prompt(string $levelHint, string $nonce): string {
    return
        "Return ONLY valid JSON. No markdown. No explanations.\n"
        . "Generate exactly 10 NEW English placement test multiple-choice questions.\n"
        . "Focus on grammar, vocabulary, prepositions, verb tenses.\n"
        . "Mix difficulty: 3 EASY, 4 MEDIUM, 3 HARD.\n"
        . "User level hint: {$levelHint}\n"
        . "Generation ID: {$nonce}\n\n"
        . "JSON SCHEMA (must match exactly):\n"
        . "{\n"
        . "  \"questions\": [\n"
        . "    {\n"
        . "      \"prompt\": \"string\",\n"
        . "      \"options\": [\"A\",\"B\",\"C\",\"D\"],\n"
        . "      \"correct\": 0,\n"
        . "      \"diff\": \"EASY\"\n"
        . "    }\n"
        . "  ]\n"
        . "}\n"
        . "Rules:\n"
        . "- options must be exactly 4 strings\n"
        . "- correct must be 0..3\n"
        . "- diff must be EASY or MEDIUM or HARD\n";
}

function validate_questions_payload($decoded): array {
    if (!is_array($decoded)) return [];

    $qs = $decoded["questions"] ?? null;
    if (!is_array($qs) || count($qs) !== 10) return [];

    $out = [];
    $allowedDiff = ["EASY", "MEDIUM", "HARD"];

    foreach ($qs as $q) {
        if (!is_array($q)) return [];

        $prompt = isset($q["prompt"]) ? trim((string)$q["prompt"]) : "";
        $options = $q["options"] ?? null;
        $correct = $q["correct"] ?? null;
        $diff = isset($q["diff"]) ? strtoupper(trim((string)$q["diff"])) : "";

        if ($prompt === "") return [];
        if (!is_array($options) || count($options) !== 4) return [];

        $opts = [];
        foreach ($options as $opt) {
            $s = trim((string)$opt);
            if ($s === "") return [];
            $opts[] = $s;
        }

        if (!is_int($correct)) {
            if (is_numeric($correct) && (string)(int)$correct === (string)$correct) {
                $correct = (int)$correct;
            } else {
                return [];
            }
        }
        if ($correct < 0 || $correct > 3) return [];
        if (!in_array($diff, $allowedDiff, true)) return [];

        $out[] = [
            "prompt" => $prompt,
            "options" => $opts,
            "correct" => $correct,
            "diff" => $diff,
        ];
    }

    return $out;
}

function test_signature(array $questions): string {
    $parts = [];
    foreach ($questions as $q) {
        $parts[] = mb_strtolower($q["prompt"] . "||" . implode("||", $q["options"]), "UTF-8");
    }
    return hash("sha256", implode("\n", $parts));
}

function call_ai_once(string $apiKey, string $levelHint, string $nonce, array &$debug): array {
    $system = "You are a strict JSON generator. Output ONLY JSON. No markdown. No extra text.";
    $prompt = build_prompt($levelHint, $nonce);

    $payload = [
        "model" => GROQ_MODEL,
        "temperature" => 0.9,
        "top_p" => 0.95,
        "max_tokens" => 1400,
        "frequency_penalty" => 0.4,
        "presence_penalty" => 0.3,
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
            "Content-Type: application/json; charset=utf-8",
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 45,
    ]);

    $raw = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $debug["http"] = $http;
    $debug["curl_errno"] = $errno;
    $debug["curl_error"] = $err ? $err : null;

    if ($errno !== 0 || !$raw || $http < 200 || $http >= 300) return [];

    $data = json_decode($raw, true);
    if (!is_array($data)) return [];

    $content = $data["choices"][0]["message"]["content"] ?? "";
    if (!is_string($content) || trim($content) === "") return [];

    $decoded = json_decode($content, true);
    if (!is_array($decoded)) return [];

    return validate_questions_payload($decoded);
}

// ---- generation with anti-repeat ----

$questions = [];
$used = "fixed_fallback";
$debug = [
    "attempts" => 0,
    "http" => null,
    "curl_errno" => null,
    "curl_error" => null,
    "nonce" => null,
    "repeat_blocked" => false,
];

$history = $_SESSION["placement_history"] ?? [];
if (!is_array($history)) $history = [];
$history = array_values(array_filter($history, fn($x) => is_string($x) && $x !== ""));
$history = array_slice($history, -5);

$MAX_ATTEMPTS = 3;

if ($apiKey && trim($apiKey) !== "") {
    for ($attempt = 1; $attempt <= $MAX_ATTEMPTS; $attempt++) {
        $debug["attempts"] = $attempt;

        $nonce = bin2hex(random_bytes(6));
        $debug["nonce"] = $nonce;

        $candidate = call_ai_once($apiKey, $levelHint, $nonce, $debug);
        if (count($candidate) !== 10) continue;

        $sig = test_signature($candidate);

        if (in_array($sig, $history, true)) {
            $debug["repeat_blocked"] = true;
            continue;
        }

        $questions = $candidate;
        $used = "ai";

        $history[] = $sig;
        $history = array_slice($history, -5);
        $_SESSION["placement_history"] = $history;
        break;
    }
}

if (count($questions) !== 10) {
    $questions = fixed_test();
    $used = "fixed_fallback";
}

$_SESSION["placement_questions"] = $questions;
session_write_close();

echo json_encode([
    "ok" => true,
    "count" => count($questions),
    "used" => $used,
    "debug" => $debug,
], JSON_UNESCAPED_UNICODE);


