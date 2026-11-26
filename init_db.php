<?php
/**
 * Mero Sahayata - One-Click Database Initialization Script
 * BCA 6th Semester Project
 * Can be run from CLI or Browser
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$user = 'root';
$pass = '';
$port = '3306';
$dbname = 'mero_sahayata';
$sqlFile = __DIR__ . '/database.sql';

$isCli = (php_sapi_name() === 'cli');

function outputMsg($msg, $type = 'info') {
    global $isCli;
    if ($isCli) {
        echo "[$type] " . strip_tags($msg) . "\n";
    } else {
        $colors = [
            'info' => '#2563eb',
            'success' => '#16a34a',
            'error' => '#dc2626'
        ];
        $color = $colors[$type] ?? '#333';
        echo "<p style='color:{$color};font-family:monospace;margin:6px 0;'><strong>" . strtoupper($type) . ":</strong> {$msg}</p>";
    }
}

if (!$isCli) {
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Mero Sahayata - DB Installer</title></head>";
    echo "<body style='font-family:sans-serif;max-width:700px;margin:40px auto;background:#f8fafc;padding:30px;border-radius:12px;border:1px solid #e2e8f0;'>";
    echo "<h2>⚙️ Mero Sahayata Database Installer</h2><hr style='border:0;border-top:1px solid #e2e8f0;margin:15px 0;'>";
}

try {
    outputMsg("Connecting to MySQL server at {$host}:{$port}...", 'info');
    $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    outputMsg("Successfully connected to MySQL server.", 'success');

    if (!file_exists($sqlFile)) {
        throw new Exception("SQL script file not found at: " . $sqlFile);
    }

    outputMsg("Reading SQL schema and seed data...", 'info');
    $sqlContent = file_get_contents($sqlFile);

    outputMsg("Executing database schema and seed data...", 'info');
    // Execute full SQL script
    $pdo->exec($sqlContent);
    outputMsg("Database `{$dbname}` created and configured with all tables, rules, and seed data!", 'success');

    // Verification check
    $pdo->exec("USE `{$dbname}`");
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM services");
    $servicesCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt2 = $pdo->query("SELECT COUNT(*) as count FROM service_rules");
    $rulesCount = $stmt2->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt3 = $pdo->query("SELECT COUNT(*) as count FROM search_keywords");
    $keywordsCount = $stmt3->fetch(PDO::FETCH_ASSOC)['count'];

    outputMsg("Verification: {$servicesCount} services, {$rulesCount} decision rules, {$keywordsCount} search keywords loaded.", 'success');

    if ($isCli) {
        echo "\n[SUCCESS] Setup complete! You can now start using Mero Sahayata.\n";
    } else {
        echo "<div style='margin-top:25px;padding:15px;background:#ecfdf5;border-radius:8px;border:1px solid #a7f3d0;'>";
        echo "<h3 style='color:#065f46;margin-top:0;'>🎉 Installation Completed Successfully!</h3>";
        echo "<p style='color:#047857;'>Default Admin Credentials: <strong>admin</strong> / <strong>admin123</strong></p>";
        echo "<p><a href='../index.php' style='display:inline-block;padding:10px 20px;background:#10b981;color:white;text-decoration:none;border-radius:6px;font-weight:bold;'>Go to Mero Sahayata Application &rarr;</a></p>";
        echo "</div></body></html>";
    }

} catch (Exception $e) {
    outputMsg("Installation failed: " . $e->getMessage(), 'error');
    if (!$isCli) {
        echo "<p style='color:#666;'>Ensure your MySQL server (e.g. from XAMPP Control Panel) is running and accessible.</p>";
        echo "</body></html>";
    }
}
