<?php
// inc/init.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}

function redirect(string $to): void {
    header("Location: $to");
    exit;
}

function require_login(): void {
    if (!isset($_SESSION["user"])) {
        redirect("login.php");
    }
}

function current_user(): ?array {
    return $_SESSION["user"] ?? null;
}
