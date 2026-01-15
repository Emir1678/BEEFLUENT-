<?php
// inc/db.php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "ai_tutor";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
$conn->set_charset("utf8mb4");
