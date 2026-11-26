<?php
/**
 * Mero Sahayata - User Feedback API
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$rawInput = file_get_contents('php://input');
$body = json_decode($rawInput, true);

$serviceId = isset($body['service_id']) ? (int)$body['service_id'] : (isset($_POST['service_id']) ? (int)$_POST['service_id'] : null);
$userName = isset($body['user_name']) ? sanitize($body['user_name']) : (isset($_POST['user_name']) ? sanitize($_POST['user_name']) : 'अनाम नागरिक');
$phone = isset($body['phone']) ? sanitize($body['phone']) : (isset($_POST['phone']) ? sanitize($_POST['phone']) : '');
$rating = isset($body['rating']) ? (int)$body['rating'] : (isset($_POST['rating']) ? (int)$_POST['rating'] : 5);
$comments = isset($body['comments']) ? sanitize($body['comments']) : (isset($_POST['comments']) ? sanitize($_POST['comments']) : '');

if ($rating < 1 || $rating > 5) {
    $rating = 5;
}

$db = Database::getConnection();

try {
    $stmt = $db->prepare("INSERT INTO feedback (service_id, user_name, phone, rating, comments) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$serviceId, $userName, $phone, $rating, $comments]);

    jsonResponse([
        'success' => true,
        'message_en' => 'Thank you for your valuable feedback!',
        'message_np' => 'तपाईंको अमूल्य प्रतिक्रियाको लागि धेरै धन्यवाद!'
    ]);
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'error' => 'Failed to save feedback: ' . $e->getMessage()
    ], 500);
}
