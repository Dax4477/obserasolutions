<?php
session_start();
$ADMIN_PASSWORD = "obserax"; 

date_default_timezone_set('Asia/Kolkata');

// --- AJAX LIVE DATA ENDPOINT ---
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['admin_approvals'])) { echo json_encode(['error' => 'Unauthorized']); exit; }
    
    $file = 'pending_ads.json';
    $pending_ads = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    
    echo json_encode(['ads' => $pending_ads]);
    exit;
}

// --- AJAX ARCHIVE ENDPOINT ---
if (isset($_GET['ajax']) && $_GET['ajax'] == 'archive') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['admin_approvals'])) { echo json_encode(['error' => 'Unauthorized']); exit; }
    
    $file = 'completed_ads.json';
    $completed_ads = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    
    echo json_encode(['archive' => $completed_ads]);
    exit;
}

// Handle Moving an Ad to the Archive
if (isset($_POST['action']) && $_POST['action'] === 'complete' && isset($_POST['id'])) {
    header('Content-Type: application/json');
    if (!isset($_SESSION['admin_approvals'])) { echo json_encode(['error' => 'Unauthorized']); exit; }
    
    $file = 'pending_ads.json';
    $archive_file = 'completed_ads.json';
    
    if (file_exists($file)) {
        $ads = json_decode(file_get_contents($file), true);
        $completed_ad = null;
        
        // Find the ad to move
        foreach($ads as $index => $ad) {
            if($ad['id'] === $_POST['id']) {
                $completed_ad = $ad;
                unset($ads[$index]); // Remove from pending
                break;
            }
        }
        
        // If found, save to archive
        if ($completed_ad) {
            file_put_contents($file, json_encode(array_values($ads), JSON_PRETTY_PRINT)); // Update pending list
            
            $archive = file_exists($archive_file) ? json_decode(file_get_contents($archive_file), true) : [];
            $completed_ad['completed_at'] = date('c'); // Tag with exact completion time
            array_unshift($archive, $completed_ad);
            file_put_contents($archive_file, json_encode($archive, JSON_PRETTY_PRINT)); // Save to archive list
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

// Handle Permanent Delete from Archive
if (isset($_POST['action']) && $_POST['action'] === 'permanent_delete' && isset($_POST['id'])) {
    header('Content-Type: application/json');
    if (!isset($_SESSION['admin_approvals'])) { echo json_encode(['error' => 'Unauthorized']); exit; }
    $file = 'completed_ads.json';
    if (file_exists($file)) {
        $ads = json_decode(file_get_contents($file), true);
        $ads = array_filter($ads, function($ad) { return $ad['id'] !== $_POST['id']; });
        file_put_contents($file, json_encode(array_values($ads), JSON_PRETTY_PRINT));
    }
    echo json_encode(['success' => true]);
    exit;
}

// Login Logic
if (isset($_GET['logout'])) { session_destroy(); header("Location: " . strtok($_SERVER["REQUEST_URI"], '?')); exit; }
if (isset($_POST['password']) && $_POST['password'] === $ADMIN_PASSWORD) {
    $_SESSION['admin_approvals'] = true;
    header("Location: " . $_SERVER['PHP_SELF']); exit;
}

// Show Login Screen
if (!isset($_SESSION['admin_approvals'])) {
    die('
    <body style="background:#020617; display:flex; align-items:center; justify-content:center; height:100vh; font-family:sans-serif; margin:0;">
        <form method="POST" style="background:rgba(15,23,42,0.8); padding:40px; border-radius:24px; text-align:center; border:1px solid rgba(255,255,255,0.1); width:320px; backdrop-filter:blur(10px);">
            <div style="background:#0ea5e9; width:50px; height:50px; border-radius:12px; margin:0 auto 20px; display:flex; align-items:center; justify-content:center; color:#020617; font-weight:bold; font-size:24px;">✓</div>
            <h2 style="color:white; margin-bottom:10px; font-size:1.5rem; font-family:\'Inter\', sans-serif;">Ad Approvals</h2>
            <input type="password" name="password" placeholder="••••••" autofocus style="padding:14px; border-radius:12px; border:1px solid #334155; background:#0f172a; color:white; margin-bottom:15px; width:100%; text-align:center; font-size:1.2rem; outline:none;">
            <button type="submit" style="background:#0ea5e9; color:#0f172a; border:none; padding:14px; border-radius:12px; cursor:pointer; font-weight:bold; width:100%; font-size:1rem;">ACCESS QUEUE</button>
        </form>
    </body>');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obsera Ad Approvals</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #020617; color: white; font-family: 'Inter', sans-serif; }
        .font-display { font-family: 'Space Grotesk', sans-serif; }
        .glass { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.05); }
        .fade-in { animation: fadeIn 0.4s ease-out forwards; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(56,189,248,0.5); border-radius: 2px; }
    </style>
</head>
<body class="p-6 md:p-10 min-h-screen relative">
    
    <div class="max-w-4xl mx-auto">
        <header class="flex justify-between items-end mb-10 border-b border-slate-800 pb-6">
            <div>
                <h1 class="text-4xl font-display font-bold text-white tracking-wide">Approval Queue</h1>
                <p class="text-slate-400 mt-1 text-sm">Review, contact, and deploy client ad requests.</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right mr-3">
                    <div class="text-slate-500 text-[10px] uppercase font-bold tracking-widest mb-1">Pending</div>
                    <div id="pending-count" class="text-3xl font-bold text-sky-400 leading-none">0</div>
                </div>
                <button onclick="toggleArchive()" class="bg-slate-800 hover:bg-slate-700 border border-slate-700 text-sky-400 px-4 py-2.5 rounded-lg text-sm transition-all font-bold flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                    History
                </button>
                <a href="?logout=true" class="bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 px-4 py-2.5 rounded-lg text-sm transition-all font-bold">Logout</a>
            </div>
        </header>

        <div id="queue-container" class="grid grid-cols-1 gap-6">
            <div class="col-span-full text-center py-10">
                <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sky-500 mx-auto"></div>
            </div>
        </div>
    </div>

    <div id="archiveModal" class="hidden fixed inset-0 bg-[#020617]/95 backdrop-blur-md z-[100] fade-in overflow-y-auto custom-scrollbar flex items-start justify-center p-4 md:p-10">
        <div class="w-full max-w-4xl bg-slate-900 border border-slate-700 rounded-3xl p-6 md:p-10 relative">
            <div class="flex justify-between items-center mb-8 border-b border-slate-800 pb-4">
                <div>
                    <h2 class="text-3xl font-display font-bold text-white">Completed Campaigns</h2>
                    <p class="text-slate-400 text-sm mt-1">Archived history of deployed requests.</p>
                </div>
                <button onclick="toggleArchive()" class="bg-slate-800 hover:bg-slate-700 text-slate-300 p-2.5 rounded-full transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div id="archive-container" class="grid grid-cols-1 gap-6">
                <div class="text-center py-10 text-slate-500">Loading history...</div>
            </div>
        </div>
    </div>

    <script>
        const CURRENT_URL = window.location.pathname;
        let currentAdsDataStr = "";

        async function fetchQueue() {
            try {
                const response = await fetch(CURRENT_URL + '?ajax=1&t=' + Date.now());
                const data = await response.json();
                
                if (data.error) return;

                const newDataStr = JSON.stringify(data.ads);
                
                if (newDataStr !== currentAdsDataStr) {
                    currentAdsDataStr = newDataStr;
                    
                    document.getElementById('pending-count').innerText = data.ads.length;
                    const container = document.getElementById('queue-container');

                    if (data.ads.length === 0) {
                        container.innerHTML = `
                            <div class="glass rounded-3xl p-16 text-center border-dashed border-2 border-slate-800 fade-in">
                                <div class="w-20 h-20 bg-slate-900 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <svg class="w-10 h-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <h2 class="text-2xl font-bold text-white mb-2 font-display">Queue is Empty</h2>
                                <p class="text-slate-500">All caught up! No pending requests.</p>
                            </div>
                        `;
                        return;
                    }

                    let html = '';
                    data.ads.forEach(ad => {
                        
                        let targetScreensHTML = '';
                        if (ad.targetScreens) {
                            const screenArray = ad.targetScreens.split(', ');
                            if (screenArray.length > 1) {
                                const listItems = screenArray.map(s => `<li class="px-2 py-1.5 border-b border-slate-700 last:border-0 truncate hover:bg-slate-700 transition-colors">${s}</li>`).join('');
                                targetScreensHTML = `
                                    <div class="relative w-full">
                                        <button onclick="document.getElementById('screens-${ad.id}').classList.toggle('hidden')" class="text-[11px] font-bold text-sky-400 hover:text-sky-300 transition-colors flex items-center justify-between w-full bg-sky-500/10 px-2 py-1.5 rounded border border-sky-500/20">
                                            <span>View ${screenArray.length}</span>
                                            <svg class="w-3 h-3 pointer-events-none shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </button>
                                        <ul id="screens-${ad.id}" class="hidden mt-1 w-full bg-slate-800 border border-slate-600 rounded-md shadow-inner max-h-24 overflow-y-auto custom-scrollbar text-[10px] text-slate-300">
                                            ${listItems}
                                        </ul>
                                    </div>
                                `;
                            } else {
                                targetScreensHTML = `<span class="text-xs font-bold text-sky-400 line-clamp-2">${ad.targetScreens}</span>`;
                            }
                        } else {
                            targetScreensHTML = `<span class="text-xs font-bold text-slate-500">Unknown</span>`;
                        }

                        const wa_link = ad.contactDetails ? "https://wa.me/" + ad.contactDetails.replace(/[^0-9]/g, '') : "#";
                        const dateObj = new Date(ad.timestamp);
                        const dateStr = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ', ' + dateObj.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

                        html += `
                            <div class="glass rounded-2xl overflow-visible flex flex-col group transition-all hover:border-sky-500/30 shadow-lg fade-in" id="card-${ad.id}">
                                <div class="p-6 md:p-8 flex flex-col md:flex-row justify-between gap-6">
                                    
                                    <div class="flex-grow">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="bg-slate-800 px-2 py-1 rounded text-sky-400 font-mono text-[10px] tracking-widest uppercase font-bold border border-slate-700">${ad.requestType || 'Enquiry'}</span>
                                            <span class="text-xs text-slate-500 font-mono">${dateStr}</span>
                                        </div>
                                        
                                        <h2 class="text-3xl font-display font-bold text-white leading-none mb-4">${ad.businessName || 'Unknown Business'}</h2>
                                        
                                        <div class="inline-block bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 mb-6">
                                            <span class="text-[10px] text-slate-500 uppercase tracking-widest font-bold block mb-1">Contact via ${ad.contactMethod || 'Phone'}</span>
                                            <span class="text-white font-bold">${ad.contactDetails || 'No details provided'}</span>
                                        </div>

                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-slate-900/50 rounded-xl p-4 border border-white/5 items-start">
                                            <div>
                                                <span class="block text-[10px] text-slate-500 uppercase font-bold tracking-wider mb-1">Format</span>
                                                <span class="text-sm font-bold text-white">${ad.format || 'Unknown'}</span>
                                            </div>
                                            <div>
                                                <span class="block text-[10px] text-slate-500 uppercase font-bold tracking-wider mb-1">Duration</span>
                                                <span class="text-sm font-bold text-white">${ad.duration || 'Unknown'}</span>
                                            </div>
                                            <div>
                                                <span class="block text-[10px] text-slate-500 uppercase font-bold tracking-wider mb-1">Campaign</span>
                                                <span class="text-sm font-bold text-white">${ad.days || 'Unknown'}</span>
                                            </div>
                                            <div class="w-full">
                                                <span class="block text-[10px] text-slate-500 uppercase font-bold tracking-wider mb-1">Target Screens</span>
                                                ${targetScreensHTML}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-3 shrink-0 md:w-48 justify-between">
                                        <div class="bg-slate-900 p-4 rounded-xl border border-emerald-500/20 text-center">
                                            <span class="block text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-1">Est. Revenue</span>
                                            <span class="text-2xl font-bold text-emerald-400 font-mono leading-none block">${ad.cost || '₹0'}</span>
                                        </div>
                                        
                                        <div class="flex flex-col gap-2 mt-auto">
                                            <a href="${wa_link}" target="_blank" class="w-full bg-[#25D366]/10 hover:bg-[#25D366]/20 text-[#25D366] border border-[#25D366]/30 px-4 py-2.5 rounded-xl font-bold text-sm text-center transition-all">
                                                Message Client
                                            </a>
                                            <button onclick="markDeployed('${ad.id}')" class="flex-grow bg-sky-500 hover:bg-sky-400 text-slate-950 px-3 py-2.5 rounded-xl font-bold text-sm text-center transition-all shadow-lg shadow-sky-500/20">
                                                Mark Done & Save
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        `;
                    });
                    
                    container.innerHTML = html;
                }
            } catch (error) {
                console.error("Queue sync error:", error);
            }
        }

        async function markDeployed(id) {
            if(!confirm('Mark this campaign as deployed and move to history?')) return;
            
            const card = document.getElementById('card-' + id);
            if(card) {
                card.style.opacity = '0.5';
                card.style.pointerEvents = 'none';
                card.style.transform = 'scale(0.95)';
            }
            
            const fd = new FormData();
            fd.append('action', 'complete'); // Send to Archive
            fd.append('id', id);
            
            await fetch(CURRENT_URL, { method: 'POST', body: fd });
            fetchQueue(); // Refresh the list instantly
        }

        // --- HISTORY / ARCHIVE LOGIC ---
        function toggleArchive() {
            const modal = document.getElementById('archiveModal');
            modal.classList.toggle('hidden');
            if(!modal.classList.contains('hidden')) {
                loadArchive();
            }
        }

        async function loadArchive() {
            try {
                const response = await fetch(CURRENT_URL + '?ajax=archive&t=' + Date.now());
                const data = await response.json();
                
                const container = document.getElementById('archive-container');
                if (!data.archive || data.archive.length === 0) {
                    container.innerHTML = '<div class="text-center py-10 text-slate-500 font-bold">History is empty.</div>';
                    return;
                }

                let html = '';
                data.archive.forEach(ad => {
                    const completedDate = new Date(ad.completed_at || ad.timestamp);
                    const completedDateStr = completedDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' at ' + completedDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                    
                    const submitDate = new Date(ad.timestamp);
                    const submitDateStr = submitDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

                    // Clean target screens string
                    const screens = ad.targetScreens ? ad.targetScreens.replace(/, /g, ' • ') : 'Unknown';

                    html += `
                        <div class="bg-slate-800/50 border border-slate-700 p-6 rounded-2xl flex flex-col gap-4 group transition-colors hover:border-slate-500" id="archive-${ad.id}">
                            <div class="flex justify-between items-start border-b border-slate-700 pb-4">
                                <div>
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="bg-emerald-500/10 text-emerald-400 px-2 py-1 rounded font-mono text-[10px] tracking-widest uppercase font-bold border border-emerald-500/20">Completed</span>
                                        <span class="text-xs text-slate-500 font-mono">Done: ${completedDateStr}</span>
                                    </div>
                                    <h3 class="text-white font-display font-bold text-2xl leading-tight mb-1">${ad.businessName || 'Unknown Business'}</h3>
                                    <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Submitted: ${submitDateStr} • ${ad.requestType || 'Enquiry'}</p>
                                </div>
                                <div class="text-right">
                                    <button onclick="deleteForever('${ad.id}')" class="bg-slate-900 hover:bg-red-500/20 text-slate-500 hover:text-red-400 border border-slate-700 hover:border-red-500/30 p-2.5 rounded-xl transition-all" title="Delete Permanently">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <span class="block text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-1">Contact (${ad.contactMethod || 'Phone'})</span>
                                    <span class="text-sm font-bold text-white break-all">${ad.contactDetails || 'N/A'}</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-1">Format</span>
                                    <span class="text-sm font-bold text-white">${ad.format || 'Unknown'} (${ad.duration || '-'})</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-1">Campaign</span>
                                    <span class="text-sm font-bold text-white">${ad.days || 'Unknown'}</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-1">Revenue</span>
                                    <span class="text-lg font-bold text-emerald-400 font-mono leading-none block">${ad.cost || '₹0'}</span>
                                </div>
                            </div>
                            
                            <div class="bg-slate-900/50 p-4 rounded-xl border border-slate-700 mt-2">
                                 <span class="block text-[10px] text-slate-500 uppercase font-bold tracking-wider mb-1">Target Screens</span>
                                 <span class="text-xs font-bold text-sky-400 leading-relaxed">${screens}</span>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            } catch (e) {
                console.error(e);
            }
        }

        async function deleteForever(id) {
            if(!confirm('Permanently delete this record? This cannot be undone.')) return;
            const el = document.getElementById('archive-' + id);
            if(el) {
                el.style.opacity = '0.5';
                el.style.pointerEvents = 'none';
            }
            
            const fd = new FormData();
            fd.append('action', 'permanent_delete');
            fd.append('id', id);
            await fetch(CURRENT_URL, { method: 'POST', body: fd });
            
            if(el) el.style.display = 'none';
        }

        // Close dropdowns if user clicks outside of them
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.relative')) {
                document.querySelectorAll('[id^="screens-"]').forEach(el => {
                    el.classList.add('hidden');
                });
            }
        });

        // Run instantly, then poll every 10 seconds
        fetchQueue();
        setInterval(fetchQueue, 10000);
    </script>
</body>
</html>