<?php
require_once __DIR__ . "/inc/db.php";
require_once __DIR__ . "/inc/user_repo.php";

$email = "admin@gmail.com";
$pass  = "123456";

$u = find_user_by_email($email);

echo "<pre>";
if (!$u) {
    die("USER NOT FOUND");
}

echo "DB HASH:\n";
echo $u["password_hash"] . "\n\n";

echo "PHP HASH:\n";
echo hash("sha256", $pass) . "\n\n";

echo "MATCH? ";
echo hash("sha256", $pass) === $u["password_hash"] ? "YES" : "NO";
echo "</pre>";
