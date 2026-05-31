<?php
// Separate the Client session from the Admin session
session_name("obsera_client_session");
session_start();

// --- 1. STRICT CACHE PREVENTION ---
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$error_msg = "";

// Safely determine the exact URL for redirects (Strips out ?logout=true)
$base_url = strtok($_SERVER["REQUEST_URI"], '?');

// --- 2. CRASH PROTECTION FOR AUTH.JSON ---
$authData = [];
if (file_exists('auth.json')) {
    $parsedData = json_decode(file_get_contents('auth.json'), true);
    if (is_array($parsedData)) {
        $authData = $parsedData;
    }
}

// --- 3. BULLETPROOF LOGOUT LOGIC ---
if (isset($_GET['logout']) || strpos($_SERVER['REQUEST_URI'], 'logout=true') !== false) { 
    session_unset();
    session_destroy(); 
    setcookie("obsera_remember", "", time() - 3600, "/");
    header("Location: " . $base_url); 
    exit; 
}

// --- 4. AUTO-LOGIN VIA COOKIE (REMEMBER ME) ---
if (empty($_SESSION['owner_logged_in']) && !empty($_COOKIE['obsera_remember'])) {
    $cookieParts = explode("::", $_COOKIE['obsera_remember']);
    if (count($cookieParts) === 2) {
        $cookieUser = $cookieParts[0];
        $cookieHash = $cookieParts[1];
        if (isset($authData[$cookieUser]) && md5($authData[$cookieUser]['password']) === $cookieHash && !empty($authData[$cookieUser]['menuSheetId'])) {
            $_SESSION['owner_logged_in'] = true;
            $_SESSION['client_id'] = $cookieUser;
            $_SESSION['client_sheet_id'] = $authData[$cookieUser]['menuSheetId'];
            header("Location: " . $base_url);
            exit;
        } else {
            setcookie("obsera_remember", "", time() - 3600, "/");
        }
    }
}

// --- 5. MANUAL LOGIN LOGIC ---
if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($authData)) {
        $error_msg = "System error: Accounts file is missing or broken. Check auth.json!";
    } elseif (isset($authData[$username])) {
        if ($authData[$username]['password'] === $password) {
            if (empty($authData[$username]['menuSheetId'])) {
                $error_msg = "Account error: No Google Sheet connected. Contact Admin.";
            } else {
                $_SESSION['owner_logged_in'] = true;
                $_SESSION['client_id'] = $username;
                $_SESSION['client_sheet_id'] = $authData[$username]['menuSheetId']; 
                setcookie("obsera_remember", $username . "::" . md5($password), time() + (86400 * 30), "/");
                header("Location: " . $base_url); 
                exit;
            }
        } else {
            $error_msg = "Invalid Password.";
        }
    } else {
        $error_msg = "Invalid Username.";
    }
}

// =========================================================
// 🛠️ THE ULTIMATE ENFORCER LOGIN WALL 
// =========================================================
$is_valid_session = !empty($_SESSION['owner_logged_in']) && 
                    !empty($_SESSION['client_id']) && trim($_SESSION['client_id']) !== '' && 
                    !empty($_SESSION['client_sheet_id']) && trim($_SESSION['client_sheet_id']) !== '';

if (!$is_valid_session) {
    session_unset();
    session_destroy(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Menu Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 h-screen flex items-center justify-center p-4">
    <div class="bg-slate-800 border border-slate-700 p-8 rounded-2xl shadow-2xl max-w-md w-full text-center">
        <h1 class="text-2xl font-bold text-white mb-2">Menu Manager</h1>
        <p class="text-slate-400 text-sm mb-6">Enter your credentials to manage your live menu.</p>
        
        <?php if($error_msg): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-400 text-sm py-2 rounded mb-4"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-4">
            <input type="text" name="username" required autofocus placeholder="Username" class="w-full bg-slate-900 border border-slate-600 rounded-lg px-4 py-3 text-white text-center">
            <input type="password" name="password" required placeholder="Password" class="w-full bg-slate-900 border border-slate-600 rounded-lg px-4 py-3 text-white text-center">
            <button type="submit" class="w-full bg-sky-500 hover:bg-sky-400 text-slate-900 font-bold py-3 rounded-lg uppercase tracking-wide mt-2">Login to Dashboard</button>
        </form>
    </div>
</body>
</html>
<?php 
    exit; 
} 
// =========================================================
// 🚀 END LOGIN WALL - START DASHBOARD
// =========================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Live Menu Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style> body { background-color: #0f172a; color: white; font-family: 'Roboto', sans-serif; } </style>
</head>
<body class="p-4 md:p-10 min-h-screen">

    <script>
        const IMGBB_API_KEY = "6aa95dc0f064beb2af336e93b2833e13"; 
        const GOOGLE_SCRIPT_URL = "https://script.google.com/macros/s/AKfycbxfzUzSnBbbIfCOCPFlIDxcKRjLXOSPeihIu1MvXuigbndMjslYCtDO_qat5mjpI5oA/exec"; 
        
        const CLIENT_ID = "<?php echo $_SESSION['client_id']; ?>";
        const CLIENT_SHEET_ID = "<?php echo $_SESSION['client_sheet_id']; ?>";
        
        const MOBILE_MENU_URL = `https://obserasolutions.site/mobile?client=${CLIENT_ID}`;
        const QR_CODE_IMAGE_URL = `https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=${encodeURIComponent(MOBILE_MENU_URL)}`;
        
        // State variables for editing
        let isEditing = false;
        let editRowNumber = null;
        let editOriginalImageUrl = null;
    </script>

    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-6 md:mb-8 border-b border-slate-700 pb-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-white flex items-center gap-3 font-['Oswald']">Menu Manager</h1>
                <p class="text-slate-400 text-xs mt-1">Logged in as: <span class="text-sky-400 font-bold"><?php echo $_SESSION['client_id']; ?></span></p>
            </div>
            <a href="?logout=true" class="bg-slate-800 hover:bg-red-500/20 text-slate-400 hover:text-red-400 px-3 py-1.5 md:px-4 md:py-2 rounded-lg text-xs md:text-sm transition-all border border-slate-700">Logout</a>
        </div>

        <div class="flex flex-col md:grid md:grid-cols-3 gap-6 md:gap-8">
            
            <div class="w-full md:col-span-1 flex flex-col gap-6 md:sticky md:top-6">
                
                <div class="bg-slate-800 border border-slate-700 p-5 md:p-6 rounded-2xl shadow-xl" id="form-container">
                    <h2 id="form-title" class="text-xl font-bold text-sky-400 mb-4">Add New Item</h2>
                    
                    <button type="button" id="cancelEditBtn" onclick="cancelEdit()" class="hidden mb-4 text-xs bg-slate-700 hover:bg-slate-600 text-white px-3 py-1 rounded">← Cancel Edit</button>

                    <form id="addItemForm" class="space-y-3 md:space-y-4" onsubmit="handleFormSubmit(event)">
                        <div>
                            <label class="text-xs font-bold text-slate-400 uppercase">Category</label>
                            <input type="text" id="cat" required placeholder="e.g., Snacks, Biriyani" class="w-full mt-1 bg-slate-900 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-400 uppercase">Item Name</label>
                            <input type="text" id="name" required placeholder="e.g., Chicken Roll" class="w-full mt-1 bg-slate-900 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-400 uppercase">Price</label>
                            <input type="text" id="price" placeholder="e.g., ₹120" class="w-full mt-1 bg-slate-900 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-400 uppercase">Description</label>
                            <textarea id="desc" rows="2" placeholder="Spiced to perfection..." class="w-full mt-1 bg-slate-900 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm focus:border-sky-500 focus:outline-none"></textarea>
                        </div>
                        <div>
                            <label id="image-label" class="text-xs font-bold text-slate-400 uppercase">Upload Image</label>
                            <input type="file" id="imageFile" accept="image/*" required class="w-full mt-1 text-sm text-slate-400 file:mr-2 file:py-2 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-sky-500/10 file:text-sky-400 hover:file:bg-sky-500/20 focus:outline-none">
                            <p id="image-help-text" class="text-[10px] text-slate-500 mt-1 hidden">Leave blank to keep existing image.</p>
                        </div>
                        <button type="submit" id="submitBtn" class="w-full bg-sky-500 hover:bg-sky-400 text-slate-900 font-bold py-3 rounded-lg transition-colors mt-4 text-sm md:text-base">
                            Add to Menu
                        </button>
                    </form>
                </div>

                <div class="bg-slate-800 border border-slate-700 p-5 md:p-6 rounded-2xl shadow-xl text-center flex flex-col items-center">
                    <h2 class="text-lg font-bold text-white mb-1">Your Digital Menu</h2>
                    <p class="text-slate-400 text-xs mb-4">Print this for your tables so customers can scan and view your menu.</p>
                    <div class="bg-white p-2 rounded-lg inline-block mb-4 shadow-inner">
                        <script>document.write(`<img src="${QR_CODE_IMAGE_URL}" alt="Menu QR Code" class="w-32 h-32 md:w-40 md:h-40">`);</script>
                    </div>
                    <button onclick="printTableTent()" class="w-full bg-slate-700 hover:bg-slate-600 text-white font-bold py-3 rounded-lg transition-colors flex items-center justify-center gap-2 text-sm md:text-base border border-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Print QR Code
                    </button>
                </div>

            </div>

            <div class="w-full md:col-span-2">
                <div class="bg-slate-800 border border-slate-700 p-5 md:p-6 rounded-2xl shadow-xl min-h-full">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-white">Current Menu</h2>
                        <button onclick="loadMenu()" class="text-xs md:text-sm text-sky-400 hover:underline">Refresh List</button>
                    </div>
                    <div id="menuGrid" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <p class="text-slate-400 text-sm">Loading menu...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function printTableTent() {
            const printWin = window.open('', '_blank');
            const printContent = `
                <!DOCTYPE html>
                <html>
                    <head>
                        <title>Print Menu QR</title>
                        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
                        <style>
                            @page { size: A4 portrait; margin: 0; }
                            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; color-adjust: exact !important; box-sizing: border-box; }
                            body { margin: 0; padding: 20mm; display: flex; justify-content: center; align-items: flex-start; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: white; min-height: 100vh; }
                            .cut-zone { padding: 2mm; border: 1px dashed #94a3b8; background: white; }
                            .card { background: linear-gradient(to bottom, #df3135, #f47216); width: 4in; height: 6in; display: flex; flex-direction: column; align-items: center; color: white; overflow: hidden; position: relative; }
                            .top-icon { margin-top: 0.2in; margin-bottom: 0.05in; width: 28px; height: 28px; background: white; border-radius: 50%; display: flex; justify-content: center; align-items: center; }
                            .top-icon svg { width: 16px; height: 16px; fill: #df3135; }
                            .restaurant-name { font-size: 11px; font-weight: bold; letter-spacing: 1px; margin-bottom: 0.1in; text-transform: uppercase; }
                            .title { font-size: 42px; font-weight: 900; line-height: 0.95; margin-bottom: 0.15in; letter-spacing: -1px; text-align: center; }
                            .scan-pill { background: #b91c1c; border: 2px solid #fef08a; border-radius: 20px; padding: 5px 20px; font-size: 12px; font-weight: bold; margin-bottom: 0.2in; box-shadow: 0 4px 6px rgba(0,0,0,0.2); }
                            .qr-box { background: white; border-radius: 24px; padding: 0.12in; width: 2.1in; height: 2.1in; box-shadow: 0 10px 20px rgba(0,0,0,0.25), inset 0 0 0 2px #fef08a; display: flex; justify-content: center; align-items: center; margin-bottom: 0.2in; }
                            .qr-img { width: 100%; height: 100%; object-fit: contain; }
                            .instructions { display: flex; justify-content: space-between; width: 3.4in; margin-bottom: 0.1in; }
                            .step { flex: 1; display: flex; flex-direction: column; align-items: center; padding: 0 2px; }
                            .step-circle { width: 20px; height: 20px; background: #601014; color: white; border-radius: 50%; font-size: 10px; font-weight: bold; display: flex; justify-content: center; align-items: center; margin-bottom: 5px; }
                            .step-text { font-size: 8px; line-height: 1.3; text-align: center; font-weight: 500; }
                            .footer { margin-top: auto; margin-bottom: 0.15in; font-size: 10px; color: white; font-weight: 500; letter-spacing: 0.5px; }
                            .footer strong { color: black; font-weight: 900; }
                        </style>
                    </head>
                    <body onload="setTimeout(function(){ window.print(); }, 500);">
                        <div class="cut-zone">
                            <div class="card">
                                <div class="top-icon">
                                    <svg viewBox="0 0 24 24"><path d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm10-5h-3v18h3V4zm-4 0H15c-1.1 0-2 .9-2 2v6h4V4z"/></svg>
                                </div>
                                <div class="restaurant-name">"${CLIENT_ID}"</div>
                                <div class="title">VIEW OUR<br>MENU</div>
                                <div class="scan-pill">Scan the QR Code</div>
                                <div class="qr-box">
                                    <img src="${QR_CODE_IMAGE_URL}" class="qr-img" />
                                </div>
                                <div class="instructions">
                                    <div class="step">
                                        <div class="step-circle">1</div>
                                        <div class="step-text">Open your phone's<br>camera app.</div>
                                    </div>
                                    <div class="step">
                                        <div class="step-circle">2</div>
                                        <div class="step-text">Point it at the<br>QR code</div>
                                    </div>
                                    <div class="step">
                                        <div class="step-circle">3</div>
                                        <div class="step-text">Tap the notification<br>to view our menu</div>
                                    </div>
                                </div>
                                <div class="footer">Powered by <strong>OBSERA SOLUTIONS</strong></div>
                            </div>
                        </div>
                    </body>
                </html>
            `;
            printWin.document.write(printContent);
            printWin.document.close();
        }

// 1. LOAD MENU
        async function loadMenu() {
            const grid = document.getElementById('menuGrid');
            grid.innerHTML = '<p class="text-slate-400 text-sm animate-pulse">Communicating with Google...</p>';
            
            try {
                const response = await fetch(`${GOOGLE_SCRIPT_URL}?sheetId=${CLIENT_SHEET_ID}`);
                const textResponse = await response.text(); 
                
                let items;
                try {
                    items = JSON.parse(textResponse);
                } catch (parseError) {
                    console.error("Google returned non-JSON data:", textResponse);
                    grid.innerHTML = `<p class="text-red-400 text-sm font-bold">Deploy Error: Google blocked the request.</p>`;
                    return;
                }

                if (items.error) {
                    grid.innerHTML = `<p class="text-amber-400 text-sm font-bold">Google Sheet Error: ${items.error}</p>`;
                    return;
                }
                
                if (!Array.isArray(items) || items.length === 0) {
                    grid.innerHTML = '<p class="text-slate-500 text-sm">No items found in the menu.</p>';
                    return;
                }

                let html = '';
                items.forEach(item => {
                    // 🛠️ THE FIX: Convert everything to a String() first so numbers don't crash the code!
                    const safeName = item.name != null ? String(item.name).replace(/'/g, "\\'").replace(/"/g, "&quot;") : '';
                    const safeCat = item.category != null ? String(item.category).replace(/'/g, "\\'").replace(/"/g, "&quot;") : '';
                    const safeDesc = item.desc != null ? String(item.desc).replace(/'/g, "\\'").replace(/"/g, "&quot;").replace(/\n/g, "\\n").replace(/\r/g, "") : '';
                    const safePrice = item.price != null ? String(item.price).replace(/'/g, "\\'").replace(/"/g, "&quot;") : '';
                    const safeImg = item.image != null ? String(item.image).replace(/'/g, "\\'").replace(/"/g, "&quot;") : '';

                    html += `
                        <div class="bg-slate-900 border border-slate-700 rounded-xl overflow-hidden flex relative group">
                            <div class="w-1/3 bg-slate-800 shrink-0 relative">
                                <img src="${item.image}" class="w-full h-full object-cover absolute inset-0" onerror="this.src='https://placehold.co/300x300/1f2937/FFF?text=No+Image'">
                            </div>
                            <div class="w-2/3 p-3 md:p-4 flex flex-col justify-center overflow-hidden">
                                <span class="text-[10px] font-bold text-sky-400 uppercase tracking-widest mb-1 break-words">${item.category}</span>
                                <h3 class="text-base md:text-lg font-bold text-white leading-tight font-['Oswald'] break-words">${item.name}</h3>
                                <span class="text-xs font-bold text-slate-300 mt-0.5">${item.price || ''}</span>
                                <p class="text-slate-500 text-xs mt-1 line-clamp-2 break-words">${item.desc}</p>
                            </div>
                            
                            <div class="absolute top-2 right-2 flex flex-col gap-1 opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity">
                                <button onclick="setupEdit(${item.row}, '${safeCat}', '${safeName}', '${safePrice}', '${safeDesc}', '${safeImg}')" class="bg-sky-500/80 md:bg-sky-500/20 text-white md:text-sky-400 p-1.5 md:p-2 rounded hover:bg-sky-500 hover:text-white transition-colors shadow-md md:shadow-none">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </button>
                                <button onclick="deleteItem(${item.row})" class="bg-red-500/80 md:bg-red-500/20 text-white md:text-red-400 p-1.5 md:p-2 rounded hover:bg-red-500 hover:text-white transition-colors shadow-md md:shadow-none">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                    `;
                });
                grid.innerHTML = html;
            } catch (error) {
                console.error("Caught a JS Error:", error);
                grid.innerHTML = '<p class="text-red-400 text-sm">Critical Fetch Error. Check console.</p>';
            }
        }

        function setupEdit(row, cat, name, price, desc, img) {
            isEditing = true;
            editRowNumber = row;
            editOriginalImageUrl = img;

            document.getElementById('cat').value = cat;
            document.getElementById('name').value = name;
            document.getElementById('price').value = price;
            document.getElementById('desc').value = desc.replace(/\\n/g, "\n");

            document.getElementById('imageFile').removeAttribute('required');
            document.getElementById('image-label').innerText = "Upload New Image (Optional)";
            document.getElementById('image-help-text').classList.remove('hidden');

            document.getElementById('form-title').innerText = "Edit Item";
            document.getElementById('form-title').classList.replace('text-sky-400', 'text-amber-400');
            const btn = document.getElementById('submitBtn');
            btn.innerText = "Update Item";
            btn.classList.replace('bg-sky-500', 'bg-amber-500');
            btn.classList.replace('hover:bg-sky-400', 'hover:bg-amber-400');
            document.getElementById('cancelEditBtn').classList.remove('hidden');

            document.getElementById('form-container').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function cancelEdit() {
            isEditing = false;
            editRowNumber = null;
            editOriginalImageUrl = null;
            
            document.getElementById('addItemForm').reset();
            
            document.getElementById('imageFile').setAttribute('required', 'true');
            document.getElementById('image-label').innerText = "Upload Image";
            document.getElementById('image-help-text').classList.add('hidden');

            document.getElementById('form-title').innerText = "Add New Item";
            document.getElementById('form-title').classList.replace('text-amber-400', 'text-sky-400');
            const btn = document.getElementById('submitBtn');
            btn.innerText = "Add to Menu";
            btn.classList.replace('bg-amber-500', 'bg-sky-500');
            btn.classList.replace('hover:bg-amber-400', 'hover:bg-sky-400');
            document.getElementById('cancelEditBtn').classList.add('hidden');
        }

        async function handleFormSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const originalText = btn.innerText;
            btn.innerText = "Processing..."; btn.disabled = true;

            try {
                let finalImageUrl = editOriginalImageUrl; 

                const fileInput = document.getElementById('imageFile');
                if (fileInput.files.length > 0) {
                    btn.innerText = "Uploading Image...";
                    const imgData = new FormData();
                    imgData.append('image', fileInput.files[0]);
                    const imgRes = await fetch(`https://api.imgbb.com/1/upload?key=${IMGBB_API_KEY}`, { method: 'POST', body: imgData });
                    const imgJson = await imgRes.json();
                    if (!imgJson.success) throw new Error("Image upload failed");
                    finalImageUrl = imgJson.data.url;
                }

                btn.innerText = "Saving to Database...";
                
                const sheetData = new URLSearchParams();
                sheetData.append('action', isEditing ? 'edit' : 'add');
                sheetData.append('sheetId', CLIENT_SHEET_ID); 
                sheetData.append('category', document.getElementById('cat').value);
                sheetData.append('name', document.getElementById('name').value);
                sheetData.append('price', document.getElementById('price').value);
                sheetData.append('description', document.getElementById('desc').value);
                sheetData.append('image', finalImageUrl);

                if (isEditing) {
                    sheetData.append('row', editRowNumber);
                }

                await fetch(GOOGLE_SCRIPT_URL, { method: 'POST', body: sheetData, mode: 'no-cors' });

                btn.innerText = "Success! ✓";
                btn.classList.add('bg-emerald-500');
                
                setTimeout(() => {
                    cancelEdit(); 
                    btn.disabled = false;
                }, 2000);
                
                setTimeout(loadMenu, 1000);
            } catch (error) {
                alert("Something went wrong!"); btn.innerText = originalText; btn.disabled = false;
            }
        }

        async function deleteItem(rowNumber) {
            if(!confirm("Are you sure you want to delete this item?")) return;
            try {
                const sheetData = new URLSearchParams();
                sheetData.append('action', 'delete');
                sheetData.append('sheetId', CLIENT_SHEET_ID); 
                sheetData.append('row', rowNumber);

                await fetch(GOOGLE_SCRIPT_URL, { method: 'POST', body: sheetData, mode: 'no-cors' });
                setTimeout(loadMenu, 1000);
            } catch (error) {
                alert("Failed to delete item.");
            }
        }

        loadMenu();
    </script>
</body>
</html>