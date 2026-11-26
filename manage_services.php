<?php
/**
 * Mero Sahayata - Service Management CRUD
 */

require_once __DIR__ . '/../config/db.php';
startSessionSafely();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = Database::getConnection();
$message = '';

// Handle New Service Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $nameEn = trim($_POST['name_en'] ?? '');
    $nameNp = trim($_POST['name_np'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $standardFee = trim($_POST['standard_fee'] ?? 'निःशुल्क');
    $estimatedDays = trim($_POST['estimated_days'] ?? '१ दिन');
    $descEn = trim($_POST['desc_en'] ?? '');
    $descNp = trim($_POST['desc_np'] ?? '');
    $portalUrl = trim($_POST['portal_url'] ?? '');
    $icon = trim($_POST['icon'] ?? 'bi-file-earmark-text');

    if (!empty($code) && !empty($nameNp)) {
        try {
            $stmt = $db->prepare("INSERT INTO services (code, name_en, name_np, category, description_en, description_np, standard_fee, estimated_days, online_portal_url, icon) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$code, $nameEn, $nameNp, $category, $descEn, $descNp, $standardFee, $estimatedDays, $portalUrl, $icon]);
            $message = 'नयाँ नागरिक सेवा सफलतापूर्वक थपियो! (Service added successfully)';
        } catch (Exception $e) {
            $message = 'त्रुटि (Error): ' . $e->getMessage();
        }
    }
}

// Fetch all services
$services = $db->query("SELECT * FROM services ORDER BY id DESC")->fetchAll();
$activeTab = 'services';
?>
<!DOCTYPE html>
<html lang="ne">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>सेवा व्यवस्थापन - Mero Sahayata Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Mukta:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background:#f8fafc;">

<?php include __DIR__ . '/includes/admin_nav.php'; ?>

<main class="main-wrapper" style="padding-top:25px;">
    <h2 style="font-size:1.6rem;font-weight:800;color:var(--primary);margin-bottom:20px;">
        <i class="bi bi-folder-plus"></i> <span data-np="सार्वजनिक सेवा व्यवस्थापन" data-en="Public Services Management">सार्वजनिक सेवा व्यवस्थापन</span>
    </h2>

    <?php if ($message): ?>
        <div style="padding:14px;background:#ecfdf5;color:#065f46;border-radius:8px;margin-bottom:20px;font-weight:600;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Add Service Form -->
    <div style="background:white;padding:24px;border-radius:var(--radius-md);border:1px solid var(--border-color);margin-bottom:30px;box-shadow:var(--shadow-sm);">
        <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:15px;color:var(--text-main);">
            <span data-np="नयाँ नागरिक सेवा थप्नुहोस्" data-en="Add New Public Service">नयाँ नागरिक सेवा थप्नुहोस्</span>
        </h3>
        <form method="POST" action="manage_services.php" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:15px;">
            <input type="hidden" name="action" value="add">
            
            <div>
                <label style="font-size:0.85rem;font-weight:700;">Service Code (उदा: PAN_CARD)</label>
                <input type="text" name="code" class="form-control" placeholder="PAN_CARD" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;">
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="सेवाको नाम (नेपालीमा)" data-en="Service Name (Nepali)">सेवाको नाम (नेपालीमा)</label>
                <input type="text" name="name_np" class="form-control" placeholder="उदा: व्यक्तिगत प्यान नम्बर" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;">
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;">Service Name (English)</label>
                <input type="text" name="name_en" class="form-control" placeholder="Individual PAN Card" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;">
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="श्रेणी (Category)" data-en="Category">श्रेणी (Category)</label>
                <input type="text" name="category" class="form-control" placeholder="Revenue & Tax" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;">
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="सरकारी दस्तुर (Fee)" data-en="Standard Fee">सरकारी दस्तुर (Fee)</label>
                <input type="text" name="standard_fee" class="form-control" placeholder="निःशुल्क (Free)" style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;">
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="लाग्ने समय (Est. Days)" data-en="Estimated Days">लाग्ने समय (Est. Days)</label>
                <input type="text" name="estimated_days" class="form-control" placeholder="१ दिन (Same Day)" style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;">
            </div>

            <div style="grid-column: 1 / -1;">
                <label style="font-size:0.85rem;font-weight:700;" data-np="विवरण (नेपालीमा)" data-en="Description (Nepali)">विवरण (नेपालीमा)</label>
                <textarea name="desc_np" rows="2" style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;" placeholder="सेवा सम्बन्धी संक्षिप्त विवरण..."></textarea>
            </div>

            <div style="grid-column: 1 / -1;">
                <button type="submit" class="btn-action btn-action-primary" style="padding:10px 24px;">
                    <i class="bi bi-plus-circle"></i> <span data-np="सेवा सुरक्षित गर्नुहोस्" data-en="Save Service">सेवा सुरक्षित गर्नुहोस्</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Existing Services Table -->
    <div style="background:white;padding:24px;border-radius:var(--radius-md);border:1px solid var(--border-color);box-shadow:var(--shadow-sm);overflow-x:auto;">
        <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:15px;" data-np="हाल दर्ता भएका सेवाहरू" data-en="Registered Public Services">हाल दर्ता भएका सेवाहरू</h3>
        <table class="custom-table" style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:2px solid #e2e8f0;text-align:left;">
                    <th>ID</th>
                    <th>Code</th>
                    <th data-np="सेवाको नाम" data-en="Service Name">सेवाको नाम</th>
                    <th data-np="श्रेणी" data-en="Category">श्रेणी</th>
                    <th data-np="शुल्क" data-en="Fee">शुल्क</th>
                    <th data-np="समय" data-en="Est. Time">समय</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $svc): ?>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:10px;"><?= $svc['id'] ?></td>
                        <td style="padding:10px;"><code><?= htmlspecialchars($svc['code']) ?></code></td>
                        <td style="padding:10px;"><strong><?= htmlspecialchars($svc['name_np']) ?></strong></td>
                        <td style="padding:10px;"><?= htmlspecialchars($svc['category']) ?></td>
                        <td style="padding:10px;"><?= htmlspecialchars($svc['standard_fee']) ?></td>
                        <td style="padding:10px;"><?= htmlspecialchars($svc['estimated_days']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<script src="assets/admin.js"></script>
</body>
</html>
