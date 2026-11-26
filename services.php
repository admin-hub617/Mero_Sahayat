<?php
/**
 * Mero Sahayata - All Services & Dependency Directory
 * BCA 6th Semester Project
 */

require_once __DIR__ . '/config/db.php';

$pageTitle = 'सबै नागरिक सेवाहरू तथा पूर्वसर्तहरू - मेरो सहायता';
$db = Database::getConnection();

// Fetch all services with prerequisites
$stmt = $db->query("SELECT s.*, 
                    GROUP_CONCAT(p.name_np SEPARATOR ', ') as prereq_names_np,
                    GROUP_CONCAT(p.name_en SEPARATOR ', ') as prereq_names_en
                    FROM services s
                    LEFT JOIN service_prerequisites sp ON s.id = sp.service_id
                    LEFT JOIN services p ON sp.prerequisite_service_id = p.id
                    GROUP BY s.id
                    ORDER BY s.id ASC");
$services = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<main class="main-wrapper">
    <div style="margin-bottom:30px;">
        <h1 class="section-title" style="font-size:1.8rem;margin-bottom:8px;">
            <i class="bi bi-diagram-3-fill" style="color:var(--primary);"></i>
            <span data-np="नागरिक सेवा निर्देशिका तथा पूर्वसर्त सम्बन्ध" data-en="Public Services & Dependency Matrix">
                नागरिक सेवा निर्देशिका तथा पूर्वसर्त सम्बन्ध
            </span>
        </h1>
        <p style="color:var(--text-muted);font-size:1rem;" data-np="यहाँ नेपालका मुख्य सरकारी सेवाहरू, तिनीहरूका कानुनी पूर्वसर्तहरू (Prerequisites), र प्रक्रियागत विवरण प्रस्तुत गरिएको छ।" data-en="Browse all public administrative services, understand their foundational prerequisite requirements, and initiate step-by-step guidance.">
            यहाँ नेपालका मुख्य सरकारी सेवाहरू, तिनीहरूका कानुनी पूर्वसर्तहरू (Prerequisites), र प्रक्रियागत विवरण प्रस्तुत गरिएको छ।
        </p>
    </div>

    <!-- Service Dependency Architecture Card -->
    <div style="background:white;padding:24px;border-radius:var(--radius-lg);border:1px solid var(--border-color);margin-bottom:35px;box-shadow:var(--shadow-sm);">
        <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:12px;color:var(--primary);">
            <i class="bi bi-shield-lock"></i> 
            <span data-np="प्रणालीको नियम-आधारित सेवा सम्बन्ध (Dependency Architecture)" data-en="Rule-Based Dependency Architecture">प्रणालीको नियम-आधारित सेवा सम्बन्ध (Dependency Architecture)</span>
        </h3>
        <p style="font-size:0.95rem;color:var(--text-muted);margin-bottom:18px;">
            नेपालको प्रशासनिक प्रणालीमा कुनै पनि सेवा लिन अन्य आधारभूत परिचयपत्रहरू अनिवार्य हुन्छन्:
        </p>
        <div style="display:flex;flex-wrap:wrap;gap:12px;">
            <span style="background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;padding:8px 14px;border-radius:20px;font-size:0.9rem;font-weight:600;">
                १. नेपाली नागरिकता &rarr; आधारभूत दस्तावेज (Base Document)
            </span>
            <span style="background:#fef3c7;border:1px solid #fde68a;color:#92400e;padding:8px 14px;border-radius:20px;font-size:0.9rem;font-weight:600;">
                २. राष्ट्रिय परिचयपत्र &larr; नागरिकता आवश्यक (Requires Citizenship)
            </span>
            <span style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:8px 14px;border-radius:20px;font-size:0.9rem;font-weight:600;">
                ३. ई-पासपोर्ट &larr; नागरिकता + राष्ट्रिय परिचयपत्र (Requires Both)
            </span>
            <span style="background:#dcfce7;border:1px solid #bbf7d0;color:#166534;padding:8px 14px;border-radius:20px;font-size:0.9rem;font-weight:600;">
                ४. सवारी चालक अनुमति पत्र &larr; नागरिकता आवश्यक (Requires Citizenship)
            </span>
        </div>
    </div>

    <!-- Detailed Services List -->
    <div style="display:flex;flex-direction:column;gap:20px;">
        <?php foreach ($services as $s): ?>
            <div style="background:white;border:1px solid var(--border-color);border-radius:var(--radius-lg);padding:24px;box-shadow:var(--shadow-sm);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px;">
                <div style="display:flex;align-items:flex-start;gap:18px;max-width:800px;">
                    <div class="service-icon-wrap" style="width:60px;height:60px;font-size:1.8rem;">
                        <i class="bi <?= htmlspecialchars($s['icon'] ?? 'bi-file-earmark-text') ?>"></i>
                    </div>
                    <div>
                        <div style="display:flex;gap:10px;align-items:center;margin-bottom:6px;">
                            <span class="service-meta-tag"><?= htmlspecialchars($s['category']) ?></span>
                            <?php if (!empty($s['prereq_names_np'])): ?>
                                <span style="background:#fee2e2;color:#991b1b;font-size:0.75rem;padding:3px 8px;border-radius:6px;font-weight:700;">
                                    ⚠️ पूर्वसर्त: <?= htmlspecialchars($s['prereq_names_np']) ?>
                                </span>
                            <?php else: ?>
                                <span style="background:#dcfce7;color:#166534;font-size:0.75rem;padding:3px 8px;border-radius:6px;font-weight:700;">
                                    ✓ आधारभूत सेवा (Foundational)
                                </span>
                            <?php endif; ?>
                        </div>
                        <h3 style="font-size:1.25rem;font-weight:700;color:var(--text-main);margin-bottom:6px;">
                            <?= htmlspecialchars($s['name_np']) ?> (<?= htmlspecialchars($s['name_en']) ?>)
                        </h3>
                        <p style="font-size:0.9rem;color:var(--text-muted);margin-bottom:10px;line-height:1.5;">
                            <?= htmlspecialchars($s['description_np']) ?>
                        </p>
                        <div style="display:flex;gap:20px;font-size:0.85rem;color:var(--text-main);">
                            <span><i class="bi bi-cash" style="color:var(--success);"></i> शुल्क: <strong><?= htmlspecialchars($s['standard_fee']) ?></strong></span>
                            <span><i class="bi bi-clock" style="color:var(--primary);"></i> समय: <strong><?= htmlspecialchars($s['estimated_days']) ?></strong></span>
                        </div>
                    </div>
                </div>

                <div>
                    <button class="btn-start-wizard" style="padding:10px 18px;font-size:0.95rem;" onclick="openServiceWizard(<?= (int)$s['id'] ?>)">
                        <span data-np="मार्गदर्शन सुरु गर्नुहोस्" data-en="Start Wizard">मार्गदर्शन सुरु गर्नुहोस्</span> <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Personalized Decision & Guidance Results View -->
    <section id="resultsView" class="results-container" style="margin-top:35px;"></section>
</main>

<!-- Interactive Decision Wizard Modal Dialog -->
<div id="wizardModalOverlay" class="wizard-overlay">
    <div class="wizard-modal">
        <div class="wizard-header">
            <div class="wizard-header-title">
                <i class="bi bi-patch-question"></i> <span id="wizardServiceTitle">सेवा मार्गदर्शन</span>
            </div>
            <button class="btn-close-modal" onclick="closeWizard()" title="बन्द गर्नुहोस् (Close)">&times;</button>
        </div>
        
        <div class="wizard-body">
            <div class="wizard-progress">
                <div id="wizardProgressBar" class="wizard-progress-bar"></div>
            </div>
            
            <div id="wizardQuestionContainer"></div>
        </div>

        <div class="wizard-footer">
            <button id="btnWizardPrev" class="btn-action btn-action-secondary" onclick="wizardPrev()" style="display:none;">
                &larr; <span data-np="अघिल्लो" data-en="Previous">अघिल्लो</span>
            </button>
            <div style="flex:1;"></div>
            <button id="btnWizardNext" class="btn-action btn-action-primary" onclick="wizardNext()">
                <span data-np="अर्को &rarr;" data-en="Next &rarr;">अर्को &rarr;</span>
            </button>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
