<?php
/**
 * Mero Sahayata - Dynamic Wizard API
 * Provides dynamic questions, options, and prerequisite metadata for a chosen service.
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$serviceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;
$serviceCode = isset($_GET['code']) ? trim($_GET['code']) : '';

if ($serviceId <= 0 && empty($serviceCode)) {
    jsonResponse(['success' => false, 'message' => 'Please provide service_id or code.'], 400);
}

$db = Database::getConnection();

// 1. Fetch Service Info
if ($serviceId > 0) {
    $stmt = $db->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$serviceId]);
} else {
    $stmt = $db->prepare("SELECT * FROM services WHERE code = ?");
    $stmt->execute([$serviceCode]);
}
$service = $stmt->fetch();

if (!$service) {
    jsonResponse(['success' => false, 'message' => 'Service not found.'], 404);
}

$sid = $service['id'];

// 2. Fetch Prerequisites
$stmtPrereq = $db->prepare("SELECT sp.*, s.code as prereq_code, s.name_en as prereq_name_en, s.name_np as prereq_name_np, s.icon as prereq_icon 
                           FROM service_prerequisites sp
                           JOIN services s ON sp.prerequisite_service_id = s.id
                           WHERE sp.service_id = ?");
$stmtPrereq->execute([$sid]);
$prerequisites = $stmtPrereq->fetchAll();

// 3. Fetch Dynamic Questions
$stmtQ = $db->prepare("SELECT id, question_key, question_text_en, question_text_np, input_type, options_json, priority
                      FROM service_questions
                      WHERE service_id = ?
                      ORDER BY priority ASC");
$stmtQ->execute([$sid]);
$questions = $stmtQ->fetchAll();

foreach ($questions as &$q) {
    if (!empty($q['options_json'])) {
        $q['options'] = json_decode($q['options_json'], true);
    } else {
        $q['options'] = null;
    }
}

jsonResponse([
    'success' => true,
    'service' => $service,
    'prerequisites' => $prerequisites,
    'questions' => $questions
]);
