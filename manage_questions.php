<?php
/**
 * Mero Sahayata - Dynamic Wizard Questionnaire Manager
 * Allows administrator to configure what questions the wizard asks citizens for each service.
 */

require_once __DIR__ . '/../config/db.php';
startSessionSafely();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = Database::getConnection();
$message = '';
$messageType = 'success';

// Handle Delete Question
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmtDel = $db->prepare("DELETE FROM service_questions WHERE id = ?");
    $stmtDel->execute([$delId]);
    $message = "प्रश्न सफलतापूर्वक हटाइयो!";
}

// Handle Add Question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_question') {
    $serviceId = (int)($_POST['service_id'] ?? 0);
    $questionKey = trim($_POST['question_key'] ?? '');
    $textNp = trim($_POST['text_np'] ?? '');
    $textEn = trim($_POST['text_en'] ?? '');
    $inputType = trim($_POST['input_type'] ?? 'yes_no');
    $optionsJson = !empty($_POST['options_json']) ? trim($_POST['options_json']) : null;
    $priority = (int)($_POST['priority'] ?? 1);

    if ($serviceId > 0 && !empty($questionKey) && !empty($textNp)) {
        try {
            $stmt = $db->prepare("INSERT INTO service_questions (service_id, question_key, question_text_en, question_text_np, input_type, options_json, priority) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$serviceId, $questionKey, $textEn, $textNp, $inputType, $optionsJson, $priority]);
            $message = "नयाँ विजार्ड प्रश्न सफलतापूर्वक थपियो!";
        } catch (Exception $e) {
            $message = "त्रुटि: " . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = "कृपया आवश्यक विवरण भर्नुहोस्।";
        $messageType = 'error';
    }
}

$services = $db->query("SELECT id, name_np, name_en FROM services ORDER BY id ASC")->fetchAll();

$questions = $db->query("SELECT q.*, s.name_np as service_name, s.name_en as service_name_en
                        FROM service_questions q
                        JOIN services s ON q.service_id = s.id
                        ORDER BY q.service_id ASC, q.priority ASC")->fetchAll();

$activeTab = 'questions';
?>
<!DOCTYPE html>
<html lang="ne">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>विजार्ड प्रश्न व्यवस्थापक - Mero Sahayata Questions Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Mukta:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background:#f8fafc;">

<?php include __DIR__ . '/includes/admin_nav.php'; ?>

<main class="main-wrapper" style="padding-top:25px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
        <div>
            <h2 style="font-size:1.6rem;font-weight:800;color:var(--primary);margin-bottom:4px;">
                <i class="bi bi-question-circle-fill"></i> <span data-np="सेवा सोधपुछ विजार्ड प्रश्न व्यवस्थापक" data-en="Wizard Questionnaire Manager">सेवा सोधपुछ विजार्ड प्रश्न व्यवस्थापक</span>
            </h2>
            <p style="color:var(--text-muted);font-size:0.9rem;" data-np="नागरिकले सेवा सुरु गर्दा प्रणालीले सोध्ने चरणबद्ध प्रश्नहरू यहाँबाट नियन्त्रण गरिन्छ।" data-en="Configure the dynamic step-by-step questions presented to citizens during service evaluation.">
                नागरिकले सेवा सुरु गर्दा प्रणालीले सोध्ने चरणबद्ध प्रश्नहरू यहाँबाट नियन्त्रण गरिन्छ।
            </p>
        </div>
    </div>

    <?php if ($message): ?>
        <div style="padding:14px;background:<?= $messageType === 'success' ? '#ecfdf5' : '#fee2e2' ?>;color:<?= $messageType === 'success' ? '#065f46' : '#991b1b' ?>;border-radius:8px;margin-bottom:20px;font-weight:600;">
            <i class="bi bi-info-circle"></i> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Questions Table -->
    <div style="background:white;padding:24px;border-radius:var(--radius-md);border:1px solid var(--border-color);box-shadow:var(--shadow-sm);margin-bottom:30px;overflow-x:auto;">
        <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:15px;">
            <span data-np="हाल सक्रिय विजार्ड प्रश्नहरू" data-en="Active Wizard Questions">हाल सक्रिय विजार्ड प्रश्नहरू</span> 
            <small style="color:var(--text-muted);font-size:0.9rem;">(Total: <?= count($questions) ?>)</small>
        </h3>
        <table class="custom-table" style="width:100%;border-collapse:collapse;font-size:0.9rem;">
            <thead>
                <tr style="border-bottom:2px solid #e2e8f0;text-align:left;background:#f8fafc;">
                    <th style="padding:10px;" data-np="सम्बन्धित सेवा" data-en="Target Service">सम्बन्धित सेवा</th>
                    <th style="padding:10px;text-align:center;" data-np="क्रम" data-en="Priority">क्रम</th>
                    <th style="padding:10px;" data-np="फिल्ड कोड (Key)" data-en="Field Key">फिल्ड कोड (Key)</th>
                    <th style="padding:10px;" data-np="प्रकार" data-en="Input Type">प्रकार</th>
                    <th style="padding:10px;" data-np="प्रश्न विवरण (नेपाली / English)" data-en="Question Text">प्रश्न विवरण (नेपाली / English)</th>
                    <th style="padding:10px;text-align:center;" data-np="कार्य" data-en="Action">कार्य</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questions as $q): ?>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:10px;font-weight:700;color:var(--primary);">
                            <?= htmlspecialchars($q['service_name']) ?><br>
                            <small style="color:var(--text-muted);font-weight:400;"><?= htmlspecialchars($q['service_name_en'] ?? '') ?></small>
                        </td>
                        <td style="padding:10px;text-align:center;">
                            <span style="background:#f1f5f9;padding:2px 8px;border-radius:4px;font-weight:700;"><?= $q['priority'] ?></span>
                        </td>
                        <td style="padding:10px;"><code><?= htmlspecialchars($q['question_key']) ?></code></td>
                        <td style="padding:10px;">
                            <span style="background:#eff6ff;color:#1e40af;padding:2px 8px;border-radius:4px;font-weight:600;font-size:0.8rem;">
                                <?= htmlspecialchars($q['input_type']) ?>
                            </span>
                        </td>
                        <td style="padding:10px;">
                            <strong><?= htmlspecialchars($q['question_text_np']) ?></strong>
                            <?php if (!empty($q['question_text_en'])): ?>
                                <br><small style="color:var(--text-muted);"><?= htmlspecialchars($q['question_text_en']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td style="padding:10px;text-align:center;">
                            <a href="manage_questions.php?delete=<?= $q['id'] ?>" onclick="return confirm('के तपाईं यो प्रश्न हटाउन निश्चित हुनुहुन्छ? / Are you sure?');" style="color:#dc2626;font-size:1.1rem;" title="हटाउनुहोस्">
                                <i class="bi bi-trash-fill"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Question Form -->
    <div style="background:white;padding:24px;border-radius:var(--radius-md);border:1px solid var(--border-color);box-shadow:var(--shadow-sm);">
        <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:15px;">
            <i class="bi bi-patch-question-fill" style="color:var(--primary);"></i> 
            <span data-np="नयाँ विजार्ड प्रश्न थप्नुहोस्" data-en="Add Wizard Question">नयाँ विजार्ड प्रश्न थप्नुहोस्</span>
        </h3>
        <form method="POST" action="manage_questions.php" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:15px;">
            <input type="hidden" name="action" value="add_question">

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="कुन सेवाको लागि? (Target Service)" data-en="Select Target Service">कुन सेवाको लागि? (Target Service)</label>
                <select name="service_id" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;">
                    <option value="" data-np="-- सेवा छान्नुहोस् --" data-en="-- Select Service --">-- सेवा छान्नुहोस् --</option>
                    <?php foreach ($services as $svc): ?>
                        <option value="<?= $svc['id'] ?>"><?= htmlspecialchars($svc['name_np']) ?> (<?= htmlspecialchars($svc['name_en']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="फिल्ड कोड (Question Key: has_citizenship, applicant_age)" data-en="Field Key (e.g. has_citizenship, applicant_age)">फिल्ड कोड (Question Key)</label>
                <input type="text" name="question_key" placeholder="has_citizenship" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;"
                       data-placeholder-np="उदा: has_citizenship" data-placeholder-en="e.g. has_citizenship">
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="इनपुट प्रकार (Input Type)" data-en="Input Type">इनपुट प्रकार (Input Type)</label>
                <select name="input_type" style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;">
                    <option value="yes_no" data-np="हो / होइन बटन (Yes / No Buttons)" data-en="Yes / No (Buttons)">हो / होइन बटन (Yes / No Buttons)</option>
                    <option value="select" data-np="ड्रपडाउन छनौट (Dropdown Select)" data-en="Dropdown Select">ड्रपडाउन छनौट (Dropdown Select)</option>
                    <option value="number" data-np="अंक / उमेर (Number / Age)" data-en="Number / Age Input">अंक / उमेर (Number / Age)</option>
                </select>
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="प्रश्न क्रम (Step Priority: 1, 2, 3...)" data-en="Step Priority (1, 2, 3...)">प्रश्न क्रम (Step Priority: 1, 2, 3...)</label>
                <input type="number" name="priority" value="1" min="1" max="20" style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;">
            </div>

            <div style="grid-column: 1 / -1;">
                <label style="font-size:0.85rem;font-weight:700;" data-np="प्रश्न (नेपालीमा - Question in Nepali)" data-en="Question Text (Nepali)">प्रश्न (नेपालीमा - Question in Nepali)</label>
                <input type="text" name="text_np" placeholder="उदा: के तपाईंसँग नेपाली नागरिकताको प्रमाणपत्र छ?" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;"
                       data-placeholder-np="उदा: के तपाईंसँग नेपाली नागरिकताको प्रमाणपत्र छ?" data-placeholder-en="e.g. Do you have a Nepali citizenship certificate?">
            </div>

            <div style="grid-column: 1 / -1;">
                <label style="font-size:0.85rem;font-weight:700;" data-np="Question Text (English)" data-en="Question Text (English)">Question Text (English)</label>
                <input type="text" name="text_en" placeholder="e.g. Do you possess a Nepali Citizenship Certificate?" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;"
                       data-placeholder-np="उदा: Do you possess a Nepali Citizenship Certificate?" data-placeholder-en="e.g. Do you possess a Nepali Citizenship Certificate?">
            </div>

            <div style="grid-column: 1 / -1;">
                <label style="font-size:0.85rem;font-weight:700;" data-np="Dropdown Options JSON (ड्रपडाउन छनौट भएको खण्डमा मात्र, नत्र खाली छोड्नुहोस्)" data-en="Dropdown Options JSON (Only for Dropdown Select, otherwise leave empty)">Dropdown Options JSON (ड्रपडाउन छनौट भएको खण्डमा मात्र)</label>
                <textarea name="options_json" rows="2" placeholder='{"first_time": "पहिलो पटक / First Time", "renewal": "नवीकरण / Renewal"}' style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;font-family:monospace;font-size:0.85rem;"></textarea>
            </div>

            <div style="grid-column: 1 / -1;">
                <button type="submit" class="btn-action btn-action-primary" style="padding:10px 24px;">
                    <i class="bi bi-plus-circle"></i> <span data-np="प्रश्न सुरक्षित गर्नुहोस्" data-en="Save Question">प्रश्न सुरक्षित गर्नुहोस्</span>
                </button>
            </div>
        </form>
    </div>
</main>

<!-- Admin Interactive Script for Translation, Font Scaling and Contrast -->
<script src="assets/admin.js"></script>

</body>
</html>
