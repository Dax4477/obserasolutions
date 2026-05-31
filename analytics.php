<?php
session_start();

// --- SECURE PASSWORD ---
$DASHBOARD_PASSWORD = "obserax"; 

// --- HANDLE DELETE ACTIONS (AJAX) ---
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    if (!isset($_SESSION['obsera_admin_logged_in'])) { echo json_encode(['error' => 'Unauthorized']); exit; }
    
    $db_file = __DIR__ . '/obsera_tracker.sqlite';
    if (!file_exists($db_file)) { echo json_encode(['error' => 'No database']); exit; }
    
    $pdo = new PDO("sqlite:" . $db_file);
    
    if ($_POST['action'] === 'delete_single' && isset($_POST['device_id'])) {
        $stmt = $pdo->prepare("DELETE FROM visitors WHERE device_id = ?");
        $stmt->execute([$_POST['device_id']]);
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($_POST['action'] === 'clear_all') {
        $pdo->exec("DELETE FROM visitors"); // Wipes the whole table
        echo json_encode(['success' => true]);
        exit;
    }
}

// --- AJAX LIVE DATA ENDPOINT ---
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['obsera_admin_logged_in'])) { echo json_encode(['error' => 'Unauthorized']); exit; }
    
    $db_file = __DIR__ . '/obsera_tracker.sqlite';
    if (!file_exists($db_file)) { 
        echo json_encode(['live' => 0, 'total' => 0, 'logs' => []]); 
        exit; 
    }
    
    $pdo = new PDO("sqlite:" . $db_file);
    
    // Calculate Live Users (Active in the last 30 seconds)
    $live_stmt = $pdo->query("SELECT COUNT(*) as count FROM visitors WHERE strftime('%s', 'now') - strftime('%s', last_active) < 30");
    $live_users = $live_stmt->fetch()['count'];

    // Total Unique Devices
    $total_stmt = $pdo->query("SELECT COUNT(*) as count FROM visitors");
    $total_users = $total_stmt->fetch()['count'];

    // Get Active Sessions Log
    $log_stmt = $pdo->query("SELECT * FROM visitors ORDER BY last_active DESC LIMIT 15");
    $logs = $log_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $parsed_logs = [];
    foreach($logs as $row) {
        // SQLite saves in UTC, so we tell PHP to read it as UTC
        $dt = new DateTime($row['last_active'], new DateTimeZone('UTC'));
        
        // Convert to Indian Standard Time (IST)
        $dt->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $formatted_date = $dt->format('M j, Y \a\t g:i A'); // Output: Feb 23, 2026 at 5:30 PM
        
        $seconds_ago = time() - $dt->getTimestamp();
        
        // Determine the "Live / Away" status
        $status = ($seconds_ago < 30) ? '<span class="text-emerald-400 flex items-center gap-1"><span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>Live</span>' : '<span class="text-slate-500">Away</span>';
        
        // Make the relative time smarter (mins, hours, days)
        if ($seconds_ago < 60) {
            $rel_time = "Just now";
        } elseif ($seconds_ago < 3600) {
            $rel_time = floor($seconds_ago/60) . " mins ago";
        } elseif ($seconds_ago < 86400) {
            $rel_time = floor($seconds_ago/3600) . " hours ago";
        } else {
            $rel_time = floor($seconds_ago/86400) . " days ago";
        }

        $parsed_logs[] = [
            'raw_id' => $row['device_id'], 
            'id' => substr($row['device_id'], 0, 12) . '...',
            'page' => htmlspecialchars($row['current_page']),
            'visits' => $row['total_visits'],
            'status' => $status,
            // Stack the relative time on top of the exact Date & Time!
            'time_ago' => $rel_time . '<br><span class="text-[10px] text-slate-500 not-italic">' . $formatted_date . '</span>'
        ];
    }
    
    echo json_encode([
        'live' => $live_users,
        'total' => $total_users,
        'logs' => $parsed_logs
    ]);
    exit;
}

// --- LOGIN LOGIC ---
if (isset($_GET['logout'])) { session_destroy(); header("Location: " . strtok($_SERVER["REQUEST_URI"], '?')); exit; }
if (isset($_POST['password'])) {
    if ($_POST['password'] === $DASHBOARD_PASSWORD) {
        $_SESSION['obsera_admin_logged_in'] = true;
        header("Location: " . $_SERVER['PHP_SELF']); exit;
    }
}

// --- SHOW LOGIN SCREEN ---
if (!isset($_SESSION['obsera_admin_logged_in']) || $_SESSION['obsera_admin_logged_in'] !== true) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Analytics | Restricted Access</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 h-screen flex items-center justify-center p-4">
    <div class="bg-slate-800 border border-slate-700 p-8 rounded-2xl shadow-2xl max-w-md w-full text-center">
        <h1 class="text-2xl font-bold text-white mb-2">Live Radar</h1>
        <p class="text-slate-400 text-sm mb-8">Enter password to view live traffic.</p>
        <form method="POST" class="space-y-4">
            <input type="password" name="password" required autofocus class="w-full bg-slate-900 border border-slate-600 rounded-lg px-4 py-3 text-white text-center">
            <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-400 text-slate-900 font-bold py-3 rounded-lg uppercase">View Radar</button>
        </form>
    </div>
</body>
</html>
<?php exit; } ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obsera Live Radar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
    <style> body { background-color: #0f172a; color: white; font-family: 'Inter', sans-serif; } </style>
</head>
<body class="p-6 md:p-10 min-h-screen">

    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-8 border-b border-slate-700 pb-4">
            <div>
                <h1 class="text-3xl font-bold text-emerald-400 flex items-center gap-3">
                    <span class="w-3 h-3 bg-emerald-500 rounded-full animate-ping absolute"></span>
                    <span class="w-3 h-3 bg-emerald-500 rounded-full relative"></span>
                    Live Radar
                </h1>
                <p class="text-slate-400 text-sm mt-1">Real-time visitor tracking for obserasolutions.site</p>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="clearAllData()" class="bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/30 hover:border-red-500/60 px-4 py-2 rounded-lg text-sm font-bold transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Reset DB
                </button>
                <a href="?logout=true" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-lg text-sm transition-all border border-slate-600">Logout</a>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-8">
            <div class="bg-slate-800/50 border border-slate-700 p-6 rounded-2xl flex flex-col items-center justify-center text-center">
                <span class="text-slate-400 text-sm font-bold uppercase tracking-widest mb-2">People Online Right Now</span>
                <span id="metric-live" class="text-6xl font-bold text-emerald-400" style="font-family: 'Space Grotesk', sans-serif;">-</span>
            </div>
            <div class="bg-slate-800/50 border border-slate-700 p-6 rounded-2xl flex flex-col items-center justify-center text-center">
                <span class="text-slate-400 text-sm font-bold uppercase tracking-widest mb-2">Total Unique Visitors</span>
                <span id="metric-total" class="text-6xl font-bold text-sky-400" style="font-family: 'Space Grotesk', sans-serif;">-</span>
            </div>
        </div>

        <div class="bg-slate-800/50 border border-slate-700 rounded-2xl overflow-hidden shadow-xl">
            <div class="px-6 py-4 border-b border-slate-700 bg-slate-800 flex justify-between items-center">
                <h2 class="font-bold text-white">Recent Activity Log</h2>
                <span class="text-xs text-slate-400 bg-slate-900 px-2 py-1 rounded">Updates every 3s</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-900/50 text-slate-400 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Device ID</th>
                            <th class="px-6 py-4">Current Page</th>
                            <th class="px-6 py-4 text-center">Total Visits</th>
                            <th class="px-6 py-4">Last Seen</th>
                            <th class="px-6 py-4 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="log-table" class="divide-y divide-slate-700/50">
                        <tr><td colspan="6" class="px-6 py-8 text-center text-slate-500">Loading live data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Get the current URL dynamically so the AJAX doesn't break if you rename the file
        const CURRENT_URL = window.location.pathname;

        async function fetchLiveAnalytics() {
            try {
                const response = await fetch(CURRENT_URL + '?ajax=1');
                const data = await response.json();
                
                if (data.error) {
                    if (data.error === 'No database') {
                        document.getElementById('metric-live').innerText = '0';
                        document.getElementById('metric-total').innerText = '0';
                        document.getElementById('log-table').innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-slate-500">Awaiting first visitor...</td></tr>';
                    }
                    return;
                }

                // Update Metrics
                document.getElementById('metric-live').innerText = data.live;
                document.getElementById('metric-total').innerText = data.total;

                // Update Table
                let tableHtml = '';
                data.logs.forEach(log => {
                    tableHtml += `
                        <tr class="hover:bg-slate-700/20 transition-colors">
                            <td class="px-6 py-4 font-medium">${log.status}</td>
                            <td class="px-6 py-4 text-slate-300 font-mono text-xs">${log.id}</td>
                            <td class="px-6 py-4 text-sky-400">${log.page}</td>
                            <td class="px-6 py-4 text-slate-300 font-bold text-center">${log.visits}</td>
                            <td class="px-6 py-4 text-slate-500 italic">${log.time_ago}</td>
                            <td class="px-6 py-4 text-center">
                                <button onclick="deleteRecord('${log.raw_id}')" class="text-slate-500 hover:text-red-400 transition-colors" title="Delete Visitor">
                                    <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                
                if (data.logs.length === 0) {
                    tableHtml = '<tr><td colspan="6" class="px-6 py-8 text-center text-slate-500">No visitors logged yet.</td></tr>';
                }
                
                document.getElementById('log-table').innerHTML = tableHtml;

            } catch (error) {
                console.error("Live sync error", error);
            }
        }

        // --- NEW DELETE LOGIC ---
        async function deleteRecord(deviceId) {
            if (!confirm('Are you sure you want to delete this visitor?')) return;
            
            const fd = new FormData();
            fd.append('action', 'delete_single');
            fd.append('device_id', deviceId);
            
            await fetch(CURRENT_URL, { method: 'POST', body: fd });
            fetchLiveAnalytics(); // Refresh the table instantly
        }

        async function clearAllData() {
            if (!confirm('⚠️ WARNING: This will permanently erase ALL tracking data. Are you absolutely sure?')) return;
            
            const fd = new FormData();
            fd.append('action', 'clear_all');
            
            await fetch(CURRENT_URL, { method: 'POST', body: fd });
            fetchLiveAnalytics(); // Refresh the table instantly
        }

        // Run immediately, then every 3 seconds
        fetchLiveAnalytics();
        setInterval(fetchLiveAnalytics, 3000);
    </script>
</body>
</html>