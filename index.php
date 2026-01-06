<?php
/**
 * TACKLEBOX PRO 
 */

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
session_start();

function abort_app(string $msg, int $code = 400): void {
    http_response_code($code);
    echo "<h2>TackleBox Error</h2><p>".htmlspecialchars($msg)."</p>";
    exit;
}

$dbFile = '/var/lib/tacklebox/database.sqlite';
$configFile = 'config_auth.json'; 

// --- SPRACH-LOGIK ---
$lang = $_SESSION['lang'] ?? 'de';
if (isset($_GET['lang'])) { $lang = $_GET['lang'] == 'en' ? 'en' : 'de'; $_SESSION['lang'] = $lang; }

$texts = [
    'de' => [
        'title'=>'TackleBox', 'search'=>'Suche...', 'stats_n'=>'Menge', 'stats_w'=>'Wert', 
        'add'=>'Neu hinzufügen', 'brand'=>'Marke', 'model'=>'Modell', 'color'=>'Farbe', 
        'save'=>'Speichern', 'weight'=>'Gewicht (g)', 'length'=>'Länge (cm)', 'qty'=>'Stück', 
        'price'=>'Preis (€)', 'lang_btn'=>'EN', 'theme_btn'=>'Design', 'logout'=>'Logout', 
        'backup'=>'Export', 'restore'=>'Import', 'category'=>'Kategorie', 'date'=>'Zielfische', 
        'image'=>'Bild', 'back'=>'← Zurück', 'edit'=>'Bearbeiten', 'delete'=>'Löschen',
        'confirm'=>'Wirklich löschen?', 'all'=>'Alle', 'sort_new' => '✨ Neu', 'sort_abc' => '🔤 A-Z',
        'setup_title' => 'Erstes Setup', 'setup_desc' => 'Erstelle deinen Admin-Account:', 'user' => 'Benutzername', 'pass' => 'Passwort', 'create' => 'Account erstellen', 'login_btn' => 'Einloggen', 'error' => 'Falsche Daten!',
        'cats' => ["Hardbaits", "Gummiköder", "Metallköder", "Angelruten", "Rollen", "Haken", "Zubehör"],
        'fish' => ["Hecht", "Zander", "Barsch", "Forelle", "Wels", "Aal", "Döbel", "Rapfen", "Karpfen", "Schleie", "Brasse", "Rotauge", "Meerforelle", "Dorsch"]
    ],
    'en' => [
        'title'=>'TackleBox', 'search'=>'Search...', 'stats_n'=>'Qty', 'stats_w'=>'Value', 
        'add'=>'Add New', 'brand'=>'Brand', 'model'=>'Model', 'color'=>'Color', 
        'save'=>'Save', 'weight'=>'Weight (g)', 'length'=>'Length (cm)', 'qty'=>'Quantity', 
        'price'=>'Price (€)', 'lang_btn'=>'DE', 'theme_btn'=>'Theme', 'logout'=>'Logout', 
        'backup'=>'Export', 'restore'=>'Import', 'category'=>'Category', 'date'=>'Target Fish', 
        'image'=>'Image', 'back'=>'← Back', 'edit'=>'Edit', 'delete'=>'Delete',
        'confirm'=>'Really delete?', 'all'=>'All', 'sort_new' => '✨ New', 'sort_abc' => '🔤 A-Z',
        'setup_title' => 'First Setup', 'setup_desc' => 'Create your admin account:', 'user' => 'Username', 'pass' => 'Password', 'create' => 'Create Account', 'login_btn' => 'Login', 'error' => 'Wrong credentials!',
        'cats' => ["Hardbaits", "Softbaits", "Metal Baits", "Rods", "Reels", "Hooks", "Accessories"],
        'fish' => ["Pike", "Zander", "Perch", "Trout", "Catfish", "Eel", "Chub", "Asp", "Carp", "Tench", "Bream", "Roach", "Sea Trout", "Cod"]
    ]
];
$t = $texts[$lang];

// --- SESSION TIMEOUT ---
if (isset($_SESSION['logged_in'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
        session_unset(); session_destroy(); header("Location: index.php"); exit;
    }
    $_SESSION['last_activity'] = time();
}

// --- SETUP ---
if (!file_exists($configFile)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_user'], $_POST['set_pass'])) {
        $hash = password_hash($_POST['set_pass'], PASSWORD_DEFAULT);
        file_put_contents($configFile, json_encode(['name' => $_POST['set_user'], 'pass' => $hash]));
        header("Location: index.php"); exit;
    }
    echo '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1.0"><style>body{background:#0f172a;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;} .box{background:#1e293b;padding:30px;border-radius:12px;width:90%;max-width:320px;box-shadow:0 10px 25px rgba(0,0,0,0.3);position:relative;} input{width:100%;padding:12px;margin:10px 0;background:#0f172a;border:1px solid #334155;color:#fff;border-radius:8px;box-sizing:border-box;font-size:16px;} button{width:100%;padding:12px;background:#38bdf8;border:none;border-radius:8px;font-weight:bold;cursor:pointer;margin-top:10px;} .lang-switch{position:absolute;top:10px;right:10px;text-decoration:none;color:#38bdf8;font-size:0.8rem;font-weight:bold;}</style></head><body><div class="box"><a href="?lang='.($lang=='de'?'en':'de').'" class="lang-switch">🌐 '.$t['lang_btn'].'</a><h2>'.$t['setup_title'].'</h2><p style="color:#94a3b8;font-size:0.9rem;">'.$t['setup_desc'].'</p><form method="POST"><input type="text" name="set_user" placeholder="'.$t['user'].'" required><input type="password" name="set_pass" placeholder="'.$t['pass'].'" required><button type="submit">'.$t['create'].'</button></form></div></body></html>';
    exit;
}

$user_data = json_decode(file_get_contents($configFile), true);

// --- LOGIN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_user'], $_POST['login_pass'])) {
    if ($_POST['login_user'] === $user_data['name'] && password_verify($_POST['login_pass'], $user_data['pass'])) {
        session_regenerate_id(true); $_SESSION['logged_in'] = true; $_SESSION['last_activity'] = time();
        header("Location: index.php"); exit;
    } else { $error = $t['error']; }
}

if (isset($_GET['logout'])) { session_destroy(); header("Location: index.php"); exit; }

if (!isset($_SESSION['logged_in'])) {
    echo '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1.0"><style>body{background:#0f172a;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;} .box{background:#1e293b;padding:30px;border-radius:12px;width:90%;max-width:320px;box-shadow:0 10px 25px rgba(0,0,0,0.3);text-align:center;position:relative;} input{width:100%;padding:12px;margin:10px 0;background:#0f172a;border:1px solid #334155;color:#fff;border-radius:8px;box-sizing:border-box;font-size:16px;} button{width:100%;padding:12px;background:#38bdf8;border:none;border-radius:8px;font-weight:bold;cursor:pointer;margin-top:10px;} .lang-switch{position:absolute;top:10px;right:10px;text-decoration:none;color:#38bdf8;font-size:0.8rem;font-weight:bold;}</style></head><body><div class="box"><a href="?lang='.($lang=='de'?'en':'de').'" class="lang-switch">🌐 '.$t['lang_btn'].'</a><h2>🎣 '.$t['title'].' Login</h2>';
    if(isset($error)) echo '<p style="color:#f87171;">'.$error.'</p>';
    echo '<form method="POST"><input type="text" name="login_user" placeholder="'.$t['user'].'" required><input type="password" name="login_pass" placeholder="'.$t['pass'].'" required><button type="submit">'.$t['login_btn'].'</button></form></div></body></html>';
    exit;
}

// --- DB CONNECTION ---
try {
    $db = new PDO('sqlite:'.$dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("CREATE TABLE IF NOT EXISTS tackle (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, hersteller TEXT, kategorie TEXT, farbe TEXT, gewicht REAL, laenge REAL, preis REAL, menge INTEGER DEFAULT 1, bild TEXT, datum TEXT)");
} catch (Exception $e) { die("DB Error"); }

// --- AJAX API ---
if (isset($_GET['get_stats'])) {
    $kat = $_GET['get_stats'];
    $sql = ($kat === 'all') ? "SELECT SUM(menge) as n, SUM(preis*menge) as w FROM tackle" : "SELECT SUM(menge) as n, SUM(preis*menge) as w FROM tackle WHERE kategorie = ?";
    $st = $db->prepare($sql); ($kat === 'all') ? $st->execute() : $st->execute([$kat]);
    $res = $st->fetch(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode(['n' => (int)($res['n']??0), 'w' => number_format($res['w']??0, 2, '.', '')]); exit;
}

if (isset($_GET['load_more'])) {
    header('Content-Type: application/json');
    $offset = (int)($_GET['offset'] ?? 0); $kat = $_GET['filter_kat'] ?? 'all'; $search = $_GET['q'] ?? ''; $sort = $_GET['sort'] ?? 'new';
    $query = "SELECT * FROM tackle WHERE 1=1"; $params = [];
    if ($kat === 'all') { $query .= " AND kategorie NOT IN ('Angelruten', 'Rollen', 'Haken', 'Zubehör')"; } 
    else { $query .= " AND kategorie = ?"; $params[] = $kat; }
    if ($search !== '') { $query .= " AND (name LIKE ? OR hersteller LIKE ? OR farbe LIKE ?)"; $term = "%$search%"; $params=array_merge($params, [$term,$term,$term]); }
    $query .= ($sort === 'abc') ? " ORDER BY hersteller ASC, name ASC" : " ORDER BY id DESC";
    $query .= " LIMIT 16 OFFSET $offset";
    $stmt = $db->prepare($query); $stmt->execute($params);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC)); exit;
}

// --- ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) || isset($_POST['delete_id']))) {
    if (($_POST['csrf_token'] ?? '') !== $_SESSION['csrf_token']) die("CSRF mismatch");
    if (isset($_POST['delete_id'])) {
        $st = $db->prepare("SELECT bild FROM tackle WHERE id = ?"); $st->execute([$_POST['delete_id']]);
        if ($img = $st->fetchColumn()) if(file_exists("uploads/$img")) unlink("uploads/$img");
        $db->prepare("DELETE FROM tackle WHERE id = ?")->execute([$_POST['delete_id']]);
    } else {
        $bild = $_POST['current_bild'] ?? "";
        if (!empty($_FILES['bild']['name'])) {
            $ext = strtolower(pathinfo($_FILES['bild']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                if($bild && file_exists("uploads/$bild")) unlink("uploads/$bild");
                $bild = bin2hex(random_bytes(16)).'.'.$ext; if(!is_dir('uploads')) mkdir('uploads', 0755, true);
                move_uploaded_file($_FILES['bild']['tmp_name'], "uploads/$bild");
            }
        }
        $fische = isset($_POST['zielfische']) ? implode(', ', $_POST['zielfische']) : '';
        $v = [$_POST['name'], $_POST['hersteller'], $_POST['kategorie'], $_POST['farbe'], (float)$_POST['gewicht'], (float)$_POST['laenge'], (float)$_POST['preis'], (int)$_POST['menge'], $bild, $fische];
        if ($_POST['action'] == 'save') { $db->prepare("INSERT INTO tackle (name, hersteller, kategorie, farbe, gewicht, laenge, preis, menge, bild, datum) VALUES (?,?,?,?,?,?,?,?,?,?)")->execute($v); }
        else { $v[] = (int)$_POST['id']; $db->prepare("UPDATE tackle SET name=?, hersteller=?, kategorie=?, farbe=?, gewicht=?, laenge=?, preis=?, menge=?, bild=?, datum=? WHERE id=?")->execute($v); }
    }
    header("Location: index.php"); exit;
}

$detail_id = $_GET['id'] ?? null; $is_edit = isset($_GET['edit']);
$item = $detail_id ? ($db->query("SELECT * FROM tackle WHERE id = ".(int)$detail_id)->fetch()) : null;
$stats = $db->query("SELECT SUM(menge) as n, SUM(preis*menge) as w FROM tackle")->fetch();
$theme = $_SESSION['theme'] ?? 'dark';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$db_cats = $texts['de']['cats']; $fish_list = $texts[$lang]['fish'];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>TackleBox</title>
    <style>
        :root { --bg: #0f172a; --card: #1e293b; --accent: #38bdf8; --label: #94a3b8; --text: #f8fafc; }
        body.light { --bg: #f1f5f9; --card: #ffffff; --accent: #0ea5e9; --label: #64748b; --text: #1e293b; }
        body { font-family: sans-serif; background: var(--bg); color: var(--text); margin: 0; padding-bottom: 100px; }
        .container { max-width: 800px; margin: auto; padding: 10px; }
        
        /* STICKY HEADER & ADD MENU */
        .sticky-wrapper { position: sticky; top: 0; z-index: 90; background: var(--bg); padding-top: 5px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; }
        .card { background: var(--card); border-radius: 8px; overflow: hidden; text-decoration: none; color: inherit; border: 1px solid rgba(255,255,255,0.05); }
        .card-img { width: 100%; aspect-ratio: 1/1; background: #000; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .card img { width: 100%; height: 100%; object-fit: cover; }
        .card-info { padding: 8px; font-size: 0.8rem; }
        
        input, select { width: 100%; padding: 10px; background: var(--card); border: 1px solid #334155; color: var(--text); border-radius: 6px; box-sizing: border-box; font-size: 16px; }
        .btn-full { background: var(--accent); color: #000; font-weight: bold; border: none; padding: 14px; border-radius: 8px; width: 100%; cursor: pointer; margin-top: 12px; }
        
        .kat-bar-wrapper { position: fixed; bottom: 0; left: 0; right: 0; background: var(--bg); padding: 15px 0; border-top: 1px solid rgba(255,255,255,0.1); z-index: 100; }
        .kat-bar { display: flex; gap: 8px; overflow-x: auto; padding: 0 15px; scrollbar-width: none; }
        .kat-btn { background: var(--card); padding: 8px 16px; border-radius: 20px; font-size: 0.85rem; border: 1px solid #334155; white-space: nowrap; cursor: pointer; }
        .kat-btn.active { background: var(--accent); color: #000; border-color: var(--accent); }
        
        .dropdown { position: absolute; right: 0; top: 40px; background: var(--card); border: 1px solid #334155; border-radius: 8px; display: none; flex-direction: column; z-index: 200; width: 160px; }
        .dropdown a, .dropdown label { padding: 12px; color: var(--text); text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.1); font-size: 0.9rem; }
        
        .fish-selector { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 5px; }
        .fish-chip input { display: none; }
        .fish-chip span { display: block; padding: 6px 12px; background: var(--card); border: 1px solid #334155; border-radius: 15px; font-size: 0.75rem; cursor: pointer; }
        .fish-chip input:checked + span { background: var(--accent); color: #000; }
        
        #lightbox { display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); justify-content: center; align-items: center; }
        #lightbox img { max-width: 95%; max-height: 90%; }
        
        summary { cursor: pointer; font-weight: bold; color: var(--accent); padding: 5px 0; }
        details[open] { padding-bottom: 15px; }
    </style>
</head>
<body class="<?= $theme ?>">
<div id="lightbox" onclick="this.style.display='none'"><img id="lbImg"></div>
<div class="container">
    <?php if (!$item || $is_edit): ?>
        <div class="sticky-wrapper">
            <div class="header">
                <h2 style="margin:0;">🎣 <?= $t['title'] ?></h2>
                <div style="display:flex; gap:8px; align-items:center; position:relative;">
                    <select id="topSort" style="width: auto; padding: 5px;" onchange="setSort(this.value)">
                        <option value="new"><?= $t['sort_new'] ?></option>
                        <option value="abc"><?= $t['sort_abc'] ?></option>
                    </select>
                    <input type="text" id="liveSearch" style="width:80px; padding:5px;" placeholder="<?= $t['search'] ?>" onkeyup="doSearch()">
                    <button onclick="toggleDropdown(event)" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text);">⚙️</button>
                    <div class="dropdown" id="myDropdown">
                        <a href="?lang=<?= $lang=='de'?'en':'de' ?>">🌐 <?= $t['lang_btn'] ?></a>
                        <a href="?set_theme=<?= $theme=='dark'?'light':'dark' ?>">🌓 <?= $t['theme_btn'] ?></a>
                        <a href="?logout=1" style="color:#f87171;">🚪 <?= $t['logout'] ?></a>
                    </div>
                </div>
            </div>

            <div id="statsHeader" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:10px;">
                <div style="background:var(--card); padding:8px; border-radius:10px; font-size:0.85rem;"><span style="color:var(--label);"><?= $t['stats_n'] ?>:</span> <b id="stat_n"><?= (int)$stats['n'] ?></b></div>
                <div style="background:var(--card); padding:8px; border-radius:10px; text-align:right; font-size:0.85rem;"><span style="color:var(--label);"><?= $t['stats_w'] ?>:</span> <b id="stat_w" style="color:#4ade80;"><?= number_format($stats['w']??0, 2) ?> €</b></div>
            </div>

            <details id="addMenu" style="background:var(--card); padding:0 12px; border-radius:12px; border: 1px solid rgba(255,255,255,0.1); margin-bottom:10px;" <?= $is_edit ? 'open' : '' ?>>
                <summary>➕ <?= $is_edit ? $t['edit'] : $t['add'] ?></summary>
                <form method="POST" enctype="multipart/form-data" style="margin-top:10px;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="action" value="<?= $is_edit ? 'update' : 'save' ?>">
                    <?php if($is_edit): ?><input type="hidden" name="id" value="<?= $item['id'] ?>"><?php endif; ?>
                    <input type="hidden" name="current_bild" value="<?= $item['bild'] ?? '' ?>">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                        <input type="text" name="hersteller" placeholder="<?= $t['brand'] ?>" value="<?= htmlspecialchars($item['hersteller']??'') ?>" required>
                        <input type="text" name="name" placeholder="<?= $t['model'] ?>" value="<?= htmlspecialchars($item['name']??'') ?>" required>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-top:8px;">
                        <input type="text" name="farbe" placeholder="<?= $t['color'] ?>" value="<?= htmlspecialchars($item['farbe']??'') ?>">
                        <select name="kategorie">
                            <?php foreach($db_cats as $idx => $cat): ?>
                                <option value="<?= $cat ?>" <?= ($item['kategorie']??'')==$cat?'selected':'' ?>><?= $t['cats'][$idx] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:8px; margin-top:8px;">
                        <input type="number" step="0.01" name="gewicht" placeholder="g" value="<?= $item['gewicht']??'' ?>">
                        <input type="number" step="0.1" name="laenge" placeholder="cm" value="<?= $item['laenge']??'' ?>">
                        <input type="number" name="menge" placeholder="x" value="<?= $item['menge']??1 ?>">
                    </div>
                    <input type="number" step="0.01" name="preis" placeholder="€" style="margin-top:8px;" value="<?= $item['preis']??'' ?>">
                    <div class="fish-selector">
                        <?php $sel = isset($item['datum']) ? explode(', ', $item['datum']) : []; foreach($fish_list as $f): ?>
                            <label class="fish-chip"><input type="checkbox" name="zielfische[]" value="<?= $f ?>" <?= in_array($f, $sel)?'checked':'' ?>><span><?= $f ?></span></label>
                        <?php endforeach; ?>
                    </div>
                    <input type="file" name="bild" style="margin-top:10px; border:none; padding:0;">
                    <button type="submit" class="btn-full"><?= $t['save'] ?></button>
                </form>
            </details>
        </div>

        <div id="tackleGrid" class="grid"></div>
        <div id="sentinel" style="height:40px;"></div>

    <?php elseif ($item): ?>
        <div style="text-align:left;">
            <a href="index.php" style="color:var(--accent); text-decoration:none; font-weight:bold; display:block; margin-bottom:15px;"><?= $t['back'] ?></a>
            <div style="width:100%; height:300px; background:#000; border-radius:12px; overflow:hidden; display:flex; align-items:center; justify-content:center; border: 1px solid rgba(255,255,255,0.1);" onclick="showLightbox('uploads/<?= $item['bild'] ?>')">
                <?php if($item['bild']): ?><img src="uploads/<?= $item['bild'] ?>" style="width:100%; height:100%; object-fit:contain;"><?php else: ?><span style="font-size:4rem;">🎣</span><?php endif; ?>
            </div>
            <h1 style="margin:15px 0 5px 0;"><?= htmlspecialchars($item['name']) ?></h1>
            <p style="color:var(--label); margin-bottom:20px;"><?= htmlspecialchars($item['hersteller']) ?> / <?= htmlspecialchars($item['kategorie']) ?></p>
            <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:10px;">
                <div style="background:var(--card); padding:10px; border-radius:10px; text-align:center;"><b><?= (float)$item['laenge'] ?> cm</b></div>
                <div style="background:var(--card); padding:10px; border-radius:10px; text-align:center;"><b><?= (float)$item['gewicht'] ?> g</b></div>
                <div style="background:var(--card); padding:10px; border-radius:10px; text-align:center;"><b><?= number_format($item['preis'],2) ?> €</b></div>
                <div style="background:var(--card); padding:10px; border-radius:10px; text-align:center;"><b><?= (int)$item['menge'] ?> x</b></div>
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:20px;">
                <a href="?id=<?= $item['id'] ?>&edit=1" class="btn-full" style="text-align:center; text-decoration:none; margin:0;"><?= $t['edit'] ?></a>
                <form method="POST" style="margin:0;" onsubmit="return confirm('<?= $t['confirm'] ?>')">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="delete_id" value="<?= $item['id'] ?>"><button type="submit" class="btn-full" style="background:#f87171; color:white; margin:0;"><?= $t['delete'] ?></button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="kat-bar-wrapper" id="bottomNav" style="<?= ($item || $is_edit) ? 'display:none;' : '' ?>">
    <div class="kat-bar">
        <div onclick="filterKat('all')" class="kat-btn active" id="btn-all"><?= $t['all'] ?></div>
        <?php foreach($db_cats as $idx => $cat): ?>
            <div onclick="filterKat('<?= $cat ?>')" class="kat-btn" id="btn-<?= str_replace(' ', '_', $cat) ?>"><?= $t['cats'][$idx] ?></div>
        <?php endforeach; ?>
    </div>
</div>

<script>
let offset = 0, currentKat = 'all', currentSearch = '', currentSort = 'new', loading = false, allLoaded = false;

function toggleDropdown(e) { e.stopPropagation(); const d = document.getElementById("myDropdown"); d.style.display = (d.style.display==="flex")?"none":"flex"; }
window.onclick = () => document.getElementById("myDropdown").style.display = "none";

async function loadStats(k) { 
    const r = await fetch('index.php?get_stats=' + encodeURIComponent(k)); const d = await r.json(); 
    document.getElementById('stat_n').innerText = d.n; document.getElementById('stat_w').innerText = d.w + " €"; 
}

async function loadTackle(reset = false) {
    if (loading || (allLoaded && !reset)) return; loading = true;
    if (reset) { offset = 0; allLoaded = false; document.getElementById('tackleGrid').innerHTML = ''; }
    try {
        const r = await fetch(`index.php?load_more=1&offset=${offset}&filter_kat=${encodeURIComponent(currentKat)}&q=${encodeURIComponent(currentSearch)}&sort=${currentSort}`);
        const data = await r.json(); if (data.length < 16) allLoaded = true;
        const grid = document.getElementById('tackleGrid');
        data.forEach(i => {
            const card = document.createElement('a'); card.className = 'card'; card.href = '?id=' + i.id;
            card.innerHTML = `<div class="card-img">${i.bild ? `<img src="uploads/${i.bild}" loading="lazy">` : '🎣'}</div>
                <div class="card-info"><b>${i.hersteller}</b><br><span style="color:var(--label); font-size:0.75rem;">${i.name}</span></div>`;
            grid.appendChild(card);
        });
        offset += 16;
    } catch(e) {}
    loading = false;
}

function filterKat(k) {
    currentKat = k; document.querySelectorAll('.kat-btn').forEach(b => b.classList.remove('active'));
    const safeId = 'btn-' + k.replace(/\s+/g, '_'); if(document.getElementById(safeId)) document.getElementById(safeId).classList.add('active');
    loadStats(k); loadTackle(true);
}

function doSearch() { currentSearch = document.getElementById('liveSearch').value; loadTackle(true); }
function setSort(s) { currentSort = s; loadTackle(true); }
function showLightbox(src) { if(!src || src.includes('undefined')) return; document.getElementById('lbImg').src = src; document.getElementById('lightbox').style.display = 'flex'; }

const observer = new IntersectionObserver(entries => { if (entries[0].isIntersecting) loadTackle(); }, { threshold: 0.1 });
if (document.getElementById('sentinel')) observer.observe(document.getElementById('sentinel'));
if (document.getElementById('tackleGrid')) loadTackle();
</script>
</body>
</html>
