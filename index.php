cat << 'EOF' > /var/www/html/index.php
<?php
/**
 * TACKLEBOX PRO - Filter Update (Excluding hardware from Grid but including in Stats)
 * Plus: Clickable Detail Image for Fullscreen View
 */

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
session_start();

$dbFile = 'tackle_app_db_9f2a.sqlite'; 
$configFile = 'config_auth.json'; 

// --- 1. SESSION TIMEOUT ---
if (isset($_SESSION['logged_in'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
        session_unset();
        session_destroy();
        header("Location: index.php");
        exit;
    }
    $_SESSION['last_activity'] = time();
}

// --- 2. SETUP ---
if (!file_exists($configFile)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_user'], $_POST['set_pass'])) {
        $hash = password_hash($_POST['set_pass'], PASSWORD_DEFAULT);
        $content = json_encode(['name' => $_POST['set_user'], 'pass' => $hash]);
        file_put_contents($configFile, $content);
        header("Location: index.php");
        exit;
    }
    echo '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1.0"><style>body{background:#0f172a;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;} .box{background:#1e293b;padding:30px;border-radius:12px;width:90%;max-width:320px;box-shadow:0 10px 25px rgba(0,0,0,0.3);} input{width:100%;padding:12px;margin:10px 0;background:#0f172a;border:1px solid #334155;color:#fff;border-radius:8px;box-sizing:border-box;font-size:16px;} button{width:100%;padding:12px;background:#38bdf8;border:none;border-radius:8px;font-weight:bold;cursor:pointer;margin-top:10px;}</style></head>';
    echo '<body><div class="box"><h2>Erstes Setup</h2><p style="color:#94a3b8;font-size:0.9rem;">Erstelle deinen Admin-Account:</p><form method="POST"><input type="text" name="set_user" placeholder="Benutzername" required><input type="password" name="set_pass" placeholder="Passwort" required><button type="submit">Account erstellen</button></form></div></body></html>';
    exit;
}

$user_data = json_decode(file_get_contents($configFile), true);

// --- 3. LOGIN LOGIK ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_user'], $_POST['login_pass'])) {
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    if ($_SESSION['login_attempts'] > 15) die("Zu viele Fehlversuche. Bitte Browser neu starten.");

    if ($_POST['login_user'] === $user_data['name'] && password_verify($_POST['login_pass'], $user_data['pass'])) {
        session_regenerate_id(true);
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();
        $_SESSION['login_attempts'] = 0;
        header("Location: index.php");
        exit;
    } else {
        $error = "Falsche Daten!";
    }
}

// --- 4. LOGOUT ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1.0"><style>body{background:#0f172a;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;} .box{background:#1e293b;padding:30px;border-radius:12px;width:90%;max-width:320px;box-shadow:0 10px 25px rgba(0,0,0,0.3);text-align:center;} input{width:100%;padding:12px;margin:10px 0;background:#0f172a;border:1px solid #334155;color:#fff;border-radius:8px;box-sizing:border-box;font-size:16px;} button{width:100%;padding:12px;background:#38bdf8;border:none;border-radius:8px;font-weight:bold;cursor:pointer;margin-top:10px;}</style></head>';
    echo '<body><div class="box"><h2>🎣 TackleBox Login</h2>';
    if(isset($error)) echo '<p style="color:#f87171;">'.$error.'</p>';
    echo '<form method="POST"><input type="text" name="login_user" placeholder="Benutzername" required><input type="password" name="login_pass" placeholder="Passwort" required><button type="submit">Einloggen</button></form></div></body></html>';
    exit;
}

// --- 5. HAUPTLOGIK ---
header("Content-Security-Policy: default-src 'self'; img-src 'self' https: data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

function is_safe_url($url) {
    $p = parse_url($url);
    if (!$p || !in_array($p['scheme'], ['http', 'https'])) return false;
    $ip = gethostbyname($p['host'] ?? '');
    if (!$ip || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) return false;
    return $url;
}

$lang = $_SESSION['lang'] ?? 'de';
if (isset($_GET['lang'])) { $lang = $_GET['lang'] == 'en' ? 'en' : 'de'; $_SESSION['lang'] = $lang; }
$theme = $_SESSION['theme'] ?? 'dark';
if (isset($_GET['set_theme'])) { $theme = $_GET['set_theme']; $_SESSION['theme'] = $theme; header("Location: index.php"); exit; }

$texts = [
    'de' => [
        'title'=>'TackleBox', 'search'=>'Suche...', 'stats_n'=>'Menge', 'stats_w'=>'Wert', 
        'add'=>'Neu hinzufügen', 'brand'=>'Marke', 'model'=>'Modell', 'color'=>'Farbe', 
        'save'=>'Speichern', 'weight'=>'Gewicht (gramm)', 'length'=>'Länge (cm)', 'qty'=>'Stück', 
        'price'=>'Preis (€)', 'lang_btn'=>'EN', 'theme_btn'=>'Design', 'logout'=>'Logout', 
        'backup'=>'Export', 'restore'=>'Import', 'category'=>'Kategorie', 'date'=>'Zielfische', 
        'image'=>'Bild', 'back'=>'← Zurück', 'edit'=>'Bearbeiten', 'delete'=>'Löschen',
        'confirm'=>'Wirklich löschen?', 'all'=>'Alle',
        'cats' => ["Hardbaits", "Gummiköder", "Angelruten", "Rollen", "Haken", "Zubehör"],
        'fish' => ["Hecht", "Zander", "Barsch", "Forelle", "Wels", "Aal", "Döbel", "Rapfen", "Karpfen", "Schleie", "Brasse", "Rotauge", "Meerforelle", "Dorsch"]
    ],
    'en' => [
        'title'=>'TackleBox', 'search'=>'Search...', 'stats_n'=>'Qty', 'stats_w'=>'Value', 
        'add'=>'Add New', 'brand'=>'Brand', 'model'=>'Model', 'color'=>'Color', 
        'save'=>'Save', 'weight'=>'Weight (gram)', 'length'=>'Length (cm)', 'qty'=>'Quantity', 
        'price'=>'Price (€)', 'lang_btn'=>'DE', 'theme_btn'=>'Theme', 'logout'=>'Logout', 
        'backup'=>'Export', 'restore'=>'Import', 'category'=>'Category', 'date'=>'Target Fish', 
        'image'=>'Image', 'back'=>'← Back', 'edit'=>'Edit', 'delete'=>'Delete',
        'confirm'=>'Really delete?', 'all'=>'All',
        'cats' => ["Hardbaits", "Softbaits", "Rods", "Reels", "Hooks", "Accessories"],
        'fish' => ["Pike", "Zander", "Perch", "Trout", "Catfish", "Eel", "Chub", "Asp", "Carp", "Tench", "Bream", "Roach", "Sea Trout", "Cod"]
    ]
];
$t = $texts[$lang];
$db_cats = $texts['de']['cats'];
$fish_list = $texts[$lang]['fish'];

try {
    $db = new PDO('sqlite:'.$dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("CREATE TABLE IF NOT EXISTS tackle (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, hersteller TEXT, kategorie TEXT, farbe TEXT, gewicht REAL, laenge REAL, preis REAL, menge INTEGER DEFAULT 1, bild TEXT, datum TEXT)");
} catch (Exception $e) { die("DB Error"); }

// --- API FÜR KATEGORIE-STATS ---
if (isset($_GET['get_stats'])) {
    $kat = $_GET['get_stats'];
    if ($kat === 'all') {
        $st = $db->query("SELECT SUM(menge) as n, SUM(preis*menge) as w FROM tackle")->fetch(PDO::FETCH_ASSOC);
    } else {
        $st = $db->prepare("SELECT SUM(menge) as n, SUM(preis*menge) as w FROM tackle WHERE kategorie = ?");
        $st->execute([$kat]);
        $st = $st->fetch(PDO::FETCH_ASSOC);
    }
    header('Content-Type: application/json');
    echo json_encode(['n' => (int)($st['n']??0), 'w' => number_format($st['w']??0, 2, '.', '')]);
    exit;
}

// --- BACKUP / RESTORE ---
if (isset($_GET['backup_export'])) {
    $zip = new ZipArchive(); $zipName = 'tackle_backup.zip';
    if ($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        if (file_exists($dbFile)) $zip->addFile($dbFile, 'tackle.db');
        if (is_dir('uploads')) { foreach (glob("uploads/*") as $file) { $zip->addFile($file, 'uploads/'.basename($file)); } }
        $zip->close(); header('Content-Type: application/zip'); header('Content-Disposition: attachment; filename="'.$zipName.'"'); readfile($zipName); unlink($zipName); exit;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['restore_file'])) {
    if (($_POST['csrf_token'] ?? '') !== $_SESSION['csrf_token']) die("CSRF mismatch");
    $zip = new ZipArchive();
    if ($zip->open($_FILES['restore_file']['tmp_name']) === TRUE) {
        for($i = 0; $i < $zip->numFiles; $i++) { 
            $name = $zip->getNameIndex($i); 
            if (strpos($name, '..') !== false || strpos($name, '/') === 0) continue; 
            $extractName = ($name == 'tackle.db') ? $dbFile : $name;
            $zip->extractTo('.', $name);
            if($name == 'tackle.db' && $dbFile != 'tackle.db') { rename('tackle.db', $dbFile); }
        }
        $zip->close(); header("Location: index.php"); exit;
    }
}

if (isset($_GET['load_more'])) {
    header('Content-Type: application/json');
    $offset = (int)($_GET['offset'] ?? 0);
    $kat = $_GET['filter_kat'] ?? 'all'; $search = $_GET['q'] ?? '';
    $sort = $_GET['sort'] ?? 'new';
    $query = "SELECT * FROM tackle WHERE 1=1"; 
    $params = [];
    if ($kat === 'all') { $query .= " AND kategorie NOT IN ('Angelruten', 'Rollen', 'Haken', 'Zubehör')"; } 
    else { $query .= " AND kategorie = ?"; $params[] = $kat; }
    if ($search !== '') { $query .= " AND (name LIKE ? OR hersteller LIKE ? OR farbe LIKE ? OR datum LIKE ?)"; $term = "%$search%"; $params[]=$term;$params[]=$term;$params[]=$term;$params[]=$term; }
    if ($sort === 'abc') { $query .= " ORDER BY hersteller ASC, name ASC LIMIT 16 OFFSET $offset"; }
    else { $query .= " ORDER BY id DESC LIMIT 16 OFFSET $offset"; }
    $stmt = $db->prepare($query); $stmt->execute($params);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC)); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) || isset($_POST['delete_id']))) {
    if (($_POST['csrf_token'] ?? '') !== $_SESSION['csrf_token']) die("CSRF mismatch");
    if (isset($_POST['delete_id'])) { 
        $stmt = $db->prepare("SELECT bild FROM tackle WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $row = $stmt->fetch();
        if ($row && !empty($row['bild'])) {
            $filePath = "uploads/" . $row['bild'];
            if (file_exists($filePath)) { unlink($filePath); }
        }
        $db->prepare("DELETE FROM tackle WHERE id = ?")->execute([$_POST['delete_id']]); 
    } else {
        $bild = $_POST['current_bild'] ?? "";
        if (!empty($_FILES['bild']['name'])) {
            $ext = strtolower(pathinfo($_FILES['bild']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if(in_array($ext, $allowed)) {
                if (!empty($_POST['current_bild'])) { $oldFile = "uploads/" . $_POST['current_bild']; if (file_exists($oldFile)) unlink($oldFile); }
                $bild = bin2hex(random_bytes(16)).'.'.$ext;
                if(!is_dir('uploads')) mkdir('uploads', 0755, true); 
                move_uploaded_file($_FILES['bild']['tmp_name'], "uploads/".$bild);
            }
        } elseif (!empty($_POST['remote_img']) && is_safe_url($_POST['remote_img'])) {
            $ctx = stream_context_create(['http' => ['timeout' => 5]]);
            $data = @file_get_contents($_POST['remote_img'], false, $ctx);
            if($data && strlen($data) < 2000000) { 
                if (!empty($_POST['current_bild'])) { $oldFile = "uploads/" . $_POST['current_bild']; if (file_exists($oldFile)) unlink($oldFile); }
                $bild = bin2hex(random_bytes(16)).'.jpg'; 
                if(!is_dir('uploads')) mkdir('uploads', 0755, true);
                file_put_contents("uploads/".$bild, $data); 
            }
        }
        $fische = isset($_POST['zielfische']) ? implode(', ', $_POST['zielfische']) : '';
        $v = [$_POST['name'], $_POST['hersteller'], $_POST['kategorie'], $_POST['farbe'], (float)$_POST['gewicht'], (float)$_POST['laenge'], (float)$_POST['preis'], (int)$_POST['menge'], $bild, $fische];
        if ($_POST['action'] == 'save') $db->prepare("INSERT INTO tackle (name, hersteller, kategorie, farbe, gewicht, laenge, preis, menge, bild, datum) VALUES (?,?,?,?,?,?,?,?,?,?)")->execute($v);
        else { $v[] = (int)$_POST['id']; $db->prepare("UPDATE tackle SET name=?, hersteller=?, kategorie=?, farbe=?, gewicht=?, laenge=?, preis=?, menge=?, bild=?, datum=? WHERE id=?")->execute($v); }
    }
    header("Location: index.php"); exit;
}

$detail_id = $_GET['id'] ?? null; $is_edit = isset($_GET['edit']);
$item = $detail_id ? ($db->query("SELECT * FROM tackle WHERE id = ".(int)$detail_id)->fetch()) : null;
$stats = $db->query("SELECT SUM(menge) as n, SUM(preis*menge) as w FROM tackle")->fetch();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TackleBox</title>
    <style>
        :root { --bg: #0f172a; --card: #1e293b; --accent: #38bdf8; --label: #94a3b8; --text: #f8fafc; }
        body.light { --bg: #f1f5f9; --card: #ffffff; --accent: #0ea5e9; --label: #64748b; --text: #1e293b; }
        body { font-family: sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 5px 10px 100px 10px; }
        .container { max-width: 800px; margin: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; }
        .card { background: var(--card); border-radius: 8px; overflow: hidden; text-decoration: none; color: inherit; border: 1px solid rgba(255,255,255,0.05); }
        .card-img { width: 100%; aspect-ratio: 16/9; background: #000; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .card img { width: 100%; height: 100%; object-fit: cover; }
        .card-info { padding: 8px; font-size: 0.8rem; position: relative; }
        .form-label { display: block; color: var(--label); font-weight: bold; font-size: 0.8rem; margin: 6px 0 2px 0; }
        input, select { width: 100%; padding: 8px; background: var(--card); border: 1px solid #334155; color: var(--text); border-radius: 6px; box-sizing: border-box; font-size: 16px; }
        .btn-full { background: var(--accent); color: #000; font-weight: bold; border: none; padding: 12px; border-radius: 8px; width: 100%; cursor: pointer; margin-top: 12px; font-size: 1rem; }
        .detail-img-box { width: 100%; height: 300px; background: #000; border-radius: 12px; overflow: hidden; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.1); cursor: pointer; }
        .detail-img-box img { width: 100%; height: 100%; object-fit: cover; }
        .mini-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 10px; }
        .mini-card { background: var(--card); padding: 10px; border-radius: 10px; text-align: center; border: 1px solid rgba(255,255,255,0.05); }
        .mini-card label { display: block; color: var(--label); font-weight: bold; font-size: 0.85rem; margin-bottom: 4px; }
        .action-btns { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 25px; }
        .action-btns a, .action-btns button { margin: 0 !important; width: 100%; height: 50px; display: flex; align-items: center; justify-content: center; text-decoration: none; border: none; font-weight: bold; border-radius: 8px; cursor: pointer; }
        .kat-bar-wrapper { position: fixed; bottom: 0; left: 0; right: 0; background: var(--bg); padding: 15px 0; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: center; z-index: 100; }
        .kat-bar { display: flex; gap: 8px; overflow-x: auto; padding: 0 15px; scrollbar-width: none; }
        .kat-btn { background: var(--card); padding: 8px 16px; border-radius: 20px; font-size: 0.85rem; border: 1px solid #334155; white-space: nowrap; cursor: pointer; }
        .kat-btn.active { background: var(--accent); color: #000; }
        .dropdown { position: absolute; right: 10px; top: 40px; background: var(--card); border: 1px solid #334155; border-radius: 8px; display: none; flex-direction: column; z-index: 200; width: 150px; }
        .dropdown a, .dropdown label { padding: 10px; color: var(--text); text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.1); cursor: pointer; font-size: 0.85rem; }
        .top-sort { background: var(--card); border: 1px solid #334155; color: var(--text); border-radius: 6px; padding: 7px; font-size: 16px; cursor: pointer; }
        .fish-selector { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 5px; }
        .fish-chip { display: block; }
        .fish-chip input { display: none; }
        .fish-chip span { display: block; padding: 6px 12px; background: var(--card); border: 1px solid #334155; border-radius: 15px; font-size: 0.75rem; cursor: pointer; transition: 0.2s; }
        .fish-chip input:checked + span { background: var(--accent); color: #000; border-color: var(--accent); font-weight: bold; }
        /* Lightbox CSS */
        #lightbox { display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); justify-content: center; align-items: center; }
        #lightbox img { max-width: 95%; max-height: 95%; border-radius: 8px; }
        #lightbox .close { position: absolute; top: 20px; right: 30px; font-size: 40px; color: #fff; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body class="<?= $theme ?>">
<div id="lightbox" onclick="this.style.display='none'"><span class="close">&times;</span><img id="lbImg"></div>
<div class="container">
    <?php if (!$item || $is_edit): ?>
        <div class="header">
            <h2 style="margin:0;">🎣 <?= $t['title'] ?></h2>
            <div style="display:flex; gap:6px; align-items:center; position:relative;">
                <select id="topSort" class="top-sort" onchange="setSort(this.value)">
                    <option value="new">✨ Neu</option>
                    <option value="abc">🔤 A-Z</option>
                </select>
                <input type="text" id="liveSearch" style="width:100px;" placeholder="<?= $t['search'] ?>" onkeyup="doSearch()">
                <button id="gearBtn" onclick="toggleDropdown(event)" style="background:none; border:none; font-size:1.4rem; cursor:pointer; color:var(--text);">⚙️</button>
                <div class="dropdown" id="myDropdown">
                    <a href="?lang=<?= $lang=='de'?'en':'de' ?>">🌐 <?= $t['lang_btn'] ?></a>
                    <a href="?set_theme=<?= $theme=='dark'?'light':'dark' ?>">🌓 <?= $t['theme_btn'] ?></a>
                    <a href="?backup_export=1">📦 <?= $t['backup'] ?></a>
                    <label for="restore_input">📥 <?= $t['restore'] ?></label>
                    <a href="?logout=1" style="color:#f87171;">🚪 <?= $t['logout'] ?></a>
                    <form id="restoreForm" method="POST" enctype="multipart/form-data" style="display:none;"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="file" name="restore_file" id="restore_input" onchange="this.form.submit()"></form>
                </div>
            </div>
        </div>
        <div id="statsHeader" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:10px;">
            <div style="background:var(--card); padding:8px; border-radius:10px;"><span style="color:var(--label); font-size:0.8rem; font-weight:bold;"><?= $t['stats_n'] ?>:</span> <b id="stat_n"><?= (int)$stats['n'] ?></b></div>
            <div style="background:var(--card); padding:8px; border-radius:10px; text-align:right;"><span style="color:var(--label); font-size:0.8rem; font-weight:bold;"><?= $t['stats_w'] ?>:</span> <b id="stat_w" style="color:#4ade80;"><?= number_format($stats['w']??0, 2) ?> €</b></div>
        </div>
        <details id="addMenu" style="background:var(--card); padding:10px; border-radius:12px; margin-bottom:10px;" ontoggle="toggleGridVisibility(this)" <?= $is_edit ? 'open' : '' ?>>
            <summary style="cursor:pointer; font-weight:bold; font-size:1rem; color:var(--label);">➕ <?= $is_edit ? $t['model'] : $t['add'] ?></summary>
            <div id="preview" style="text-align:center; margin-top:8px;"></div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="action" value="<?= $is_edit ? 'update' : 'save' ?>"><input type="hidden" name="remote_img" id="f_img">
                <?php if($is_edit): ?><input type="hidden" name="id" value="<?= $item['id'] ?>"><?php endif; ?>
                <input type="hidden" name="current_bild" value="<?= $item['bild'] ?? '' ?>">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                    <div><label class="form-label"><?= $t['brand'] ?></label><input type="text" name="hersteller" id="f_h" value="<?= htmlspecialchars($item['hersteller']??'') ?>" required></div>
                    <div><label class="form-label"><?= $t['model'] ?></label><input type="text" name="name" id="f_n" value="<?= htmlspecialchars($item['name']??'') ?>" required></div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                    <div><label class="form-label"><?= $t['farbe'] ?></label><input type="text" name="farbe" value="<?= htmlspecialchars($item['farbe']??'') ?>"></div>
                    <div><label class="form-label"><?= $t['category'] ?></label><select name="kategorie"><?php foreach($db_cats as $index => $db_name): ?><option value="<?= $db_name ?>" <?= ($item['kategorie']??'')==$db_name?'selected':'' ?>><?= $t['cats'][$index] ?></option><?php endforeach; ?></select></div>
                </div>
                <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:8px;">
                    <div><label class="form-label"><?= $t['weight'] ?></label><input type="number" step="0.01" name="gewicht" id="f_g" value="<?= $item['gewicht']??'' ?>"></div>
                    <div><label class="form-label"><?= $t['length'] ?></label><input type="number" step="0.1" name="laenge" id="f_l" value="<?= $item['laenge']??'' ?>"></div>
                    <div><label class="form-label"><?= $t['qty'] ?></label><input type="number" name="menge" value="<?= $item['menge']??1 ?>"></div>
                </div>
                <div><label class="form-label"><?= $t['price'] ?></label><input type="number" step="0.01" name="preis" id="f_p" value="<?= $item['preis']??'' ?>"></div>
                <div>
                    <label class="form-label"><?= $t['date'] ?></label>
                    <div class="fish-selector">
                        <?php 
                        $selected_fish = isset($item['datum']) ? explode(', ', $item['datum']) : [];
                        foreach($fish_list as $f): ?>
                            <label class="fish-chip">
                                <input type="checkbox" name="zielfische[]" value="<?= $f ?>" <?= in_array($f, $selected_fish)?'checked':'' ?>>
                                <span><?= $f ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div style="margin-top:10px;"><label class="form-label" style="display:inline-block; margin-right:10px;"><?= $t['image'] ?></label><input type="file" name="bild" style="border:none; width:auto; font-size:0.8rem; padding:0;"></div>
                <button type="submit" class="btn-full"><?= $t['save'] ?></button>
            </form>
        </details>
        <div id="mainGridContainer"><div class="grid" id="tackleGrid"></div><div id="sentinel" style="height:30px;"></div></div>
    <?php elseif ($item): ?>
        <div style="text-align:center;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;"><a href="index.php" style="color:var(--accent); text-decoration:none; font-weight:bold;"><?= $t['back'] ?></a><h2 style="margin:0;"><?= htmlspecialchars($item['hersteller']) ?></h2><div style="width:40px;"></div></div>
            <div class="detail-img-box" onclick="showLightbox('uploads/<?= $item['bild'] ?>')"><?php if($item['bild']): ?><img src="uploads/<?= $item['bild'] ?>"><?php else: ?>🎣<?php endif; ?></div>
            <h1 style="margin:15px 0; font-size:1.6rem;"><?= htmlspecialchars($item['name']) ?></h1>
            <div class="mini-stats">
                <div class="mini-card"><label><?= $t['farbe'] ?></label><b><?= htmlspecialchars($item['farbe'] ?: '-') ?></b></div>
                <div class="mini-card"><label><?= $t['length'] ?></label><b><?= (float)$item['laenge'] ?> cm</b></div>
                <div class="mini-card"><label><?= $t['weight'] ?></label><b><?= (float)$item['gewicht'] ?> g</b></div>
                <div class="mini-card"><label><?= $t['price'] ?></label><b><?= number_format($item['preis'],2) ?> €</b></div>
                <div class="mini-card"><label><?= $t['qty'] ?></label><b><?= (int)$item['menge'] ?></b></div>
                <div class="mini-card"><label><?= $t['category'] ?></label><b><?php $idx = array_search($item['kategorie'], $db_cats); echo $t['cats'][$idx !== false ? $idx : 0]; ?></b></div>
            </div>
            <div class="mini-card" style="margin-top:10px; width:100%; box-sizing:border-box;"><label><?= $t['date'] ?></label><b style="color:var(--accent);"><?= htmlspecialchars($item['datum'] ?: '-') ?></b></div>
            <div class="action-btns"><a href="?id=<?= $item['id'] ?>&edit=1" style="background:var(--accent); color:#000;"><?= $t['edit'] ?></a><form method="POST" style="margin:0;"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="delete_id" value="<?= $item['id'] ?>"><button type="submit" style="background:#f87171; color:#fff;" onclick="return confirm('<?= $t['confirm'] ?>')"><?= $t['delete'] ?></button></form></div>
        </div>
    <?php endif; ?>
</div>
<div class="kat-bar-wrapper" id="bottomNav" style="<?= ($item || $is_edit) ? 'display:none;' : '' ?>">
    <div class="kat-bar"><div onclick="filterKat('all')" class="kat-btn active" id="btn-all"><?= $t['all'] ?></div><?php foreach($db_cats as $index => $db_name): ?><div onclick="filterKat('<?= $db_name ?>')" class="kat-btn" id="btn-<?= $db_name ?>"><?= $t['cats'][$index] ?></div><?php endforeach; ?></div>
</div>
<script>
function escapeHTML(str) { if(!str) return ''; return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
let offset = 0, currentKat = 'all', currentSearch = '', currentSort = 'new', loading = false, allLoaded = false;

function toggleDropdown(e) { 
    e.stopPropagation();
    let d = document.getElementById("myDropdown"); 
    d.style.display = (d.style.display==="flex")?"none":"flex"; 
}

window.addEventListener('click', function(e) {
    let d = document.getElementById("myDropdown");
    let btn = document.getElementById("gearBtn");
    if (d && d.style.display === "flex" && !d.contains(e.target) && e.target !== btn) {
        d.style.display = "none";
    }
});

function toggleGridVisibility(details) { const grid = document.getElementById('mainGridContainer'), nav = document.getElementById('bottomNav'), stats = document.getElementById('statsHeader'); if (details.open) { grid.style.display='none'; nav.style.display='none'; if(stats) stats.style.display='none'; } else { grid.style.display='block'; nav.style.display='flex'; if(stats) stats.style.display='grid'; } }
function showLightbox(src) { if(!src || src.endsWith('/')) return; document.getElementById('lbImg').src = src; document.getElementById('lightbox').style.display = 'flex'; }

async function loadStats(k) { 
    try { 
        const r = await fetch('index.php?get_stats=' + encodeURIComponent(k));
        const d = await r.json();
        document.getElementById('stat_n').innerText = d.n;
        document.getElementById('stat_w').innerText = d.w + ' €';
    } catch(e){} 
}

function doSearch() {
    currentSearch = document.getElementById('liveSearch').value;
    offset = 0; allLoaded = false;
    document.getElementById('tackleGrid').innerHTML = '';
    loadMore();
}

function setSort(s) {
    currentSort = s;
    offset = 0; allLoaded = false;
    document.getElementById('tackleGrid').innerHTML = '';
    loadMore();
}

function filterKat(k) {
    currentKat = k;
    offset = 0; allLoaded = false;
    document.querySelectorAll('.kat-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('btn-' + k).classList.add('active');
    document.getElementById('tackleGrid').innerHTML = '';
    loadStats(k);
    loadMore();
}

async function loadMore() {
    if (loading || allLoaded) return;
    loading = true;
    try {
        const r = await fetch(`index.php?load_more=1&offset=${offset}&filter_kat=${currentKat}&q=${encodeURIComponent(currentSearch)}&sort=${currentSort}`);
        const data = await r.json();
        if (data.length < 16) allLoaded = true;
        const grid = document.getElementById('tackleGrid');
        data.forEach(i => {
            const card = document.createElement('a');
            card.className = 'card';
            card.href = '?id=' + i.id;
            const imgHtml = i.bild ? `<img src="uploads/${i.bild}" loading="lazy">` : '<span style="font-size:2rem;">🎣</span>';
            card.innerHTML = `<div class="card-img">${imgHtml}</div><div class="card-info"><b>${escapeHTML(i.hersteller)}</b><br>${escapeHTML(i.name)}<br><span style="color:var(--accent); font-weight:bold;">${parseFloat(i.preis).toFixed(2)} €</span></div>`;
            grid.appendChild(card);
        });
        offset += 16;
    } catch(e){}
    loading = false;
}

// Infinite Scroll
const observer = new IntersectionObserver(entries => {
    if (entries[0].isIntersecting) loadMore();
}, { threshold: 0.1 });
const sentinel = document.getElementById('sentinel');
if (sentinel) observer.observe(sentinel);

// Start
if (document.getElementById('tackleGrid')) loadMore();
</script>
</body>
</html>
EOF
