<?php
require_once __DIR__ . "/inc/init.php";
session_destroy();
redirect("login.php");
