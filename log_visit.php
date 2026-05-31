<?php
// Allow your frontend to talk to this file
header("Access-Control-Allow-Origin: *");

// 1. Connect to a Zero-Setup SQLite Database
$db_file = __DIR__ . '/obsera_tracker.sqlite';
$pdo = new PDO("sqlite:" . $db_file);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 2. Create the tracking table if it doesn't exist yet
$pdo->exec("CREATE TABLE IF NOT EXISTS visitors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    device_id TEXT UNIQUE,
    ip_address TEXT,
    current_page TEXT,
    first_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_active DATETIME DEFAULT CURRENT_TIMESTAMP,
    total_visits INTEGER DEFAULT 1
)");

// 3. Grab the incoming data from your index.html
$device_id = $_POST['device_id'] ?? null;
$page_url = $_POST['page_url'] ?? 'Unknown Page';
$ip = $_SERVER['REMOTE_ADDR'];

if (!$device_id) {
    exit("No device ID");
}

// Clean up the URL to just show the path
$parsed_url = parse_url($page_url);
$clean_page = ($parsed_url['path'] ?? '') . (isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '');
if ($clean_page == '' || $clean_page == '/') $clean_page = '/index.html';

// 4. Check if we have seen this visitor before
$stmt = $pdo->prepare("SELECT last_active, total_visits FROM visitors WHERE device_id = ?");
$stmt->execute([$device_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // If they haven't pinged us in over 30 minutes, count this as a brand new visit
    $last_active_timestamp = strtotime($user['last_active']);
    $now = time();
    $visits = $user['total_visits'];
    
    if (($now - $last_active_timestamp) > 300) { 
        $visits++;
    }
    
    // Update their status to "Online Now"
    $update = $pdo->prepare("UPDATE visitors SET last_active = CURRENT_TIMESTAMP, total_visits = ?, current_page = ?, ip_address = ? WHERE device_id = ?");
    $update->execute([$visits, $clean_page, $ip, $device_id]);
} else {
    // Brand new visitor! Add them to the database.
    $insert = $pdo->prepare("INSERT INTO visitors (device_id, ip_address, current_page) VALUES (?, ?, ?)");
    $insert->execute([$device_id, $ip, $clean_page]);
}

echo "Tracked successfully.";
?>