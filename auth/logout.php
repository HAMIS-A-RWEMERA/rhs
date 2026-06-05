<?php
require_once __DIR__ . '/../config/helpers.php';
start_secure_session();

session_unset();
session_destroy();

header("Location: ../auth/login.php");
exit();
