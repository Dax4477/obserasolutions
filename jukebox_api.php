<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$stateFile = 'jukebox_state.json';
$playlistFile = 'playlist.json';
$suggestionsFile = 'suggestions.json';
$adFile = 'ad.json';

// --- 1. HANDLE SUGGESTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'suggest') {
    $title = isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
    $artist = isset($_POST['artist']) ? htmlspecialchars($_POST['artist']) : 'Unknown';
    if (!empty($title)) {
        $suggestions = file_exists($suggestionsFile) ? json_decode(file_get_contents($suggestionsFile), true) : [];
        $suggestions[] = ["id" => uniqid('sug_'), "title" => $title, "artist" => $artist, "timestamp" => date('c')];
        file_put_contents($suggestionsFile, json_encode($suggestions, JSON_PRETTY_PRINT));
        echo json_encode(["success" => true]); exit;
    }
}

// --- 2. LOAD DATA & INITIALIZE ---
$master_playlist = file_exists($playlistFile) ? json_decode(file_get_contents($playlistFile), true) : [];
if (empty($master_playlist)) { echo json_encode(["error" => "Playlist is empty"]); exit; }

if (!file_exists($stateFile)) {
    $initial_state = [
        "active_clients" => [],
        "current" => $master_playlist[array_rand($master_playlist)], 
        "options" => []
    ];
    file_put_contents($stateFile, json_encode($initial_state));
}
$state = json_decode(file_get_contents($stateFile), true);

// Upgrade old state files to support Clients
if (!isset($state['active_clients'])) $state['active_clients'] = [];
foreach ($state['options'] as &$opt) { if (!isset($opt['played_by'])) $opt['played_by'] = []; } unset($opt);

// --- 3. CLIENT HEARTBEAT (Keep track of who is online) ---
$client_id = isset($_GET['client_id']) ? htmlspecialchars($_GET['client_id']) : null;
$now = time();

if ($client_id) {
    $state['active_clients'][$client_id] = $now;
}

// Remove dead TVs (offline for more than 15 minutes / 900 seconds)
foreach ($state['active_clients'] as $cid => $last_seen) {
    if ($now - $last_seen > 600) { unset($state['active_clients'][$cid]); }
}
$active_client_ids = array_keys($state['active_clients']);

// --- 4. PRESERVE SHUFFLE & SYNC OPTIONS ---
$valid_options = [];
$existing_ids = [];
foreach ($state['options'] as $opt) {
    $song_id = $opt['song']['id'];
    $exists_in_master = false;
    foreach ($master_playlist as $m_song) { if ($m_song['id'] === $song_id) { $exists_in_master = true; break; } }
    if ($exists_in_master) { $valid_options[] = $opt; $existing_ids[] = $song_id; }
}

$new_songs = [];
foreach ($master_playlist as $song) {
    if (!in_array($song['id'], $existing_ids)) { $new_songs[] = ["song" => $song, "votes" => 0, "played_by" => []]; }
}
shuffle($new_songs);
$state['options'] = array_merge($valid_options, $new_songs);
usort($state['options'], function($a, $b) { return $b['votes'] - $a['votes']; });

// --- 5. HANDLE VOTING ---
if (isset($_GET['action']) && $_GET['action'] == 'vote' && isset($_GET['id'])) {
    foreach ($state['options'] as &$option) { if ($option['song']['id'] === $_GET['id']) $option['votes']++; }
    usort($state['options'], function($a, $b) { return $b['votes'] - $a['votes']; });
    file_put_contents($stateFile, json_encode($state));
    echo json_encode(["success" => true, "state" => $state]); exit;
}

// --- 6. HANDLE 'NEXT SONG' FOR A SPECIFIC CLIENT ---
if (isset($_GET['action']) && $_GET['action'] == 'next' && $client_id) {
    
    $winner_index = -1;
    
    // Find the highest voted song that THIS specific client hasn't played yet
    foreach ($state['options'] as $idx => $opt) {
        if ($opt['votes'] > 0 && !in_array($client_id, $opt['played_by'])) {
            $winner_index = $idx;
            break;
        }
    }

    if ($winner_index !== -1) {
        // We found a voted track!
        $winner = $state['options'][$winner_index]['song'];
        
        // Mark that this TV just played it
        $state['options'][$winner_index]['played_by'][] = $client_id;
        
        // CHECK: Have ALL active TVs played this song?
        $all_played = true;
        foreach ($active_client_ids as $acid) {
            if (!in_array($acid, $state['options'][$winner_index]['played_by'])) {
                $all_played = false; break;
            }
        }
        
        // If yes, delete the votes!
        if ($all_played || empty($active_client_ids)) {
            $state['options'][$winner_index]['votes'] = 0;
            $state['options'][$winner_index]['played_by'] = [];
        }
        
    } else {
        // No voted tracks left for this TV. Pick a random 0-vote track.
        $available = array_filter($state['options'], function($o) { return $o['votes'] == 0; });
        if (empty($available)) $available = $state['options']; 
        $random_opt = $available[array_rand($available)];
        $winner = $random_opt['song'];
    }

    // Clean up 'played_by' on any track that hit 0 votes organically
    foreach ($state['options'] as &$opt) { if ($opt['votes'] == 0) $opt['played_by'] = []; } unset($opt);

    usort($state['options'], function($a, $b) { return $b['votes'] - $a['votes']; });
    $state['current'] = $winner; // Update "Now Playing" for mobile app
    
    file_put_contents($stateFile, json_encode($state));
    
    $adData = file_exists($adFile) ? json_decode(file_get_contents($adFile), true) : ["url" => ""];
    $state['ad_url'] = $adData['url'];
    echo json_encode(["success" => true, "state" => $state]); exit;
}

// Save regular state updates
file_put_contents($stateFile, json_encode($state));

$adData = file_exists($adFile) ? json_decode(file_get_contents($adFile), true) : ["url" => ""];
$state['ad_url'] = $adData['url'];
echo json_encode($state);
?>