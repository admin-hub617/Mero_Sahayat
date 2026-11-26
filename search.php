<?php
/**
 * Mero Sahayata - Smart Search API
 * Implements Tokenization, Keyword Inverted Index & Levenshtein Distance Fuzzy Matching
 * Handles English, Nepali Unicode, and Romanized Nepali Queries
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query)) {
    jsonResponse(['success' => false, 'message' => 'Query cannot be empty', 'results' => []]);
}

$db = Database::getConnection();
$cleanQuery = mb_strtolower($query, 'UTF-8');
$tokens = preg_split('/[\s,\.\?\!\-\_]+/', $cleanQuery, -1, PREG_SPLIT_NO_EMPTY);

// 1. Fetch all search keywords and services for in-memory scoring
$sql = "SELECT sk.keyword, sk.language_type, s.id as service_id, s.code, s.name_en, s.name_np, s.category, s.icon, s.description_np, s.standard_fee, s.estimated_days
        FROM search_keywords sk
        JOIN services s ON sk.service_id = s.id";
$stmt = $db->query($sql);
$allKeywords = $stmt->fetchAll();

$scores = []; // service_id => ['score' => int, 'matches' => [], 'service' => []]

foreach ($allKeywords as $row) {
    $sid = $row['service_id'];
    $kw = mb_strtolower($row['keyword'], 'UTF-8');
    
    if (!isset($scores[$sid])) {
        $scores[$sid] = [
            'service_id' => $sid,
            'code' => $row['code'],
            'name_en' => $row['name_en'],
            'name_np' => $row['name_np'],
            'category' => $row['category'],
            'icon' => $row['icon'],
            'description_np' => $row['description_np'],
            'standard_fee' => $row['standard_fee'],
            'estimated_days' => $row['estimated_days'],
            'score' => 0,
            'matched_terms' => []
        ];
    }

    // Direct substring or exact match on full query
    if (mb_strpos($cleanQuery, $kw) !== false) {
        $scores[$sid]['score'] += 30;
        $scores[$sid]['matched_terms'][] = "Exact: " . $row['keyword'];
    }

    // Token-by-token comparison & Levenshtein Distance
    foreach ($tokens as $token) {
        // Skip common stop-words in Nepali / English
        if (in_array($token, ['chahincha', 'k', 'ko', 'ma', 'lai', 'ra', 'cha', 'garnu', 'banauna', 'how', 'to', 'get', 'for', 'the', 'in', 'मलाई', 'चाहिन्छ', 'कसरी', 'गर्ने', 'बनाउने', 'कहाँ'])) {
            continue;
        }

        // Exact token match
        if ($token === $kw) {
            $scores[$sid]['score'] += 25;
            $scores[$sid]['matched_terms'][] = "Token match: " . $token;
            continue;
        }

        // Fuzzy matching (Levenshtein) for romanized words (length >= 4)
        $lenToken = strlen($token);
        $lenKw = strlen($kw);

        // If ascii/romanized
        if (mb_check_encoding($token, 'ASCII') && mb_check_encoding($kw, 'ASCII') && $lenToken >= 4 && $lenKw >= 4) {
            $dist = levenshtein($token, $kw);
            $maxLen = max($lenToken, $lenKw);
            $similarity = (1 - ($dist / $maxLen)) * 100;

            if ($similarity >= 70) {
                $scores[$sid]['score'] += (int)($similarity / 5);
                $scores[$sid]['matched_terms'][] = "Fuzzy ({$token} ~ {$kw}): " . round($similarity) . "%";
            }
        }
    }
}

// 2. Also check direct service title matches
$stmtServices = $db->query("SELECT id, name_en, name_np FROM services");
while ($s = $stmtServices->fetch()) {
    $sid = $s['id'];
    $enTitle = mb_strtolower($s['name_en'], 'UTF-8');
    $npTitle = mb_strtolower($s['name_np'], 'UTF-8');

    if (mb_strpos($cleanQuery, $enTitle) !== false || mb_strpos($cleanQuery, $npTitle) !== false) {
        if (isset($scores[$sid])) {
            $scores[$sid]['score'] += 50;
            $scores[$sid]['matched_terms'][] = "Title Match";
        }
    }
}

// 3. Filter services with score > 0, sort by score descending
$results = array_filter($scores, function($item) {
    return $item['score'] > 0;
});

usort($results, function($a, $b) {
    return $b['score'] <=> $a['score'];
});

// Take top 3 most relevant results
$topResults = array_slice($results, 0, 3);

// If no results, provide fallback suggested services
$suggestedFallback = false;
if (empty($topResults)) {
    $suggestedFallback = true;
    $stmtFallback = $db->query("SELECT id as service_id, code, name_en, name_np, category, icon, description_np, standard_fee, estimated_days FROM services LIMIT 3");
    $topResults = $stmtFallback->fetchAll();
    foreach ($topResults as &$res) {
        $res['score'] = 0;
        $res['matched_terms'] = ['General Recommendation'];
    }
}

// Log the search query for administrator analytics
$searchType = isset($_GET['type']) && $_GET['type'] === 'voice' ? 'voice' : 'text';
$matchedServiceId = (!$suggestedFallback && !empty($topResults)) ? ($topResults[0]['service_id'] ?? null) : null;
try {
    $stmtLog = $db->prepare("INSERT INTO search_logs (query, search_type, matched_service_id) VALUES (?, ?, ?)");
    $stmtLog->execute([$query, $searchType, $matchedServiceId]);
} catch (Exception $e) {
    // Continue gracefully even if logging fails
}

jsonResponse([
    'success' => true,
    'query' => $query,
    'total_matches' => count($results),
    'is_fallback' => $suggestedFallback,
    'results' => $topResults
]);
