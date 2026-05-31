<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Obsera | Live Jukebox</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="master.css">
    <script src="theme.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap');
        
        body { 
            overscroll-behavior-y: none; 
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #020617;
        }

        .custom-scrollbar::-webkit-scrollbar { display: none; }
        .custom-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Equalizer Animation */
        .equalizer-bar { width: 3px; background-color: #ef4444; border-radius: 3px; animation: bounce 1.2s ease-in-out infinite; }
        .eq-1 { animation-delay: 0.0s; height: 12px; } .eq-2 { animation-delay: 0.2s; height: 16px; }
        .eq-3 { animation-delay: 0.4s; height: 10px; } .eq-4 { animation-delay: 0.1s; height: 14px; }
        @keyframes bounce { 0%, 100% { transform: scaleY(0.5); } 50% { transform: scaleY(1.2); } }

        /* Glassmorphism Classes */
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        /* Vinyl Rotation for Current Song */
        .vinyl-glow {
            position: relative;
            animation: rotate 8s linear infinite;
        }
        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

        .bottom-sheet { transition: transform 0.5s cubic-bezier(0.32, 0.72, 0, 1); }
        
        /* Voting Progress Bar */
        .vote-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #38bdf8);
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes popIn { 
            0% { transform: scale(0); } 
            80% { transform: scale(1.1); } 
            100% { transform: scale(1); } 
        }
    </style>
</head>
<body class="text-slate-200 h-screen flex flex-col overflow-hidden relative">

    <div class="fixed top-[-10%] left-[-10%] w-96 h-96 bg-sky-500/20 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="fixed bottom-[-10%] right-[-10%] w-96 h-96 bg-fuchsia-600/10 rounded-full blur-[120px] pointer-events-none"></div>

    <div class="glass-card m-4 mb-2 rounded-3xl p-4 z-40 shrink-0 shadow-2xl relative overflow-hidden">
        <div class="absolute top-4 right-4">
             <span class="flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
            </span>
        </div>
        <div class="relative z-10 flex items-center gap-5">
            <div class="relative shrink-0">
                <div class="w-16 h-16 rounded-full overflow-hidden border-2 border-white/10 vinyl-glow shadow-[0_0_20px_rgba(56,189,248,0.2)]">
                    <img id="now-playing-thumb" src="" class="w-full h-full object-cover scale-110">
                </div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-3 h-3 bg-[#020617] rounded-full border border-white/20"></div>
                </div>
            </div>

            <div class="flex flex-col min-w-0 pr-6">
                <div class="flex items-center gap-2 mb-1">
                    <div class="flex items-end gap-[2px] h-3">
                        <div class="equalizer-bar eq-1"></div><div class="equalizer-bar eq-2"></div>
                        <div class="equalizer-bar eq-3"></div><div class="equalizer-bar eq-4"></div>
                    </div>
                    <span class="text-[10px] text-sky-400 uppercase tracking-[0.15em] font-extrabold">Now On TV</span>
                </div>
                <h2 id="now-playing-title" class="font-bold text-white text-lg leading-tight truncate">Syncing...</h2>
            </div>
        </div>
    </div>

    <div class="px-6 pt-4 pb-4 shrink-0 relative z-10">
        <div class="flex items-end justify-between mb-1">
            <h1 class="text-3xl font-extrabold text-white tracking-tight">Queue</h1>
            
            <div class="flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/20 px-3 py-1.5 rounded-full mb-1">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span id="screen-count" class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Syncing...</span>
            </div>
        </div>
        
        <p class="text-slate-400 text-sm mb-4 font-medium">Winner of the votes plays next.</p>
        
        <button onclick="toggleSuggestModal()" class="w-full glass-card hover:bg-white/5 rounded-2xl p-4 flex items-center justify-between group transition-all active:scale-95">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-sky-500/10 rounded-xl flex items-center justify-center text-sky-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                </div>
                <span class="font-bold text-white">Suggest a Track</span>
            </div>
            <span class="text-slate-500 group-hover:text-white transition-colors">→</span>
        </button>
    </div>

    <div id="scroll-container" class="flex-grow overflow-y-auto custom-scrollbar relative z-10">
        <div id="voting-list" class="px-4 space-y-3 pb-32">
            <div class="flex justify-center py-10">
                <div class="w-8 h-8 border-4 border-sky-500/20 border-t-sky-500 rounded-full animate-spin"></div>
            </div>
        </div>
    </div>

    <div id="vote-lock-msg" class="fixed bottom-8 left-1/2 -translate-x-1/2 w-[85%] max-w-sm glass-card rounded-full p-4 border-emerald-500/40 flex items-center justify-center gap-3 translate-y-[250%] transition-transform duration-700 z-[100] shadow-[0_20px_50px_rgba(0,0,0,0.5)]">
        <div class="w-10 h-10 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400 shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
        </div>
        <div class="text-left min-w-0">
            <p class="text-white font-extrabold text-sm leading-tight truncate">Vote Confirmed!</p>
            <p class="text-emerald-400/80 text-[10px] uppercase font-bold tracking-widest truncate">Locked for this round</p>
        </div>
    </div>

    <div id="suggest-modal" class="fixed inset-0 z-[100] hidden flex flex-col justify-end">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-md transition-opacity" onclick="toggleSuggestModal()"></div>
        <div id="suggest-sheet" class="bottom-sheet bg-[#0f172a] border-t border-white/10 rounded-t-[2.5rem] p-8 relative z-10 transform translate-y-full shadow-[0_-10px_40px_rgba(0,0,0,0.5)]">
            <div class="w-12 h-1.5 bg-white/10 rounded-full mx-auto mb-8"></div>
            
            <div id="suggest-form-container">
                <h3 class="text-2xl font-extrabold text-white mb-2">Make a Request</h3>
                <p class="text-slate-400 text-sm mb-8 font-medium">We'll review your track and try to add it to the live jukebox rotation.</p>
                
                <form id="suggest-form" class="space-y-4">
                    <input type="text" id="suggest-title" name="title" placeholder="Song Title" required 
                           class="w-full bg-white/5 border border-white/10 rounded-2xl p-5 text-white focus:border-sky-500 focus:ring-1 focus:ring-sky-500 outline-none transition-all placeholder:text-slate-600">
                    
                    <input type="text" id="suggest-artist" name="artist" placeholder="Artist Name (optional)" 
                           class="w-full bg-white/5 border border-white/10 rounded-2xl p-5 text-white focus:border-sky-500 focus:ring-1 focus:ring-sky-500 outline-none transition-all placeholder:text-slate-600">
                    
                    <button type="submit" id="suggest-submit-btn" class="w-full bg-sky-500 text-black font-extrabold py-5 rounded-2xl hover:bg-sky-400 active:scale-95 transition-all mt-4 disabled:opacity-50 disabled:cursor-not-allowed">
                        Submit Request
                    </button>
                </form>
            </div>

            <div id="suggest-success" class="hidden text-center py-10">
                <div class="w-20 h-20 bg-emerald-500/20 text-emerald-400 rounded-full flex items-center justify-center mx-auto mb-6 animate-[popIn_0.5s_ease-out]">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <h4 class="text-2xl font-extrabold text-white mb-2">Request Sent</h4>
                <p class="text-slate-400 font-medium">Keep an eye on the TV!</p>
            </div>
        </div>
    </div>

    <script>
        let currentSongId = ""; 
        let hasVotedForCurrentRound = false;

        async function syncState() {
            try {
                const res = await fetch('jukebox_api.php?t=' + Date.now()); 
                const data = await res.json();
                
                if (!data) return;

                // 🛠️ ADDED: Update the Live TV Counter
                if (data.active_clients) {
                    let tvCount = Object.keys(data.active_clients).length;
                    document.getElementById('screen-count').innerText = `${tvCount} Screen${tvCount !== 1 ? 's' : ''} Online`;
                }

                if (data.current) {
                    if (data.current.id !== currentSongId) {
                        currentSongId = data.current.id; 
                        hasVotedForCurrentRound = false; 
                        
                        document.getElementById('now-playing-title').innerText = data.current.title;
                        document.getElementById('now-playing-thumb').src = `https://img.youtube.com/vi/${data.current.id}/mqdefault.jpg`;
                        document.getElementById('vote-lock-msg').classList.add('translate-y-[250%]');
                    }
                }
                
                if(data.options) {
                    updateUI(data.options);
                }
            } catch (e) {}
        }

        function updateUI(options) {
            const container = document.getElementById('voting-list');
            let sortedOptions = [...options].sort((a,b) => b.votes - a.votes);
            let html = '';

            let totalVotes = sortedOptions.reduce((acc, opt) => acc + opt.votes, 0);

            sortedOptions.forEach((opt, index) => {
                const isVoted = (opt.votes > 0);
                const isLeader = (isVoted && index === 0);
                
                const lockStyle = hasVotedForCurrentRound ? 'opacity-50 grayscale-[0.5]' : 'active:scale-95 cursor-pointer';
                const borderClass = isLeader ? 'border-sky-500/50 shadow-[0_0_20px_rgba(56,189,248,0.15)]' : 'border-white/5';
                const progressWidth = totalVotes > 0 ? (opt.votes / totalVotes) * 100 : 0;

                html += `
                    <div onclick="castVote('${opt.song.id}')" class="glass-card rounded-2xl p-4 flex items-center gap-4 transition-all duration-300 relative overflow-hidden ${lockStyle} ${borderClass}">
                        <div class="vote-progress" style="width: ${progressWidth}%"></div>
                        
                        <div class="w-14 h-14 rounded-xl overflow-hidden shrink-0 relative border border-white/10 bg-slate-800">
                            <img src="https://img.youtube.com/vi/${opt.song.id}/mqdefault.jpg" class="w-full h-full object-cover scale-150">
                        </div>
                        
                        <div class="flex-grow min-w-0">
                            <h3 class="text-white font-bold truncate text-base leading-tight">${opt.song.title}</h3>
                            <p class="text-slate-400 text-xs truncate mt-1 font-medium">${opt.song.artist || 'Unknown Artist'}</p>
                        </div>

                        <div class="flex flex-col items-end shrink-0 pl-2">
                            <span class="text-lg font-black ${isVoted ? 'text-sky-400' : 'text-slate-600'}">${opt.votes}</span>
                            <span class="text-[9px] uppercase font-bold tracking-widest text-slate-500">Votes</span>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = `<div id="list-copy-1" class="space-y-3 pb-3">${html}</div><div id="list-copy-2" class="space-y-3 pb-3">${html}</div>`;
        }

        async function castVote(songId) {
            if (hasVotedForCurrentRound) return;
            
            if (navigator.vibrate) navigator.vibrate(40);
            hasVotedForCurrentRound = true; 
            document.getElementById('vote-lock-msg').classList.remove('translate-y-[250%]'); 

            try {
                const res = await fetch(`jukebox_api.php?action=vote&id=${songId}`);
                const data = await res.json();
                updateUI(data.state.options);
            } catch (err) {
                hasVotedForCurrentRound = false;
                document.getElementById('vote-lock-msg').classList.add('translate-y-[250%]');
            }
        }

        function toggleSuggestModal() {
            const modal = document.getElementById('suggest-modal');
            const sheet = document.getElementById('suggest-sheet');
            
            if (modal.classList.contains('hidden')) {
                modal.classList.remove('hidden');
                setTimeout(() => sheet.classList.remove('translate-y-full'), 10);
            } else {
                sheet.classList.add('translate-y-full');
                setTimeout(() => {
                    modal.classList.add('hidden');
                    document.getElementById('suggest-form-container').classList.remove('hidden');
                    document.getElementById('suggest-success').classList.add('hidden');
                    document.getElementById('suggest-form').reset();
                    
                    const btn = document.getElementById('suggest-submit-btn');
                    btn.disabled = false;
                    btn.innerText = "Submit Request";
                }, 400); 
            }
        }

        document.getElementById('suggest-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('suggest-submit-btn');
            btn.disabled = true;
            btn.innerText = "Sending..."; 

            const fd = new FormData(e.target);
            fd.append('action', 'suggest');
            
            try {
                const response = await fetch('jukebox_api.php', { method: 'POST', body: fd });
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('suggest-form-container').classList.add('hidden');
                    document.getElementById('suggest-success').classList.remove('hidden');
                    if (navigator.vibrate) navigator.vibrate([40, 40, 40]); 
                    setTimeout(toggleSuggestModal, 2200);
                } else {
                    throw new Error(result.error || "Failed to save");
                }
            } catch (err) {
                alert("Failed to send suggestion. Please ensure connection is active.");
                btn.disabled = false;
                btn.innerText = "Submit Request";
            }
        });

        // --- RESTORED AUTO-SCROLL ENGINE ---
        const scroller = document.getElementById('scroll-container'); 
        let isAutoScrolling = true; 
        let scrollTimeout; 
        let scrollPos = 0;

        function autoScroll() {
            if (isAutoScrolling && !hasVotedForCurrentRound && document.getElementById('suggest-modal').classList.contains('hidden')) {
                scrollPos += 0.3; 
                const copy1 = document.getElementById('list-copy-1');
                if (copy1) { 
                    if (scrollPos >= copy1.offsetHeight) { scrollPos -= copy1.offsetHeight; } 
                    scroller.scrollTop = scrollPos; 
                }
            } else { 
                scrollPos = scroller.scrollTop; 
            } 
            requestAnimationFrame(autoScroll);
        } 
        requestAnimationFrame(autoScroll);

        function pauseScroll() { 
            isAutoScrolling = false; 
            clearTimeout(scrollTimeout); 
            scrollTimeout = setTimeout(() => { isAutoScrolling = true; }, 5000); 
        }

        scroller.addEventListener('touchstart', pauseScroll, {passive: true}); 
        scroller.addEventListener('touchmove', pauseScroll, {passive: true}); 
        scroller.addEventListener('mousedown', pauseScroll); 
        scroller.addEventListener('wheel', pauseScroll, {passive: true});

        // Loop Sync
        setInterval(syncState, 2500); 
        syncState();
    </script>
</body>
</html>