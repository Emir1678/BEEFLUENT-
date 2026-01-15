<?php
// inc/user_repo.php
declare(strict_types=1);

require_once __DIR__ . "/db.php";

/**
 * Matches your Java hashing:
 * MessageDigest SHA-256 -> hex string (64 chars)
 */
function hash_password_sha256(string $raw): string {
    return hash("sha256", $raw);
}

function find_user_by_email(string $email): ?array {
    global $conn;
    $email = strtolower(trim($email));

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

function find_user_by_id(int $id): ?array {
    global $conn;

    $stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

/** Like UserStore.authenticate(email, pass) */
function authenticate_user(string $email, string $rawPassword): ?array {
    $u = find_user_by_email($email);
    if (!$u) return null;

    $hash = hash_password_sha256($rawPassword);
    if ($hash !== $u["password_hash"]) return null;

    return [
    "id" => (int)$u["id"],
    "name" => $u["name"],
    "email" => $u["email"],
    "level" => $u["language_level"],
    "avatar" => $u["avatar_path"],
    "role" => $u["role"] ?? "user"
];

}

/** Like UserStore.saveNew(name,email,password) */
function create_user(string $name, string $email, string $rawPassword): array {
    global $conn;

    $name = trim($name);
    $email = strtolower(trim($email));

    if ($name === "" || $email === "" || $rawPassword === "") {
        throw new RuntimeException("Missing required fields.");
    }

    if (find_user_by_email($email)) {
        throw new RuntimeException("Email already registered.");
    }

    $hash = hash_password_sha256($rawPassword);

    $stmt = $conn->prepare(
        "INSERT INTO users (name, email, password_hash, language_level, avatar_path)
         VALUES (?,?,?,NULL,NULL)"
    );
    $stmt->bind_param("sss", $name, $email, $hash);
    $stmt->execute();

    $id = (int)$conn->insert_id;

    return [
    "id" => $id,
    "name" => $name,
    "email" => $email,
    "level" => null,
    "avatar" => null,
    "role" => "user"
];

}

/** Like UserStore.updateUserLevel(userId, newLevel) */
function update_user_level(int $userId, string $newLevel): void {
    global $conn;

    $stmt = $conn->prepare("UPDATE users SET language_level=? WHERE id=?");
    $stmt->bind_param("si", $newLevel, $userId);
    $stmt->execute();
}

/** Forgot password: create token + expiry */
function create_reset_token(int $userId): string {
    global $conn;

    $token = bin2hex(random_bytes(24)); // 48 chars
    $expires = (new DateTime("+30 minutes"))->format("Y-m-d H:i:s");

    $stmt = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE id=?");
    $stmt->bind_param("ssi", $token, $expires, $userId);
    $stmt->execute();

    return $token;
}

function find_user_by_reset_token(string $token): ?array {
    global $conn;

    $token = trim($token);
    if ($token === "") return null;

    $stmt = $conn->prepare("SELECT * FROM users WHERE reset_token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) return null;

    if (empty($row["reset_expires"])) return null;

    $now = new DateTime();
    $exp = new DateTime($row["reset_expires"]);
    if ($exp < $now) return null;

    return $row;
}

function update_password_by_user_id(int $userId, string $newRawPassword): void {
    global $conn;

    $hash = hash_password_sha256($newRawPassword);

    $stmt = $conn->prepare(
        "UPDATE users
         SET password_hash=?, reset_token=NULL, reset_expires=NULL
         WHERE id=?"
    );
    $stmt->bind_param("si", $hash, $userId);
    $stmt->execute();
}

function update_user_profile(int $userId, string $name, ?string $avatarPath): void {
    global $conn;

    $name = trim($name);
    if ($name === "") {
        throw new RuntimeException("Name cannot be empty.");
    }

    // normalize avatar
    $avatarPath = $avatarPath !== null ? trim($avatarPath) : null;
    if ($avatarPath === "") $avatarPath = null;

    $stmt = $conn->prepare("UPDATE users SET name=?, avatar_path=? WHERE id=?");
    $stmt->bind_param("ssi", $name, $avatarPath, $userId);
    $stmt->execute();
}

function get_user_public(int $userId): ?array {
    $u = find_user_by_id($userId);
    if (!$u) return null;

    return [
        "id" => (int)$u["id"],
        "name" => $u["name"],
        "email" => $u["email"],
        "level" => $u["language_level"],
        "avatar" => $u["avatar_path"],
        "created_at" => $u["created_at"] ?? null,
    ];
}
