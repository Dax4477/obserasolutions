<?php
session_name("obsera_admin_session");
session_start();

// --- YOUR SECRET PASSWORD ---
$ADMIN_PASSWORD = "obserax"; 

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin");
    exit;
}

// Handle Login Attempt
$error_msg = "";
if (isset($_POST['password'])) {
    if ($_POST['password'] === $ADMIN_PASSWORD) {
        $_SESSION['obsera_admin_logged_in'] = true;
        header("Location: admin.php"); 
        exit;
    } else {
        $error_msg = "Incorrect password. Access denied.";
    }
}

// =========================================================
// IF NOT LOGGED IN: SHOW THE LOGIN SCREEN
// =========================================================
if (!isset($_SESSION['obsera_admin_logged_in']) || $_SESSION['obsera_admin_logged_in'] !== true) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obsera Admin | Restricted Access</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
</head>
<body class="bg-slate-900 h-screen flex items-center justify-center p-4">
    <div class="bg-slate-800 border border-slate-700 p-8 rounded-2xl shadow-2xl max-w-md w-full text-center">
        <div class="w-16 h-16 bg-slate-900 border border-slate-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-inner">
            <svg class="w-8 h-8 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
        </div>
        <h1 class="text-2xl font-bold text-white mb-2" style="font-family: 'Space Grotesk', sans-serif;">Admin Portal</h1>
        <p class="text-slate-400 text-sm mb-8">Please enter the master password to access the Obsera network controls.</p>
        
        <?php if($error_msg): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-400 text-sm py-2 rounded mb-4"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form method="POST" action="admin.php" class="space-y-4">
            <input type="password" name="password" required autofocus placeholder="Enter Password..." class="w-full bg-slate-900 border border-slate-600 rounded-lg px-4 py-3 text-white focus:border-sky-400 focus:outline-none text-center tracking-widest">
            <button type="submit" class="w-full bg-sky-500 hover:bg-sky-400 text-slate-900 font-bold py-3 rounded-lg transition-colors uppercase tracking-wider text-sm shadow-[0_0_15px_rgba(56,189,248,0.3)]">Unlock Dashboard</button>
        </form>
    </div>
</body>
</html>
<?php
    exit; 
}
// =========================================================
// IF LOGGED IN: SHOW THE ADMIN DASHBOARD
// =========================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obsera Admin | Control Center</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.0/papaparse.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
    <style> body { background-color: #0f172a; color: white; font-family: 'Inter', sans-serif; } </style>
</head>
<body class="p-4 md:p-10 min-h-screen flex flex-col items-center justify-center relative">

    <a href="admin.php?logout=true" class="absolute top-4 right-4 md:top-8 md:right-8 bg-slate-800 border border-slate-700 hover:bg-red-500/20 hover:border-red-500/50 hover:text-red-400 text-slate-400 text-xs font-bold px-4 py-2 rounded-lg transition-all flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
        Secure Logout
    </a>

    <div class="w-full max-w-6xl grid md:grid-cols-2 gap-8 mt-10 md:mt-0">
        
        <div class="bg-slate-800/50 border border-slate-700 p-6 rounded-2xl shadow-xl flex flex-col justify-between">
            <div>
                <h1 class="text-2xl font-bold text-sky-400 mb-2">Register New Client</h1>
                <p class="text-slate-400 text-sm mb-6">Fill this out to generate code, test sheets, and create their login.</p>

                <form id="clientForm" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wide">Username (Client ID)</label>
                            <input type="text" id="clientId" required placeholder="e.g., ccd_calicut" class="w-full mt-1 bg-slate-900 border border-slate-600 rounded-lg px-4 py-2 text-white focus:border-sky-400 focus:outline-none">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-amber-400 uppercase tracking-wide">Client Password</label>
                            <input type="text" id="clientPassword" required placeholder="e.g., ccd123" class="w-full mt-1 bg-slate-900 border border-amber-600/50 rounded-lg px-4 py-2 text-white focus:border-amber-400 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wide">Business Name</label>
                        <input type="text" id="clientName" required placeholder="e.g., Cafe Coffee Day" class="w-full mt-1 bg-slate-900 border border-slate-600 rounded-lg px-4 py-2 text-white focus:border-sky-400 focus:outline-none">
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wide">Hours</label>
                            <input type="number" id="clientHours" required placeholder="e.g., 24" class="w-full mt-1 bg-slate-900 border border-slate-600 rounded-lg px-4 py-2 text-white focus:border-sky-400 focus:outline-none">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-sky-400 uppercase tracking-wide">Items/Slide</label>
                            <input type="number" id="itemsPerSlide" value="4" required class="w-full mt-1 bg-slate-900 border border-sky-600/50 rounded-lg px-4 py-2 text-white focus:border-sky-400 focus:outline-none">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wide">Map Link (Opt)</label>
                            <input type="url" id="clientMap" placeholder="http://..." class="w-full mt-1 bg-slate-900 border border-slate-600 rounded-lg px-4 py-2 text-white focus:border-sky-400 focus:outline-none">
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-700 mt-4">
                        <label class="text-xs font-bold text-emerald-400 uppercase tracking-wide">Menu Google Sheet ID</label>
                        <input type="text" id="menuSheetId" required placeholder="Paste the long ID here..." class="w-full mt-1 bg-slate-900 border border-emerald-600/50 rounded-lg px-4 py-2 text-white focus:border-emerald-400 focus:outline-none">
                    </div>

                    <div>
                        <label class="text-xs font-bold text-purple-400 uppercase tracking-wide">Advertisement Google Sheet ID</label>
                        <input type="text" id="adSheetId" required placeholder="Paste the long ID here..." class="w-full mt-1 bg-slate-900 border border-purple-600/50 rounded-lg px-4 py-2 text-white focus:border-purple-400 focus:outline-none">
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-2 gap-4 mt-8">
                <button type="button" onclick="analyzeSheets()" class="w-full bg-slate-700 hover:bg-slate-600 border border-slate-500 text-white font-bold py-3 rounded-lg transition-colors flex items-center justify-center gap-2 text-sm">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    Analyze Live Data
                </button>
                <button type="button" onclick="generateCode()" class="w-full bg-sky-500 hover:bg-sky-400 text-slate-900 font-bold py-3 rounded-lg transition-colors text-sm uppercase tracking-wider">
                    Generate Code
                </button>
            </div>
        </div>

        <div class="flex flex-col gap-6">
            
            <div class="bg-slate-900 border border-slate-700 p-6 rounded-2xl shadow-xl min-h-[200px] flex flex-col justify-center">
                <h2 class="text-xl font-bold text-white mb-4 border-b border-slate-700 pb-2">Sheet Analysis & Loop Math</h2>
                <div id="analyzer-results" class="text-slate-400 text-sm leading-relaxed">
                    Waiting for input... Paste your Sheet IDs and click <strong>Analyze Live Data</strong> to check connections and calculate loop timing.
                </div>
            </div>

            <div class="bg-slate-900 border border-slate-700 p-6 rounded-2xl shadow-xl flex flex-col flex-grow">
                <div class="flex justify-between items-end mb-4">
                    <div>
                        <h2 class="text-xl font-bold text-white">Generated Code</h2>
                        <p class="text-slate-400 text-xs mt-1">Paste this into your clients.js file or auto-save</p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="copyCode()" id="copyBtn" class="bg-slate-700 hover:bg-slate-600 text-white text-xs font-bold px-3 py-1.5 rounded transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                            Copy
                        </button>
                        <button onclick="saveToServer()" id="saveBtn" class="hidden bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-3 py-1.5 rounded transition-colors flex items-center gap-2 shadow-[0_0_10px_rgba(16,185,129,0.3)]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                            Save & Create Login
                        </button>
                    </div>
                </div>
                <textarea id="outputCode" readonly class="w-full flex-grow bg-black border border-slate-700 rounded-lg p-4 text-sky-400 font-mono text-sm focus:outline-none resize-none min-h-[150px]" placeholder="Your JSON code will appear here..."></textarea>
            </div>

        </div>
    </div>

<script>
    let pendingClientCode = "";
    let clientAuthData = {};

function generateCode() {
        const id = document.getElementById('clientId').value.trim().toLowerCase().replace(/[^a-z0-9_]/g, '');
        const name = document.getElementById('clientName').value.trim();
        const hours = document.getElementById('clientHours').value.trim();
        const map = document.getElementById('clientMap').value.trim();
        const menuId = document.getElementById('menuSheetId').value.trim();
        const adId = document.getElementById('adSheetId').value.trim();
        const password = document.getElementById('clientPassword').value.trim(); 
        
        // NEW: Grab the Items Per Slide variable!
        const itemsPerSlide = document.getElementById('itemsPerSlide').value.trim() || "4";

        if (!id || !name || !menuId || !adId || !password) {
            alert("Please fill in all required fields, including the Client Password!");
            return;
        }

        // NEW: We are now saving itemsPerSlide into clients.js!
        pendingClientCode = `    "${id}": {
        name: "${name}",
        tag: "${hours}h Active",
        hours: ${hours},
        mapLink: "${map}",
        menuSheetId: "${menuId}",
        adSheetId: "${adId}",
        itemsPerSlide: ${itemsPerSlide}
    },`;

        clientAuthData = {
            id: id,
            name: name,
            password: password,
            menuSheetId: menuId
        };

        document.getElementById('outputCode').value = pendingClientCode;
        document.getElementById('saveBtn').classList.remove('hidden');
    }

    async function saveToServer() {
        if (!pendingClientCode) return; 

        const btn = document.getElementById('saveBtn');
        const originalText = btn.innerHTML;
        btn.innerHTML = "Saving...";
        
        try {
            const response = await fetch('save_client.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    newClientCode: pendingClientCode,
                    authData: clientAuthData 
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                btn.innerHTML = "Created Successfully! ✓";
                
                const shareableMessage = `Welcome to Obsera Solutions, ${clientAuthData.name}! 🚀

Here is the link to your Live Menu Manager portal:
👉 https://obserasolutions.site/menu_manager

Your Login Credentials:
👤 Username: ${clientAuthData.id}
🔑 Password: ${clientAuthData.password}

You can log in here anytime to add, edit, or delete items from your digital menu!`;

                document.getElementById('outputCode').value = shareableMessage;
                
                setTimeout(() => {
                    btn.classList.add('hidden');
                    btn.innerHTML = originalText;
                    document.getElementById('clientForm').reset();
                    pendingClientCode = "";
                }, 4000);
            } else {
                alert("Server Error: " + result.message);
                btn.innerHTML = originalText;
            }
        } catch (error) {
            console.error("Error:", error);
            alert("Failed to connect to save_client.php.");
            btn.innerHTML = originalText;
        }
    }

    function copyCode() {
        const codeBox = document.getElementById('outputCode');
        if (!codeBox.value) return;
        codeBox.select();
        document.execCommand('copy');
        const btn = document.getElementById('copyBtn');
        const originalText = btn.innerHTML;
        btn.innerHTML = `<span class="text-sky-400">Copied!</span>`;
        setTimeout(() => { btn.innerHTML = originalText; }, 2000);
    }

    function analyzeSheets() {
        const menuId = document.getElementById('menuSheetId').value.trim();
        const adId = document.getElementById('adSheetId').value.trim();
        const resultsBox = document.getElementById('analyzer-results');
        
        const itemsPerSlide = parseInt(document.getElementById('itemsPerSlide').value) || 4;

        if (!menuId || !adId) {
            resultsBox.innerHTML = `<span class="text-red-400 font-bold">⚠️ Error: Please paste both Google Sheet IDs first!</span>`;
            return;
        }

        resultsBox.innerHTML = `<div class="flex items-center gap-2 text-sky-400 animate-pulse"><div class="w-4 h-4 border-2 border-sky-400 border-t-transparent rounded-full animate-spin"></div>Pinging Google Servers...</div>`;

        const menuUrl = `https://docs.google.com/spreadsheets/d/${menuId}/export?format=csv&gid=0&t=${Date.now()}`;
        const adUrl = `https://docs.google.com/spreadsheets/d/${adId}/export?format=csv&gid=0&t=${Date.now()}`;

        Promise.all([
            fetch(menuUrl).then(r => r.ok ? r.text() : null).catch(() => null),
            fetch(adUrl).then(r => r.ok ? r.text() : null).catch(() => null)
        ])
        .then(([menuCsv, adCsv]) => {
            if (!menuCsv || !adCsv) {
                resultsBox.innerHTML = `<span class="text-red-400 font-bold">⚠️ Connection Failed! Check sharing settings.</span>`;
                return;
            }

            let menuRows = []; let adRows = [];
            Papa.parse(menuCsv, { header: true, skipEmptyLines: true, complete: res => menuRows = res.data });
            Papa.parse(adCsv, { header: true, skipEmptyLines: true, complete: res => adRows = res.data });

            let menuGroups = {}; let totalMenuSlides = 0; let totalLoopSeconds = 0; let totalAds = 0; let totalAdSeconds = 0;

            menuRows.forEach(row => {
                let safeRow = {}; Object.keys(row).forEach(k => safeRow[k.toLowerCase().trim()] = row[k]);
                let cat = safeRow.category ? safeRow.category.trim().toUpperCase() : "OUR MENU";
                if (safeRow.name && safeRow.name.toUpperCase() !== "TICKER" && cat !== "ADVERTISEMENT") {
                    if (!menuGroups[cat]) menuGroups[cat] = []; menuGroups[cat].push(safeRow);
                }
            });

            Object.keys(menuGroups).forEach(cat => {
                const items = menuGroups[cat]; 
                
                const slidesForCategory = Math.ceil(items.length / itemsPerSlide);
                
                totalMenuSlides += slidesForCategory; 
                totalLoopSeconds += (slidesForCategory * 8); 
            });

            adRows.forEach(row => {
                let safeRow = {}; Object.keys(row).forEach(k => safeRow[k.toLowerCase().trim()] = row[k]);
                let cat = safeRow.category ? safeRow.category.trim().toUpperCase() : "";
                if (cat === "ADVERTISEMENT" || cat === "AD") {
                    if (safeRow.image) {
                        totalAds++; let adDuration = safeRow.price ? parseInt(safeRow.price) : 10;
                        totalAdSeconds += adDuration; totalLoopSeconds += adDuration;
                    }
                }
            });

            const playsPerHour = Math.floor(3600 / totalLoopSeconds);
            const mins = Math.floor(totalLoopSeconds / 60); const secs = totalLoopSeconds % 60;

            resultsBox.innerHTML = `
                <div class="grid grid-cols-2 gap-4 text-sm mt-2">
                    <div class="bg-slate-800 p-3 rounded border border-slate-700 flex flex-col items-start relative">
                        <span class="text-slate-400 block text-[10px] uppercase font-bold mb-1 tracking-wider">Menu Breakdown</span>
                        <span class="text-white font-mono text-xl block">${totalMenuSlides} Slides</span>
                        <span class="absolute top-2 right-2 text-[10px] font-bold text-sky-400 bg-sky-400/10 px-2 py-0.5 rounded">At ${itemsPerSlide}/Slide</span>
                    </div>
                    <div class="bg-slate-800 p-3 rounded border border-slate-700">
                        <span class="text-slate-400 block text-[10px] uppercase font-bold mb-1 tracking-wider">Ad Breakdown</span>
                        <span class="text-white font-mono text-xl block">${totalAds} Ads Loading</span>
                    </div>
                </div>
                <div class="mt-4 bg-sky-900/30 border border-sky-500/50 p-4 rounded-xl text-center">
                    <span class="text-sky-200 block text-xs uppercase tracking-widest font-bold mb-1">Total Screen Loop Time</span>
                    <div class="text-3xl font-bold text-sky-400 mb-2">
                        ${mins}m ${secs}s 
                        <span class="text-lg text-sky-200/50 font-normal">(${totalLoopSeconds}s total)</span>
                    </div>
                </div>
            `;
        }).catch(err => { resultsBox.innerHTML = `<span class="text-red-400">Failed to parse data.</span>`; });
    }
</script>
</body>
</html>