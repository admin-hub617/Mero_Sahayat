<?php
/**
 * Mero Sahayata - Main Interactive Portal
 * BCA 6th Semester Project
 */

require_once __DIR__ . '/config/db.php';

$pageTitle = 'मेरो सहायता - सार्वजनिक सेवा मार्गदर्शन तथा निर्णय प्रणाली';

// Fetch default services for display
$db = Database::getConnection();
$stmt = $db->query("SELECT * FROM services ORDER BY id ASC");
$services = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<main class="main-wrapper">
    <!-- Hero & Voice Search Section -->
    <section class="hero-search-box">
        <h1 class="hero-title" data-np="सरकारी सेवाहरू अब सरल र स्पष्ट" data-en="Public Services Made Clear & Accessible">
            सरकारी सेवाहरू अब सरल र स्पष्ट
        </h1>
        <p class="hero-subtitle" data-np="कुन कागजात चाहिन्छ? कहाँ जाने? प्रक्रिया के हो? बोलेर वा लेखेर खोज्नुहोस्, प्रणालीले तपाईंलाई चरणबद्ध बाटो देखाउनेछ।" data-en="Which documents do you need? Where do you go? Search by voice or text, and let the rule engine guide you step-by-step.">
            कुन कागजात चाहिन्छ? कहाँ जाने? प्रक्रिया के हो? बोलेर वा लेखेर खोज्नुहोस्, प्रणालीले तपाईंलाई चरणबद्ध बाटो देखाउनेछ।
        </p>

        <!-- Search Bar with Web Speech Voice Input -->
        <div class="search-form-wrapper">
            <form id="searchForm">
                <div class="search-input-group">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" id="searchInput" class="search-input-field" 
                           placeholder="तपाईंलाई कुन सरकारी सेवा चाहिएको छ? (उदा: राहदानी, नागरिकता, लाइसेन्स...)" 
                           autocomplete="off">
                    
                    <!-- Microphone voice button -->
                    <button type="button" id="btnVoiceSearch" class="btn-voice-input" title="बोलेर खोज्नुहोस् / Voice Search">
                        <i class="bi bi-mic-fill"></i>
                    </button>
                    
                    <button type="submit" class="btn-search-submit" data-np="खोज्नुहोस्" data-en="Search">
                        खोज्नुहोस्
                    </button>
                </div>
            </form>
            <div id="voiceStatus" class="voice-status-indicator"></div>
        </div>
    </section>

    <!-- Popular Anchor Services Grid -->
    <section>
        <div class="section-heading">
            <h2 id="servicesSectionTitle" class="section-title">
                <i class="bi bi-stars" style="color:#f59e0b;"></i>
                <span data-np="प्रमुख नागरिक सेवाहरू" data-en="Featured Public Services">प्रमुख नागरिक सेवाहरू</span>
            </h2>
            <a href="services.php" style="color:var(--primary);font-weight:700;text-decoration:none;font-size:0.95rem;">
                <span data-np="सबै सेवाहरू हेर्नुहोस् &rarr;" data-en="View All Services &rarr;">सबै सेवाहरू हेर्नुहोस् &rarr;</span>
            </a>
        </div>

        <div id="servicesGrid" class="services-grid">
            <?php foreach ($services as $s): ?>
                <div class="service-card" onclick="openServiceWizard(<?= (int)$s['id'] ?>)">
                    <div>
                        <div class="service-card-top">
                            <div class="service-icon-wrap">
                                <i class="bi <?= htmlspecialchars($s['icon'] ?? 'bi-file-earmark-text') ?>"></i>
                            </div>
                            <div>
                                <span class="service-meta-tag"><?= htmlspecialchars($s['category']) ?></span>
                                <h3 class="service-card-title"><?= htmlspecialchars($s['name_np']) ?></h3>
                            </div>
                        </div>
                        <p class="service-card-desc"><?= htmlspecialchars($s['description_np']) ?></p>
                    </div>
                    <div class="service-card-footer">
                        <span class="fee-badge"><i class="bi bi-cash"></i> <?= htmlspecialchars($s['standard_fee']) ?></span>
                        <button class="btn-start-wizard" onclick="event.stopPropagation(); openServiceWizard(<?= (int)$s['id'] ?>)">
                            <span data-np="मार्गदर्शन लिनुहोस्" data-en="Get Guidance">मार्गदर्शन लिनुहोस्</span> <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Personalized Decision & Guidance Results View (Populated Dynamically) -->
    <section id="resultsView" class="results-container"></section>
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
            
            <div id="wizardQuestionContainer">
                <!-- Dynamically populated via app.js -->
            </div>
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
