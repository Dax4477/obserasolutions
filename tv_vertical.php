<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" /> 
    
    <title>Obsera Display Engine - Vertical</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.0/papaparse.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">

    <script src="clients.js"></script>

    <style>
        @media (min-width: 600px) {
            body::-webkit-scrollbar { display: none; }
            body { -ms-overflow-style: none; scrollbar-width: none; } 
        }
        .font-heading { font-family: 'Oswald', sans-serif; }
        .font-body { font-family: 'Roboto', sans-serif; }
        .fade-in { animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* 🛠️ FIX 1: Strict Canvas Forcing for Portrait (1080x1920) */
        #obsera-canvas {
            min-width: 1080px !important;
            min-height: 1920px !important;
            max-width: 1080px !important;
            max-height: 1920px !important;
            width: 1080px !important;
            height: 1920px !important;
            transform-origin: center;
        }

        .smooth-scroll {
            display: inline-block; 
            white-space: nowrap; 
            will-change: transform;
            animation-name: scroll-left; 
            animation-timing-function: linear;
            animation-iteration-count: infinite;
        }

        /* 🛠️ FIX 2: Ticker animation starts at 1080px wide now */
        @keyframes scroll-left {
            from { transform: translateX(1080px); } 
            to { transform: translateX(-100%); } 
        }
    </style>
</head>

<body class="bg-black text-white m-0 p-0 overflow-hidden flex items-center justify-center h-screen w-screen font-body">

    <div id="obsera-canvas" class="bg-gray-900 relative overflow-hidden shadow-2xl block">

        <header class="absolute top-0 left-0 w-full h-[8%] bg-blue-800 px-6 shadow-lg z-40 flex items-center justify-between transition-transform duration-700 ease-in-out" id="main-header">        
            <div class="text-left w-1/4">
                <h1 id="restaurant-name-display" class="text-3xl font-heading font-bold uppercase tracking-wider leading-tight truncate">LOADING...</h1>
                <p class="text-red-200 text-sm font-bold">Powered by Obsera</p>
            </div>
            
            <div class="text-center w-2/4">
                 <h2 id="menu-title" class="text-6xl font-heading font-bold text-white uppercase truncate px-2 drop-shadow-md">INITIALIZING...</h2>
            </div>

            <div class="text-right w-1/4">
                <div id="clock" class="text-4xl font-mono font-bold text-white bg-red-900/40 px-4 py-2 rounded-lg border border-red-700/50 inline-block whitespace-nowrap shadow-inner">00:00 PM</div>
            </div>
        </header>

        <main class="absolute top-[8%] left-0 w-full h-[84%] px-8 py-8 z-10 overflow-hidden" id="main-content">
            <div id="menu-container" class="w-full h-full uppercase relative">
                <div class="flex flex-col items-center justify-center h-full space-y-4">
                    <div class="animate-spin rounded-full h-20 w-20 border-t-8 border-b-8 border-blue-500"></div>
                    <p class="text-white text-3xl uppercase tracking-widest font-bold mt-4">Connecting to Obsera Network...</p>
                </div>
            </div>
        </main>

        <footer class="absolute bottom-0 left-0 w-full h-[8%] bg-black z-40 border-t-4 border-blue-800 flex items-center overflow-hidden transition-transform duration-700 ease-in-out" id="main-footer">
            <div id="ticker-text" class="smooth-scroll text-6xl text-yellow-400 font-bold leading-none w-max min-w-full shrink-0 tracking-wide mt-1">
                ★ CONTACT OBSERA SOLUTIONS ★ WHATSAPP- +91 9562551376
            </div>
            
            <div class="absolute right-0 bottom-0 bg-gray-900/90 px-3 py-2 rounded-tl-xl border-t border-l border-gray-700 z-50 shadow-lg">
                <img src="https://github.com/Dax4477/live-menu/blob/main/Gemini_Generated_Image_jejmw3jejmw3jejm%20(3).png?raw=true" 
                     class="h-10 w-auto object-contain opacity-90" 
                     alt="Obsera Logo">
            </div>
        </footer>

    </div>

<script>
    // --- 🛠️ FIX 5: CANVAS SCALING LOGIC FOR PORTRAIT ---
    function resizeCanvas() {
        const canvas = document.getElementById('obsera-canvas');
        const scale = Math.min(window.innerWidth / 1080, window.innerHeight / 1920);
        canvas.style.transform = `scale(${scale})`;
    }
    window.addEventListener('resize', resizeCanvas);
    resizeCanvas(); 
    // ----------------------------

    const urlParams = new URLSearchParams(window.location.search);
    const clientId = urlParams.get('client') || 'uruvachal'; 

    const clientData = OBSERA_CLIENTS[clientId];

    if (!clientData) {
        document.body.innerHTML = `
            <div style="display:flex; height:100vh; background:#0f172a; align-items:center; justify-content:center; flex-direction:column; text-align:center;">
                <h1 style="color:#ef4444; font-size:3rem; font-family:sans-serif; font-weight:bold; margin-bottom:1rem;">ERROR: LOCATION NOT FOUND</h1>
                <p style="color:#94a3b8; font-size:1.5rem; font-family:sans-serif;">The Screen ID "${clientId}" is not registered in the Obsera Network.</p>
            </div>`;
        throw new Error("Client ID does not exist in clients.js");
    }

    document.getElementById('restaurant-name-display').innerText = clientData.name;

    const MENU_SHEET_URL = `https://docs.google.com/spreadsheets/d/${clientData.menuSheetId}/export?format=csv&gid=0`;
    const AD_SHEET_URL = `https://docs.google.com/spreadsheets/d/${clientData.adSheetId}/export?format=csv&gid=0`;

    const ITEMS_PER_SLIDE = parseInt(clientData.itemsPerSlide) || 4;       
    const DEFAULT_MENU_DURATION = 8000; 
    const CHECK_INTERVAL = 10000;    

    let slideDeck = [];                
    let currentSlideIndex = 0;       
    let slideTimer = null;           
    let isDataChanged = false;       
    
    let isAudioEnabled = false;
    document.addEventListener('click', function() {
        if (!isAudioEnabled) {
            isAudioEnabled = true;
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            audioCtx.resume();
        }
    }, { once: true }); 

    function isMobile() { return window.innerWidth < 600; }

    function fetchMenuData() {
        const uniqueMenuUrl = MENU_SHEET_URL + '&t=' + Date.now() + Math.random();
        const uniqueAdUrl = AD_SHEET_URL + '&t=' + Date.now() + Math.random();

        Promise.all([
            fetch(uniqueMenuUrl, { cache: "no-store" }).then(r => r.ok ? r.text() : null).catch(() => null),
            fetch(uniqueAdUrl, { cache: "no-store" }).then(r => r.ok ? r.text() : null).catch(() => null)
        ])
        .then(([menuCsv, adCsv]) => {
            let combinedData = { menuRows: [], adRows: [] };

            if (menuCsv) {
                Papa.parse(menuCsv, {
                    header: true, skipEmptyLines: true,
                    complete: function(results) { combinedData.menuRows = results.data; }
                });
            }

            if (adCsv) {
                Papa.parse(adCsv, {
                    header: true, skipEmptyLines: true,
                    complete: function(results) { combinedData.adRows = results.data; }
                });
            }

            setTimeout(() => { processData(combinedData); }, 100);
        })
        .catch(e => console.error("Error fetching sheets:", e));
    }

    function processData(combinedData) {
        let menuGroups = {};
        let adList = [];
        let tickerMessage = "★ FOR ADVERTISING THIS SCREEN ★ CONTACT OBSERA SOLUTIONS ★ obserasolutions.site ★ WHATSAPP- +91 9562551376";

        combinedData.menuRows.forEach(row => {
            let safeRow = {};
            Object.keys(row).forEach(k => safeRow[k.toLowerCase().trim()] = row[k]);

            if (safeRow.name && safeRow.name.toUpperCase() === "TICKER") {
                let msg = safeRow.desc || safeRow.description || "";
                if (msg.length > 0) tickerMessage = msg;
                return; 
            }

            let cat = safeRow.category ? safeRow.category.trim().toUpperCase() : "OUR MENU";
            
            if (safeRow.name && cat !== "ADVERTISEMENT") {
                if (!menuGroups[cat]) menuGroups[cat] = [];
                menuGroups[cat].push({
                    name: safeRow.name,
                    price: safeRow.price,
                    desc: safeRow.desc || safeRow.description || "",
                    image: safeRow.image
                });
            }
        });

        combinedData.adRows.forEach(row => {
            let safeRow = {};
            Object.keys(row).forEach(k => safeRow[k.toLowerCase().trim()] = row[k]);
            
            let cat = safeRow.category ? safeRow.category.trim().toUpperCase() : "";
            
            if (cat === "ADVERTISEMENT" || cat === "AD") {
                if (safeRow.image) {
                    const isVideo = safeRow.image.match(/\.(mp4|webm|ogg)$/i);
                    const desc = (safeRow.desc || safeRow.description || "").toUpperCase();
                    const wantsSound = desc.includes("SOUND");
                    let pos = parseInt(safeRow.position);

                    adList.push({
                        type: isVideo ? 'video' : 'image',
                        image: safeRow.image,
                        duration: safeRow.price ? parseInt(safeRow.price) * 1000 : 10000,
                        hasAudio: wantsSound,
                        position: isNaN(pos) ? null : pos
                    });
                }
            }
        });

        const tickerElement = document.getElementById('ticker-text');
        if (tickerElement) {
             tickerElement.innerText = tickerMessage;
             setTimeout(adjustTickerSpeed, 300); 
        }

        let newDeck = [];
        let currentCategoryNumber = 1;
        const categories = Object.keys(menuGroups);
        
        categories.forEach((categoryName) => {
            const items = menuGroups[categoryName];
            const totalPages = Math.ceil(items.length / ITEMS_PER_SLIDE);
            
            for(let i=0; i < totalPages; i++) {
                const start = i * ITEMS_PER_SLIDE;
                const end = start + ITEMS_PER_SLIDE;
                newDeck.push({
                    type: 'menu',
                    title: categoryName, 
                    items: items.slice(start, end),
                    duration: DEFAULT_MENU_DURATION
                });
            }

            const adsForThisPosition = adList.filter(ad => ad.position === currentCategoryNumber);
            
            adsForThisPosition.forEach(adToShow => {
                newDeck.push({
                    type: 'ad',
                    mediaType: adToShow.type,
                    title: 'ADVERTISEMENT',
                    image: adToShow.image,
                    duration: adToShow.duration,
                    hasAudio: adToShow.hasAudio 
                });
            });

            currentCategoryNumber++;
        });

        const unplacedAds = adList.filter(ad => !ad.position || ad.position > categories.length);
        unplacedAds.forEach(adToShow => {
            newDeck.push({
                type: 'ad',
                mediaType: adToShow.type,
                title: 'ADVERTISEMENT',
                image: adToShow.image,
                duration: adToShow.duration,
                hasAudio: adToShow.hasAudio 
            });
        });

        if (categories.length === 0 && adList.length > 0) {
             adList.forEach(ad => {
                 newDeck.push({ 
                    type: 'ad', 
                    mediaType: ad.type,
                    image: ad.image, 
                    duration: ad.duration,
                    hasAudio: ad.hasAudio
                });
            });
        }

        if (slideDeck.length === 0) {
            slideDeck = newDeck;
            startCorrectView();
        } else {
            if (JSON.stringify(slideDeck) !== JSON.stringify(newDeck)) {
                slideDeck = newDeck;
                if(isMobile()) renderMobileView(); 
            }
        }
    }

    function startCorrectView() {
        if(isMobile()) renderMobileView();
        else if(!slideTimer) initSlideshow();
    }

    function renderMobileView() {
        if(slideTimer) clearTimeout(slideTimer);
        const container = document.getElementById('menu-container');
        const titleElement = document.getElementById('menu-title');
        
        container.className = "flex flex-col gap-4 overflow-y-auto pb-20 h-full w-full";
        container.innerHTML = '';
        titleElement.innerText = "FULL MENU"; 

        if (slideDeck.length === 0) return;

        slideDeck.forEach(page => {
            if(page.type === 'ad') return; 

            page.items.forEach(item => {
                const fallbackImage = 'https://placehold.co/600x400/333/FFF?text=No+Image';
                const nameText = item.name.toUpperCase();
                const descText = item.desc || "";

                let nameSize = "text-lg";
                if (nameText.length > 25) nameSize = "text-base";
                if (nameText.length > 40) nameSize = "text-sm";

                let descSize = "text-sm";
                if (descText.length > 60) descSize = "text-xs";

                container.innerHTML += `
                    <div class="flex bg-gray-800 rounded-xl overflow-hidden border border-gray-700 shadow-md min-h-[120px] fade-in relative shrink-0">
                        <span class="absolute top-0 left-0 bg-red-600 text-white text-[10px] font-bold px-2 py-1 z-10 rounded-br-lg shadow-sm">${page.title}</span>
                        <div class="w-1/3 bg-gray-700 relative shrink-0 min-h-0">
                            <img src="${item.image}" class="h-full w-full object-cover absolute inset-0" onerror="this.src='${fallbackImage}'">
                        </div>
                        <div class="w-2/3 p-3 flex flex-col justify-center overflow-hidden min-h-0">
                            <div class="flex justify-between items-start mb-1 gap-2 mt-4">
                                <h3 class="${nameSize} font-bold text-yellow-500 font-heading leading-tight line-clamp-2" style="word-break: break-word;">${nameText}</h3>
                            </div>
                            <p class="text-gray-300 ${descSize} leading-snug line-clamp-3" style="word-break: break-word;">${descText}</p>
                        </div>
                    </div>`;
            });
        });
    }

    function initSlideshow() {
        currentSlideIndex = 0;
        showSlide(0); 
    }

    function nextSlide() {
        currentSlideIndex++;
        if (currentSlideIndex >= slideDeck.length) {
            window.location.reload(true);
            return; 
        } 
        showSlide(currentSlideIndex);
    }

    function showSlide(index) {
        if(!slideDeck[index]) return;

        const slide = slideDeck[index];
        const container = document.getElementById('menu-container');
        const titleElement = document.getElementById('menu-title');
        const header = document.getElementById('main-header');
        const footer = document.getElementById('main-footer');

        if(slideTimer) clearTimeout(slideTimer);
        container.innerHTML = ''; 

        if (slide.type === 'ad') {
            header.style.transform = "translateY(-100%)"; 
            footer.style.transform = "translateY(100%)";
            container.className = "absolute inset-0 w-full h-full bg-black flex items-center justify-center z-50 fade-in";
            
            if (slide.mediaType === 'video') {
                const shouldPlaySound = isAudioEnabled && slide.hasAudio;
                container.innerHTML = `<video id="ad-video-player" src="${slide.image}" class="w-full h-full object-contain" autoplay playsinline muted></video>`;
                
                const videoElement = document.getElementById('ad-video-player');
                if (shouldPlaySound) videoElement.muted = false;

                const playPromise = videoElement.play();
                if (playPromise !== undefined) {
                    playPromise.catch(() => {
                        videoElement.muted = true;
                        videoElement.play();
                    });
                }

                videoElement.onended = function() { nextSlide(); };
                videoElement.onerror = function() { nextSlide(); };
                return; 
            } else {
                container.innerHTML = `<img src="${slide.image}" class="w-full h-full object-contain" alt="Advertisement">`;
                slideTimer = setTimeout(nextSlide, slide.duration);
                return;
            }
        } 
        else {
            header.style.transform = "translateY(0)"; 
            footer.style.transform = "translateY(0)";
            titleElement.innerText = slide.title;

            // 🛠️ FIX 6: NEW GRID LOGIC OPTIMIZED FOR VERTICAL STACKING
            const actualItemCount = slide.items.length;
            let gridLayout = ""; 
            let gapClass = "gap-6";
            
            if (actualItemCount <= 3) { gridLayout = "grid-cols-1 grid-rows-3"; gapClass = "gap-10"; }
            else if (actualItemCount <= 4) { gridLayout = "grid-cols-1 grid-rows-4"; gapClass = "gap-8"; }
            else if (actualItemCount <= 6) { gridLayout = "grid-cols-1 grid-rows-6"; gapClass = "gap-6"; }
            else if (actualItemCount <= 8) { gridLayout = "grid-cols-1 grid-rows-8"; gapClass = "gap-4"; }
            else { gridLayout = "grid-cols-1 grid-rows-5"; gapClass = "gap-3"; } 

            container.className = `grid ${gridLayout} ${gapClass} h-full w-full relative z-0`;

            slide.items.forEach(item => {
                const fallbackImage = 'https://placehold.co/600x400/333/FFF?text=No+Image';
                const nameText = item.name.toUpperCase();
                const descText = item.desc || "";

                let nameSize = "";
                let descSize = "";

                // Adjust text sizes based on whether it's a 1-column or 2-column layout
                if (actualItemCount > 4) {
                    // 2 columns (narrower cards)
                    if (nameText.length < 12) nameSize = "text-7xl"; 
                    else if (nameText.length < 22) nameSize = "text-4xl"; 
                    else nameSize = "text-3xl"; 
                    
                    descSize = "text-2xl"; 
                } 
                else {
                    // 1 column (wider cards)
                    if (nameText.length < 12) nameSize = "text-7xl";
                    else if (nameText.length < 22) nameSize = "text-6xl";
                    else nameSize = "text-5xl";
                    
                    descSize = "text-4xl";
                }

                container.innerHTML += `
                    <div class="flex bg-gray-800 rounded-xl overflow-hidden border-2 border-gray-700 shadow-xl fade-in h-full w-full min-h-0">
                        <div class="w-5/12 bg-gray-700 relative shrink-0 min-h-0">
                            <img src="${item.image}" class="h-full w-full object-cover absolute inset-0" onerror="this.src='${fallbackImage}'">
                        </div>
                        <div class="w-7/12 p-6 flex flex-col justify-center overflow-hidden min-h-0">
                            <div class="flex justify-between items-start mb-2 gap-4">
                                <h3 class="${nameSize} font-bold text-yellow-500 font-heading leading-tight line-clamp-2" style="word-break: break-word;">${nameText}</h3>
                            </div>
                            <p class="text-gray-300 ${descSize} leading-snug line-clamp-3 mt-2" style="word-break: break-word;">${descText}</p>
                        </div>
                    </div>`;
            });
            
            let nextDuration = slide.duration || 8000;
            slideTimer = setTimeout(nextSlide, nextDuration);
        }
    }

    function updateClock() {
        document.getElementById('clock').innerText = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' ,second: "2-digit"});
    }
    
    function adjustTickerSpeed() {
        const ticker = document.getElementById('ticker-text');
        if (!ticker) return;
        const textWidth = ticker.offsetWidth;
        // 🛠️ FIX 7: Ticker speed calculation updated for 1080 width
        const duration = (1080 + textWidth) / 150; 
        ticker.style.animationDuration = `${duration}s`;
    }

    fetchMenuData(); 
    setInterval(updateClock, 1000); 
    setInterval(fetchMenuData, CHECK_INTERVAL); 

    setTimeout(() => {
        if(!isMobile() && !slideTimer && slideDeck.length > 0) initSlideshow();
    }, 3000);

</script>
</body>
</html>