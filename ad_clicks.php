<?php
// 1. Password Protect the Ad Radar
$ADMIN_PASSWORD = "obserax"; 

session_start();
if (isset($_POST['pass']) && $_POST['pass'] === $ADMIN_PASSWORD) {
    $_SESSION['admin_radar'] = true;
}

if (!isset($_SESSION['admin_radar'])) {
    die('
    <body style="background:#0f172a; display:flex; align-items:center; justify-content:center; height:100vh; font-family:sans-serif; margin:0;">
        <form method="POST" style="background:#1e293b; padding:40px; border-radius:24px; text-align:center; border:1px solid #334155; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); width:320px;">
            <div style="background:#0ea5e9; width:50px; height:50px; border-radius:12px; margin:0 auto 20px; display:flex; align-items:center; justify-content:center; color:white; font-weight:bold; font-size:24px;">$</div>
            <h2 style="color:white; margin-bottom:10px; font-size:1.5rem;">Ad Tracker Login</h2>
            <input type="password" name="pass" placeholder="••••••" autofocus style="padding:14px; border-radius:12px; border:1px solid #334155; background:#0f172a; color:white; margin-bottom:15px; width:100%; text-align:center; font-size:1.2rem; outline:none;">
            <button type="submit" style="background:#0ea5e9; color:white; border:none; padding:14px; border-radius:12px; cursor:pointer; font-weight:bold; width:100%; font-size:1rem;">UNLOCK TRACKER</button>
        </form>
    </body>');
}

// 2. Load the Analytics Data
$dataFile = 'analytics_data.json';
$stats = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];

// 3. Process Ad Data for Merchants AND Global Links
$grandTotal = 0;
$totalAdClicks = 0;
$globalLinks = [];

foreach($stats as $client => $data) { 
    $grandTotal += $data['total_views'] ?? 0; 
    if(isset($data['ad_clicks'])) { 
        foreach($data['ad_clicks'] as $url => $clicks) { 
            $totalAdClicks += $clicks; 
            
            // Build Global Links Array
            if(!isset($globalLinks[$url])) {
                $globalLinks[$url] = [
                    'total_clicks' => 0,
                    'merchants' => []
                ];
            }
            $globalLinks[$url]['total_clicks'] += $clicks;
            $globalLinks[$url]['merchants'][$client] = $clicks;
        } 
    }
}

// Sort global links by highest clicks first
uasort($globalLinks, function($a, $b) {
    return $b['total_clicks'] <=> $a['total_clicks'];
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obsera Ad Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@700&family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-oswald { font-family: 'Oswald', sans-serif; }
        
        /* --- PRINT CSS: Hides the dark dashboard and shows a clean white report --- */
        @media print {
            body { background: white !important; color: black !important; padding: 0 !important; margin: 0 !important; }
            #radar-data { display: none !important; }
            #print-area { display: block !important; position: absolute; top: 0; left: 0; width: 100%; }
        }
        @media screen {
            #print-area { display: none; }
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 p-4 md:p-10 min-h-screen">
    
    <div id="print-area"></div>

    <div id="radar-data" class="max-w-7xl mx-auto">
        
        <script type="application/json" id="live-stats-data"><?php echo json_encode($stats); ?></script>

        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6 border-b border-slate-800 pb-10">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="flex h-3 w-3 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-cyan-500"></span>
                    </span>
                    <h1 class="text-4xl md:text-5xl font-black text-white font-oswald italic tracking-tighter">AD RADAR</h1>
                </div>
                <p class="text-sky-500 font-bold uppercase text-[10px] tracking-[0.3em] mt-1">Sponsor Performance Hub</p>
            </div>
            <div class="flex gap-8 md:gap-12">
                <div class="text-left md:text-right">
                    <div class="text-slate-500 text-[10px] uppercase font-bold tracking-widest mb-1">Total Network Scans</div>
                    <div class="text-3xl md:text-4xl font-bold text-white leading-none"><?php echo number_format($grandTotal); ?></div>
                </div>
                <div class="text-left md:text-right">
                    <div class="text-slate-500 text-[10px] uppercase font-bold tracking-widest mb-1">Total Ad Clicks</div>
                    <div class="text-3xl md:text-5xl font-bold text-sky-400 leading-none"><?php echo number_format($totalAdClicks); ?></div>
                </div>
            </div>
        </header>

        <div class="mb-16">
            <h2 class="text-xl font-oswald text-white mb-6 border-l-4 border-emerald-500 pl-3">NETWORK-WIDE CAMPAIGNS</h2>
            
            <?php if(empty($globalLinks)): ?>
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 text-center text-slate-500 font-bold uppercase tracking-widest text-xs">
                    No active link data available.
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach($globalLinks as $url => $linkData): ?>
                        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col hover:border-emerald-500/30 transition-colors relative">
                            
                            <div class="flex justify-between items-start mb-4 border-b border-slate-800 pb-4">
                                <div class="pr-2 overflow-hidden">
                                    <h3 class="text-emerald-500 font-bold uppercase tracking-widest text-[10px] mb-1">Global Link</h3>
                                    <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" class="text-lg font-bold text-white truncate block hover:underline hover:text-sky-400 transition-colors"><?php echo htmlspecialchars($url); ?></a>
                                </div>
                                
                                <div class="text-right shrink-0 ml-2">
                                    <div class="text-slate-500 text-[9px] font-bold uppercase tracking-widest mb-1">Total Clicks</div>
                                    <div class="text-3xl font-black text-emerald-400 leading-none"><?php echo number_format($linkData['total_clicks']); ?></div>
                                </div>
                            </div>

                            <div class="flex-grow">
                                <h3 class="text-slate-500 font-bold uppercase tracking-widest text-[9px] mb-2">Active On <?php echo count($linkData['merchants']); ?> Screen(s)</h3>
                                <div class="flex flex-wrap gap-2 mb-4">
                                    <?php foreach($linkData['merchants'] as $merchant => $clicks): ?>
                                        <span class="bg-slate-800 border border-slate-700 text-slate-300 text-[10px] px-2 py-1 rounded uppercase font-bold tracking-wider">
                                            <?php echo htmlspecialchars($merchant); ?> <span class="text-emerald-400 ml-1"><?php echo number_format($clicks); ?></span>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <button onclick="printGlobalReport('<?php echo addslashes(htmlspecialchars($url)); ?>')" class="mt-auto w-full text-[10px] bg-slate-800 hover:bg-emerald-500 hover:text-slate-900 text-slate-400 font-bold px-3 py-3 rounded-xl transition-all uppercase tracking-widest flex items-center justify-center gap-2 border border-slate-700 hover:border-emerald-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                Print Network Report
                            </button>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <h2 class="text-xl font-oswald text-white mb-6 border-l-4 border-sky-500 pl-3">INDIVIDUAL MERCHANT DATA</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach($stats as $id => $data): ?>
                    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col hover:border-sky-500/30 transition-colors relative">
                        
                        <div class="flex justify-between items-start mb-6">
                            <div class="pr-2">
                                <h3 class="text-sky-500 font-bold uppercase tracking-widest text-[10px] mb-1">Merchant</h3>
                                <h2 class="text-2xl md:text-3xl font-bold text-white uppercase"><?php echo htmlspecialchars($id); ?></h2>
                            </div>
                            
                            <div class="flex flex-col items-end gap-2 shrink-0">
                                <div class="text-right bg-slate-800/50 px-3 py-2 rounded-xl">
                                    <div class="text-slate-500 text-[9px] font-bold uppercase tracking-widest mb-1">Menu Views</div>
                                    <div class="text-xl font-black text-white"><?php echo number_format($data['total_views'] ?? 0); ?></div>
                                </div>
                                
                                <button onclick="printMerchantReport('<?php echo addslashes(htmlspecialchars($id)); ?>')" class="text-[9px] bg-slate-800 hover:bg-sky-500 hover:text-slate-900 text-slate-400 font-bold px-3 py-1.5 rounded-lg transition-all uppercase tracking-widest flex items-center gap-1.5 border border-slate-700 hover:border-sky-500">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                    Print Details
                                </button>
                            </div>
                        </div>

                        <div class="flex-grow bg-slate-950/50 rounded-2xl p-4 border border-slate-800/50 mt-2">
                            <h3 class="text-slate-400 font-bold uppercase tracking-widest text-[10px] mb-4 border-b border-slate-800 pb-2">Ad Links Clicked</h3>
                            
                            <?php if(!empty($data['ad_clicks'])): ?>
                                <div class="space-y-3">
                                    <?php foreach($data['ad_clicks'] as $url => $clicks): ?>
                                        <div class="flex justify-between items-center bg-slate-900 p-3 rounded-xl border border-slate-800 transition hover:border-sky-500/50">
                                            <div class="flex flex-col overflow-hidden mr-3">
                                                <span class="text-[9px] font-bold text-slate-500 uppercase mb-0.5">Destination</span>
                                                <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" class="text-[11px] text-sky-400 truncate font-mono hover:underline"><?php echo htmlspecialchars($url); ?></a>
                                            </div>
                                            <div class="text-white font-black text-2xl text-right flex flex-col items-end leading-none">
                                                <?php echo number_format($clicks); ?>
                                                <span class="text-[8px] text-slate-500 uppercase tracking-widest mt-1">Clicks</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-6 text-slate-600">
                                    <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    <p class="text-[10px] uppercase tracking-widest font-bold">No Clicks Recorded</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <footer class="mt-20 py-10 border-t border-slate-900 text-center text-slate-600 text-[10px] font-bold uppercase tracking-[0.4em]">
            Obsera Solutions &bull; Ad Tracking System
        </footer>
    </div>

    <script>
        // --- 1. PRINT DETAILED SINGLE MERCHANT REPORT ---
        function printMerchantReport(merchantId) {
            const statsStr = document.getElementById('live-stats-data').textContent;
            const allStats = JSON.parse(statsStr);
            const data = allStats[merchantId];
            if(!data) return;

            let totalClicks = 0;
            let clicksHtml = '';
            
            // Build Link Clicks Rows
            if (data.ad_clicks) {
                for (const [url, clicks] of Object.entries(data.ad_clicks)) {
                    totalClicks += clicks;
                    clicksHtml += `
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 12px; font-family: monospace; font-size: 13px; color: #0284c7; word-break: break-all;">${url}</td>
                            <td style="padding: 12px; text-align: right; font-weight: bold; font-size: 16px;">${clicks.toLocaleString()}</td>
                        </tr>
                    `;
                }
            }
            if (!clicksHtml) {
                clicksHtml = `<tr><td colspan="2" style="padding: 20px; text-align: center; color: #94a3b8; font-style: italic;">No ad clicks have been recorded for this location.</td></tr>`;
            }

            // Build Daily Views Rows
            let dailyHtml = '';
            if (data.daily_views) {
                // Sort dates newest to oldest
                const dates = Object.keys(data.daily_views).sort((a, b) => new Date(b) - new Date(a));
                dates.forEach(dateStr => {
                    const dateObj = new Date(dateStr);
                    const formattedDate = dateObj.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
                    const views = data.daily_views[dateStr];
                    
                    dailyHtml += `
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 12px; font-size: 13px; color: #475569;">${formattedDate}</td>
                            <td style="padding: 12px; text-align: right; font-weight: bold; font-size: 14px;">${views.toLocaleString()} Scans</td>
                        </tr>
                    `;
                });
            }
            if (!dailyHtml) {
                dailyHtml = `<tr><td colspan="2" style="padding: 20px; text-align: center; color: #94a3b8; font-style: italic;">No daily scan data available.</td></tr>`;
            }

            const totalScans = data.total_views || 0;
            const ctr = totalScans > 0 ? ((totalClicks / totalScans) * 100).toFixed(1) : 0;
            const timestamp = new Date().toLocaleString();

            const printHtml = `
                <div style="font-family: 'Inter', sans-serif; padding: 40px; max-width: 800px; margin: 0 auto; color: #0f172a;">
                    <div style="text-align: center; margin-bottom: 40px; border-bottom: 2px solid #0f172a; padding-bottom: 20px;">
                        <h1 style="font-family: 'Oswald', sans-serif; font-size: 38px; margin: 0; text-transform: uppercase;">${merchantId}</h1>
                        <p style="margin: 5px 0 0 0; color: #64748b; text-transform: uppercase; letter-spacing: 3px; font-size: 14px; font-weight: bold;">Detailed Performance Report</p>
                        <p style="margin: 5px 0 0 0; color: #94a3b8; font-size: 11px;">Generated: ${timestamp}</p>
                    </div>

                    <div style="display: flex; justify-content: space-between; margin-bottom: 40px;">
                        <div style="text-align: center; border: 1px solid #cbd5e1; padding: 25px 10px; border-radius: 16px; width: 31%; background: #f8fafc;">
                            <p style="margin: 0; font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: bold; letter-spacing: 1px;">Total Menu Scans</p>
                            <h2 style="margin: 10px 0 0 0; font-size: 40px; font-family: 'Oswald', sans-serif;">${totalScans.toLocaleString()}</h2>
                        </div>
                        <div style="text-align: center; border: 2px solid #bae6fd; padding: 25px 10px; border-radius: 16px; width: 31%; background: #f0f9ff;">
                            <p style="margin: 0; font-size: 12px; color: #0284c7; text-transform: uppercase; font-weight: bold; letter-spacing: 1px;">Total Ad Clicks</p>
                            <h2 style="margin: 10px 0 0 0; font-size: 40px; font-family: 'Oswald', sans-serif; color: #0369a1;">${totalClicks.toLocaleString()}</h2>
                        </div>
                        <div style="text-align: center; border: 1px solid #cbd5e1; padding: 25px 10px; border-radius: 16px; width: 31%; background: #f8fafc;">
                            <p style="margin: 0; font-size: 12px; color: #10b981; text-transform: uppercase; font-weight: bold; letter-spacing: 1px;">Conversion (CTR)</p>
                            <h2 style="margin: 10px 0 0 0; font-size: 40px; font-family: 'Oswald', sans-serif; color: #059669;">${ctr}%</h2>
                        </div>
                    </div>

                    <h3 style="font-size: 16px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px; color: #334155; font-weight: bold;">Ad Clicks Breakdown</h3>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 40px;">
                        <thead>
                            <tr style="background: #f1f5f9; text-transform: uppercase; font-size: 11px; color: #475569; letter-spacing: 1px;">
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #cbd5e1;">Destination URL</th>
                                <th style="padding: 12px; text-align: right; border-bottom: 2px solid #cbd5e1;">Click Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${clicksHtml}
                        </tbody>
                    </table>

                    <h3 style="font-size: 16px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px; color: #334155; font-weight: bold;">Daily Scans Breakdown</h3>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 40px;">
                        <thead>
                            <tr style="background: #f1f5f9; text-transform: uppercase; font-size: 11px; color: #475569; letter-spacing: 1px;">
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #cbd5e1;">Date</th>
                                <th style="padding: 12px; text-align: right; border-bottom: 2px solid #cbd5e1;">Menu Scans</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${dailyHtml}
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 60px; text-align: center; font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 3px; font-weight: bold;">
                        Powered by Obsera Solutions
                    </div>
                </div>
            `;

            document.getElementById('print-area').innerHTML = printHtml;
            window.print();
        }

        // --- 2. PRINT GLOBAL NETWORK REPORT ---
        function printGlobalReport(targetUrl) {
            const statsStr = document.getElementById('live-stats-data').textContent;
            const allStats = JSON.parse(statsStr);
            
            let totalNetworkClicks = 0;
            let activeScreensCount = 0;
            let breakdownHtml = '';
            
            // Loop through all merchants to find this specific link
            for (const [merchantId, data] of Object.entries(allStats)) {
                if (data.ad_clicks && data.ad_clicks[targetUrl]) {
                    const clicks = data.ad_clicks[targetUrl];
                    totalNetworkClicks += clicks;
                    activeScreensCount++;
                    
                    breakdownHtml += `
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 12px; font-family: 'Oswald', sans-serif; font-size: 18px; color: #0284c7; text-transform: uppercase;">${merchantId}</td>
                            <td style="padding: 12px; text-align: right; font-weight: bold; font-size: 18px;">${clicks.toLocaleString()}</td>
                        </tr>
                    `;
                }
            }

            const timestamp = new Date().toLocaleString();

            const printHtml = `
                <div style="font-family: 'Inter', sans-serif; padding: 40px; max-width: 800px; margin: 0 auto; color: #0f172a;">
                    <div style="text-align: center; margin-bottom: 40px; border-bottom: 2px solid #0f172a; padding-bottom: 20px;">
                        <p style="margin: 0 0 5px 0; color: #10b981; text-transform: uppercase; letter-spacing: 3px; font-size: 14px; font-weight: bold;">Network-Wide Campaign Report</p>
                        <h1 style="font-family: monospace; font-size: 20px; margin: 0; color: #334155; word-break: break-all; background: #f8fafc; padding: 10px; border-radius: 8px;">${targetUrl}</h1>
                        <p style="margin: 15px 0 0 0; color: #94a3b8; font-size: 11px;">Generated: ${timestamp}</p>
                    </div>

                    <div style="display: flex; justify-content: space-between; margin-bottom: 40px;">
                        <div style="text-align: center; border: 1px solid #cbd5e1; padding: 30px; border-radius: 16px; width: 45%; background: #f8fafc;">
                            <p style="margin: 0; font-size: 14px; color: #64748b; text-transform: uppercase; font-weight: bold; letter-spacing: 1px;">Active Screens</p>
                            <h2 style="margin: 10px 0 0 0; font-size: 48px; font-family: 'Oswald', sans-serif;">${activeScreensCount}</h2>
                        </div>
                        <div style="text-align: center; border: 2px solid #a7f3d0; padding: 30px; border-radius: 16px; width: 45%; background: #ecfdf5;">
                            <p style="margin: 0; font-size: 14px; color: #059669; text-transform: uppercase; font-weight: bold; letter-spacing: 1px;">Total Network Clicks</p>
                            <h2 style="margin: 10px 0 0 0; font-size: 48px; font-family: 'Oswald', sans-serif; color: #047857;">${totalNetworkClicks.toLocaleString()}</h2>
                        </div>
                    </div>

                    <h3 style="font-size: 16px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px; color: #334155; font-weight: bold;">Breakdown by Merchant</h3>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 40px;">
                        <thead>
                            <tr style="background: #f1f5f9; text-transform: uppercase; font-size: 11px; color: #475569; letter-spacing: 1px;">
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #cbd5e1;">Merchant / Location</th>
                                <th style="padding: 12px; text-align: right; border-bottom: 2px solid #cbd5e1;">Click Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${breakdownHtml}
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 80px; text-align: center; font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 3px; font-weight: bold;">
                        Powered by Obsera Solutions
                    </div>
                </div>
            `;

            document.getElementById('print-area').innerHTML = printHtml;
            window.print();
        }

        // --- SILENT BACKGROUND UPDATE ---
        setInterval(async () => {
            try {
                const response = await fetch(window.location.href);
                const html = await response.text();
                
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newData = doc.getElementById('radar-data');
                
                if (newData) {
                    document.getElementById('radar-data').innerHTML = newData.innerHTML;
                }
            } catch (error) {
                console.error("Silent refresh failed", error);
            }
        }, 10000); 
    </script>
</body>
</html>