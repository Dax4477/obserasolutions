<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obsera Interactive TV</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="master.css">
    <script src="theme.js"></script>
    <script src="https://www.youtube.com/iframe_api"></script>
    <style>
        /* Hide all scrollbars globally */
        ::-webkit-scrollbar { display: none; }
        * { -ms-overflow-style: none; scrollbar-width: none; }

        .rank-1 { border-color: #fbbf24; box-shadow: 0 0 30px rgba(251, 191, 36, 0.5); }
        @keyframes flash { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
        .next-play-badge { animation: flash 1.5s infinite; }
        
        /* * The TV body must be pure black and completely hidden overflow
         * so the scaled canvas can float inside it without causing scrollbars.
         */
        body { margin: 0; padding: 0; overflow: hidden; background-color: #000; width: 100vw; height: 100vh; }
        
        /* The Master Canvas */
        #signage-canvas {
            width: 1920px;
            height: 1080px;
            transform-origin: top left;
            position: absolute;
            top: 0;
            left: 0;
        }

        .scroll-mask {
            mask-image: linear-gradient(to bottom, transparent, black 5%, black 95%, transparent);
            -webkit-mask-image: linear-gradient(to bottom, transparent, black 5%, black 95%, transparent);
        }
    </style>
</head>
<body>

    <div id="signage-canvas" class="bg-brand-dark text-white flex">
        
        <div class="w-2/3 h-full flex flex-col p-10 gap-10 relative">
            <div class="hidden absolute top-0 left-0 w-full h-full bg-brand-accent/5 blur-[120px] -z-10"></div>
            
            <div class="flex items-center justify-between shrink-0 z-10">
                <div class="flex items-center gap-4 animate-pulse">
                    <div class="w-4 h-4 bg-red-500 rounded-full shadow-[0_0_15px_rgba(239,68,68,0.8)]"></div>
                    <h2 class="text-2xl font-display font-bold tracking-[0.2em] uppercase text-slate-300">Live Jukebox</h2>
                </div>
                <div class="flex items-center gap-4">
                    <span id="now-playing-timer" class="text-2xl font-bold font-mono text-slate-400 tracking-widest"></span>
                    <h2 id="now-playing-title" class="text-4xl font-bold text-brand-accent truncate max-w-2xl">Loading...</h2>
                </div>
            </div>

            <div class="flex-grow min-h-0 w-full flex items-center justify-center z-10">
                <div class="aspect-video w-full max-h-full rounded-[2rem] overflow-hidden border-4 border-slate-700 border-4 border-white/10 relative bg-black shrink-0">
                    <div id="ytplayer" class="absolute top-0 left-0 w-full h-full pointer-events-none"></div>
                </div>
            </div>
            
            <div class="h-64 bg-black rounded-[2rem] border-2 border-brand-accent/30 overflow-hidden relative shrink-0 shadow-2xl flex items-center justify-center z-10">
                <img id="tv-ad-image" src="" class="w-full h-full object-conver opacity-90 transition-opacity duration-500">
                <div class="absolute bottom-4 right-6 bg-black/90 px-4 py-2 rounded-full text-xs text-white/70 uppercase tracking-widest font-bold z-20 border border-white/10">Advertisement</div>
            </div>
        </div>

        <div class="w-1/3 h-full bg-brand-panel border-l border-white/10 flex flex-col shadow-2xl relative z-20">
            
            <div class="p-12 pb-8 bg-brand-panel z-30 shadow-xl shrink-0 text-center relative">
                <h3 class="text-6xl font-display font-bold text-white mb-4">Up Next</h3>
                <p class="text-brand-accent font-black uppercase tracking-[0.2em] text-lg">Scan QR to Vote</p>
            </div>

            <div class="flex-grow overflow-hidden flex flex-col relative bg-brand-panel z-10">
                <div id="next-play-container" class="px-10 pt-6 pb-6 z-30 bg-brand-panel border-b border-white/10 shrink-0 shadow-lg relative"></div>

                <div id="scrolling-leaderboard" class="flex-grow overflow-y-auto px-10 py-0 relative opacity-60 scroll-mask z-10">
                    <div class="text-center py-10"><div class="spinner mx-auto scale-150"></div></div>
                </div>
            </div>

            <div class="p-10 bg-brand-panel border-t border-white/10 z-30 flex flex-col items-center gap-6 shrink-0 h-[450px] justify-center text-center shadow-[0_-10px_30px_rgba(0,0,0,0.3)] relative">
                <div class="p-5 bg-white rounded-[2rem] shadow-[0_0_40px_rgba(56,189,248,0.3)]">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=https://obserasolutions.site/mobile_vote&color=0f172a&bgcolor=ffffff" 
                         class="w-64 h-64 shrink-0" alt="QR Code">
                </div>
                <p class="text-yellow-400 text-3xl font-black leading-tight uppercase tracking-tight">Scan to <br>Take Control!</p>
            </div>
        </div>

    </div>

<script>
        function scaleToFit() {
            const canvas = document.getElementById('signage-canvas');
            const windowWidth = window.innerWidth;
            const windowHeight = window.innerHeight;
            
            const scaleX = windowWidth / 1920;
            const scaleY = windowHeight / 1080;
            const scale = Math.min(scaleX, scaleY);
            
            canvas.style.transform = `scale(${scale})`;
            
            const scaledWidth = 1920 * scale;
            const scaledHeight = 1080 * scale;
            canvas.style.left = `${(windowWidth - scaledWidth) / 2}px`;
            canvas.style.top = `${(windowHeight - scaledHeight) / 2}px`;
        }

        window.addEventListener('resize', scaleToFit);
        scaleToFit();

        // ==========================================================
        // ðŸŽµ JUKEBOX LOGIC (MASTER CLOCK SYNC)
        // ==========================================================
        let player; let currentVideoId = "";

        function onYouTubeIframeAPIReady() {
            fetch('jukebox_api.php?t=' + Date.now()).then(res => res.json()).then(data => {
                
                currentVideoId = data.current.id; 
                document.getElementById('now-playing-title').innerText = data.current.title;
                if (data.ad_url) document.getElementById('tv-ad-image').src = data.ad_url;
                updateLeaderboardUI(data.options);

                // ðŸ“» Calculate exact seconds to jump to!
                let elapsedSeconds = data.server_now - (data.started_at || data.server_now);
                if (elapsedSeconds < 0) elapsedSeconds = 0;

                player = new YT.Player('ytplayer', {
                    videoId: currentVideoId,
                    playerVars: { 
                        'autoplay': 1, 
                        'controls': 0, 
                        'rel': 0, 
                        'showinfo': 0, 
                        'modestbranding': 1,
                        'start': elapsedSeconds // ðŸ‘ˆ MAGIC FIX: Resumes exactly where it left off!
                    },
                    events: { 'onReady': (e) => e.target.playVideo(), 'onStateChange': onPlayerStateChange }
                });
            });
        }

        function onPlayerStateChange(event) {
            if (event.data == YT.PlayerState.ENDED) {
                fetch('jukebox_api.php?action=next').then(res => res.json()).then(data => {
                    currentVideoId = data.state.current.id; 
                    player.loadVideoById(currentVideoId, 0); // Start new song at 0:00
                    document.getElementById('now-playing-title').innerText = data.state.current.title;
                    updateLeaderboardUI(data.state.options);
                });
            }
        }

        async function fetchState() {
            try {
                const res = await fetch('jukebox_api.php?t=' + Date.now());
                const data = await res.json();
                
                if (data.ad_url) {
                    const adImg = document.getElementById('tv-ad-image');
                    if (adImg.src !== data.ad_url) adImg.src = data.ad_url;
                }

                // If the Server skips the song (e.g. from your laptop Remote Control)
                if (data.current.id !== currentVideoId && player) {
                    currentVideoId = data.current.id; 
                    
                    // Sync perfectly with Server Clock
                    let elapsed = data.server_now - (data.started_at || data.server_now);
                    if (elapsed < 0) elapsed = 0;
                    
                    player.loadVideoById(currentVideoId, elapsed);
                    document.getElementById('now-playing-title').innerText = data.current.title;
                }
                
                updateLeaderboardUI(data.options);
            } catch(e) {}
        }

        function buildTrackCard(opt, index, isWinner, totalVotes) {
            let percentage = totalVotes > 0 ? (opt.votes / totalVotes) * 100 : 0;
            let rankClass = isWinner ? 'rank-1 scale-[1.03] z-10' : 'border-white/5 opacity-80';
            let badge = isWinner ? `<div class="absolute top-3 right-5 bg-yellow-500 text-black text-sm font-black px-4 py-1.5 rounded-full next-play-badge shadow-lg">NEXT PLAY</div>` : '';

            return `
                <div class="bg-slate-900 rounded-[1.5rem] p-8 border-2 ${rankClass} transition-all relative overflow-hidden flex items-center gap-6">
                    <div class="absolute top-0 left-0 h-full bg-brand-accent/10 transition-all duration-500" style="width: ${percentage}%"></div>
                    ${badge}
                    <h1 class="font-display font-bold text-5xl text-white/20 w-12 text-center relative z-10">${isWinner ? '1' : ''}</h1>
                    <img src="https://img.youtube.com/vi/${opt.song.id}/mqdefault.jpg" class="w-36 h-24 object-cover rounded-xl shadow-lg relative z-10 shrink-0 border border-white/10 bg-slate-800">
                    <div class="flex-grow min-w-0 relative z-10">
                        <h4 class="text-orange-500 font-bold truncate text-4xl mb-2">${opt.song.title}</h4>
                        <p class="text-slate-300 text-xl truncate">${opt.song.artist || 'Unknown Artist'}</p>
                    </div>
                    <div class="text-5xl font-display font-black text-brand-accent relative z-10 pr-4">${opt.votes}</div>
                </div>
            `;
        }

        function updateLeaderboardUI(options) {
            let sorted = options; 
            let totalVotes = sorted.reduce((sum, opt) => sum + opt.votes, 0) || 1;

            if (sorted.length > 0) {
                let winner = sorted[0];
                let others = sorted.slice(1);

                document.getElementById('next-play-container').innerHTML = buildTrackCard(winner, 0, true, totalVotes);

                let scrollHtml = '';
                others.forEach((opt) => { scrollHtml += buildTrackCard(opt, 0, false, totalVotes); });

                document.getElementById('scrolling-leaderboard').innerHTML = `
                    <div id="list-copy-1" class="space-y-6 py-4">${scrollHtml}</div>
                    <div id="list-copy-2" class="space-y-6 py-4">${scrollHtml}</div>
                `;
            }
        }

        // --- UPDATE TIMER LOGIC ---
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

        const scroller = document.getElementById('scrolling-leaderboard');
        let scrollPos = 0;
        function autoScroll() {
            scrollPos += 0.3;
            const copy1 = document.getElementById('list-copy-1');
            if (copy1) {
                if (scrollPos >= copy1.offsetHeight) scrollPos -= copy1.offsetHeight;
                scroller.scrollTop = scrollPos;
            }
            requestAnimationFrame(autoScroll);
        }
        autoScroll(); setInterval(fetchState, 2500);
    </script>
</body>
</html>