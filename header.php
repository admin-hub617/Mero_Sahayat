<?php
/**
 * Mero Sahayata - Global Header Component
 */
if (!isset($pageTitle)) {
    $pageTitle = 'मेरो सहायता - सार्वजनिक सेवा मार्गदर्शन प्रणाली';
}
?>
<!DOCTYPE html>
<html lang="ne">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Google Fonts for Devanagari & Latin -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mukta:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@500;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Core Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Accessibility Utility Bar -->
<div class="utility-bar">
    <div>
        <span>🇳🇵 <span data-np="नेपाल सरकार सार्वजनिक सेवा निर्णय सहायता प्रणाली | BCA 6th Sem Project" data-en="Government of Nepal Public Service Decision Support System | BCA 6th Sem Project">नेपाल सरकार सार्वजनिक सेवा निर्णय सहायता प्रणाली | BCA 6th Sem Project</span></span>
    </div>
    <div class="utility-controls">
        <span data-np="अक्षर आकार (Font):" data-en="Font Size:">अक्षर आकार (Font):</span>
        <button id="btnFontNormal" title="Normal Font">A</button>
        <button id="btnFontLarge" title="Large Font">A+</button>
        <button id="btnFontXLarge" title="Extra Large Font">A++</button>
        <button id="btnToggleContrast" title="High Contrast Mode"><i class="bi bi-circle-half"></i> <span data-np="कन्ट्रास्ट" data-en="Contrast">कन्ट्रास्ट</span></button>
    </div>
</div>

<!-- Main Sticky Header -->
<header class="site-header">
    <div class="header-container">
        <a href="index.php" class="brand-link">
            <div class="brand-emblem">
                <i class="bi bi-shield-check"></i>
            </div>
            <div>
                <div class="brand-title" data-np="मेरो सहायता" data-en="Mero Sahayata">मेरो सहायता</div>
                <div class="brand-sub" data-np="सार्वजनिक सेवा मार्गदर्शन तथा निर्णय प्रणाली" data-en="Public Service Guidance & Decision Support System">
                    सार्वजनिक सेवा मार्गदर्शन तथा निर्णय प्रणाली
                </div>
            </div>
        </a>

        <nav>
            <ul class="nav-links">
                <li><a href="index.php"><i class="bi bi-house-door"></i> <span data-np="गृहपृष्ठ" data-en="Home">गृहपृष्ठ</span></a></li>
                <li><a href="services.php"><i class="bi bi-grid-3x3-gap"></i> <span data-np="सबै सेवाहरू" data-en="All Services">सबै सेवाहरू</span></a></li>
                <li><a href="admin/dashboard.php"><i class="bi bi-speedometer2"></i> <span data-np="प्रशासक" data-en="Admin">प्रशासक</span></a></li>
                <li>
                    <button id="btnLangToggle" class="btn-lang" title="भाषा परिवर्तन / Change Language">
                        🇳🇵 नेपाली (ने)
                    </button>
                </li>
            </ul>
        </nav>
    </div>
</header>
