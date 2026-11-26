<?php
/**
 * Mero Sahayata - Prerequisite Dependency Graph Manager
 * Allows administrator to configure which service depends on which base service.
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

// Handle Delete Prerequisite
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmtDel = $db->prepare("DELETE FROM service_prerequisites WHERE id = ?");
    $stmtDel->execute([$delId]);
    $message = "पूर्वसर्त सम्बन्ध (Prerequisite Link) हटाइयो!";
}

// Handle Add Prerequisite
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_prereq') {
    $serviceId = (int)($_POST['service_id'] ?? 0);
    $prereqId = (int)($_POST['prerequisite_service_id'] ?? 0);
    $isMandatory = isset($_POST['is_mandatory']) ? 1 : 0;
    $msgEn = trim($_POST['guidance_message_en'] ?? '');
    $msgNp = trim($_POST['guidance_message_np'] ?? '');

    if ($serviceId > 0 && $prereqId > 0 && $serviceId !== $prereqId) {
        try {
            $stmt = $db->prepare("INSERT INTO service_prerequisites (service_id, prerequisite_service_id, is_mandatory, guidance_message_en, guidance_message_np) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$serviceId, $prereqId, $isMandatory, $msgEn, $msgNp]);
            $message = "नयाँ पूर्वसर्त सम्बन्ध सफलतापूर्वक जोडिएको छ!";
        } catch (Exception $e) {
            $message = "त्रुटि: " . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = "कृपया दुई भिन्न सेवाहरू छान्नुहोस्।";
        $messageType = 'error';
    }
}

$services = $db->query("SELECT id, name_np, name_en FROM services ORDER BY id ASC")->fetchAll();

$prereqs = $db->query("SELECT sp.*, s.name_np as service_name, p.name_np as prereq_name
                      FROM service_prerequisites sp
                      JOIN services s ON sp.service_id = s.id
                      JOIN services p ON sp.prerequisite_service_id = p.id
                      ORDER BY sp.service_id ASC")->fetchAll();

$activeTab = 'prerequisites';
?>
<!DOCTYPE html>
<html lang="ne">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>पूर्वसर्त सम्बन्ध व्यवस्थापक - Mero Sahayata</title>
    <link href="https://fonts.googleapis.com/css2?family=Mukta:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background:#f8fafc;">

<?php include __DIR__ . '/includes/admin_nav.php'; ?>

<main class="main-wrapper" style="padding-top:25px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
        <div>
            <h2 style="font-size:1.6rem;font-weight:800;color:var(--primary);margin-bottom:4px;">
                <i class="bi bi-link-45deg"></i> <span data-np="सेवा पूर्वसर्त सम्बन्ध ग्राफ" data-en="Prerequisite Dependency Graph">सेवा पूर्वसर्त सम्बन्ध ग्राफ</span>
            </h2>
            <p style="color:var(--text-muted);font-size:0.9rem;" data-np="कुनै सेवा प्राप्त गर्न नागरिकसँग कुन आधारभूत सेवा अनिवार्य हुनुपर्छ भन्ने सम्बन्ध यहाँबाट जोड्नुहोस्।" data-en="Configure directed graph relationships defining which foundational documents a service requires.">
                कुनै सेवा प्राप्त गर्न नागरिकसँग कुन आधारभूत सेवा अनिवार्य हुनुपर्छ भन्ने सम्बन्ध यहाँबाट जोड्नुहोस्।
            </p>
        </div>
    </div>

    <?php if ($message): ?>
        <div style="padding:14px;background:<?= $messageType === 'success' ? '#ecfdf5' : '#fee2e2' ?>;color:<?= $messageType === 'success' ? '#065f46' : '#991b1b' ?>;border-radius:8px;margin-bottom:20px;font-weight:600;">
            <i class="bi bi-info-circle"></i> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Prerequisites Matrix Table -->
    <div style="background:white;padding:24px;border-radius:var(--radius-md);border:1px solid var(--border-color);box-shadow:var(--shadow-sm);margin-bottom:30px;overflow-x:auto;">
        <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:15px;" data-np="सक्रिय पूर्वसर्त सम्बन्धहरू" data-en="Active Dependency Links">सक्रिय पूर्वसर्त सम्बन्धहरू</h3>
        <table class="custom-table" style="width:100%;border-collapse:collapse;font-size:0.9rem;">
            <thead>
                <tr style="border-bottom:2px solid #e2e8f0;text-align:left;background:#f8fafc;">
                    <th style="padding:10px;" data-np="लक्ष्य सेवा" data-en="Target Service">लक्ष्य सेवा</th>
                    <th style="padding:10px;" data-np="आवश्यक पूर्वसेवा" data-en="Prerequisite Service">आवश्यक पूर्वसेवा</th>
                    <th style="padding:10px;" data-np="अनिवार्य?" data-en="Mandatory?">अनिवार्य?</th>
                    <th style="padding:10px;" data-np="निर्देशन सन्देश" data-en="Guidance Note">निर्देशन सन्देश</th>
                    <th style="padding:10px;text-align:center;" data-np="कार्य" data-en="Action">कार्य</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prereqs as $p): ?>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:10px;font-weight:700;color:var(--primary);"><?= htmlspecialchars($p['service_name']) ?></td>
                        <td style="padding:10px;"><span style="background:#eff6ff;color:#1e40af;padding:3px 8px;border-radius:4px;font-weight:700;">&larr; <?= htmlspecialchars($p['prereq_name']) ?></span></td>
                        <td style="padding:10px;">
                            <?= $p['is_mandatory'] ? '<span style="color:#166534;font-weight:700;" data-np="हो (अनिवार्य)" data-en="Yes (Mandatory)">हो (अनिवार्य)</span>' : '<span data-np="वैकल्पिक" data-en="Optional">वैकल्पिक</span>' ?>
                        </td>
                        <td style="padding:10px;font-size:0.85rem;color:var(--text-muted);"><?= htmlspecialchars($p['guidance_message_np']) ?></td>
                        <td style="padding:10px;text-align:center;">
                            <a href="manage_prerequisites.php?delete=<?= $p['id'] ?>" onclick="return confirm('के तपाईं यो पूर्वसर्त सम्बन्ध मेटाउन निश्चित हुनुहुन्छ?');" style="color:#dc2626;" title="हटाउनुहोस्">
                                <i class="bi bi-trash-fill"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Prerequisite Form -->
    <div style="background:white;padding:24px;border-radius:var(--radius-md);border:1px solid var(--border-color);box-shadow:var(--shadow-sm);">
        <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:15px;">
            <i class="bi bi-node-plus-fill" style="color:var(--primary);"></i> <span data-np="नयाँ पूर्वसर्त सम्बन्ध जोड्नुहोस्" data-en="Link New Prerequisite">नयाँ पूर्वसर्त सम्बन्ध जोड्नुहोस्</span>
        </h3>
        <form method="POST" action="manage_prerequisites.php" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:15px;">
            <input type="hidden" name="action" value="add_prereq">

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="लक्ष्य सेवा (Target Service)" data-en="Target Public Service">लक्ष्य सेवा (Target Service)</label>
                <select name="service_id" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;">
                    <option value="" data-np="-- सेवा छान्नुहोस् --" data-en="-- Select Service --">-- सेवा छान्नुहोस् --</option>
                    <?php foreach ($services as $svc): ?>
                        <option value="<?= $svc['id'] ?>"><?= htmlspecialchars($svc['name_np']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="यसका लागि कुन सेवा पहिले चाहिन्छ?" data-en="Required Base Prerequisite">यसका लागि कुन सेवा पहिले चाहिन्छ?</label>
                <select name="prerequisite_service_id" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;">
                    <option value="" data-np="-- पूर्वसेवा छान्नुहोस् --" data-en="-- Select Prerequisite --">-- पूर्वसेवा छान्नुहोस् --</option>
                    <?php foreach ($services as $svc): ?>
                        <option value="<?= $svc['id'] ?>"><?= htmlspecialchars($svc['name_np']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:flex;align-items:center;gap:8px;padding-top:25px;">
                <input type="checkbox" name="is_mandatory" value="1" id="is_mandatory" checked style="width:18px;height:18px;">
                <label for="is_mandatory" style="font-size:0.9rem;font-weight:700;cursor:pointer;" data-np="यो पूर्वसर्त अनिवार्य हो (Mandatory)" data-en="This Prerequisite is Mandatory">यो पूर्वसर्त अनिवार्य हो (Mandatory)</label>
            </div>

            <div style="grid-column: 1 / -1;">
                <label style="font-size:0.85rem;font-weight:700;" data-np="मार्गदर्शन सन्देश (नेपालीमा)" data-en="Guidance Note (Nepali)">मार्गदर्शन सन्देश (नेपालीमा)</label>
                <textarea name="guidance_message_np" rows="2" placeholder="यो सेवा बनाउनु अगाडि तपाईंसँग अनिवार्य... हुनुपर्दछ।" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;"></textarea>
            </div>

            <div style="grid-column: 1 / -1;">
                <label style="font-size:0.85rem;font-weight:700;" data-np="Guidance Note (English)" data-en="Guidance Note (English)">Guidance Note (English)</label>
                <textarea name="guidance_message_en" rows="2" placeholder="You must possess... before applying for this service." required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;"></textarea>
            </div>

            <div style="grid-column: 1 / -1;">
                <button type="submit" class="btn-action btn-action-primary" style="padding:10px 24px;">
                    <i class="bi bi-link"></i> <span data-np="सम्बन्ध सुरक्षित गर्नुहोस्" data-en="Save Dependency Link">सम्बन्ध सुरक्षित गर्नुहोस्</span>
                </button>
            </div>
        </form>
    </div>
</main>

<script src="assets/admin.js"></script>
</body>
</html>
