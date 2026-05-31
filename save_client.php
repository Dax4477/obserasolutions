<?php
session_name("obsera_admin_session"); // 🔒 STRICT SECURITY: Looks for the Admin session
session_start();
header('Content-Type: application/json');

// ==============================================================
// 1. SECURITY CHECK: Is the Super Admin logged in?
// ==============================================================
if (!isset($_SESSION['obsera_admin_logged_in']) || $_SESSION['obsera_admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'SECURITY ERROR: Unauthorized Access.']);
    exit;
}

// 2. Receive the data sent from the Admin Dashboard
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['newClientCode']) || !isset($data['authData'])) {
    echo json_encode(['success' => false, 'message' => 'No data received.']);
    exit;
}

$newClientCode = $data['newClientCode'];
$authData = $data['authData'];
$clientId = $authData['id'];


// ==============================================================
// 3. SAFELY MERGE INTO auth.json (Without deleting old ones)
// ==============================================================
$authFile = 'auth.json';
$authArray = [];

// If the file exists, read the current clients so we don't overwrite them
if (file_exists($authFile)) {
    $authArray = json_decode(file_get_contents($authFile), true);
    if (!is_array($authArray)) $authArray = []; // Failsafe
}

// ADD or UPDATE this specific client
$authArray[$clientId] = [
    'password' => $authData['password'],
    'menuSheetId' => $authData['menuSheetId']
];

// Save the combined list back to the server
file_put_contents($authFile, json_encode($authArray, JSON_PRETTY_PRINT));


// ==============================================================
// 4. SAFELY UPDATE clients.js
// ==============================================================
$jsFile = 'clients.js'; 

if (file_exists($jsFile)) {
    $currentContents = file_get_contents($jsFile);
    
    // Safely insert the new client at the top of the list
    $search = 'const OBSERA_CLIENTS = {';
    $replace = "const OBSERA_CLIENTS = {\n" . $newClientCode;
    $newContents = str_replace($search, $replace, $currentContents);
    
    file_put_contents($jsFile, $newContents);
} else {
    // If it doesn't exist, create it
    $jsContent = "const OBSERA_CLIENTS = {\n" . $newClientCode . "\n};";
    file_put_contents($jsFile, $jsContent);
}


// Success!
echo json_encode(['success' => true, 'message' => 'Client saved securely!']);
?>