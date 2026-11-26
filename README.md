# मेरो सहायता (Mero Sahayata)
### A Rule-Based Public Service Guidance and Decision Support System for Low Digital-Literate Citizens

**Academic Level:** Bachelor in Computer Application (BCA) — 6th Semester Project  
**Technology Stack:** PHP (Core / OOP PDO), MySQL & SQLite (Dual Database Engine), HTML5, CSS3 (Accessible & High Contrast), Vanilla JavaScript, Web Speech API (STT & TTS)

---

## 1. Project Overview & Motivation

In Nepal, administrative public services (such as **Passport / E-Passport**, **National Identity Card (NID)**, **Citizenship**, and **Driving License**) have strict, non-linear dependencies. For example:
- An applicant cannot apply for an E-Passport without a 16-digit National Identity Number (NIN).
- An applicant cannot get an NID without an original physical Citizenship Certificate.
- A minor under 16 cannot obtain standard citizenship; they must present a Birth Registration certificate.

Most existing government websites act merely as static content portals with lengthy text that confuse low-literate citizens. 

**Mero Sahayata** transforms public service navigation from a passive blog into an **interactive Knowledge-Based Decision Support System (DSS)**. It interrogates the user through a guided interview wizard, evaluates administrative rules, detects missing prerequisites, generates a personalized document checklist with real-time progress tracking, routes the user to their local district government office, and provides audio narration in Nepali.

---

## 2. Technical Architecture & Algorithmic Modules

```
[ User Input: Nepali / Romanized / Voice Speech ]
                        │
                        ▼
           [ Smart Search Engine ]
    (Tokenization + Inverted Index + Levenshtein Distance)
                        │
                        ▼
           [ Service Intent Identified ]
                        │
                        ▼
       [ Dynamic Question Wizard (State Machine) ]
                        │
                        ▼
     [ Rule-Based Inference Engine (Forward Chaining) ]
       ├── 1. Prerequisite Graph Check (Citizenship -> NID -> Passport)
       ├── 2. Conditional Rule Check (Age limit, First-Time vs Renewal)
       ├── 3. Dynamic Document Filtering (Mandatory vs Conditional)
       └── 4. District Office Geo-Routing
                        │
                        ▼
        [ Spoken Audio Guidance (Web Speech TTS) ]
        [ Interactive Document Checklist (Progress 0/N) ]
        [ 4-Step Actionable Procedure Timeline ]
        [ One-Click Print & Official Portal Redirection ]
```

### Core Algorithms Implemented:
1. **Rule-Based Inference Engine (`api/evaluate.php`):**
   - Implements **Forward-Chaining Propositional Logic**:
     $$\text{Status} = \begin{cases} 
     \text{BLOCK\_AND\_REDIRECT}(S_{\text{prereq}}) & \text{if } \exists p \in \text{Prerequisites}(S), \; \text{Missing}(p) \\
     \text{INELIGIBLE}(\text{Rule}) & \text{if } \text{Condition}(\text{Age}, \text{Criteria}) = \text{False} \\
     \text{ELIGIBLE} & \text{otherwise}
     \end{cases}$$
2. **Bilingual Fuzzy Search (`api/search.php`):**
   - Employs **Levenshtein Distance** for romanized Nepali typos (`nagrita` $\rightarrow$ `nagarikta` $\rightarrow$ Citizenship).
   - Word tokenization and keyword index matching for natural sentence queries (e.g. *"विदेश जान राहदानी"*).
3. **Speech & Audio Narration (`assets/js/speech.js`):**
   - Web Speech Recognition API (`ne-NP` and `en-US`) for hands-free voice search.
   - Speech Synthesis API (TTS) for narrating personalized instructions aloud to citizens who struggle with reading.

---

## 3. Directory & File Structure

```
mero-sahayata/
├── config/
│   └── db.php                  # PDO connection with dual driver (MySQL + SQLite fallback)
├── database/
│   ├── database.sql            # Full MySQL relational schema + seed data
│   ├── init_db.php             # Web & CLI one-click MySQL installer
│   ├── setup_sqlite.php        # Standalone SQLite database generator
│   └── mero_sahayata.sqlite    # Ready-to-use SQLite database
├── api/
│   ├── search.php              # Smart search (Tokenization + Levenshtein fuzzy matching)
│   ├── wizard.php              # Dynamic question provider API
│   ├── evaluate.php            # Core Rule-Based Decision Engine
│   └── feedback.php            # User ratings & comments handler
├── includes/
│   ├── header.php              # Global navigation, language toggle & font scaler
│   └── footer.php              # Global footer & government hotline directory
├── assets/
│   ├── css/
│   │   └── style.css           # High accessibility, high contrast, mobile-first styling
│   └── js/
│       ├── speech.js           # Web Speech API (Voice Recognition & TTS Audio)
│       └── app.js              # State machine, interactive wizard & checklist controller
├── admin/
│   ├── login.php               # Admin authentication guard
│   ├── logout.php              # Session cleanup
│   ├── dashboard.php           # Analytics, rule engine matrix & feedback list
│   └── manage_services.php     # Service CRUD management
├── index.php                   # Citizen interactive guidance dashboard
├── services.php                # Directory of all services & prerequisite dependency tree
└── README.md                   # Complete documentation & defense guide
```

---

## 4. How to Run the Project

You can run the project using either **Option A (Instant Built-in PHP Server with SQLite)** or **Option B (XAMPP Apache + MySQL)**.

### Option A: Instant Run with PHP Built-in Server (Recommended for Quick Demo)
Since the SQLite database `mero_sahayata.sqlite` is already populated and configured with dual-driver fallback:
1. Open PowerShell / Command Prompt.
2. Navigate to the project directory:
   ```powershell
   cd C:\Users\NITRO\.gemini\antigravity\scratch\mero-sahayata
   ```
3. Start the PHP server:
   ```powershell
   C:\xampp\php\php.exe -S localhost:8000
   ```
4. Open your browser and visit:  
   `http://localhost:8000`

---

### Option B: Run with XAMPP (Apache + MySQL)
1. Start **Apache** and **MySQL** in your **XAMPP Control Panel**.
2. Copy or symlink this folder to your XAMPP `htdocs`:
   - Destination: `C:\xampp\htdocs\mero-sahayata`
3. Initialize the MySQL database:
   - Method 1: Open your browser and visit: `http://localhost/mero-sahayata/database/init_db.php`
   - Method 2: Or import `database/database.sql` directly inside **phpMyAdmin** (`http://localhost/phpmyadmin`).
4. Access the application:  
   `http://localhost/mero-sahayata/`

---

## 5. Administrative Credentials

- **Admin Login URL:** `http://localhost:8000/admin/login.php` (or `http://localhost/mero-sahayata/admin/login.php`)
- **Username:** `admin`
- **Password:** `admin123`

---

## 6. Viva / Defense Q&A Preparation for BCA Examiners

### Q1: How is this system different from a government blog or CMS?
> **Answer:** A blog or standard CMS only provides static CRUD display where the same text is shown to everyone. In *Mero Sahayata*, the output is dynamically synthesized using a **Rule-Based Inference Engine**. Depending on user answers (e.g., whether they hold citizenship, whether they have a National ID, their age, or whether it is their first time vs renewal), the engine branches into different dependency states, blocks invalid paths, generates an individualized document checklist, and routes to their local office.

### Q2: What algorithm was used for rule evaluation?
> **Answer:** We implemented **Forward-Chaining Inference Logic**. The system starts with the user's premise answers ($\text{Premise}_1, \text{Premise}_2, \dots$) and matches them against the condition rules table ($\text{Condition} \rightarrow \text{Action}$). If an unsatisfied prerequisite rule fires (`BLOCK_AND_REDIRECT`), the engine intercepts the execution and recommends the foundational service first.

### Q3: How did you implement fuzzy search for Romanized Nepali?
> **Answer:** Low-literate users often type phonetic Romanized Nepali with typos (e.g., `nagrita` or `nagrikta` instead of `nagarikta`). We implemented string normalization combined with the **Levenshtein Distance metric** to compute character-level similarity. If similarity $\ge 70\%$, the term is mapped to the corresponding service intent.

### Q4: How is accessibility for low digital-literacy users addressed?
> **Answer:**
> 1. **Voice Input:** Citizens can click the microphone icon and speak their requirement in Nepali (`ne-NP`).
> 2. **Audio Guidance (TTS):** The system generates a natural spoken Nepali script and reads the eligibility decision and procedure aloud.
> 3. **Visual Hierarchy:** Large touch targets, font scaling (A, A+, A++), high contrast mode, and an interactive checklist with strike-through progress bars.
