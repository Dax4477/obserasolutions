<?php
// Allow requests from your portal
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// FIX: Force the server to use Indian Standard Time
date_default_timezone_set('Asia/Kolkata'); 

// Catch the incoming data
$data = json_decode(file_get_contents("php://input"), true);

if ($data) {
    $file = 'pending_ads.json';
    
    // Read existing ads or create a new array
    $current_ads = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    
    // Add a unique ID and an exact ISO timestamp
    $data['id'] = uniqid('ad_');
    $data['timestamp'] = date('c'); // 'c' creates an exact, timezone-aware timestamp
    
    // Add it to the top of the list
    array_unshift($current_ads, $data);
    
    // Save it back to the file
    file_put_contents($file, json_encode($current_ads, JSON_PRETTY_PRINT));
    
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "No data received"]);
}
?>