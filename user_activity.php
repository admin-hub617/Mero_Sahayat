<?php
/**
 * Mero Sahayata - User Activity & Voice Search Analytics
 * Shows what citizens are searching for, voice vs text ratio, and citizen feedback.
 */

require_once __DIR__ . '/../config/db.php';
startSessionSafely();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = Database::getConnection();

// Handle Delete Feedback
if (isset($_GET['delete_feedback'])) {
    $fbId = (int)$_GET['delete_feedback'];
    $stmtDel = $db->prepare("DELETE FROM feedback WHERE id = ?");
    $stmtDel->execute([$fbId]);
    header('Location: user_activity.php');
    exit;
}

// 1. User Search Analytics Metrics
$totalQueries = $db->query("SELECT COUNT(*) FROM search_logs")->fetchColumn();
$voiceQueries = $db->query("SELECT COUNT(*) FROM search_logs WHERE search_type = 'voice'")->fetchColumn();
$textQueries = $totalQueries - $voiceQueries;
$voicePercent = $totalQueries > 0 ? round(($voiceQueries / $totalQueries) * 100, 1) : 0;
$unmatchedQueries = $db->query("SELECT COUNT(*) FROM search_logs WHERE matched_service_id IS NULL")->fetchColumn();

// 2. Fetch Recent Search Logs (with matched service details)
$stmtLogs = $db->query("SELECT sl.*, s.name_np as service_name_np, s.name_en as service_name_en, s.icon
                       FROM search_logs sl
                       LEFT JOIN services s ON sl.matched_service_id = s.id
                       ORDER BY sl.created_at DESC LIMIT 25");
$searchLogs = $stmtLogs->fetchAll();

// 3. Fetch Citizen Feedback
$feedbacks = $db->query("SELECT f.*, s.name_np as service_name, s.name_en as service_name_en
                         FROM feedback f
                         LEFT JOIN services s ON f.service_id = s.id
                         ORDER BY f.created_at DESC LIMIT 20")->fetchAll();

$activeTab = 'activity';
?>
<!DOCTYPE html>
<html lang="ne">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>नागरिक गतिविधि तथा भ्वाइस लग - Mero Sahayata User Activity</title>
    <link href="https://fonts.googleapis.com/css2?family=Mukta:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .activity-stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .activity-card {
            background: white;
            padding: 20px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }
        .badge-voice { background: #fee2e2; color: #dc2626; padding: 4px 10px; border-radius: 12px; font-weight: 700; font-size: 0.75rem; }
        .badge-text { background: #eff6ff; color: #2563eb; padding: 4px 10px; border-radius: 12px; font-weight: 700; font-size: 0.75rem; }
        .badge-unmatched { background: #fef3c7; color: #92400e; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; }
    </style>
</head>
<body style="background:#f8fafc;">

<?php include __DIR__ . '/includes/admin_nav.php'; ?>

<main class="main-wrapper" style="padding-top:25px;">
    <div style="margin-bottom:25px;">
        <h2 style="font-size:1.6rem;font-weight:800;color:var(--primary);margin-bottom:4px;">
            <i class="bi bi-people-fill"></i> <span data-np="नागरिक अन्तरक्रिया तथा भ्वाइस खोजी विश्लेषण" data-en="Citizen Activity & Voice Search Analytics">नागरिक अन्तरक्रिया तथा भ्वाइस खोजी विश्लेषण</span>
        </h2>
        <p style="color:var(--text-muted);font-size:0.95rem;" data-np="नागरिकहरूले वेबसाइटमा के-के खोजिरहेका छन्, बोलेर खोज्नेको संख्या कति छ, र कुन-कुन माग पूरा हुन सकेन (Unmatched) भन्ने प्रत्यक्ष तथ्याङ्क।" data-en="Live analytics on what citizens are searching for, voice vs text ratio, and unmatched service queries.">
            नागरिकहरूले वेबसाइटमा के-के खोजिरहेका छन्, बोलेर खोज्नेको संख्या कति छ, र कुन-कुन माग पूरा हुन सकेन (Unmatched) भन्ने प्रत्यक्ष तथ्याङ्क।
        </p>
    </div>

    <!-- Analytics Cards -->
    <div class="activity-stat-grid">
        <div class="activity-card">
            <div style="font-size:0.85rem;color:var(--text-muted);margin-bottom:6px;" data-np="कुल नागरिक खोजहरू (Total Queries)" data-en="Total Citizen Queries">कुल नागरिक खोजहरू (Total Queries)</div>
            <div style="font-size:1.8rem;font-weight:800;color:var(--text-main);"><?= $totalQueries ?></div>
            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:4px;" data-np="टेक्स्ट र भ्वाइस दुबै समेटिएको" data-en="Both text and voice queries">टेक्स्ट र भ्वाइस दुबै समेटिएको</div>
        </div>

        <div class="activity-card">
            <div style="font-size:0.85rem;color:var(--text-muted);margin-bottom:6px;" data-np="🎙️ भ्वाइस इनपुट खोज (Voice Searches)" data-en="🎙️ Voice Searches">🎙️ भ्वाइस इनपुट खोज (Voice Searches)</div>
            <div style="font-size:1.8rem;font-weight:800;color:#dc2626;"><?= $voiceQueries ?> <small style="font-size:1rem;color:var(--text-muted);">(<?= $voicePercent ?>%)</small></div>
            <div style="font-size:0.8rem;color:#16a34a;font-weight:600;" data-np="डिजिटल साक्षरता कम भएका नागरिक प्रयोगकर्ता" data-en="Assisted low digital-literacy citizens">डिजिटल साक्षरता कम भएका नागरिक प्रयोगकर्ता</div>
        </div>

        <div class="activity-card">
            <div style="font-size:0.85rem;color:var(--text-muted);margin-bottom:6px;" data-np="⌨️ टेक्स्ट खोजहरू (Text Searches)" data-en="⌨️ Text Searches">⌨️ टेक्स्ट खोजहरू (Text Searches)</div>
            <div style="font-size:1.8rem;font-weight:800;color:#2563eb;"><?= $textQueries ?></div>
            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:4px;" data-np="नेपाली र Romanized Nepali" data-en="Nepali and Romanized queries">नेपाली र Romanized Nepali</div>
        </div>

        <div class="activity-card">
            <div style="font-size:0.85rem;color:var(--text-muted);margin-bottom:6px;" data-np="⚠️ समाधान नभएका खोजहरू (Unmatched)" data-en="⚠️ Unmatched Queries">⚠️ समाधान नभएका खोजहरू (Unmatched)</div>
            <div style="font-size:1.8rem;font-weight:800;color:#d97706;"><?= $unmatchedQueries ?></div>
            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:4px;" data-np="प्रशासकले नयाँ सेवा वा किवर्ड थप्न आवश्यक" data-en="Requires adding new services or keywords">प्रशासकले नयाँ सेवा वा किवर्ड थप्न आवश्यक</div>
        </div>
    </div>

    <!-- Live Search & Voice Query Stream -->
    <div style="background:white;padding:24px;border-radius:var(--radius-md);border:1px solid var(--border-color);box-shadow:var(--shadow-sm);margin-bottom:30px;overflow-x:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;flex-wrap:wrap;gap:10px;">
            <h3 style="font-size:1.2rem;font-weight:700;color:var(--primary);">
                <i class="bi bi-broadcast"></i> <span data-np="नागरिक खोज स्ट्रिम" data-en="Citizen Search & Voice Query Stream">नागरिक खोज स्ट्रिम</span>
            </h3>
            <span style="font-size:0.85rem;color:var(--text-muted);" data-np="पछिल्ला २५ अन्तरक्रियाहरू" data-en="Latest 25 Interactions">पछिल्ला २५ अन्तरक्रियाहरू</span>
        </div>

        <table class="custom-table" style="width:100%;border-collapse:collapse;font-size:0.9rem;">
            <thead>
                <tr style="border-bottom:2px solid #e2e8f0;text-align:left;background:#f8fafc;">
                    <th style="padding:10px;" data-np="समय" data-en="Time">समय</th>
                    <th style="padding:10px;" data-np="माध्यम" data-en="Type">माध्यम</th>
                    <th style="padding:10px;" data-np="नागरिकले खोजेको वाक्य" data-en="Citizen Query">नागरिकले खोजेको वाक्य</th>
                    <th style="padding:10px;" data-np="प्रणालीले फेला पारेको सेवा" data-en="Matched Service">प्रणालीले फेला पारेको सेवा</th>
                    <th style="padding:10px;" data-np="अवस्था" data-en="Status">अवस्था</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($searchLogs)): ?>
                    <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted);" data-np="हालसम्म कुनै खोजी भएको छैन।" data-en="No search queries recorded yet.">हालसम्म कुनै खोजी भएको छैन।</td></tr>
                <?php else: ?>
                    <?php foreach ($searchLogs as $log): ?>
                        <tr style="border-bottom:1px solid #f1f5f9;">
                            <td style="padding:10px;"><small style="color:var(--text-muted);"><?= htmlspecialchars($log['created_at']) ?></small></td>
                            <td style="padding:10px;">
                                <?php if ($log['search_type'] === 'voice'): ?>
                                    <span class="badge-voice"><i class="bi bi-mic-fill"></i> <span data-np="भ्वाइस" data-en="Voice">भ्वाइस</span></span>
                                <?php else: ?>
                                    <span class="badge-text"><i class="bi bi-keyboard-fill"></i> <span data-np="टेक्स्ट" data-en="Text">टेक्स्ट</span></span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:10px;font-weight:700;">
                                "<?= htmlspecialchars($log['query']) ?>"
                            </td>
                            <td style="padding:10px;">
                                <?php if ($log['matched_service_id']): ?>
                                    <span style="color:var(--primary);font-weight:700;">
                                        <i class="bi <?= htmlspecialchars($log['icon'] ?? 'bi-file-earmark') ?>"></i>
                                        <?= htmlspecialchars($log['service_name_np']) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);font-style:italic;" data-np="कुनै मेल खाएन" data-en="No Match">कुनै मेल खाएन</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:10px;">
                                <?php if ($log['matched_service_id']): ?>
                                    <span style="color:#16a34a;font-weight:700;"><i class="bi bi-check-circle-fill"></i> <span data-np="समाधान भयो" data-en="Resolved">समाधान भयो</span></span>
                                <?php else: ?>
                                    <span class="badge-unmatched"><i class="bi bi-exclamation-triangle-fill"></i> <span data-np="सेवा थप्न बाँकी" data-en="Unmatched">सेवा थप्न बाँकी</span></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Citizen Feedback Management -->
    <div style="background:white;padding:24px;border-radius:var(--radius-md);border:1px solid var(--border-color);box-shadow:var(--shadow-sm);overflow-x:auto;">
        <h3 style="font-size:1.2rem;font-weight:700;color:var(--primary);margin-bottom:15px;">
            <i class="bi bi-chat-square-heart-fill" style="color:#dc2626;"></i> 
            <span data-np="नागरिक प्रतिक्रिया व्यवस्थापन तथा मूल्याङ्कन" data-en="Citizen Feedback & Rating Moderation">नागरिक प्रतिक्रिया व्यवस्थापन तथा मूल्याङ्कन</span>
        </h3>

        <table class="custom-table" style="width:100%;border-collapse:collapse;font-size:0.9rem;">
            <thead>
                <tr style="border-bottom:2px solid #e2e8f0;text-align:left;background:#f8fafc;">
                    <th style="padding:10px;" data-np="मिति" data-en="Date">मिति</th>
                    <th style="padding:10px;" data-np="नागरिकको नाम" data-en="Citizen Name">नागरिकको नाम</th>
                    <th style="padding:10px;" data-np="सम्बन्धित सेवा" data-en="Service">सम्बन्धित सेवा</th>
                    <th style="padding:10px;" data-np="रेटिङ" data-en="Rating">रेटिङ</th>
                    <th style="padding:10px;" data-np="प्रतिक्रिया / सुझाव" data-en="Comments">प्रतिक्रिया / सुझाव</th>
                    <th style="padding:10px;text-align:center;" data-np="कार्य" data-en="Action">कार्य</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($feedbacks)): ?>
                    <tr><td colspan="6" style="text-align:center;padding:20px;color:var(--text-muted);" data-np="हालसम्म कुनै प्रतिक्रिया प्राप्त भएको छैन।" data-en="No feedback submitted yet.">हालसम्म कुनै प्रतिक्रिया प्राप्त भएको छैन।</td></tr>
                <?php else: ?>
                    <?php foreach ($feedbacks as $fb): ?>
                        <tr style="border-bottom:1px solid #f1f5f9;">
                            <td style="padding:10px;"><small><?= htmlspecialchars($fb['created_at']) ?></small></td>
                            <td style="padding:10px;"><strong><?= htmlspecialchars($fb['user_name']) ?></strong></td>
                            <td style="padding:10px;"><?= htmlspecialchars($fb['service_name'] ?? 'सामान्य पोर्टल') ?></td>
                            <td style="padding:10px;color:#eab308;font-size:1.05rem;">
                                <?= str_repeat('★', (int)$fb['rating']) . str_repeat('☆', 5 - (int)$fb['rating']) ?>
                            </td>
                            <td style="padding:10px;"><?= htmlspecialchars($fb['comments'] ?: 'टिप्पणी छैन') ?></td>
                            <td style="padding:10px;text-align:center;">
                                <a href="user_activity.php?delete_feedback=<?= $fb['id'] ?>" onclick="return confirm('के तपाईं यो प्रतिक्रिया मेटाउन चाहनुहुन्छ? / Delete this feedback?');" style="color:#dc2626;font-size:1.1rem;" title="मेटाउनुहोस्">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- Admin Interactive Script for Translation, Font Scaling and Contrast -->
<script src="assets/admin.js"></script>

</body>
</html>
