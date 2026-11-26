<?php
/**
 * Mero Sahayata - Admin Dashboard
 * System Analytics, Rule Management & Feedback Reports
 */

require_once __DIR__ . '/../config/db.php';
startSessionSafely();

// Authentication Guard
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = Database::getConnection();

// 1. Fetch Aggregated System Metrics
$totalServices = $db->query("SELECT COUNT(*) FROM services")->fetchColumn();
$totalRules = $db->query("SELECT COUNT(*) FROM service_rules")->fetchColumn();
$totalDocs = $db->query("SELECT COUNT(*) FROM documents")->fetchColumn();
$totalOffices = $db->query("SELECT COUNT(*) FROM offices")->fetchColumn();
$totalFeedback = $db->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
$avgRating = $db->query("SELECT ROUND(AVG(rating), 1) FROM feedback")->fetchColumn() ?: '5.0';

// 2. Fetch All Rules with Service Names
$stmtRules = $db->query("SELECT r.*, s.name_np as service_name_np, t.name_np as target_name_np
                        FROM service_rules r
                        JOIN services s ON r.service_id = s.id
                        LEFT JOIN services t ON r.target_service_id = t.id
                        ORDER BY r.service_id ASC, r.id ASC");
$rules = $stmtRules->fetchAll();

// 3. Fetch Recent Feedback
$stmtFeed = $db->query("SELECT f.*, s.name_np as service_name_np 
                       FROM feedback f
                       LEFT JOIN services s ON f.service_id = s.id
                       ORDER BY f.created_at DESC LIMIT 10");
$feedbacks = $stmtFeed->fetchAll();

// 4. Fetch Services for Quick Overview
$services = $db->query("SELECT * FROM services ORDER BY id ASC")->fetchAll();
$activeTab = 'dashboard';
?>
<!DOCTYPE html>
<html lang="ne">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>प्रशासक ड्यासबोर्ड - मेरो सहायता Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Mukta:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-nav {
            background: #0f172a;
            color: white;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .stat-num {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1.2;
        }
        .stat-label {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        .data-table-card {
            background: white;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            padding: 24px;
            margin-bottom: 30px;
            overflow-x: auto;
        }
        .custom-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            text-align: left;
        }
        .custom-table th {
            background: #f8fafc;
            padding: 12px 14px;
            border-bottom: 2px solid var(--border-color);
            color: var(--text-muted);
            font-weight: 700;
        }
        .custom-table td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border-color);
        }
        .badge-action {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .badge-block { background: #fee2e2; color: #991b1b; }
        .badge-special { background: #fef3c7; color: #92400e; }
        .badge-fee { background: #dcfce7; color: #166534; }
    </style>
</head>
<body style="background:#f8fafc;">

<?php include __DIR__ . '/includes/admin_nav.php'; ?>

<main class="main-wrapper" style="padding-top:25px;">

    <!-- System Metrics -->
    <div class="admin-stat-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-grid-fill"></i></div>
            <div>
                <div class="stat-num"><?= $totalServices ?></div>
                <div class="stat-label" data-np="सक्रिय नागरिक सेवाहरू" data-en="Active Public Services">सक्रिय नागरिक सेवाहरू</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#fef3c7;color:#d97706;"><i class="bi bi-cpu-fill"></i></div>
            <div>
                <div class="stat-num"><?= $totalRules ?></div>
                <div class="stat-label" data-np="निर्णय नियमहरू" data-en="Active Decision Rules">निर्णय नियमहरू</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="bi bi-file-earmark-check-fill"></i></div>
            <div>
                <div class="stat-num"><?= $totalDocs ?></div>
                <div class="stat-label" data-np="प्रमाणित कागजात सूची" data-en="Required Documents">प्रमाणित कागजात सूची</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#ecfdf5;color:#059669;"><i class="bi bi-star-fill"></i></div>
            <div>
                <div class="stat-num"><?= $avgRating ?> / 5.0</div>
                <div class="stat-label" data-np="नागरिक सन्तुष्टि" data-en="Citizen Satisfaction">नागरिक सन्तुष्टि</div>
            </div>
        </div>
    </div>

    <!-- Active Rule-Based Engine Logic Table -->
    <div class="data-table-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
            <h3 style="font-size:1.25rem;font-weight:700;color:var(--primary);">
                <i class="bi bi-diagram-2-fill"></i> <span data-np="नियम-आधारित इन्जिनका सक्रिय नियमहरू" data-en="Active Rule-Based Engine Matrix">नियम-आधारित इन्जिनका सक्रिय नियमहरू</span>
            </h3>
            <a href="manage_rules.php" class="btn-action btn-action-primary" style="padding:6px 14px;font-size:0.85rem;text-decoration:none;">
                <i class="bi bi-gear-fill"></i> <span data-np="नियमहरू व्यवस्थापन गर्नुहोस्" data-en="Manage Rules">नियमहरू व्यवस्थापन गर्नुहोस्</span> &rarr;
            </a>
        </div>

        <table class="custom-table">
            <thead>
                <tr>
                    <th data-np="Rule Code" data-en="Rule Code">Rule Code</th>
                    <th data-np="सम्बन्धित सेवा" data-en="Target Service">सम्बन्धित सेवा</th>
                    <th data-np="कन्डिसन र अपरेटर" data-en="Condition & Op">कन्डिसन र अपरेटर</th>
                    <th data-np="मान" data-en="Expected Value">मान</th>
                    <th data-np="एक्सन प्रकार" data-en="Action Type">एक्सन प्रकार</th>
                    <th data-np="असर / रिडाइरेक्ट" data-en="Consequence / Redirect">असर / रिडाइरेक्ट</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rules as $r): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($r['rule_code']) ?></code></td>
                        <td><strong><?= htmlspecialchars($r['service_name_np']) ?></strong></td>
                        <td><code><?= htmlspecialchars($r['condition_field']) ?> <?= htmlspecialchars($r['operator']) ?></code></td>
                        <td><span style="font-weight:700;color:var(--primary);"><?= htmlspecialchars($r['condition_value']) ?></span></td>
                        <td>
                            <?php if ($r['action_type'] === 'BLOCK_AND_REDIRECT'): ?>
                                <span class="badge-action badge-block">BLOCK & REDIRECT</span>
                            <?php elseif ($r['action_type'] === 'SPECIAL_INSTRUCTION'): ?>
                                <span class="badge-action badge-special">SPECIAL NOTE</span>
                            <?php else: ?>
                                <span class="badge-action badge-fee"><?= htmlspecialchars($r['action_type']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['target_service_id']): ?>
                                &rarr; <span data-np="सिफारिस" data-en="Recommend">सिफारिस</span>: <strong><?= htmlspecialchars($r['target_name_np']) ?></strong>
                            <?php else: ?>
                                <span style="font-size:0.85rem;color:var(--text-muted);"><?= htmlspecialchars($r['result_title_np']) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Citizen Feedback & Reporting -->
    <div class="data-table-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
            <h3 style="font-size:1.25rem;font-weight:700;color:var(--primary);">
                <i class="bi bi-chat-heart-fill" style="color:#dc2626;"></i> <span data-np="नागरिक प्रतिक्रिया तथा मूल्याङ्कन" data-en="Citizen Feedback & Rating Moderation">नागरिक प्रतिक्रिया तथा मूल्याङ्कन</span>
            </h3>
            <a href="user_activity.php" class="btn-action btn-action-secondary" style="padding:6px 14px;font-size:0.85rem;text-decoration:none;">
                <i class="bi bi-people-fill"></i> <span data-np="सबै नागरिक गतिविधि हेर्नुहोस्" data-en="View All User Activity">सबै नागरिक गतिविधि हेर्नुहोस्</span> &rarr;
            </a>
        </div>

        <?php if (empty($feedbacks)): ?>
            <p style="color:var(--text-muted);text-align:center;padding:20px;" data-np="हालसम्म कुनै प्रतिक्रिया प्राप्त भएको छैन।" data-en="No feedback received yet.">हालसम्म कुनै प्रतिक्रिया प्राप्त भएको छैन।</p>
        <?php else: ?>
            <table class="custom-table">
                <thead>
                    <tr>
                        <th data-np="मिति" data-en="Date">मिति</th>
                        <th data-np="नागरिक" data-en="Citizen">नागरिक</th>
                        <th data-np="सम्बन्धित सेवा" data-en="Service">सम्बन्धित सेवा</th>
                        <th data-np="रेटिङ" data-en="Rating">रेटिङ</th>
                        <th data-np="टिप्पणी / प्रतिक्रिया" data-en="Comments">टिप्पणी / प्रतिक्रिया</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($feedbacks as $fb): ?>
                        <tr>
                            <td><small><?= htmlspecialchars($fb['created_at']) ?></small></td>
                            <td><strong><?= htmlspecialchars($fb['user_name']) ?></strong></td>
                            <td><?= htmlspecialchars($fb['service_name_np'] ?? 'सामान्य') ?></td>
                            <td>
                                <span style="color:#eab308;">
                                    <?= str_repeat('★', (int)$fb['rating']) . str_repeat('☆', 5 - (int)$fb['rating']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($fb['comments'] ?: 'टिप्पणी छैन') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</main>

<script src="assets/admin.js"></script>
</body>
</html>
