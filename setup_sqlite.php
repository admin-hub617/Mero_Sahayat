<?php
/**
 * Mero Sahayata - SQLite Database Generator
 * Creates a portable standalone SQLite database populated with all services, rules, and documents.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$sqliteFile = __DIR__ . '/mero_sahayata.sqlite';
if (file_exists($sqliteFile)) {
    unlink($sqliteFile);
}

try {
    $db = new PDO("sqlite:" . $sqliteFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $schema = <<<SQL
    CREATE TABLE IF NOT EXISTS services (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      code TEXT NOT NULL UNIQUE,
      name_en TEXT NOT NULL,
      name_np TEXT NOT NULL,
      category TEXT NOT NULL,
      description_en TEXT,
      description_np TEXT,
      standard_fee TEXT DEFAULT 'निःशुल्क / Free',
      estimated_days TEXT DEFAULT '१-३ दिन / 1-3 Days',
      online_portal_url TEXT NULL,
      icon TEXT DEFAULT 'bi-file-earmark-text',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS service_prerequisites (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      service_id INTEGER NOT NULL,
      prerequisite_service_id INTEGER NOT NULL,
      is_mandatory INTEGER DEFAULT 1,
      guidance_message_en TEXT NOT NULL,
      guidance_message_np TEXT NOT NULL,
      FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
      FOREIGN KEY (prerequisite_service_id) REFERENCES services(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS documents (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      code TEXT NOT NULL UNIQUE,
      name_en TEXT NOT NULL,
      name_np TEXT NOT NULL,
      issuing_authority_en TEXT,
      issuing_authority_np TEXT,
      description TEXT NULL
    );

    CREATE TABLE IF NOT EXISTS service_documents (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      service_id INTEGER NOT NULL,
      document_id INTEGER NOT NULL,
      is_mandatory INTEGER DEFAULT 1,
      condition_key TEXT NULL,
      condition_value TEXT NULL,
      notes_en TEXT NULL,
      notes_np TEXT NULL,
      FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
      FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS service_questions (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      service_id INTEGER NOT NULL,
      question_key TEXT NOT NULL,
      question_text_en TEXT NOT NULL,
      question_text_np TEXT NOT NULL,
      input_type TEXT DEFAULT 'yes_no',
      options_json TEXT NULL,
      priority INTEGER DEFAULT 1,
      FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS service_rules (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      service_id INTEGER NOT NULL,
      rule_code TEXT NOT NULL,
      condition_field TEXT NOT NULL,
      operator TEXT NOT NULL,
      condition_value TEXT NOT NULL,
      action_type TEXT NOT NULL,
      target_service_id INTEGER NULL,
      result_title_en TEXT NOT NULL,
      result_title_np TEXT NOT NULL,
      result_message_en TEXT NOT NULL,
      result_message_np TEXT NOT NULL,
      action_payload TEXT NULL,
      FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
      FOREIGN KEY (target_service_id) REFERENCES services(id) ON DELETE SET NULL
    );

    CREATE TABLE IF NOT EXISTS offices (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name_en TEXT NOT NULL,
      name_np TEXT NOT NULL,
      district TEXT NOT NULL,
      municipality TEXT NULL,
      service_category TEXT NOT NULL,
      address_en TEXT NOT NULL,
      address_np TEXT NOT NULL,
      phone TEXT NULL,
      email TEXT NULL,
      opening_hours TEXT DEFAULT 'आइतबार - बिहीबार १०:०० - ५:००, शुक्रबार १०:०० - ३:००',
      google_maps_url TEXT NULL
    );

    CREATE TABLE IF NOT EXISTS search_keywords (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      service_id INTEGER NOT NULL,
      keyword TEXT NOT NULL,
      language_type TEXT DEFAULT 'roman',
      FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS admins (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      username TEXT NOT NULL UNIQUE,
      password_hash TEXT NOT NULL,
      full_name TEXT NOT NULL,
      role TEXT DEFAULT 'admin',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS feedback (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      service_id INTEGER NULL,
      user_name TEXT DEFAULT 'अनाम / Anonymous',
      phone TEXT NULL,
      rating INTEGER DEFAULT 5,
      comments TEXT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
    );
SQL;

    $db->exec($schema);

    // Insert Services
    $stmt = $db->prepare("INSERT INTO services (id, code, name_en, name_np, category, description_en, description_np, standard_fee, estimated_days, online_portal_url, icon) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $services = [
        [1, 'CITIZENSHIP', 'Citizenship Certificate', 'नागरिकता प्रमाणपत्र', 'Identity', 
         'The foundational legal identity document for Nepali citizens issued by District Administration Offices.',
         'नेपाली नागरिकको मुख्य कानुनी पहिचान पत्र, जुन सम्बन्धित जिल्ला प्रशासन कार्यालय वा इलाका प्रशासन कार्यालयबाट जारी हुन्छ।',
         'निःशुल्क (रु ०)', '१ दिन (Same Day)', 'https://moha.gov.np', 'bi-person-badge'],
        [2, 'NATIONAL_ID', 'National Identity Card (NID)', 'राष्ट्रिय परिचयपत्र', 'Digital Identity',
         'Biometric digital smart card with unique National Identity Number (NIN) required for passport, social security, and banking.',
         'बायोमेट्रिक विवरणसहितको डिजिटल परिचयपत्र र १६ अंकको व्यक्तिगत नम्बर, जुन राहदानी र अन्य सरकारी सेवाका लागि अनिवार्य छ।',
         'निःशुल्क (पहिलो पटक)', 'विवरण संकलन: १ दिन / कार्ड: ३-६ महिना', 'https://donidcr.gov.np', 'bi-smartwatch'],
        [3, 'PASSPORT', 'E-Passport (MRP/Electronic Passport)', 'विद्युतीय राहदानी (ई-पासपोर्ट)', 'Travel & Foreign Affairs',
         'Official international travel document issued by the Department of Passport and District Administration Offices.',
         'विदेश यात्राका लागि आवश्यक आधिकारिक सरकारी यात्रा दस्तावेज, राहदानी विभाग र जिल्ला प्रशासन कार्यालयबाट उपलब्ध हुन्छ।',
         'रु ५,००० (जिल्लाबाट) / रु १२,००० (द्रुत सेवा काठमाडौँ)', 'जिल्ला: १०-१५ दिन / द्रुत: २-३ दिन', 'https://emrtds.nepalpassport.gov.np', 'bi-airplane'],
        [4, 'DRIVING_LICENSE', 'Smart Driving License', 'सवारी चालक अनुमति पत्र', 'Transport',
         'Official license permitting citizens to drive motor vehicles in Nepal, managed by the Department of Transport Management.',
         'यातायात व्यवस्था विभाग अन्तर्गत विभिन्न यातायात कार्यालयहरूबाट जारी गरिने सवारी साधन चलाउने इजाजतपत्र।',
         'दरखास्त: रु ५०० / लाइसेन्स दस्तुर: रु १,५०० - रु २,५००', 'लिखित र ट्रायल: २-३ हप्ता', 'https://onlineedlreg.dotm.gov.np', 'bi-car-front'],
        [5, 'BIRTH_REGISTRATION', 'Birth Registration Certificate', 'जन्म दर्ता प्रमाणपत्र', 'Civil Registration',
         'Official civil registration of newborn birth issued by Local Ward Offices within local municipalities.',
         'स्थानीय तहको सम्बन्धित वडा कार्यालयबाट जारी गरिने नवजात शिशुको जन्म प्रमाणित गर्ने आधिकारिक कानुनी दस्तावेज।',
         '३५ दिनभित्र: निःशुल्क / ३५ दिनपछि: रु ५०-१०० जरिवाना', '१ दिन (Same Day)', 'https://public.donidcr.gov.np', 'bi-award']
    ];
    foreach ($services as $s) { $stmt->execute($s); }

    // Prerequisites
    $stmt = $db->prepare("INSERT INTO service_prerequisites (service_id, prerequisite_service_id, is_mandatory, guidance_message_en, guidance_message_np) VALUES (?, ?, ?, ?, ?)");
    $prereqs = [
        [3, 1, 1, 'You must possess a valid Nepali Citizenship before applying for a Passport.', 'राहदानी बनाउनु अगाडि तपाईंसँग अनिवार्य नेपाली नागरिकता हुनुपर्दछ।'],
        [3, 2, 1, 'National ID Number (NIN) is mandatory for E-Passport enrollment in Nepal.', 'ई-पासपोर्ट आवेदन भर्न राष्ट्रिय परिचयपत्र नम्बर (NIN) वा दर्ता नम्बर अनिवार्य चाहिन्छ।'],
        [2, 1, 1, 'National ID biometric registration strictly requires an original Nepali Citizenship.', 'राष्ट्रिय परिचयपत्र विवरण दर्ता गर्न सक्कल नेपाली नागरिकता हुनैपर्छ।'],
        [4, 1, 1, 'Nepali citizenship is required to apply for a motor vehicle driving license.', 'सवारी चालक अनुमति पत्र (ड्राइभिङ लाइसेन्स) को फारम भर्न नागरिकता आवश्यक पर्दछ।']
    ];
    foreach ($prereqs as $p) { $stmt->execute($p); }

    // Master Documents
    $stmt = $db->prepare("INSERT INTO documents (id, code, name_en, name_np, issuing_authority_en, issuing_authority_np, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $docs = [
        [1, 'DOC_CITIZENSHIP_ORIG', 'Original Nepali Citizenship', 'सक्कल नेपाली नागरिकता प्रमाणपत्र', 'District Administration Office', 'जिल्ला प्रशासन कार्यालय', 'Physical original citizenship certificate.'],
        [2, 'DOC_CITIZENSHIP_PARENTS', 'Parents’ Citizenship Copies', 'बुवा-आमाको नागरिकताको प्रतिलिपि', 'District Administration Office', 'जिल्ला प्रशासन कार्यालय', 'Photocopies of father and mother citizenship.'],
        [3, 'DOC_WARD_RECOMMENDATION', 'Ward Office Recommendation', 'वडा कार्यालयको सिफारिस पत्र', 'Local Ward Office', 'सम्बन्धित वडा कार्यालय', 'Official recommendation letter from local ward.'],
        [4, 'DOC_BIRTH_CERTIFICATE', 'Birth Registration Certificate', 'जन्म दर्ता प्रमाणपत्र', 'Local Ward Office', 'सम्बन्धित वडा कार्यालय', 'Official government birth certificate.'],
        [5, 'DOC_NID_SLIP', 'National ID Card or Pre-Enrollment Slip', 'राष्ट्रिय परिचयपत्र वा दर्ता नम्बर (NIN स्लिप)', 'DoNIDCR', 'राष्ट्रिय परिचयपत्र तथा पञ्जीकरण विभाग', 'Slip showing 16-digit National Identity Number.'],
        [6, 'DOC_PP_PHOTOS', 'Passport Sized Photos (35x45mm)', 'पासपोर्ट साइजको फोटो (३५x४५ मिमी)', 'Authorized Photo Studio', 'फोटो स्टुडियो', 'Recent clear photograph with white background.'],
        [7, 'DOC_MARRIAGE_CERTIFICATE', 'Marriage Registration Certificate', 'विवाह दर्ता प्रमाणपत्र', 'Local Ward Office', 'सम्बन्धित वडा कार्यालय', 'Required for married applicants if surname changes.'],
        [8, 'DOC_OLD_PASSPORT', 'Old Passport Copy (If Renewal)', 'पुरानो राहदानीको प्रतिलिपि', 'Department of Passport', 'राहदानी विभाग', 'Original or copy of expiring/expired passport.'],
        [9, 'DOC_POLICE_REPORT', 'Police Loss Report', 'प्रहरी प्रतिवेदन (हराएको पत्र)', 'Nepal Police', 'नेपाल प्रहरी', 'Required if previous document was lost or stolen.'],
        [10, 'DOC_MEDICAL_REPORT', 'Medical Fitness & Blood Group Report', 'स्वास्थ्य परीक्षण तथा रक्त समूह रिपोर्ट', 'Certified Health Clinic', 'मान्यता प्राप्त स्वास्थ्य संस्था', 'Eye sight and blood group verification.']
    ];
    foreach ($docs as $d) { $stmt->execute($d); }

    // Service Documents mapping
    $stmt = $db->prepare("INSERT INTO service_documents (service_id, document_id, is_mandatory, condition_key, condition_value, notes_en, notes_np) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $sdocs = [
        [1, 4, 1, null, null, 'Original birth registration certificate', 'सक्कल जन्म दर्ता'],
        [1, 2, 1, null, null, 'Copies of parents citizenship', 'बुवा र आमा दुबैको नागरिकता प्रतिलिपि'],
        [1, 3, 1, null, null, 'Ward recommendation for new citizenship', 'वडा कार्यालयको नागरिकता सिफारिस पत्र'],
        [1, 6, 1, null, null, 'Recent 2 passport size photographs', 'हालसालै खिचिएको २ प्रति फोटो'],

        [2, 1, 1, null, null, 'Original citizenship certificate is required at biometric station', 'सक्कल नागरिकता प्रमाणपत्र'],
        [2, 7, 0, 'marital_status', 'married', 'Required if married and changing permanent address', 'विवाहित महिलाको हकमा विवाह दर्ता'],

        [3, 1, 1, null, null, 'Original Nepali citizenship certificate', 'सक्कल नागरिकता प्रमाणपत्र'],
        [3, 5, 1, null, null, 'NIN Number verified online', 'राष्ट्रिय परिचयपत्र नम्बर (NIN)'],
        [3, 8, 0, 'passport_type', 'renewal', 'Required if renewing an expired MRP/E-Passport', 'नवीकरण गर्दा पुरानो राहदानी अनिवार्य'],
        [3, 9, 0, 'passport_type', 'lost', 'Police report indicating lost passport', 'राहदानी हराएको प्रमाणित गर्ने प्रहरी रिपोर्ट'],
        [3, 7, 0, 'marital_status', 'married', 'Required if surname is different from citizenship', 'थर परिवर्तनको लागि विवाह दर्ता'],

        [4, 1, 1, null, null, 'Original citizenship certificate and copy', 'सक्कल नागरिकता र १ प्रति फोटोकपी'],
        [4, 10, 1, null, null, 'Blood group test card and eye examination', 'रक्त समूह कार्ड र स्वास्थ्य परीक्षण'],
        [4, 6, 1, null, null, '3 passport sized photos', '३ प्रति पासपोर्ट साइज फोटो'],

        [5, 2, 1, null, null, 'Copies of father and mother citizenship', 'बुवा-आमाको नागरिकताको प्रतिलिपि'],
        [5, 7, 1, null, null, 'Parents marriage certificate', 'बुवा-आमाको विवाह दर्ता प्रमाणपत्र'],
        [5, 3, 0, 'after_35_days', 'yes', 'Affidavit or delay fee receipt if after 35 days', '३५ दिन कटेको भए ढिलो दर्ता सिफारिस']
    ];
    foreach ($sdocs as $sd) { $stmt->execute($sd); }

    // Service Questions
    $stmt = $db->prepare("INSERT INTO service_questions (service_id, question_key, question_text_en, question_text_np, input_type, options_json, priority) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $questions = [
        [3, 'has_citizenship', 'Do you have a Nepali Citizenship Certificate?', 'के तपाईंसँग नेपाली नागरिकताको प्रमाणपत्र छ?', 'yes_no', null, 1],
        [3, 'has_nid', 'Do you have a National ID Card or NID Number (NIN)?', 'के तपाईंसँग राष्ट्रिय परिचयपत्र वा १६ अंकको NIN नम्बर छ?', 'yes_no', null, 2],
        [3, 'passport_type', 'Is this your first-time passport, renewal, or lost replacement?', 'तपाईं नयाँ राहदानी बनाउँदै हुनुहुन्छ कि नवीकरण/हराएको हो?', 'select', '{"first_time": "पहिलो पटक / First Time", "renewal": "नवीकरण / Renewal", "lost": "हराएको वा बिग्रिएको / Lost or Damaged"}', 3],
        [3, 'applicant_age', 'What is your current age?', 'तपाईंको उमेर कति वर्ष भयो?', 'number', null, 4],
        [3, 'district', 'Select your District for appointment & collection:', 'तपाईं कुन जिल्लाबाट सेवा लिन चाहनुहुन्छ?', 'select', '{"Kathmandu": "काठमाडौँ (Kathmandu)", "Lalitpur": "लalitpur (Lalitpur)", "Bhaktapur": "भक्तपुर (Bhaktapur)", "Kaski": "कास्की (Kaski/Pokhara)", "Morang": "मोरङ (Morang/Biratnagar)", "Chitwan": "चितवन (Chitwan)", "Rupandehi": "रुपन्देही (Rupandehi/Butwal)"}', 5],

        [2, 'has_citizenship', 'Do you have an original Nepali Citizenship Certificate?', 'के तपाईंसँग सक्कल नेपाली नागरिकता छ?', 'yes_no', null, 1],
        [2, 'marital_status', 'What is your marital status?', 'तपाईंको वैवाहिक स्थिति के हो?', 'select', '{"single": "अविवाहित (Single)", "married": "विवाहित (Married)"}', 2],
        [2, 'district', 'Select your District for biometric enrollment:', 'बायोमेट्रिक दर्ता कुन जिल्लाबाट गराउनुहुन्छ?', 'select', '{"Kathmandu": "काठमाडौँ", "Lalitpur": "ललितपुर", "Bhaktapur": "भक्तपुर", "Kaski": "कास्की", "Morang": "मोरङ", "Chitwan": "चितवन", "Rupandehi": "रुपन्देही"}', 3],

        [4, 'has_citizenship', 'Do you possess a valid Nepali Citizenship?', 'के तपाईंसँग नेपाली नागरिकता छ?', 'yes_no', null, 1],
        [4, 'applicant_age', 'What is your age?', 'तपाईंको उमेर कति भयो?', 'number', null, 2],
        [4, 'vehicle_category', 'Which vehicle category do you wish to apply for?', 'कुन सवारी साधनको लाइसेन्स लिन चाहनुहुन्छ?', 'select', '{"A": "वर्ग A - मोटरसाइकल / स्कुटर (Two Wheeler)", "B": "वर्ग B - कार / जीप (Light Four Wheeler)", "K": "वर्ग K - स्कुटर मात्र (Scooter)"}', 3],
        [4, 'district', 'Select your Zone/District office:', 'कुन जिल्ला/यातायात कार्यालयबाट परीक्षा दिनुहुन्छ?', 'select', '{"Kathmandu": "काठमाडौँ (ठूलो भर्याङ / चाबहिल)", "Lalitpur": "ललितपुर (एकान्तकुना)", "Bhaktapur": "भक्तपुर (राधे राधे)", "Kaski": "कास्की (पोखरा)", "Rupandehi": "रुपन्देही (बुटवल)"}', 4],

        [1, 'applicant_age', 'What is the applicant age in years?', 'निवेदकको उमेर कति वर्ष भयो?', 'number', null, 1],
        [1, 'parents_citizenship', 'Do both or either of your parents hold Nepali Citizenship?', 'के तपाईंको बुवा वा आमाको नेपाली नागरिकता छ?', 'yes_no', null, 2],
        [1, 'has_birth_certificate', 'Do you have your official Birth Certificate?', 'के तपाईंसँग जन्म दर्ता प्रमाणपत्र छ?', 'yes_no', null, 3]
    ];
    foreach ($questions as $q) { $stmt->execute($q); }

    // Service Rules
    $stmt = $db->prepare("INSERT INTO service_rules (service_id, rule_code, condition_field, operator, condition_value, action_type, target_service_id, result_title_en, result_title_np, result_message_en, result_message_np, action_payload) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $rules = [
        [3, 'RULE_PPT_NO_CITIZEN', 'has_citizenship', '==', 'no', 'BLOCK_AND_REDIRECT', 1,
         'Citizenship Required First', 'पहिले नागरिकता बनाउनु पर्दछ',
         'You cannot apply for a Passport without Nepali citizenship. You must obtain your Citizenship Certificate first from your District Administration Office.',
         'नेपाली नागरिकता नभएसम्म राहदानी (पासपोर्ट) बनाउन मिल्दैन। सर्वप्रथम आफ्नो जिल्ला प्रशासन कार्यालयबाट नागरिकता लिनुहोस्।',
         '{"redirect_service_code": "CITIZENSHIP"}'],

        [3, 'RULE_PPT_NO_NID', 'has_nid', '==', 'no', 'BLOCK_AND_REDIRECT', 2,
         'National ID (NIN) Required First', 'पहिले राष्ट्रिय परिचयपत्र नम्बर (NIN) लिनुहोस्',
         'The Nepal Department of Passport requires a 16-digit National Identity Number (NIN). Please enroll for National ID first.',
         'नेपाल सरकारको नियम अनुसार ई-पासपोर्ट फारम भर्न राष्ट्रिय परिचयपत्र नम्बर (NIN) अनिवार्य छ। पहिले राष्ट्रिय परिचयपत्र दर्ता गराउनुहोस्।',
         '{"redirect_service_code": "NATIONAL_ID"}'],

        [4, 'RULE_DL_UNDERAGE_18', 'applicant_age', '<', '18', 'BLOCK_AND_REDIRECT', null,
         'Age Eligibility Error (Under 18)', 'उमेर नपुगेको (१८ वर्ष मुनि)',
         'As per the Motor Vehicles and Transport Management Act, you must be at least 18 years old to apply for a motor vehicle driving license.',
         'सवारी तथा यातायात व्यवस्था ऐन अनुसार सवारी चालक अनुमति पत्र (लाइसेन्स) को लागि न्यूनतम १८ वर्ष उमेर पूरा भएको हुनुपर्छ।',
         '{"eligible": false}'],

        [4, 'RULE_DL_UNDERAGE_CAR', 'applicant_age', '<', '21', 'SPECIAL_INSTRUCTION', null,
         'Car / Light Vehicle Age Note', 'चारपाङ्ग्रे सवारी उमेर जानकारी',
         'For Car/Jeep (Category B), the minimum age requirement is 21 years in Nepal.',
         'कार/जीप (वर्ग B) को लाइसेन्स लिनको लागि न्यूनतम २१ वर्ष उमेर पुगेको हुनुपर्दछ।',
         '{"min_age_car": 21}'],

        [1, 'RULE_CITIZEN_UNDERAGE', 'applicant_age', '<', '16', 'BLOCK_AND_REDIRECT', 5,
         'Under 16 Years - Minor Identification', 'उमेर १६ वर्ष भन्दा कम',
         'In Nepal, standard Citizenship Certificate is issued only after reaching 16 years of age. For minors, Birth Registration or Minor Identity Card is used.',
         'नेपालमा १६ वर्ष पूरा भएपछि मात्र नागरिकता जारी हुन्छ। १६ वर्ष मुनिका बालबालिकाको हकमा जन्म दर्ता नै मुख्य कानुनी प्रमाण हो।',
         '{"redirect_service_code": "BIRTH_REGISTRATION"}']
    ];
    foreach ($rules as $r) { $stmt->execute($r); }

    // Offices
    $stmt = $db->prepare("INSERT INTO offices (name_en, name_np, district, municipality, service_category, address_en, address_np, phone, email, opening_hours, google_maps_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $offices = [
        ['Department of Passports (DoP)', 'राहदानी विभाग (केन्द्रीय कार्यालय)', 'Kathmandu', 'Kathmandu Metropolitan', 'Travel & Foreign Affairs', 'Tripureshwor, Kathmandu', 'त्रिपुरेश्वर, काठमाडौँ', '+977-1-5970360', 'communication@nepalpassport.gov.np', 'आइतबार - बिहीबार १०:०० - ५:००, शुक्रबार १०:०० - ३:००', 'https://maps.google.com/?q=Department+of+Passports+Tripureshwor'],
        ['District Administration Office (DAO), Kathmandu', 'जिल्ला प्रशासन कार्यालय, काठमाडौँ', 'Kathmandu', 'Kathmandu Metropolitan', 'Identity', 'Babarmahal, Kathmandu', 'बबरमहल, काठमाडौँ', '+977-1-4262444', 'daokathmandu@moha.gov.np', 'आइतबार - शुक्रबार १०:०० - ५:००', 'https://maps.google.com/?q=DAO+Babarmahal+Kathmandu'],
        ['District Administration Office (DAO), Lalitpur', 'जिल्ला प्रशासन कार्यालय, ललितपुर', 'Lalitpur', 'Lalitpur Metropolitan', 'Identity', 'Jawalakhel, Lalitpur', 'जावलाखेल, ललितपुर', '+977-1-5521204', 'daolalitpur@moha.gov.np', 'आइतबार - शुक्रबार १०:०० - ५:००', 'https://maps.google.com/?q=DAO+Jawalakhel+Lalitpur'],
        ['District Administration Office (DAO), Bhaktapur', 'जिल्ला प्रशासन कार्यालय, भक्तपुर', 'Bhaktapur', 'Suryabinayak', 'Identity', 'Sallaghari, Bhaktapur', 'सल्लाघारी, भक्तपुर', '+977-1-6610023', 'daobhaktapur@moha.gov.np', 'आइतबार - शुक्रबार १०:०० - ५:००', 'https://maps.google.com/?q=DAO+Bhaktapur'],
        ['Transport Management Office (Drivers License)', 'यातायात व्यवस्था कार्यालय (सवारी चालक अनुमति पत्र)', 'Lalitpur', 'Lalitpur Metropolitan', 'Transport', 'Ekantakuna, Lalitpur', 'एकान्तकुना, ललितपुर', '+977-1-5527333', 'info.ekantakuna@dotm.gov.np', 'आइतबार - शुक्रबार १०:०० - ४:००', 'https://maps.google.com/?q=Dotm+Ekantakuna+Lalitpur'],
        ['Transport Management Office, Thulo Bharyang', 'यातायात व्यवस्था कार्यालय, ठूलो भर्याङ', 'Kathmandu', 'Nagarjun', 'Transport', 'Thulo Bharyang, Swayambhu', 'ठूलो भर्याङ, स्वयम्भू, काठमाडौँ', '+977-1-4890333', 'info@dotm.gov.np', 'आइतबार - शुक्रबार १०:०० - ४:००', 'https://maps.google.com/?q=Transport+Management+Thulo+Bharyang'],
        ['District Administration Office, Kaski', 'जिल्ला प्रशासन कार्यालय, कास्की', 'Kaski', 'Pokhara Metropolitan', 'Identity', 'Sahid Chowk, Pokhara', 'शहीद चोक, पोखरा', '+977-61-461022', 'daokaski@moha.gov.np', 'आइतबार - शुक्रबार १०:०० - ५:००', 'https://maps.google.com/?q=DAO+Kaski+Pokhara']
    ];
    foreach ($offices as $o) { $stmt->execute($o); }

    // Search Keywords
    $stmt = $db->prepare("INSERT INTO search_keywords (service_id, keyword, language_type) VALUES (?, ?, ?)");
    $keywords = [
        [3, 'passport', 'en'], [3, 'rahadani', 'roman'], [3, 'pasport', 'roman'], [3, 'bidesh jana', 'roman'], [3, 'bidesh', 'roman'],
        [3, 'e-passport', 'en'], [3, 'राहदानी', 'np'], [3, 'पासपोर्ट', 'np'], [3, 'विदेश', 'np'], [3, 'विदेश जान', 'np'],
        [1, 'citizenship', 'en'], [1, 'nagarikta', 'roman'], [1, 'nagrita', 'roman'], [1, 'nagrikta', 'roman'], [1, 'nagarikata', 'roman'],
        [1, 'certificate', 'en'], [1, 'नागरिकता', 'np'], [1, 'नेपाली नागरिकता', 'np'],
        [2, 'national id', 'en'], [2, 'nid', 'en'], [2, 'nin', 'en'], [2, 'rastriya parichayapatra', 'roman'], [2, 'parichayapatra', 'roman'],
        [2, 'राष्ट्रिय परिचयपत्र', 'np'], [2, 'परिचय पत्र', 'np'],
        [4, 'driving license', 'en'], [4, 'license', 'en'], [4, 'laicense', 'roman'], [4, 'sawari chalak', 'roman'], [4, 'bike license', 'en'],
        [4, 'car license', 'en'], [4, 'trial', 'roman'], [4, 'सवारी चालक अनुमति पत्र', 'np'], [4, 'लाइसेन्स', 'np'], [4, 'गाडी चलाउने', 'np'],
        [5, 'birth registration', 'en'], [5, 'birth certificate', 'en'], [5, 'janma darta', 'roman'], [5, 'bachha ko darta', 'roman'],
        [5, 'जन्म दर्ता', 'np'], [5, 'जन्म दर्ता प्रमाणपत्र', 'np']
    ];
    foreach ($keywords as $k) { $stmt->execute($k); }

    // Admin default account
    $passHash = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO admins (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['admin', $passHash, 'BCA System Administrator', 'Super Admin']);

    echo "SQLite database successfully created and populated at: " . $sqliteFile . "\n";
} catch (Exception $e) {
    echo "SQLite Setup Error: " . $e->getMessage() . "\n";
}
