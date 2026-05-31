<?php
session_start();
$ADMIN_PASSWORD = "obserax"; 
$playlistFile = 'playlist.json';
$suggestionsFile = 'suggestions.json';
$adFile = 'ad.json';

// --- Handle Login ---
if (isset($_GET['logout'])) { session_destroy(); header("Location: " . strtok($_SERVER["REQUEST_URI"], '?')); exit; }
if (isset($_POST['password']) && $_POST['password'] === $ADMIN_PASSWORD) {
    $_SESSION['admin_jukebox'] = true;
    header("Location: " . $_SERVER['PHP_SELF']); exit;
}

if (!isset($_SESSION['admin_jukebox'])) {
    die('
    <body style="background:#0f172a; display:flex; align-items:center; justify-content:center; height:100vh; font-family:sans-serif; margin:0;">
        <form method="POST" style="background:#1e293b; padding:40px; border-radius:24px; text-align:center; border:1px solid #334155; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); width:320px;">
            <div style="background:#8b5cf6; width:50px; height:50px; border-radius:12px; margin:0 auto 20px; display:flex; align-items:center; justify-content:center; color:white; font-weight:bold; font-size:24px;">♫</div>
            <h2 style="color:white; margin-bottom:10px; font-size:1.5rem;">Jukebox Admin</h2>
            <input type="password" name="password" placeholder="••••••" autofocus style="padding:14px; border-radius:12px; border:1px solid #334155; background:#0f172a; color:white; margin-bottom:15px; width:100%; text-align:center; font-size:1.2rem; outline:none;">
            <button type="submit" style="background:#8b5cf6; color:white; border:none; padding:14px; border-radius:12px; cursor:pointer; font-weight:bold; width:100%; font-size:1rem;">ACCESS STUDIO</button>
        </form>
    </body>');
}

$playlist = file_exists($playlistFile) ? json_decode(file_get_contents($playlistFile), true) : [];
$suggestions = file_exists($suggestionsFile) ? json_decode(file_get_contents($suggestionsFile), true) : [];

// --- Handle Update Ad ---
if (isset($_POST['action']) && $_POST['action'] === 'update_ad') {
    file_put_contents($adFile, json_encode(["url" => $_POST['ad_url']]));
    header("Location: " . $_SERVER['PHP_SELF'] . "?success=2"); exit;
}

// --- Handle Add Song ---
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $url = $_POST['url'];
    $title = htmlspecialchars($_POST['title']);
    $artist = htmlspecialchars($_POST['artist']);
    
    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $url, $match);
    $yt_id = $match[1] ?? null;

    if ($yt_id && $title && $artist) {
        $playlist[] = ["id" => $yt_id, "title" => $title, "artist" => $artist];
        file_put_contents($playlistFile, json_encode($playlist, JSON_PRETTY_PRINT));
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1"); exit;
    } else {
        $error = "Invalid YouTube URL or missing details.";
    }
}

// --- Handle Delete Song ---
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $delete_id = $_POST['id'];
    $playlist = array_filter($playlist, function($song) use ($delete_id) { return $song['id'] !== $delete_id; });
    file_put_contents($playlistFile, json_encode(array_values($playlist), JSON_PRETTY_PRINT));
    header("Location: " . $_SERVER['PHP_SELF']); exit;
}

// --- Handle Delete Suggestion ---
if (isset($_POST['action']) && $_POST['action'] === 'delete_suggestion') {
    $sug_id = $_POST['id'];
    $suggestions = array_filter($suggestions, function($s) use ($sug_id) { return $s['id'] !== $sug_id; });
    file_put_contents($suggestionsFile, json_encode(array_values($suggestions), JSON_PRETTY_PRINT));
    header("Location: " . $_SERVER['PHP_SELF']); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jukebox Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="master.css">
    <script src="theme.js"></script>
</head>
<body class="bg-brand-dark text-white min-h-screen p-6 md:p-10">
    
    <div class="max-w-5xl mx-auto">
        <header class="flex justify-between items-end mb-10 border-b border-white/10 pb-6">
            <div>
                <h1 class="text-4xl font-display font-bold text-white tracking-wide">Jukebox Studio</h1>
                <p class="text-slate-400 mt-1 text-sm">Manage tracks, user requests, and TV ads.</p>
            </div>
            

            
            
            <div class="flex gap-4">
                <div class="text-right">
                    <div class="text-slate-500 text-[10px] uppercase font-bold tracking-widest mb-1">Total Tracks</div>
                    <div class="text-3xl font-bold text-purple-400 leading-none"><?php echo count($playlist); ?></div>
                </div>
                <a href="?logout=true" class="bg-slate-800 hover:bg-slate-700 border border-slate-700 px-4 py-2 mt-auto rounded-lg text-sm font-bold transition-all h-max">Logout</a>
            </div>
            
            
            
            
        </header>

        <?php if(isset($error)): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-400 px-4 py-3 rounded-xl mb-6 font-bold animate-pulse">⚠️ <?php echo $error; ?></div>
        <?php endif; ?>
        <?php if(isset($_GET['success'])): ?>
            <div class="bg-emerald-500/10 border border-emerald-500/50 text-emerald-400 px-4 py-3 rounded-xl mb-6 font-bold">✓ Action completed successfully!</div>
        <?php endif; ?>

        <div class="grid md:grid-cols-3 gap-8">
            
            <div class="md:col-span-1 space-y-8">
                
                <div class="glass p-6 rounded-2xl">
                    <h2 class="text-xl font-bold text-white mb-6 border-b border-white/10 pb-2 flex items-center gap-2">
                        <span class="text-purple-400">⊕</span> Add New Track
                    </h2>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add">
                        <div>
                            <label class="block text-[10px] text-slate-400 uppercase font-bold tracking-widest mb-1">YouTube Link</label>
                            <input type="text" name="url" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:border-purple-400 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] text-slate-400 uppercase font-bold tracking-widest mb-1">Song Title</label>
                            <input type="text" name="title" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:border-purple-400 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] text-slate-400 uppercase font-bold tracking-widest mb-1">Artist / Creator</label>
                            <input type="text" name="artist" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:border-purple-400 outline-none">
                        </div>
                        <button type="submit" class="w-full bg-purple-500 hover:bg-purple-400 text-white font-bold py-3 rounded-xl transition-all mt-4">Add to Rotation</button>
                    </form>
                </div>

                <div class="glass p-6 rounded-2xl border-sky-500/20 border">
                    <h2 class="text-xl font-bold text-white mb-6 border-b border-white/10 pb-2 flex items-center gap-2">
                        <span class="text-sky-400">📺</span> TV Ad Banner
                    </h2>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="update_ad">
                        <div>
                            <label class="block text-[10px] text-slate-400 uppercase font-bold tracking-widest mb-1">Image Link (URL)</label>
                            <input type="text" name="ad_url" placeholder="https://..." required class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:border-sky-400 outline-none">
                        </div>
                        <button type="submit" class="w-full bg-sky-500 hover:bg-sky-400 text-slate-900 font-bold py-3 rounded-xl transition-all mt-2">Push to TV</button>
                    </form>
                </div>
                
                            <div class="glass p-6 rounded-2xl mt-8 border border-red-500/20">
    <h2 class="text-xl font-bold text-white mb-4 border-b border-white/10 pb-2 flex items-center gap-2">
        <span class="text-red-400">⏭️</span> Remote Control
    </h2>
    <p class="text-slate-400 text-sm mb-4">Force the TV to skip the current song and play the next winner.</p>
    
    <button onclick="forceSkipTrack()" class="w-full bg-red-500 hover:bg-red-400 text-white font-bold py-4 rounded-xl transition-all shadow-lg shadow-red-500/20 active:scale-95">
        SKIP CURRENT TRACK
    </button>
</div>

<script>
    // Add this to your Admin page scripts to make the button work
    function forceSkipTrack() {
        if(confirm("Are you sure you want to skip the current song?")) {
            fetch('jukebox_api.php?action=next')
                .then(res => res.json())
                .then(data => {
                    alert("Track Skipped! TV will update in 2 seconds.");
                });
        }
    }
</script>
                
                
                

                <div class="glass p-6 rounded-2xl border-fuchsia-500/20 border">
                    <div class="flex justify-between items-center mb-6 border-b border-white/10 pb-2">
                        <h2 class="text-xl font-bold text-white flex items-center gap-2"><span class="text-fuchsia-400">💬</span> User Requests</h2>
                        <span class="bg-fuchsia-500/20 text-fuchsia-400 text-[10px] font-bold px-2 py-0.5 rounded-full"><?php echo count($suggestions); ?></span>
                    </div>
                    <div class="space-y-3 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                        <?php if(empty($suggestions)): ?>
                            <p class="text-slate-500 text-sm italic text-center py-4">No track suggestions yet.</p>
                        <?php else: ?>
                            <?php foreach(array_reverse($suggestions) as $sug): ?>
                                <div class="bg-slate-900/50 border border-white/5 p-3 rounded-xl relative group">
                                    <h4 class="text-white font-bold text-sm leading-tight pr-6"><?php echo $sug['title']; ?></h4>
                                    <p class="text-fuchsia-400 text-[11px] font-medium"><?php echo $sug['artist']; ?></p>
                                    <form method="POST" class="absolute top-2 right-2">
                                        <input type="hidden" name="action" value="delete_suggestion">
                                        <input type="hidden" name="id" value="<?php echo $sug['id']; ?>">
                                        <button type="submit" class="text-slate-600 hover:text-red-400 transition-colors">✕</button>
                                    </form>
                                    <div class="mt-2 text-[9px] text-slate-600 uppercase font-bold tracking-tighter"><?php echo date('M d, H:i', strtotime($sug['timestamp'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <div class="md:col-span-2">
                <h2 class="text-xl font-bold text-white mb-6 border-b border-white/10 pb-2 flex items-center gap-2"><span class="text-purple-400">📚</span> Live Library</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <?php if(empty($playlist)): ?>
                        <div class="col-span-full text-center py-10 text-slate-500">Library is empty. Add a YouTube link!</div>
                    <?php else: ?>
                        <?php foreach(array_reverse($playlist) as $song): ?>
                            <div class="bg-slate-900 border border-white/5 p-3 rounded-2xl flex gap-4 items-center group hover:border-purple-500/30 transition-colors">
                                <div class="w-20 h-14 bg-black rounded-lg overflow-hidden shrink-0 relative">
                                    <img src="https://img.youtube.com/vi/<?php echo $song['id']; ?>/mqdefault.jpg" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-grow min-w-0">
                                    <h3 class="text-white font-bold text-sm truncate"><?php echo htmlspecialchars($song['title']); ?></h3>
                                    <p class="text-slate-400 text-xs truncate"><?php echo htmlspecialchars($song['artist']); ?></p>
                                </div>
                                <form method="POST" onsubmit="return confirm('Remove this track from the Jukebox?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $song['id']; ?>">
                                    <button type="submit" class="p-2 text-slate-600 hover:text-red-400 bg-white/5 hover:bg-red-500/10 rounded-lg transition-colors">🗑</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</body>
</html>