<?php
/**
 * Mero Sahayata - Rule-Based Engine Manager (Knowledge Base CRUD)
 * Allows administrator to define dynamic conditions and inference rules without touching code.
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

// Handle Delete Rule
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmtDel = $db->prepare("DELETE FROM service_rules WHERE id = ?");
    $stmtDel->execute([$delId]);
    $message = "नियम (Rule) सफलतापूर्वक हटाइयो!";
}

// Handle Add Rule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_rule') {
    $serviceId = (int)($_POST['service_id'] ?? 0);
    $ruleCode = strtoupper(trim($_POST['rule_code'] ?? ''));
    $conditionField = trim($_POST['condition_field'] ?? '');
    $operator = trim($_POST['operator'] ?? '==');
    $conditionValue = trim($_POST['condition_value'] ?? '');
    $actionType = trim($_POST['action_type'] ?? 'BLOCK_AND_REDIRECT');
    $targetServiceId = !empty($_POST['target_service_id']) ? (int)$_POST['target_service_id'] : null;
    $titleEn = trim($_POST['title_en'] ?? '');
    $titleNp = trim($_POST['title_np'] ?? '');
    $messageEn = trim($_POST['message_en'] ?? '');
    $messageNp = trim($_POST['message_np'] ?? '');

    if ($serviceId > 0 && !empty($ruleCode) && !empty($conditionField)) {
        try {
            $stmt = $db->prepare("INSERT INTO service_rules (service_id, rule_code, condition_field, operator, condition_value, action_type, target_service_id, result_title_en, result_title_np, result_message_en, result_message_np) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$serviceId, $ruleCode, $conditionField, $operator, $conditionValue, $actionType, $targetServiceId, $titleEn, $titleNp, $messageEn, $messageNp]);
            $message = "नयाँ निर्णय नियम (Rule) सफलतापूर्वक थपियो!";
        } catch (Exception $e) {
            $message = "त्रुटि: " . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = "कृपया आवश्यक विवरणहरू भर्नुहोस्।";
        $messageType = 'error';
    }
}

// Fetch all services for dropdowns
$services = $db->query("SELECT id, name_np, name_en FROM services ORDER BY id ASC")->fetchAll();

// Fetch all active rules
$rules = $db->query("SELECT r.*, s.name_np as service_name_np, t.name_np as target_name_np
                     FROM service_rules r
                     JOIN services s ON r.service_id = s.id
                     LEFT JOIN services t ON r.target_service_id = t.id
                     ORDER BY r.service_id ASC, r.id ASC")->fetchAll();

$activeTab = 'rules';
?>
<!DOCTYPE html>
<html lang="ne">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>निर्णय नियम व्यवस्थापक - Mero Sahayata Rule Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Mukta:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .rule-badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; }
        .rule-block { background: #fee2e2; color: #991b1b; }
        .rule-special { background: #fef3c7; color: #92400e; }
        .rule-fee { background: #dcfce7; color: #166534; }
    </style>
</head>
<body style="background:#f8fafc;">

<?php include __DIR__ . '/includes/admin_nav.php'; ?>

<main class="main-wrapper" style="padding-top:25px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
        <div>
            <h2 style="font-size:1.6rem;font-weight:800;color:var(--primary);margin-bottom:4px;">
                <i class="bi bi-diagram-2-fill"></i> <span data-np="नियम-आधारित इन्जिन व्यवस्थापक" data-en="Rule Engine Manager">नियम-आधारित इन्जिन व्यवस्थापक</span>
            </h2>
            <p style="color:var(--text-muted);font-size:0.9rem;" data-np="सरकारी सेवाका नियमहरू (उमेर, पूर्वसर्त, कानुनी सीमा) कोड परिवर्तन नगरी यहाँबाट नियन्त्रण गर्नुहोस्।" data-en="Dynamically configure government service rules, eligibility constraints, and prerequisite redirects without modifying source code.">
                सरकारी सेवाका नियमहरू (उमेर, पूर्वसर्त, कानुनी सीमा) कोड परिवर्तन नगरी यहाँबाट नियन्त्रण गर्नुहोस्।
            </p>
        </div>
        <button onclick="document.getElementById('addRuleCard').scrollIntoView({behavior:'smooth'})" class="btn-action btn-action-primary">
            <i class="bi bi-plus-circle"></i> <span data-np="नयाँ नियम थप्नुहोस्" data-en="Add Decision Rule">नयाँ नियम थप्नुहोस्</span>
        </button>
    </div>

    <?php if ($message): ?>
        <div style="padding:14px;background:<?= $messageType === 'success' ? '#ecfdf5' : '#fee2e2' ?>;color:<?= $messageType === 'success' ? '#065f46' : '#991b1b' ?>;border-radius:8px;margin-bottom:20px;font-weight:600;">
            <i class="bi bi-info-circle"></i> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Active Rules Table -->
    <div style="background:white;padding:24px;border-radius:var(--radius-md);border:1px solid var(--border-color);box-shadow:var(--shadow-sm);margin-bottom:30px;overflow-x:auto;">
        <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:15px;color:var(--text-main);">
            <span data-np="हाल सक्रिय निर्णय नियमहरू" data-en="Active Decision Rules">हाल सक्रिय निर्णय नियमहरू</span> (<?= count($rules) ?>)
        </h3>
        <table class="custom-table" style="width:100%;border-collapse:collapse;font-size:0.9rem;">
            <thead>
                <tr style="border-bottom:2px solid #e2e8f0;text-align:left;background:#f8fafc;">
                    <th style="padding:10px;">Rule Code</th>
                    <th style="padding:10px;" data-np="सम्बन्धित सेवा" data-en="Target Service">सम्बन्धित सेवा</th>
                    <th style="padding:10px;" data-np="कन्डिसन र अपरेटर" data-en="Condition & Op">कन्डिसन र अपरेटर</th>
                    <th style="padding:10px;" data-np="मान" data-en="Expected Value">मान</th>
                    <th style="padding:10px;" data-np="एक्सन" data-en="Action">एक्सन</th>
                    <th style="padding:10px;" data-np="असर / रिडाइरेक्ट" data-en="Consequence / Redirect">असर / रिडाइरेक्ट</th>
                    <th style="padding:10px;text-align:center;" data-np="कार्य" data-en="Action">कार्य</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rules as $r): ?>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:10px;"><code><?= htmlspecialchars($r['rule_code']) ?></code></td>
                        <td style="padding:10px;"><strong><?= htmlspecialchars($r['service_name_np']) ?></strong></td>
                        <td style="padding:10px;"><code><?= htmlspecialchars($r['condition_field']) ?> <?= htmlspecialchars($r['operator']) ?></code></td>
                        <td style="padding:10px;"><strong style="color:var(--primary);"><?= htmlspecialchars($r['condition_value']) ?></strong></td>
                        <td style="padding:10px;">
                            <span class="rule-badge <?= $r['action_type'] === 'BLOCK_AND_REDIRECT' ? 'rule-block' : 'rule-special' ?>">
                                <?= htmlspecialchars($r['action_type']) ?>
                            </span>
                        </td>
                        <td style="padding:10px;font-size:0.85rem;">
                            <?php if ($r['target_service_id']): ?>
                                &rarr; <span data-np="पूर्वसेवा" data-en="Prerequisite">पूर्वसेवा</span>: <strong><?= htmlspecialchars($r['target_name_np']) ?></strong>
                            <?php else: ?>
                                <?= htmlspecialchars($r['result_title_np']) ?>
                            <?php endif; ?>
                        </td>
                        <td style="padding:10px;text-align:center;">
                            <a href="manage_rules.php?delete=<?= $r['id'] ?>" onclick="return confirm('के तपाईं यो नियम मेटाउन निश्चित हुनुहुन्छ?');" style="color:#dc2626;text-decoration:none;" title="मेटाउनुहोस्">
                                <i class="bi bi-trash-fill"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Rule Form -->
    <div id="addRuleCard" style="background:white;padding:24px;border-radius:var(--radius-md);border:1px solid var(--border-color);box-shadow:var(--shadow-sm);">
        <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:15px;color:var(--text-main);">
            <i class="bi bi-plus-square-fill" style="color:var(--primary);"></i> <span data-np="नयाँ निर्णय नियम थप्नुहोस्" data-en="Add New Decision Rule">नयाँ निर्णय नियम थप्नुहोस्</span>
        </h3>

        <form method="POST" action="manage_rules.php" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:15px;">
            <input type="hidden" name="action" value="add_rule">

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="कुन सेवाका लागि?" data-en="Target Public Service">कुन सेवाका लागि?</label>
                <select name="service_id" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;">
                    <option value="" data-np="-- सेवा छान्नुहोस् --" data-en="-- Select Service --">-- सेवा छान्नुहोस् --</option>
                    <?php foreach ($services as $svc): ?>
                        <option value="<?= $svc['id'] ?>"><?= htmlspecialchars($svc['name_np']) ?> (<?= htmlspecialchars($svc['name_en']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;">Rule Code (उदा: RULE_PPT_UNDERAGE)</label>
                <input type="text" name="rule_code" placeholder="RULE_PPT_AGE" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;">
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;">Condition Field (उदा: applicant_age, has_nid)</label>
                <input type="text" name="condition_field" placeholder="applicant_age" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;">
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="अपरेटर (Operator)" data-en="Operator">अपरेटर (Operator)</label>
                <select name="operator" style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;">
                    <option value="==" data-np="बराबर (==)" data-en="Equals (==)">बराबर (==)</option>
                    <option value="!=" data-np="बराबर नभएको (!=)" data-en="Not Equals (!=)">बराबर नभएको (!=)</option>
                    <option value="<" data-np="भन्दा कम (<)" data-en="Less Than (<)">भन्दा कम (<)</option>
                    <option value=">" data-np="भन्दा बढी (>)" data-en="Greater Than (>)">भन्दा बढी (>)</option>
                </select>
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="सर्त मान (Expected Value)" data-en="Expected Value">सर्त मान (Expected Value)</label>
                <input type="text" name="condition_value" placeholder="18" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;">
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="एक्सन प्रकार" data-en="Action Type">एक्सन प्रकार</label>
                <select name="action_type" style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;">
                    <option value="BLOCK_AND_REDIRECT" data-np="ब्लक गरी पूर्वसेवामा पठाउने (BLOCK_AND_REDIRECT)" data-en="Block & Redirect to Prerequisite">ब्लक गरी पूर्वसेवामा पठाउने (BLOCK_AND_REDIRECT)</option>
                    <option value="SPECIAL_INSTRUCTION" data-np="विशेष निर्देशन दिने (SPECIAL_INSTRUCTION)" data-en="Show Special Notice">विशेष निर्देशन दिने (SPECIAL_INSTRUCTION)</option>
                    <option value="SET_FEE" data-np="शुल्क निर्धारण गर्ने (SET_FEE)" data-en="Set Dynamic Fee">शुल्क निर्धारण गर्ने (SET_FEE)</option>
                </select>
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="रिडाइरेक्ट गर्ने पूर्वसेवा" data-en="Redirect Service">रिडाइरेक्ट गर्ने पूर्वसेवा</label>
                <select name="target_service_id" style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;">
                    <option value="" data-np="-- कुनै छैन (None) --" data-en="-- None (Direct Ineligible) --">-- कुनै छैन (None) --</option>
                    <?php foreach ($services as $svc): ?>
                        <option value="<?= $svc['id'] ?>"><?= htmlspecialchars($svc['name_np']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="नतिजा शीर्षक (नेपालीमा)" data-en="Result Title (Nepali)">नतिजा शीर्षक (नेपालीमा)</label>
                <input type="text" name="title_np" placeholder="उदा: उमेर नपुगेको" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;">
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="नतिजा शीर्षक (English)" data-en="Result Title (English)">Result Title (English)</label>
                <input type="text" name="title_en" placeholder="Underage Requirement" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;">
            </div>

            <div style="grid-column: 1 / -1;">
                <label style="font-size:0.85rem;font-weight:700;" data-np="निर्देशन सन्देश (नेपालीमा - अडियोमा समेत बज्नेछ)" data-en="Guidance Message (Nepali - also read by TTS)">निर्देशन सन्देश (नेपालीमा - अडियोमा समेत बज्नेछ)</label>
                <textarea name="message_np" rows="2" placeholder="नागरिकलाई देखाइने वा अडियोमा सुनाइने सन्देश..." required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;"></textarea>
            </div>

            <div style="grid-column: 1 / -1;">
                <label style="font-size:0.85rem;font-weight:700;" data-np="Guidance Message (English)" data-en="Guidance Message (English)">Guidance Message (English)</label>
                <textarea name="message_en" rows="2" placeholder="English guidance message for the citizen..." required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;"></textarea>
            </div>

            <div style="grid-column: 1 / -1;">
                <button type="submit" class="btn-action btn-action-primary" style="padding:10px 24px;">
                    <i class="bi bi-check-circle"></i> <span data-np="नयाँ नियम सुरक्षित गर्नुहोस्" data-en="Save Rule">नयाँ नियम सुरक्षित गर्नुहोस्</span>
                </button>
            </div>
        </form>
    </div>
</main>

<script src="assets/admin.js"></script>
</body>
</html>
