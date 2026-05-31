<?php
// 1. Allow background requests
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// 2. Check if a client ID was sent
if (isset($_GET['client'])) {
    $client = strtolower(trim($_GET['client']));
    $ad_url = isset($_GET['ad_click']) ? $_GET['ad_click'] : null;
    $dataFile = 'analytics_data.json';

    // 3. Create the analytics database if it doesn't exist yet
    if (!file_exists($dataFile)) {
        file_put_contents($dataFile, json_encode([]));
    }

    $data = json_decode(file_get_contents($dataFile), true);

    // Get today's exact date
    $today = date('Y-m-d');

    // 4. If this is a brand new client, set up their tracking profile
    if (!isset($data[$client])) {
        $data[$client] = [
            'total_views' => 0,
            'daily_views' => [],
            'ad_clicks' => []
        ];
    }

    // 5. Handle Ad Click Tracking
    if ($ad_url) {
        if (!isset($data[$client]['ad_clicks'])) { 
            $data[$client]['ad_clicks'] = []; 
        }
        if (!isset($data[$client]['ad_clicks'][$ad_url])) { 
            $data[$client]['ad_clicks'][$ad_url] = 0; 
        }
        $data[$client]['ad_clicks'][$ad_url]++;
    } 
    // 6. Handle Regular Menu View Tracking
    else {
        $data[$client]['total_views']++;
        if (!isset($data[$client]['daily_views'][$today])) {
            $data[$client]['daily_views'][$today] = 0;
        }
        $data[$client]['daily_views'][$today]++;
    }

    // 7. Save the updated numbers back to the file
    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));

    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "No client specified"]);
}
?>