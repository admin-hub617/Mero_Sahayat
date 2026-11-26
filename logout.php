<?php
/**
 * Mero Sahayata - Admin Logout
 */
require_once __DIR__ . '/../config/db.php';
startSessionSafely();
session_unset();
session_destroy();
header('Location: login.php');
exit;
