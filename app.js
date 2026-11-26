/**
 * Mero Sahayata - Interactive Decision Wizard & Application Controller
 * Handles Search, Questionnaire State Machine, Rule Evaluation, and Checklist Tracking
 */

let currentLang = 'np'; // 'np' or 'en'
let wizardState = {
    serviceId: null,
    service: null,
    questions: [],
    currentIndex: 0,
    answers: {},
    audioScriptNp: '',
    audioScriptEn: ''
};

document.addEventListener('DOMContentLoaded', () => {
    initAccessibilityControls();
    initSearchHandler();
    initLanguage();
});

/**
 * 1. Accessibility Controls: Font Scaler & High Contrast
 */
function initAccessibilityControls() {
    const btnContrast = document.getElementById('btnToggleContrast');
    if (btnContrast) {
        btnContrast.addEventListener('click', () => {
            document.body.classList.toggle('high-contrast');
            localStorage.setItem('ms_contrast', document.body.classList.contains('high-contrast') ? '1' : '0');
        });
        if (localStorage.getItem('ms_contrast') === '1') {
            document.body.classList.add('high-contrast');
        }
    }

    const btnFontNormal = document.getElementById('btnFontNormal');
    const btnFontLarge = document.getElementById('btnFontLarge');
    const btnFontXLarge = document.getElementById('btnFontXLarge');

    if (btnFontNormal && btnFontLarge && btnFontXLarge) {
        btnFontNormal.addEventListener('click', () => setFontSize('normal'));
        btnFontLarge.addEventListener('click', () => setFontSize('large'));
        btnFontXLarge.addEventListener('click', () => setFontSize('xlarge'));
    }
}

function setFontSize(size) {
    document.body.classList.remove('font-large', 'font-xlarge');
    if (size === 'large') document.body.classList.add('font-large');
    if (size === 'xlarge') document.body.classList.add('font-xlarge');
    localStorage.setItem('ms_font_size', size);
}

/**
 * 2. Language Switcher (Nepali / English)
 */
function initLanguage() {
    const saved = localStorage.getItem('ms_lang') || 'np';
    setLanguage(saved);

    const toggleBtn = document.getElementById('btnLangToggle');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const next = currentLang === 'np' ? 'en' : 'np';
            setLanguage(next);
        });
    }
}

function setLanguage(lang) {
    currentLang = lang;
    localStorage.setItem('ms_lang', lang);
    const toggleBtn = document.getElementById('btnLangToggle');
    if (toggleBtn) {
        toggleBtn.innerHTML = lang === 'np' ? '🇳🇵 नेपाली (ने)' : '🇺🇸 English (EN)';
    }

    // Update all elements with data-np and data-en
    document.querySelectorAll('[data-np][data-en]').forEach(el => {
        el.innerText = lang === 'np' ? el.getAttribute('data-np') : el.getAttribute('data-en');
    });

    // Update search placeholder
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.placeholder = lang === 'np'
            ? 'तपाईंलाई कुन सरकारी सेवा चाहिएको छ? (उदा: राहदानी, नागरिकता, लाइसेन्स...)'
            : 'Which public service do you need guidance for? (e.g. Passport, Citizenship...)';
    }
}

/**
 * 3. Smart Search Handler
 */
function initSearchHandler() {
    const form = document.getElementById('searchForm');
    const input = document.getElementById('searchInput');
    const voiceBtn = document.getElementById('btnVoiceSearch');

    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const q = input.value.trim();
            if (q) triggerSearch(q);
        });
    }

    if (voiceBtn) {
        voiceBtn.addEventListener('click', () => {
            window.speechHelper.toggleListening();
        });
    }
}

window.triggerSearch = async function(query, type = 'text') {
    const statusEl = document.getElementById('voiceStatus');
    if (statusEl) statusEl.innerText = "खोज्दैछ... (Searching...)";

    try {
        const res = await fetch(`api/search.php?q=${encodeURIComponent(query)}&type=${type}`);
        const data = await res.json();

        if (statusEl) statusEl.innerText = "";

        if (data.success && data.results && data.results.length > 0) {
            renderSearchResults(data.results, data.is_fallback);
        } else {
            alert(currentLang === 'np' ? "कुनै सेवा फेला परेन। कृपया पुनः प्रयास गर्नुहोस्।" : "No matching services found.");
        }
    } catch (err) {
        console.error("Search error:", err);
        if (statusEl) statusEl.innerText = "Error querying search API.";
    }
};

function renderSearchResults(results, isFallback) {
    const container = document.getElementById('servicesGrid');
    if (!container) return;

    // Scroll to results section smoothly
    const heading = document.getElementById('servicesSectionTitle');
    if (heading) {
        heading.innerText = isFallback 
            ? (currentLang === 'np' ? "सिफारिस गरिएका मुख्य सेवाहरू" : "Recommended Public Services")
            : (currentLang === 'np' ? `खोज परिणामहरू (${results.length} फेला पर्यो)` : `Search Results (${results.length} Found)`);
        heading.scrollIntoView({ behavior: 'smooth' });
    }

    let html = '';
    results.forEach(s => {
        const name = currentLang === 'np' ? s.name_np : s.name_en;
        const desc = s.description_np || s.description_en || '';
        const matchBadge = s.score > 0 ? `<span class="service-meta-tag" style="background:#dbeafe;color:#1e40af;">Match Score: ${s.score}</span>` : '';

        html += `
        <div class="service-card" onclick="openServiceWizard(${s.service_id || s.id})">
            <div>
                <div class="service-card-top">
                    <div class="service-icon-wrap"><i class="bi ${s.icon || 'bi-file-earmark'}"></i></div>
                    <div>
                        ${matchBadge}
                        <h3 class="service-card-title">${name}</h3>
                    </div>
                </div>
                <p class="service-card-desc">${desc}</p>
            </div>
            <div class="service-card-footer">
                <span class="fee-badge"><i class="bi bi-cash"></i> ${s.standard_fee}</span>
                <button class="btn-start-wizard" onclick="event.stopPropagation(); openServiceWizard(${s.service_id || s.id})">
                    <span>${currentLang === 'np' ? 'सुरु गर्नुहोस्' : 'Start'}</span> <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>`;
    });

    container.innerHTML = html;
}

/**
 * 4. Interactive Decision Wizard Engine
 */
window.openServiceWizard = async function(serviceId) {
    try {
        const res = await fetch(`api/wizard.php?service_id=${serviceId}`);
        const data = await res.json();

        if (!data.success) {
            alert(data.message || "Failed to load service wizard");
            return;
        }

        wizardState = {
            serviceId: data.service.id,
            service: data.service,
            questions: data.questions,
            currentIndex: 0,
            answers: {},
            audioScriptNp: '',
            audioScriptEn: ''
        };

        const modal = document.getElementById('wizardModalOverlay');
        const title = document.getElementById('wizardServiceTitle');
        if (title) title.innerText = currentLang === 'np' ? data.service.name_np : data.service.name_en;

        if (modal) modal.classList.add('active');

        renderCurrentQuestion();
    } catch (e) {
        console.error("Error opening wizard:", e);
    }
};

window.closeWizard = function() {
    const modal = document.getElementById('wizardModalOverlay');
    if (modal) modal.classList.remove('active');
};

function renderCurrentQuestion() {
    const body = document.getElementById('wizardQuestionContainer');
    const progress = document.getElementById('wizardProgressBar');
    const btnNext = document.getElementById('btnWizardNext');
    const btnPrev = document.getElementById('btnWizardPrev');

    if (!wizardState.questions || wizardState.questions.length === 0) {
        // No questions, directly evaluate
        submitWizardEvaluation();
        return;
    }

    const total = wizardState.questions.length;
    const currentQ = wizardState.questions[wizardState.currentIndex];
    const pct = Math.round(((wizardState.currentIndex) / total) * 100);
    if (progress) progress.style.width = `${pct}%`;

    if (btnPrev) {
        btnPrev.style.display = wizardState.currentIndex > 0 ? 'inline-block' : 'none';
    }

    const qText = currentLang === 'np' ? currentQ.question_text_np : currentQ.question_text_en;
    let inputHtml = '';

    if (currentQ.input_type === 'yes_no') {
        const currentAns = wizardState.answers[currentQ.question_key] || '';
        inputHtml = `
        <div class="options-grid">
            <button type="button" class="option-btn ${currentAns === 'yes' ? 'selected' : ''}" onclick="selectOption('${currentQ.question_key}', 'yes')">
                <i class="bi bi-check-circle-fill"></i> ${currentLang === 'np' ? 'हो (Yes)' : 'Yes'}
            </button>
            <button type="button" class="option-btn ${currentAns === 'no' ? 'selected' : ''}" onclick="selectOption('${currentQ.question_key}', 'no')">
                <i class="bi bi-x-circle-fill"></i> ${currentLang === 'np' ? 'होइन (No)' : 'No'}
            </button>
        </div>`;
    } else if (currentQ.input_type === 'select' && currentQ.options) {
        inputHtml = `<select class="wizard-select-input" onchange="wizardState.answers['${currentQ.question_key}'] = this.value">
            <option value="">-- ${currentLang === 'np' ? 'कृपया छान्नुहोस्' : 'Please select'} --</option>`;
        for (const [key, label] of Object.entries(currentQ.options)) {
            const isSel = wizardState.answers[currentQ.question_key] === key ? 'selected' : '';
            inputHtml += `<option value="${key}" ${isSel}>${label}</option>`;
        }
        inputHtml += `</select>`;
    } else if (currentQ.input_type === 'number') {
        const currentVal = wizardState.answers[currentQ.question_key] || '';
        inputHtml = `
        <input type="number" class="wizard-select-input" placeholder="${currentLang === 'np' ? 'अंकमा लेख्नुहोस् (उदा: 22)' : 'Enter number (e.g. 22)'}"
            value="${currentVal}" oninput="wizardState.answers['${currentQ.question_key}'] = this.value" min="1" max="120">`;
    }

    body.innerHTML = `
        <div class="question-box">
            <div style="font-size:0.85rem;color:var(--text-muted);margin-bottom:6px;">
                ${currentLang === 'np' ? 'प्रश्न' : 'Question'} ${wizardState.currentIndex + 1} / ${total}
            </div>
            <h4 class="question-title">${qText}</h4>
            ${inputHtml}
        </div>
    `;

    if (btnNext) {
        btnNext.innerText = (wizardState.currentIndex === total - 1)
            ? (currentLang === 'np' ? 'नतिजा हेर्नुहोस् (Evaluate) →' : 'Evaluate Results →')
            : (currentLang === 'np' ? 'अर्को (Next) →' : 'Next →');
    }
}

window.selectOption = function(key, val) {
    wizardState.answers[key] = val;
    renderCurrentQuestion();
};

window.wizardNext = function() {
    const currentQ = wizardState.questions[wizardState.currentIndex];
    if (!wizardState.answers[currentQ.question_key]) {
        alert(currentLang === 'np' ? "कृपया यस प्रश्नको जवाफ दिनुहोस्।" : "Please answer this question to proceed.");
        return;
    }

    if (wizardState.currentIndex < wizardState.questions.length - 1) {
        wizardState.currentIndex++;
        renderCurrentQuestion();
    } else {
        // Last question answered -> Submit evaluation
        submitWizardEvaluation();
    }
};

window.wizardPrev = function() {
    if (wizardState.currentIndex > 0) {
        wizardState.currentIndex--;
        renderCurrentQuestion();
    }
};

/**
 * 5. Submit Answers to Rule-Based Inference Engine
 */
async function submitWizardEvaluation() {
    closeWizard();
    const resultsContainer = document.getElementById('resultsView');
    if (!resultsContainer) return;

    resultsContainer.style.display = 'block';
    resultsContainer.innerHTML = `<div style="text-align:center;padding:40px;"><i class="bi bi-arrow-repeat" style="font-size:2rem;animation:spin 1s infinite linear;"></i><p>नियमहरू परीक्षण गर्दै... (Evaluating Rule-Based Decision Engine...)</p></div>`;
    resultsContainer.scrollIntoView({ behavior: 'smooth' });

    try {
        const res = await fetch('api/evaluate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                service_id: wizardState.serviceId,
                answers: wizardState.answers
            })
        });

        const data = await res.json();
        renderDecisionResults(data);
    } catch (e) {
        console.error("Evaluation error:", e);
        resultsContainer.innerHTML = `<div class="decision-alert blocked">Error evaluating rules.</div>`;
    }
}

/**
 * 6. Render Decision, Document Checklist, Office Routing & Audio
 */
function renderDecisionResults(data) {
    const container = document.getElementById('resultsView');
    if (!container) return;

    wizardState.audioScriptNp = data.audio_assistance?.script_np || '';
    wizardState.audioScriptEn = data.audio_assistance?.script_en || '';

    let html = '';

    // A. Blocking Rule Triggered (Prerequisite or Ineligibility)
    if (data.status === 'BLOCKED') {
        const dec = data.decision;
        const rec = dec.recommended_service;

        html += `
        <div class="decision-alert blocked">
            <div class="decision-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div style="flex:1;">
                <div class="decision-title">${currentLang === 'np' ? dec.title_np : dec.title_en}</div>
                <div class="decision-message">${currentLang === 'np' ? dec.message_np : dec.message_en}</div>
            </div>
        </div>

        <div class="audio-guidance-bar">
            <div class="audio-info">
                <i class="bi bi-volume-up-fill" style="font-size:1.5rem;color:var(--primary);"></i>
                <div>
                    <strong>${currentLang === 'np' ? 'अडियो सहायता' : 'Audio Assistance'}</strong>
                    <div style="font-size:0.85rem;color:var(--text-muted);">${currentLang === 'np' ? 'यो निर्णय सुन्नुहोस्' : 'Listen to this decision aloud'}</div>
                </div>
            </div>
            <button id="btnAudioSpeak" class="btn-audio-speak" onclick="playCurrentAudio()">
                <i class="bi bi-volume-up"></i> <span>${currentLang === 'np' ? 'सुन्नुहोस् (Listen)' : 'Listen Audio'}</span>
            </button>
        </div>`;

        if (rec) {
            html += `
            <div style="background:#f8fafc;padding:24px;border-radius:12px;border:2px dashed #cbd5e1;text-align:center;margin-top:20px;">
                <h4 style="margin-bottom:8px;color:var(--primary);">${currentLang === 'np' ? 'सिफारिस गरिएको पूर्वसेवा (Recommended Next Step):' : 'Recommended Foundational Service:'}</h4>
                <p style="margin-bottom:18px;font-size:1.1rem;font-weight:700;">${currentLang === 'np' ? rec.name_np : rec.name_en}</p>
                <button class="btn-action btn-action-primary" style="margin:0 auto;" onclick="openServiceWizard(${rec.id})">
                    <i class="bi ${rec.icon || 'bi-arrow-repeat'}"></i> 
                    ${currentLang === 'np' ? `${rec.name_np} को प्रक्रिया सुरु गर्नुहोस्` : `Start ${rec.name_en} Wizard`}
                </button>
            </div>`;
        }

        container.innerHTML = html;
        return;
    }

    // B. User is Eligible -> Show Checklist, Steps, and Office
    const svc = data.service;
    const checklist = data.checklist || [];
    const office = data.recommended_office;
    const steps = data.steps || [];

    html += `
    <div class="decision-alert eligible">
        <div class="decision-icon"><i class="bi bi-check-circle-fill"></i></div>
        <div style="flex:1;">
            <div class="decision-title">${currentLang === 'np' ? data.decision.title_np : data.decision.title_en}</div>
            <div class="decision-message">${currentLang === 'np' ? svc.name_np : svc.name_en} — शुल्क: <strong>${svc.standard_fee}</strong> | समय: <strong>${svc.estimated_days}</strong></div>
        </div>
    </div>

    <!-- Audio Assistance Player -->
    <div class="audio-guidance-bar">
        <div class="audio-info">
            <i class="bi bi-volume-up-fill" style="font-size:1.6rem;color:var(--primary);"></i>
            <div>
                <strong>${currentLang === 'np' ? 'अडियो मार्गदर्शन (Audio Guidance)' : 'Spoken Audio Assistance'}</strong>
                <div style="font-size:0.85rem;color:var(--text-muted);">${currentLang === 'np' ? 'सम्पूर्ण निर्देशन र प्रक्रिया आवाजमा सुन्नुहोस्' : 'Listen to instructions in Nepali audio narration'}</div>
            </div>
        </div>
        <button id="btnAudioSpeak" class="btn-audio-speak" onclick="playCurrentAudio()">
            <i class="bi bi-volume-up"></i> <span>${currentLang === 'np' ? 'निर्देशन सुन्नुहोस् (Listen)' : 'Listen'}</span>
        </button>
    </div>

    <!-- Dynamic Document Checklist -->
    <div class="checklist-card">
        <div class="checklist-header">
            <h3 style="font-size:1.25rem;font-weight:700;"><i class="bi bi-card-checklist" style="color:var(--primary);"></i> ${currentLang === 'np' ? 'व्यक्तिगत कागजात चेकलिस्ट' : 'Personalized Document Checklist'}</h3>
            <span id="checklistProgressBadge" class="progress-counter-badge">0 / ${checklist.length} ${currentLang === 'np' ? 'तयार भयो' : 'Ready'}</span>
        </div>
        <p style="font-size:0.9rem;color:var(--text-muted);margin-bottom:12px;">${currentLang === 'np' ? 'तपाईंले संकलन गरेका कागजातमा टिक (✓) लगाउनुहोस्:' : 'Check off documents as you arrange them:'}</p>
        <ul class="checklist-items">`;

    checklist.forEach((doc, idx) => {
        const title = currentLang === 'np' ? doc.name_np : doc.name_en;
        const notes = currentLang === 'np' ? doc.notes_np : doc.notes_en;
        const authority = currentLang === 'np' ? doc.issuing_authority_np : doc.issuing_authority_en;

        html += `
        <li class="checklist-item" id="chkDoc_${idx}" onclick="toggleChecklistItem(${idx})">
            <div class="checkbox-custom" id="chkBox_${idx}"><i class="bi bi-check" style="display:none;"></i></div>
            <div class="doc-info">
                <div class="doc-title">${title} ${doc.is_mandatory ? '<span style="color:#dc2626;font-size:0.8rem;">*अनिवार्य</span>' : ''}</div>
                <div class="doc-sub">जारी गर्ने निकाय: ${authority} ${notes ? `| ${notes}` : ''}</div>
            </div>
        </li>`;
    });

    html += `
        </ul>
    </div>`;

    // Recommended Government Office
    if (office) {
        html += `
        <div class="office-card">
            <div class="office-card-header">
                <i class="bi bi-geo-alt-fill"></i> ${currentLang === 'np' ? 'सिफारिस गरिएको सेवा केन्द्र / कार्यालय' : 'Recommended Government Office'}
            </div>
            <div class="office-details-grid">
                <div>
                    <strong>${currentLang === 'np' ? office.name_np : office.name_en}</strong><br>
                    <span>${currentLang === 'np' ? office.address_np : office.address_en}</span>
                </div>
                <div>
                    <i class="bi bi-telephone"></i> ${office.phone || 'N/A'}<br>
                    <i class="bi bi-clock"></i> ${office.opening_hours}
                </div>
                <div>
                    ${office.google_maps_url ? `<a href="${office.google_maps_url}" target="_blank" style="color:var(--primary);font-weight:700;"><i class="bi bi-map"></i> Google Maps मा हेर्नुहोस् &rarr;</a>` : ''}
                </div>
            </div>
        </div>`;
    }

    // Step-by-Step Procedure Timeline
    html += `
    <div style="margin-top:25px;">
        <h3 style="font-size:1.25rem;font-weight:700;margin-bottom:15px;"><i class="bi bi-signpost-split" style="color:var(--primary);"></i> ${currentLang === 'np' ? 'प्रक्रिया तथा चरणहरू' : 'Step-by-Step Procedure'}</h3>
        <div class="steps-timeline">`;

    steps.forEach(st => {
        html += `
        <div class="step-card">
            <div class="step-number-circle">${st.step_number}</div>
            <div class="step-content">
                <div class="step-title">${currentLang === 'np' ? st.title_np : st.title_en}</div>
                <div class="step-desc">${currentLang === 'np' ? st.desc_np : st.desc_en}</div>
            </div>
        </div>`;
    });

    html += `
        </div>
    </div>

    <!-- Actions toolbar: Print & Portal link -->
    <div class="results-actions">
        <button class="btn-action btn-action-primary" onclick="window.print()">
            <i class="bi bi-printer"></i> ${currentLang === 'np' ? 'चेकलिस्ट प्रिन्ट गर्नुहोस् (Print)' : 'Print Document Checklist'}
        </button>
        ${svc.online_portal_url ? `
        <a href="${svc.online_portal_url}" target="_blank" class="btn-action btn-action-secondary" style="text-decoration:none;">
            <i class="bi bi-box-arrow-up-right"></i> ${currentLang === 'np' ? 'आधिकारिक अनलाइन पोर्टल खोल्नुहोस्' : 'Open Official Online Portal'}
        </a>` : ''}
        <button class="btn-action btn-action-secondary" onclick="openFeedbackModal(${svc.id})">
            <i class="bi bi-chat-square-quote"></i> ${currentLang === 'np' ? 'प्रतिक्रिया दिनुहोस्' : 'Give Feedback'}
        </button>
    </div>`;

    container.innerHTML = html;
}

window.toggleChecklistItem = function(idx) {
    const item = document.getElementById(`chkDoc_${idx}`);
    const box = document.getElementById(`chkBox_${idx}`);
    if (!item || !box) return;

    item.classList.toggle('checked');
    const icon = box.querySelector('i');
    if (icon) {
        icon.style.display = item.classList.contains('checked') ? 'inline-block' : 'none';
    }

    // Update counter
    const all = document.querySelectorAll('.checklist-item');
    const checked = document.querySelectorAll('.checklist-item.checked');
    const badge = document.getElementById('checklistProgressBadge');
    if (badge) {
        badge.innerText = `${checked.length} / ${all.length} ${currentLang === 'np' ? 'तयार भयो' : 'Ready'}`;
    }
};

window.playCurrentAudio = function() {
    const script = currentLang === 'np' ? wizardState.audioScriptNp : wizardState.audioScriptEn;
    window.speechHelper.speak(script, currentLang === 'np' ? 'ne-NP' : 'en-US');
};

/**
 * 7. Feedback Dialog
 */
window.openFeedbackModal = function(serviceId) {
    const name = prompt(currentLang === 'np' ? "तपाईंको नाम (वैकल्पिक):" : "Your Name (Optional):") || "नागरिक";
    const rating = prompt(currentLang === 'np' ? "यस सहायता प्रणालीलाई कति स्टार दिनुहुन्छ? (1 देखि 5):" : "Rate this assistance (1 to 5):", "5");
    const comments = prompt(currentLang === 'np' ? "कुनै सल्लाह वा प्रतिक्रिया छ?" : "Any comments or suggestions?");

    if (rating) {
        fetch('api/feedback.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                service_id: serviceId,
                user_name: name,
                rating: parseInt(rating),
                comments: comments
            })
        }).then(r => r.json()).then(res => {
            alert(res.message_np || "धन्यवाद!");
        });
    }
};
