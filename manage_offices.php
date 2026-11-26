<?php
/**
 * Mero Sahayata - Government Offices Directory Manager
 * Allows administrator to add, edit, or delete local government offices.
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

// Handle Delete Office
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmtDel = $db->prepare("DELETE FROM offices WHERE id = ?");
    $stmtDel->execute([$delId]);
    $message = "कार्यालय सफलतापूर्वक हटाइयो!";
}

// Handle Add Office
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_office') {
    $nameNp = trim($_POST['name_np'] ?? '');
    $nameEn = trim($_POST['name_en'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $municipality = trim($_POST['municipality'] ?? '');
    $category = trim($_POST['service_category'] ?? 'Identity');
    $addressNp = trim($_POST['address_np'] ?? '');
    $addressEn = trim($_POST['address_en'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $hours = trim($_POST['opening_hours'] ?? 'आइतबार - शुक्रबार १०:०० - ५:००');
    $mapsUrl = trim($_POST['google_maps_url'] ?? '');

    if (!empty($nameNp) && !empty($district) && !empty($addressNp)) {
        try {
            $stmt = $db->prepare("INSERT INTO offices (name_en, name_np, district, municipality, service_category, address_en, address_np, phone, email, opening_hours, google_maps_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nameEn, $nameNp, $district, $municipality, $category, $addressEn, $addressNp, $phone, $email, $hours, $mapsUrl]);
            $message = "नयाँ सरकारी सेवा केन्द्र सफलतापूर्वक थपियो!";
        } catch (Exception $e) {
            $message = "त्रुटि: " . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = "कृपया आवश्यक विवरण भर्नुहोस्।";
        $messageType = 'error';
    }
}

$offices = $db->query("SELECT * FROM offices ORDER BY district ASC, id ASC")->fetchAll();
$activeTab = 'offices';
?>
<!DOCTYPE html>
<html lang="ne">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>सरकारी कार्यालय निर्देशिका - Mero Sahayata Offices Directory</title>
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
                <i class="bi bi-building"></i> <span data-np="सरकारी सेवा केन्द्र तथा कार्यालय व्यवस्थापक" data-en="Government Offices & Directory Manager">सरकारी सेवा केन्द्र तथा कार्यालय व्यवस्थापक</span>
            </h2>
            <p style="color:var(--text-muted);font-size:0.9rem;" data-np="नागरिकलाई उनीहरूको जिल्ला अनुसार सिफारिस गरिने कार्यालयहरूको सूची, फोन नम्बर र गुगल म्याप्स लिंक।" data-en="Directory of government offices, contact numbers, and location links recommended based on citizen district.">
                नागरिकलाई उनीहरूको जिल्ला अनुसार सिफारिस गरिने कार्यालयहरूको सूची, फोन नम्बर र गुगल म्याप्स लिंक।
            </p>
        </div>
    </div>

    <?php if ($message): ?>
        <div style="padding:14px;background:<?= $messageType === 'success' ? '#ecfdf5' : '#fee2e2' ?>;color:<?= $messageType === 'success' ? '#065f46' : '#991b1b' ?>;border-radius:8px;margin-bottom:20px;font-weight:600;">
            <i class="bi bi-info-circle"></i> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Offices Table -->
    <div style="background:white;padding:24px;border-radius:var(--radius-md);border:1px solid var(--border-color);box-shadow:var(--shadow-sm);margin-bottom:30px;overflow-x:auto;">
        <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:15px;">
            <span data-np="दर्ता भएका सरकारी कार्यालयहरू" data-en="Registered Government Offices">दर्ता भएका सरकारी कार्यालयहरू</span>
            <small style="color:var(--text-muted);font-size:0.9rem;">(Total Offices: <?= count($offices) ?>)</small>
        </h3>
        <table class="custom-table" style="width:100%;border-collapse:collapse;font-size:0.9rem;">
            <thead>
                <tr style="border-bottom:2px solid #e2e8f0;text-align:left;background:#f8fafc;">
                    <th style="padding:10px;" data-np="जिल्ला" data-en="District">जिल्ला</th>
                    <th style="padding:10px;" data-np="कार्यालयको नाम" data-en="Office Name">कार्यालयको नाम</th>
                    <th style="padding:10px;" data-np="सेवा श्रेणी" data-en="Category">सेवा श्रेणी</th>
                    <th style="padding:10px;" data-np="ठेगाना" data-en="Address">ठेगाना</th>
                    <th style="padding:10px;" data-np="सम्पर्क फोन" data-en="Phone">सम्पर्क फोन</th>
                    <th style="padding:10px;" data-np="समय" data-en="Hours">समय</th>
                    <th style="padding:10px;text-align:center;" data-np="कार्य" data-en="Action">कार्य</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($offices as $o): ?>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:10px;font-weight:700;color:var(--primary);"><?= htmlspecialchars($o['district']) ?></td>
                        <td style="padding:10px;">
                            <strong><?= htmlspecialchars($o['name_np']) ?></strong><br>
                            <small style="color:var(--text-muted);"><?= htmlspecialchars($o['name_en']) ?></small>
                        </td>
                        <td style="padding:10px;">
                            <span style="background:#eff6ff;color:#1e40af;padding:2px 8px;border-radius:4px;font-size:0.8rem;font-weight:600;">
                                <?= htmlspecialchars($o['service_category']) ?>
                            </span>
                        </td>
                        <td style="padding:10px;"><?= htmlspecialchars($o['address_np']) ?></td>
                        <td style="padding:10px;"><?= htmlspecialchars($o['phone'] ?: 'N/A') ?></td>
                        <td style="padding:10px;font-size:0.8rem;color:var(--text-muted);"><?= htmlspecialchars($o['opening_hours']) ?></td>
                        <td style="padding:10px;text-align:center;">
                            <a href="manage_offices.php?delete=<?= $o['id'] ?>" onclick="return confirm('के तपाईं यो कार्यालय हटाउन निश्चित हुनुहुन्छ? / Are you sure?');" style="color:#dc2626;font-size:1.1rem;" title="मेटाउनुहोस्">
                                <i class="bi bi-trash-fill"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Office Form -->
    <div style="background:white;padding:24px;border-radius:var(--radius-md);border:1px solid var(--border-color);box-shadow:var(--shadow-sm);">
        <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:15px;">
            <i class="bi bi-building-add" style="color:var(--primary);"></i> 
            <span data-np="नयाँ सरकारी कार्यालय थप्नुहोस्" data-en="Add Government Office">नयाँ सरकारी कार्यालय थप्नुहोस्</span>
        </h3>
        <form method="POST" action="manage_offices.php" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:15px;">
            <input type="hidden" name="action" value="add_office">

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="कार्यालयको नाम (नेपालीमा)" data-en="Office Name (Nepali)">कार्यालयको नाम (नेपालीमा)</label>
                <input type="text" name="name_np" placeholder="उदा: जिल्ला प्रशासन कार्यालय, कास्की" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;"
                       data-placeholder-np="उदा: जिल्ला प्रशासन कार्यालय, कास्की" data-placeholder-en="e.g. District Administration Office, Kaski">
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="Office Name (English)" data-en="Office Name (English)">Office Name (English)</label>
                <input type="text" name="name_en" placeholder="District Administration Office, Kaski" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;"
                       data-placeholder-np="District Administration Office, Kaski" data-placeholder-en="District Administration Office, Kaski">
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="जिल्ला (District)" data-en="District (e.g. Kathmandu, Kaski)">जिल्ला (District)</label>
                <input type="text" name="district" placeholder="उदा: Kathmandu, Lalitpur, Kaski" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;"
                       data-placeholder-np="उदा: Kathmandu, Lalitpur, Kaski" data-placeholder-en="e.g. Kathmandu, Lalitpur, Kaski">
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="सेवा श्रेणी (Service Category)" data-en="Service Category">सेवा श्रेणी (Service Category)</label>
                <select name="service_category" style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;">
                    <option value="Identity" data-np="नागरिकता / परिचयपत्र (Identity)" data-en="Identity (Citizenship / NID)">नागरिकता / परिचयपत्र (Identity)</option>
                    <option value="Travel & Foreign Affairs" data-np="राहदानी (Travel & Passport)" data-en="Travel & Passport">राहदानी (Travel & Passport)</option>
                    <option value="Transport" data-np="यातायात / सवारी चालक (Transport)" data-en="Transport (Driving License)">यातायात / सवारी चालक (Transport)</option>
                    <option value="Civil Registration" data-np="पञ्जीकरण / जन्म दर्ता (Civil Registration)" data-en="Civil Registration (Birth/Vital Events)">पञ्जीकरण / जन्म दर्ता (Civil Registration)</option>
                </select>
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="ठेगाना (नेपालीमा)" data-en="Address (Nepali)">ठेगाना (नेपालीमा)</label>
                <input type="text" name="address_np" placeholder="उदा: बबरमहल, काठमाडौँ" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;"
                       data-placeholder-np="उदा: बबरमहल, काठमाडौँ" data-placeholder-en="e.g. Babarmahal, Kathmandu">
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="Address (English)" data-en="Address (English)">Address (English)</label>
                <input type="text" name="address_en" placeholder="Babarmahal, Kathmandu" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;"
                       data-placeholder-np="Babarmahal, Kathmandu" data-placeholder-en="Babarmahal, Kathmandu">
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="सम्पर्क फोन (Phone)" data-en="Contact Phone">सम्पर्क फोन (Phone)</label>
                <input type="text" name="phone" placeholder="+977-1-4262444" style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;">
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="इमेल (Email Address)" data-en="Email Address">इमेल (Email Address)</label>
                <input type="email" name="email" placeholder="dao@moha.gov.np" style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;">
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="कार्यालय समय (Opening Hours)" data-en="Opening Hours">कार्यालय समय (Opening Hours)</label>
                <input type="text" name="opening_hours" value="आइतबार - शुक्रबार १०:०० - ५:००" style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;"
                       data-placeholder-np="आइतबार - शुक्रबार १०:०० - ५:००" data-placeholder-en="Sun - Fri 10:00 AM - 5:00 PM">
            </div>

            <div>
                <label style="font-size:0.85rem;font-weight:700;" data-np="Google Maps URL (Location Link)" data-en="Google Maps URL Link">Google Maps URL (Location Link)</label>
                <input type="url" name="google_maps_url" placeholder="https://maps.google.com/?q=DAO+Kathmandu" style="width:100%;padding:10px;border-radius:6px;border:1px solid #cbd5e1;">
            </div>

            <div style="grid-column: 1 / -1;">
                <button type="submit" class="btn-action btn-action-primary" style="padding:10px 24px;">
                    <i class="bi bi-building-check"></i> <span data-np="कार्यालय सुरक्षित गर्नुहोस्" data-en="Save Office">कार्यालय सुरक्षित गर्नुहोस्</span>
                </button>
            </div>
        </form>
    </div>
</main>

<!-- Admin Interactive Script for Translation, Font Scaling and Contrast -->
<script src="assets/admin.js"></script>

</body>
</html>
