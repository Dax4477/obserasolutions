<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obsera Interactive TV - Vertical</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="master.css">
    <script src="theme.js"></script>
    <script src="https://www.youtube.com/iframe_api"></script>
    <style>
        /* Hide all scrollbars globally */
        ::-webkit-scrollbar { display: none; }
        * { -ms-overflow-style: none; scrollbar-width: none; }

        .rank-1 { border-color: #fbbf24; box-shadow: 0 0 20px rgba(251, 191, 36, 0.4); }
        @keyframes flash { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
        .next-play-badge { animation: flash 1.5s infinite; }
        
        /* Pure black background, overflow hidden */
        body { margin: 0; padding: 0; overflow: hidden; background-color: #000000; width: 100vw; height: 100vh; }
        
        /* The Master Canvas - VERTICAL (1080x1920) */
        #signage-canvas {
            width: 1080px;
            height: 1920px;
            transform-origin: top left;
            position: absolute;
            top: 0;
            left: 0;
            background-color: #000000;
        }
    </style>
</head>
<body>

    <div id="signage-canvas" class="text-white flex flex-col">
        
        <div class="w-full h-[60%] flex flex-col p-10 gap-8 relative bg-black shrink-0 border-b-4 border-slate-900">
            
            <div class="flex items-center justify-between shrink-0 z-10 border-b border-white/10 pb-6">
                <div class="flex items-center gap-4">
                    <div class="w-4 h-4 bg-red-600 rounded-full animate-pulse"></div>
                    <h2 class="text-2xl font-display font-bold tracking-[0.2em] uppercase text-slate-300">Live Jukebox</h2>
                </div>
                <div class="flex items-center gap-4">
                    <span id="now-playing-timer" class="text-2xl font-bold font-mono text-slate-400 tracking-widest">0:00</span>
                    <h2 id="now-playing-title" class="text-3xl font-bold text-sky-400 truncate max-w-[400px]">Loading...</h2>
                </div>
            </div>

            <div class="w-full flex items-center justify-center z-10 shrink-0">
               <div class="aspect-video w-full border-4 border-slate-800 relative bg-black shadow-[0_0_50px_rgba(0,0,0,0.8)]">
                    <div id="ytplayer" class="absolute top-0 left-0 w-full h-full pointer-events-none"></div>
               </div>
            </div>
            
            <div class="flex-grow bg-slate-950 border-2 border-slate-800 relative flex items-center justify-center z-10 overflow-hidden rounded-2xl mt-2">
                <img id="tv-ad-image" src="" class="w-full h-full object-cover opacity-90 transition-opacity duration-500">
                <div class="absolute bottom-4 right-6 bg-black/90 px-4 py-2 text-xs text-white/70 uppercase tracking-widest font-bold z-20 border border-white/10 rounded">Advertisement</div>
            </div>
        </div>

        <div class="w-full h-[40%] bg-black flex flex-row relative z-20">
            
            <div class="w-[420px] h-full bg-[#0a0f18] border-r border-white/10 flex flex-col items-center justify-center p-8 shrink-0 relative z-30">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-sky-500 to-purple-500"></div>
                
                <h3 class="text-6xl font-display font-bold text-white mb-2 text-center">Up Next</h3>
                <p class="text-sky-400 font-black uppercase tracking-[0.2em] text-sm mb-10 text-center">Scan QR to Vote</p>
                
                <div class="p-6 bg-white rounded-3xl shadow-[0_0_50px_rgba(56,189,248,0.15)] mb-10">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=https://obserasolutions.site/mobile_vote&color=0f172a&bgcolor=ffffff" 
                         class="w-56 h-56 shrink-0" alt="QR Code">
                </div>
                
                <p class="text-yellow-400 text-4xl font-black leading-tight uppercase tracking-tight text-center drop-shadow-lg">
                    Scan to <br>Take Control!
                </p>
            </div>

            <div class="flex-grow h-full flex flex-col relative bg-black z-10 overflow-hidden">
                
                <div id="next-play-container" class="px-8 pt-8 pb-6 z-30 bg-black border-b border-white/10 shrink-0 relative shadow-2xl"></div>

<div id="scrolling-leaderboard" class="flex-grow overflow-hidden px-8 py-0 relative opacity-60 z-10 bg-black">
                    <div class="text-center py-10"><div class="w-10 h-10 border-4 border-sky-500 border-t-transparent rounded-full animate-spin mx-auto"></div></div>
                </div>
                
                <div class="absolute bottom-0 left-0 w-full h-32 bg-gradient-to-t from-black to-transparent z-20 pointer-events-none"></div>
            </div>

        </div>

    </div>

    <script>
        // VERTICAL Scaling Logic
        function scaleToFit() {
            const canvas = document.getElementById('signage-canvas');
            const windowWidth = window.innerWidth;
            const windowHeight = window.innerHeight;
            
            // Switch calculation base to 1080 Width / 1920 Height
            const scaleX = windowWidth / 1080;
            const scaleY = windowHeight / 1920;
            const scale = Math.min(scaleX, scaleY);
            
            canvas.style.transform = `scale(${scale})`;
            
            const scaledWidth = 1080 * scale;
            const scaledHeight = 1920 * scale;
            canvas.style.left = `${(windowWidth - scaledWidth) / 2}px`;
            canvas.style.top = `${(windowHeight - scaledHeight) / 2}px`;
        }

        window.addEventListener('resize', scaleToFit);
        scaleToFit();

        // ==========================================================
        // 🎵 CLIENT-AWARE JUKEBOX LOGIC
        // ==========================================================
        let player; 
        
        let clientId = localStorage.getItem('obsera_client_id');
        if (!clientId) {
            clientId = 'tv_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('obsera_client_id', clientId);
        }

        function onYouTubeIframeAPIReady() {
            fetch(`jukebox_api.php?action=next&client_id=${clientId}`)
                .then(res => res.json())
                .then(data => {
                    let newTrack = data.state.current;
                    document.getElementById('now-playing-title').innerText = newTrack.title;
                    if (data.state.ad_url) document.getElementById('tv-ad-image').src = data.state.ad_url;
                    updateLeaderboardUI(data.state.options);

                    player = new YT.Player('ytplayer', {
                        videoId: newTrack.id,
                        playerVars: { 'controls': 0, 'rel': 0, 'showinfo': 0, 'modestbranding': 1 },
                        events: { 
                            'onReady': (e) => { 
                                setTimeout(() => { e.target.playVideo(); }, 1500); 
                            }, 
                            'onStateChange': onPlayerStateChange 
                        }
                    });
                });
        }

        function onPlayerStateChange(event) {
            if (event.data == YT.PlayerState.ENDED) {
                fetch(`jukebox_api.php?action=next&client_id=${clientId}`)
                    .then(res => res.json())
                    .then(data => {
                        let newTrack = data.state.current;
                        player.loadVideoById(newTrack.id, 0); 
                        document.getElementById('now-playing-title').innerText = newTrack.title;
                        updateLeaderboardUI(data.state.options);
                    });
            }
        }

        async function fetchState() {
            try {
                const res = await fetch(`jukebox_api.php?t=${Date.now()}&client_id=${clientId}`);
                const data = await res.json();
                
                if (data.ad_url) document.getElementById('tv-ad-image').src = data.ad_url;
                updateLeaderboardUI(data.options);
            } catch(e) {}
        }

        function buildTrackCard(opt, index, isWinner, totalVotes) {
            let percentage = totalVotes > 0 ? (opt.votes / totalVotes) * 100 : 0;
            let rankClass = isWinner ? 'rank-1 scale-[1.02] z-10' : 'border-slate-800 opacity-80';
            let badge = isWinner ? `<div class="absolute top-3 right-5 bg-yellow-500 text-black text-xs font-black px-3 py-1 rounded-full next-play-badge">NEXT PLAY</div>` : '';

            // Typography dynamically scales down slightly to fit the 660px right-hand column perfectly
            return `
                <div class="bg-slate-900 rounded-2xl p-6 border-2 ${rankClass} transition-all relative overflow-hidden flex items-center gap-5">
                    <div class="absolute top-0 left-0 h-full bg-sky-500/10 transition-all duration-500" style="width: ${percentage}%"></div>
                    ${badge}
                    <h1 class="font-display font-bold text-4xl text-white/20 w-8 text-center relative z-10">${isWinner ? '1' : ''}</h1>
                    <img src="https://img.youtube.com/vi/${opt.song.id}/mqdefault.jpg" class="w-28 h-20 object-cover rounded-xl relative z-10 shrink-0 border border-white/10 bg-black">
                    <div class="flex-grow min-w-0 relative z-10">
                        <h4 class="text-orange-500 font-bold truncate text-3xl mb-1">${opt.song.title}</h4>
                        <p class="text-slate-300 text-lg truncate">${opt.song.artist || 'Unknown Artist'}</p>
                    </div>
                    <div class="text-4xl font-display font-black text-sky-400 relative z-10 pr-2">${opt.votes}</div>
                </div>
            `;
        }

function updateLeaderboardUI(options) {
            let sorted = [...options].sort((a,b) => b.votes - a.votes);
            let totalVotes = sorted.reduce((sum, opt) => sum + opt.votes, 0) || 1;

            let winnerIndex = -1;
            for (let i = 0; i < sorted.length; i++) {
                let opt = sorted[i];
                let hasPlayed = opt.played_by && opt.played_by.includes(clientId);
                if (opt.votes > 0 && !hasPlayed) {
                    winnerIndex = i; break;
                }
            }

            let others = [...sorted];
            let scrollHtml = '';

            if (winnerIndex !== -1) {
                let winner = sorted[winnerIndex];
                document.getElementById('next-play-container').innerHTML = buildTrackCard(winner, 0, true, totalVotes);
                others.splice(winnerIndex, 1);
            } else {
                document.getElementById('next-play-container').innerHTML = `
                    <div class="text-center py-6 border-2 border-dashed border-white/10 rounded-2xl bg-white/5">
                        <p class="text-slate-400 font-bold tracking-widest uppercase text-sm mb-2">Queue is Open</p>
                        <h3 class="text-2xl font-bold text-white">Scan the QR code to choose the next track!</h3>
                    </div>
                `;
            }

            others.forEach((opt) => { scrollHtml += buildTrackCard(opt, 0, false, totalVotes); });

            if (sorted.length > 0) {
                // 🛠️ FIX 1: Added a hardware-accelerated wrapper container
                document.getElementById('scrolling-leaderboard').innerHTML = `
                    <div id="scroll-wrapper" style="will-change: transform;">
                        <div id="list-copy-1" class="space-y-4 py-4">${scrollHtml}</div>
                        <div id="list-copy-2" class="space-y-4 py-4">${scrollHtml}</div>
                    </div>
                `;
            }
        }

        setInterval(() => {
            if (player && player.getCurrentTime && player.getDuration) {
                let current = player.getCurrentTime();
                let total = player.getDuration();
                if(total > 0) {
                    let m = Math.floor(current / 60);
                    let s = Math.floor(current % 60).toString().padStart(2, '0');
                    document.getElementById('now-playing-timer').innerText = `${m}:${s}`;
                }
            }
        }, 1000);

        // 🛠️ FIX 2: True 60FPS Hardware Accelerated Scroll
        let scrollPos = 0;
        let lastTime = performance.now();
        const SCROLL_SPEED = 45; // Pixels per second. Increase to make it scroll faster!

        function autoScroll(currentTime) {
            // DeltaTime ensures it moves smoothly even if the Pi drops a frame
            const deltaTime = (currentTime - lastTime) / 1000;
            lastTime = currentTime;

            const wrapper = document.getElementById('scroll-wrapper');
            const copy1 = document.getElementById('list-copy-1');
            
            if (wrapper && copy1) {
                scrollPos += SCROLL_SPEED * deltaTime;
                
                // Seamless loop reset
                if (scrollPos >= copy1.offsetHeight) {
                    scrollPos -= copy1.offsetHeight;
                }
                
                // Move the layer using the GPU, not the CPU
                wrapper.style.transform = `translateY(-${scrollPos}px)`;
            }
            
            requestAnimationFrame(autoScroll);
        }
        // Start the smooth loop
        requestAnimationFrame(autoScroll); 

        setInterval(fetchState, 2500);
    </script>
</body>
</html>