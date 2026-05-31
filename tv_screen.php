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

        .rank-1 { border-color: #fbbf24; box-shadow: 0 0 20px rgba(251, 191, 36, 0.4); }
        @keyframes flash { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
        .next-play-badge { animation: flash 1.5s infinite; }
        
        /* Pure black background, overflow hidden */
        body { margin: 0; padding: 0; overflow: hidden; background-color: #000000; width: 100vw; height: 100vh; }
        
        /* The Master Canvas */
        #signage-canvas {
            width: 1920px;
            height: 1080px;
            transform-origin: top left;
            position: absolute;
            top: 0;
            left: 0;
            background-color: #000000;
        }
    </style>
</head>
<body>

    <div id="signage-canvas" class="text-white flex">
        
        <div class="w-2/3 h-full flex flex-col p-10 gap-10 relative bg-black">
            
            <div class="flex items-center justify-between shrink-0 z-10 border-b border-white/10 pb-4">
                <div class="flex items-center gap-4">
                    <div class="w-4 h-4 bg-red-600 rounded-full animate-pulse"></div>
                    <h2 class="text-2xl font-display font-bold tracking-[0.2em] uppercase text-slate-300">Live Jukebox</h2>
                </div>
                <div class="flex items-center gap-4">
                    <span id="now-playing-timer" class="text-2xl font-bold font-mono text-slate-400 tracking-widest">0:00</span>
                    <h2 id="now-playing-title" class="text-4xl font-bold text-sky-400 truncate max-w-2xl">Loading...</h2>
                </div>
            </div>

            <div class="flex-grow min-h-0 w-full flex items-center justify-center z-10">
               <div class="aspect-video w-full max-h-full border-4 border-slate-800 relative bg-black shrink-0">
                    <div id="ytplayer" class="absolute top-0 left-0 w-full h-full pointer-events-none"></div>
               </div>
            </div>
            
            <div class="h-64 bg-black border-2 border-slate-800 relative shrink-0 flex items-center justify-center z-10">
                <img id="tv-ad-image" src="" class="w-full h-full object-contain opacity-90 transition-opacity duration-500">
                <div class="absolute bottom-4 right-6 bg-black/90 px-4 py-2 text-xs text-white/70 uppercase tracking-widest font-bold z-20 border border-white/10">Advertisement</div>
            </div>
        </div>

        <div class="w-1/3 h-full bg-black border-l border-white/10 flex flex-col relative z-20">
            
            <div class="p-12 pb-8 bg-black z-30 shrink-0 text-center relative border-b border-white/10">
                <h3 class="text-6xl font-display font-bold text-white mb-4">Up Next</h3>
                <p class="text-sky-400 font-black uppercase tracking-[0.2em] text-lg">Scan QR to Vote</p>
            </div>

            <div class="flex-grow overflow-hidden flex flex-col relative bg-black z-10">
                
                <div id="next-play-container" class="px-10 pt-6 pb-6 z-30 bg-black border-b border-white/10 shrink-0 relative"></div>

                <div id="scrolling-leaderboard" class="flex-grow overflow-y-auto px-10 py-0 relative opacity-60 z-10 bg-black">
                    <div class="text-center py-10"><div class="w-10 h-10 border-4 border-sky-500 border-t-transparent rounded-full animate-spin mx-auto"></div></div>
                </div>
            </div>

            <div class="p-10 bg-black border-t border-white/10 z-30 flex flex-col items-center gap-6 shrink-0 h-[450px] justify-center text-center relative">
                <div class="p-5 bg-white rounded-xl">
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
            let rankClass = isWinner ? 'rank-1 scale-[1.03] z-10' : 'border-slate-800 opacity-80';
            let badge = isWinner ? `<div class="absolute top-3 right-5 bg-yellow-500 text-black text-sm font-black px-4 py-1.5 rounded-full next-play-badge">NEXT PLAY</div>` : '';

            return `
                <div class="bg-slate-900 rounded-2xl p-8 border-2 ${rankClass} transition-all relative overflow-hidden flex items-center gap-6">
                    <div class="absolute top-0 left-0 h-full bg-sky-500/10 transition-all duration-500" style="width: ${percentage}%"></div>
                    ${badge}
                    <h1 class="font-display font-bold text-5xl text-white/20 w-12 text-center relative z-10">${isWinner ? '1' : ''}</h1>
                    <img src="https://img.youtube.com/vi/${opt.song.id}/mqdefault.jpg" class="w-36 h-24 object-cover rounded-xl relative z-10 shrink-0 border border-white/10 bg-black">
                    <div class="flex-grow min-w-0 relative z-10">
                        <h4 class="text-orange-500 font-bold truncate text-4xl mb-2">${opt.song.title}</h4>
                        <p class="text-slate-300 text-xl truncate">${opt.song.artist || 'Unknown Artist'}</p>
                    </div>
                    <div class="text-5xl font-display font-black text-sky-400 relative z-10 pr-4">${opt.votes}</div>
                </div>
            `;
        }

        function updateLeaderboardUI(options) {
            // Sort options by votes descending
            let sorted = [...options].sort((a,b) => b.votes - a.votes);
            let totalVotes = sorted.reduce((sum, opt) => sum + opt.votes, 0) || 1;

            // 🛠️ SMART QUEUE LOGIC: Find the highest voted track that THIS TV hasn't played yet!
            let winnerIndex = -1;
            for (let i = 0; i < sorted.length; i++) {
                let opt = sorted[i];
                let hasPlayed = opt.played_by && opt.played_by.includes(clientId);
                
                if (opt.votes > 0 && !hasPlayed) {
                    winnerIndex = i;
                    break;
                }
            }

            let others = [...sorted];
            let scrollHtml = '';

            // If we found a track with votes that we haven't played:
            if (winnerIndex !== -1) {
                let winner = sorted[winnerIndex];
                document.getElementById('next-play-container').innerHTML = buildTrackCard(winner, 0, true, totalVotes);
                
                // Remove it from the 'others' list so it doesn't show twice on the screen
                others.splice(winnerIndex, 1);
            } else {
                // If there are no voted tracks, OR if we've already played all the voted ones:
                document.getElementById('next-play-container').innerHTML = `
                    <div class="text-center py-8 border-2 border-dashed border-white/10 rounded-2xl bg-white/5">
                        <p class="text-slate-400 font-bold tracking-widest uppercase text-sm mb-2">Queue is Open</p>
                        <h3 class="text-2xl font-bold text-white">Scan the QR code below to choose the next track!</h3>
                    </div>
                `;
            }

            // Build the scrolling leaderboard with whatever is left
            others.forEach((opt) => { scrollHtml += buildTrackCard(opt, 0, false, totalVotes); });

            if (sorted.length > 0) {
                document.getElementById('scrolling-leaderboard').innerHTML = `
                    <div id="list-copy-1" class="space-y-6 py-4">${scrollHtml}</div>
                    <div id="list-copy-2" class="space-y-6 py-4">${scrollHtml}</div>
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

        const scroller = document.getElementById('scrolling-leaderboard');
        let scrollPos = 0;

        function autoScroll() {
            scrollPos += 0.5; // Optimized speed for Pi
            const copy1 = document.getElementById('list-copy-1');
            if (copy1) {
                if (scrollPos >= copy1.offsetHeight) scrollPos -= copy1.offsetHeight;
                scroller.scrollTop = scrollPos;
            }
            // Throttle to 30 FPS to save CPU
            setTimeout(() => {
                requestAnimationFrame(autoScroll);
            }, 30);
        }
        autoScroll(); 
        setInterval(fetchState, 2500);
    </script>
</body>
</html>