<?php
/**
 * Mero Sahayata - Rule-Based Decision Engine API
 * Evaluates citizen inputs against conditional rules, prerequisite graph,
 * dynamic documents checklist, and office routing.
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

// Accept both form-encoded and JSON body
$rawInput = file_get_contents('php://input');
$body = json_decode($rawInput, true);

$serviceId = 0;
$answers = [];

if (is_array($body) && isset($body['service_id'])) {
    $serviceId = (int)$body['service_id'];
    $answers = isset($body['answers']) && is_array($body['answers']) ? $body['answers'] : [];
} else {
    $serviceId = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;
    $answers = isset($_POST['answers']) && is_array($_POST['answers']) ? $_POST['answers'] : [];
}

if ($serviceId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Valid service_id is required.'], 400);
}

$db = Database::getConnection();

// 1. Fetch Target Service Info
$stmt = $db->prepare("SELECT * FROM services WHERE id = ?");
$stmt->execute([$serviceId]);
$service = $stmt->fetch();

if (!$service) {
    jsonResponse(['success' => false, 'message' => 'Service not found.'], 404);
}

// 2. Fetch Rules for this service
$stmtRules = $db->prepare("SELECT * FROM service_rules WHERE service_id = ?");
$stmtRules->execute([$serviceId]);
$rules = $stmtRules->fetchAll();

// 3. Rule Evaluation Loop
$blockedByRule = null;
$specialInstructions = [];

foreach ($rules as $rule) {
    $field = $rule['condition_field'];
    $operator = $rule['operator'];
    $expectedVal = strtolower(trim($rule['condition_value']));
    
    if (!isset($answers[$field])) {
        continue;
    }
    
    $userVal = strtolower(trim((string)$answers[$field]));
    $ruleTriggered = false;

    switch ($operator) {
        case '==':
            $ruleTriggered = ($userVal === $expectedVal);
            break;
        case '!=':
            $ruleTriggered = ($userVal !== $expectedVal);
            break;
        case '<':
            $ruleTriggered = (is_numeric($userVal) && is_numeric($expectedVal) && ((float)$userVal < (float)$expectedVal));
            break;
        case '>':
            $ruleTriggered = (is_numeric($userVal) && is_numeric($expectedVal) && ((float)$userVal > (float)$expectedVal));
            break;
    }

    if ($ruleTriggered) {
        if ($rule['action_type'] === 'BLOCK_AND_REDIRECT') {
            $blockedByRule = $rule;
            break; // Stop evaluation on blocking rule
        } elseif ($rule['action_type'] === 'SPECIAL_INSTRUCTION') {
            $specialInstructions[] = [
                'title_en' => $rule['result_title_en'],
                'title_np' => $rule['result_title_np'],
                'message_en' => $rule['result_message_en'],
                'message_np' => $rule['result_message_np']
            ];
        }
    }
}

// 4. Handle Blocking Rules (Prerequisite redirects or ineligibility)
if ($blockedByRule !== null) {
    $targetService = null;
    if (!empty($blockedByRule['target_service_id'])) {
        $stmtTarget = $db->prepare("SELECT id, code, name_en, name_np, icon FROM services WHERE id = ?");
        $stmtTarget->execute([$blockedByRule['target_service_id']]);
        $targetService = $stmtTarget->fetch();
    }

    // Generate Audio Assistance Script
    $audioScriptNp = "ध्यान दिनुहोस्। " . $blockedByRule['result_title_np'] . "। " . $blockedByRule['result_message_np'];
    $audioScriptEn = "Attention. " . $blockedByRule['result_title_en'] . ". " . $blockedByRule['result_message_en'];

    jsonResponse([
        'success' => true,
        'status' => 'BLOCKED',
        'decision' => [
            'is_eligible' => false,
            'reason_code' => $blockedByRule['rule_code'],
            'title_en' => $blockedByRule['result_title_en'],
            'title_np' => $blockedByRule['result_title_np'],
            'message_en' => $blockedByRule['result_message_en'],
            'message_np' => $blockedByRule['result_message_np'],
            'recommended_service' => $targetService
        ],
        'audio_assistance' => [
            'script_np' => $audioScriptNp,
            'script_en' => $audioScriptEn
        ]
    ]);
}

// 5. User is ELIGIBLE -> Build Dynamic Document Checklist
$stmtDocs = $db->prepare("SELECT sd.*, d.code as doc_code, d.name_en, d.name_np, d.issuing_authority_en, d.issuing_authority_np, d.description
                         FROM service_documents sd
                         JOIN documents d ON sd.document_id = d.id
                         WHERE sd.service_id = ?");
$stmtDocs->execute([$serviceId]);
$allDocs = $stmtDocs->fetchAll();

$checklist = [];
foreach ($allDocs as $doc) {
    $include = false;
    $conditionReason = null;

    if ($doc['is_mandatory'] == 1 && empty($doc['condition_key'])) {
        $include = true;
    } elseif (!empty($doc['condition_key'])) {
        $key = $doc['condition_key'];
        $val = $doc['condition_value'];
        if (isset($answers[$key]) && strtolower(trim((string)$answers[$key])) === strtolower(trim($val))) {
            $include = true;
            $conditionReason = "Applicable based on your answer: {$key} = {$val}";
        }
    }

    if ($include) {
        $checklist[] = [
            'document_id' => $doc['document_id'],
            'doc_code' => $doc['doc_code'],
            'name_en' => $doc['name_en'],
            'name_np' => $doc['name_np'],
            'issuing_authority_en' => $doc['issuing_authority_en'],
            'issuing_authority_np' => $doc['issuing_authority_np'],
            'notes_en' => $doc['notes_en'],
            'notes_np' => $doc['notes_np'],
            'is_mandatory' => (bool)$doc['is_mandatory'],
            'condition_reason' => $conditionReason
        ];
    }
}

// 6. Find Nearest Office Recommendation
$userDistrict = isset($answers['district']) ? trim($answers['district']) : '';
$recommendedOffice = null;

if (!empty($userDistrict)) {
    $stmtOffice = $db->prepare("SELECT * FROM offices WHERE district LIKE ? AND (service_category = ? OR service_category = 'Identity') LIMIT 1");
    $stmtOffice->execute(["%{$userDistrict}%", $service['category']]);
    $recommendedOffice = $stmtOffice->fetch();
}

// Fallback to central office
if (!$recommendedOffice) {
    $stmtOffice = $db->prepare("SELECT * FROM offices WHERE service_category = ? LIMIT 1");
    $stmtOffice->execute([$service['category']]);
    $recommendedOffice = $stmtOffice->fetch();
}

// 7. Generate Step-by-Step Action Plan
$steps = [
    [
        'step_number' => 1,
        'title_en' => 'Prepare Physical Documents',
        'title_np' => 'कागजातहरू संकलन र तयारी',
        'desc_en' => 'Collect and arrange all the required documents and photographs checked in your personalized checklist below.',
        'desc_np' => 'तल दिइएको चेकलिस्ट अनुसार सक्कल कागजात, प्रतिलिपिहरू र पासपोर्ट साइजको फोटो सुरक्षित साथ तयार गर्नुहोस्।'
    ],
    [
        'step_number' => 2,
        'title_en' => 'Online Pre-Registration & Appointment',
        'title_np' => 'अनलाइन फारम दर्ता र टोकन',
        'desc_en' => 'Visit the official government portal (' . ($service['online_portal_url'] ?: 'gov.np') . ') and fill your pre-enrollment form.',
        'desc_np' => 'आधिकारिक अनलाइन प्रणालीमा गएर आफ्नो विवरण भरी आवेदन मिति र समय (Appointment) सुरक्षित गर्नुहोस्।'
    ],
    [
        'step_number' => 3,
        'title_en' => 'Biometric Verification & Fee Payment',
        'title_np' => 'कार्यालयमा उपस्थित र बायोमेट्रिक प्रमाणीकरण',
        'desc_en' => 'Visit the designated office with physical documents on the scheduled date for biometric (fingerprint and photo) capture and fee deposit.',
        'desc_np' => 'तोकिएको दिन र समयमा कागजात लिएर कार्यालयमा उपस्थित भई बायोमेट्रिक (फोटो, औंठाछाप) दिनुहोस् र तोकिएको दस्तुर बुझाउनुहोस्।'
    ],
    [
        'step_number' => 4,
        'title_en' => 'Collection & SMS Verification',
        'title_np' => 'कार्ड वा प्रमाणपत्र संकलन',
        'desc_en' => 'You will receive an SMS when your document is ready for collection within the estimated timeline (' . $service['estimated_days'] . ').',
        'desc_np' => 'कागजात तयार भएपछि मोबाइलमा म्यासेज आउनेछ। रसिद लिएर सम्बन्धित कार्यालयबाट संकलन गर्नुहोस्।'
    ]
];

// 8. Build Natural Audio Assistance Narration
$audioNp = "बधाई छ! तपाईं " . $service['name_np'] . " को लागि योग्य हुनुहुन्छ। " .
           "यस सेवाका लागि लाग्ने शुल्क " . $service['standard_fee'] . " हो, र समय लगभग " . $service['estimated_days'] . " लाग्नेछ। " .
           "तपाईंले जम्मा " . count($checklist) . " वटा कागजातहरू तयार गर्नुपर्नेछ। " .
           ($recommendedOffice ? "तपाईंको नजिकको सेवा केन्द्र " . $recommendedOffice['name_np'] . " (" . $recommendedOffice['address_np'] . ") हो।" : "");

$audioEn = "Congratulations! You are eligible to apply for " . $service['name_en'] . ". " .
           "Standard fee is " . $service['standard_fee'] . ", estimated processing time is " . $service['estimated_days'] . ". " .
           "You will need " . count($checklist) . " documents as shown in your checklist.";

jsonResponse([
    'success' => true,
    'status' => 'ELIGIBLE',
    'service' => $service,
    'decision' => [
        'is_eligible' => true,
        'title_en' => 'Eligible for Service Guidance',
        'title_np' => 'तपाईं सेवा प्रक्रिया सुरु गर्न योग्य हुनुहुन्छ',
        'special_instructions' => $specialInstructions
    ],
    'checklist' => $checklist,
    'recommended_office' => $recommendedOffice,
    'steps' => $steps,
    'audio_assistance' => [
        'script_np' => $audioNp,
        'script_en' => $audioEn
    ]
]);
