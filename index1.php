<?php

/**
 * TACKLEBOX PRO 
 */

/* =========================
   DEBUG / FEHLER 
   ========================= */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* =========================
   SESSION & SECURITY
   ========================= */
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
session_start();

/* =========================
   LOGOUT
   ========================= */
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

/* =========================
   SPRACHE (GLOBAL)
   ========================= */
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = ($_GET['lang'] === 'en') ? 'en' : 'de';
}
$lang = $_SESSION['lang'] ?? 'de';

/* =========================
   DATEIEN / PFADE
   ========================= */
$dbFile     = 'tackle_app_db_9f2a.sqlite';
$configFile = 'config_auth.json';


/* =========================
   AUTH TEXTE
   ========================= */
$authTexts = [
    'de' => [
        'setup_title' => 'Erstes Setup',
        'setup_desc'  => 'Erstelle deinen Admin-Account:',
        'username'    => 'Benutzername',
        'password'    => 'Passwort',
        'create'      => 'Account erstellen',
        'login_title' => '🎣 TackleBox Login',
        'login_btn'   => 'Einloggen',
        'error'       => 'Falsche Daten!',
        'too_many'    => 'Zu viele Fehlversuche. Bitte Browser neu starten.',
        'lang'        => 'DE / EN'
    ],
    'en' => [
        'setup_title' => 'Initial Setup',
        'setup_desc'  => 'Create your admin account:',
        'username'    => 'Username',
        'password'    => 'Password',
        'create'      => 'Create account',
        'login_title' => '🎣 TackleBox Login',
        'login_btn'   => 'Login',
        'error'       => 'Wrong credentials!',
        'too_many'    => 'Too many attempts. Please restart your browser.',
        'lang'        => 'DE / EN'
    ]
];
$a = $authTexts[$lang];

/* =========================
   AUTH CSS 
   ========================= */
$authCss = <<<CSS
body{
    background:#0f172a;color:#fff;font-family:sans-serif;
    display:flex;justify-content:center;align-items:center;height:100vh;margin:0;
}
.box{
    background:#1e293b;padding:30px;border-radius:12px;
    width:90%;max-width:320px;
    box-shadow:0 10px 25px rgba(0,0,0,0.3);text-align:center;
}
input, button{
    width:100%;padding:12px;box-sizing:border-box;
    border-radius:8px;font-size:16px;
}
input{
    margin:10px 0;background:#0f172a;
    border:1px solid #334155;color:#fff;
}
button{
    background:#38bdf8;border:none;
    font-weight:bold;cursor:pointer;margin-top:10px;
}
CSS;

/* =========================
   AUTH PAGE RENDER
   ========================= */
function renderAuth($title, $desc, $content) {
    global $authCss, $lang, $a;
    ?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style><?= $authCss ?></style>
</head>
<body>
<div class="box">
    <h2><?= $title ?></h2>
    <?php if ($desc): ?>
        <p style="color:#94a3b8;font-size:0.9rem;"><?= $desc ?></p>
    <?php endif; ?>

    <?= $content ?>

    <a href="?lang=<?= $lang === 'de' ? 'en' : 'de' ?>"
       style="display:block;margin-top:15px;font-size:0.8rem;color:#94a3b8;text-decoration:none;">
       🌍 <?= $a['lang'] ?>
    </a>
</div>
</body>
</html>
<?php
}

/* =========================
   SETUP
   ========================= */

if (!file_exists($configFile)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_user'], $_POST['set_pass'])) {
        file_put_contents(
            $configFile,
            json_encode([
                'name' => $_POST['set_user'],
                'pass' => password_hash($_POST['set_pass'], PASSWORD_DEFAULT)
            ])
        );
        header("Location: index.php");
        exit;
    }

    renderAuth(
        $a['setup_title'],
        $a['setup_desc'],
        '
        <form method="POST">
            <input type="text" name="set_user" placeholder="'.$a['username'].'" required>
            <input type="password" name="set_pass" placeholder="'.$a['password'].'" required>
            <button type="submit">'.$a['create'].'</button>
        </form>
        '
    );
    exit;
}

/* =========================
   LOGIN
   ========================= */
$user = json_decode(file_get_contents($configFile), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_user'], $_POST['login_pass'])) {
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    if ($_SESSION['login_attempts'] > 15) die($a['too_many']);

    if ($_POST['login_user'] === $user['name']
        && password_verify($_POST['login_pass'], $user['pass'])) {

        session_regenerate_id(true);
        $_SESSION['logged_in'] = true;
        $_SESSION['login_attempts'] = 0;
        header("Location: index.php");
        exit;
    } else {
        $error = $a['error'];
    }
}

if (empty($_SESSION['logged_in'])) {
    renderAuth(
        $a['login_title'],
        null,
        (isset($error) ? '<p style="color:#f87171;">'.$error.'</p>' : '').'
        <form method="POST">
            <input type="text" name="login_user" placeholder="'.$a['username'].'" required>
            <input type="password" name="login_pass" placeholder="'.$a['password'].'" required>
            <button type="submit">'.$a['login_btn'].'</button>
        </form>
        '
    );
    exit;
}

/* =========================
   Hauptlogik
   ========================= */

header("Content-Security-Policy: default-src 'self'; img-src 'self' https: data: api.qrserver.com; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));


$theme = $_SESSION['theme'] ?? 'dark';
if (isset($_GET['set_theme'])) { $theme = $_GET['set_theme']; $_SESSION['theme'] = $theme; header("Location: index.php"); exit; }

// Listenansicht Session
if (isset($_GET['set_view'])) { $_SESSION['view_mode'] = $_GET['set_view']; header("Location: index.php"); exit; }
$view_mode = $_SESSION['view_mode'] ?? 'grid';

$texts = [
    'de' => [
        'title'=>'TackleBox',
        'search'=>'Suche...',
        'stats_n'=>'Menge',
        'stats_w'=>'Wert',
        'add'=>'Neu hinzufügen',
        'brand'=>'Marke',
        'model'=>'Modell',
        'color'=>'Farbe',
        'save'=>'Speichern',
        'weight'=>'Gewicht (gramm)',
        'length'=>'Länge (cm)',
        'qty'=>'Stück',
        'price'=>'Preis (€)',
        'lang_btn'=>'DE / EN',
        'theme_btn'=>'Dunkel / Hell',
        'logout'=>'Abmelden',
        'backup'=>'Daten Export',
        'restore'=>'Daten Import',
        'category'=>'Kategorie',
        'date'=>'Zielfische',
        'image'=>'Bild',
        'back'=>'← Zurück',
        'edit'=>'Bearbeiten',
        'delete'=>'Löschen',
        'confirm'=>'Wirklich löschen?',
        'all'=>'Alle',
        'abc_sort'=>'🔤 A-Z',
        'new_sort'=>'✨ Neu',
        'view_grid' => 'Kachelansicht',
        'view_list' => 'Listenansicht',
        'box' => 'Box',
        'no_box' => 'Keine Box',
        'print_qr' => 'QR-Code drucken',
         'box_contents' => 'Inhalt dieser Box',



        'cats' => ["Hardbaits", "Gummiköder", "Metallköder", "Angelruten", "Rollen", "Haken", "Zubehör"],
        'fish' => ["Hecht", "Zander", "Barsch", "Forelle", "Wels", "Aal", "Döbel", "Rapfen", "Karpfen", "Schleie", "Brasse", "Rotauge", "Meerforelle", "Dorsch"],

        // 👉 NEU
        'manage_boxes' => 'Boxen verwalten',
        'new_box_name' => 'Name für neue Box...',
        'create'       => 'Erstellen',
        'qr_content'   => 'QR-Code & Inhalt',
        'delete_box'   => 'Löschen',
        'remove_box'   => 'Aus Box',
    ],
    'en' => [
    'title'=>'TackleBox',
    'search'=>'Search...',
    'stats_n'=>'Qty',
    'stats_w'=>'Value',
    'add'=>'Add New',
    'brand'=>'Brand',
    'model'=>'Model',
    'color'=>'Color',
    'save'=>'Save',
    'weight'=>'Weight (gram)',
    'length'=>'Length (cm)',
    'qty'=>'Quantity',
    'price'=>'Price (€)',
    'lang_btn'=>'DE / EN',
    'theme_btn'=>'Dark / Light',
    'logout'=>'Logout',
    'backup'=>'Export Data',
    'restore'=>'Import Data',
    'category'=>'Category',
    'date'=>'Target Fish',
    'image'=>'Image',
    'back'=>'← Back',
    'edit'=>'Edit',
    'delete'=>'Delete',
    'confirm'=>'Really delete?',
    'all'=>'All',
    'abc_sort'=>'🔤 A-Z',
    'new_sort'=>'✨ New',
    'view_grid' => 'Grid View',
    'view_list' => 'List View',      
    'box_contents' => 'Contents of this box',
    'cats' => ["Hardbaits", "Softbaits", "Metal Baits", "Rods", "Reels", "Hooks", "Accessories"],
    'fish' => ["Pike", "Zander", "Perch", "Trout", "Catfish", "Eel", "Chub", "Asp", "Carp", "Tench", "Bream", "Roach", "Sea Trout", "Cod"],
'box' => 'Box',
'no_box' => 'No box',
'print_qr' => 'Print QR code',

    // 👉 NEU
    'manage_boxes' => 'Manage Boxes',
    'new_box_name' => 'Name for new box...',
    'create'       => 'Create',
    'qr_content'   => 'QR code & contents',
    'delete_box'   => 'Delete',
    'remove_box'   => 'Remove',
],
];
$t = $texts[$lang];
$db_cats = $texts['de']['cats'];

function translateFish($savedString, $texts, $lang) {
    if (empty($savedString)) return '-';
    $savedFish = explode(', ', $savedString);
    $deFish = $texts['de']['fish'];
    $currentFishList = $texts[$lang]['fish'];
    $translated = [];
    foreach ($savedFish as $fish) {
        $idx = array_search($fish, $deFish);
        if ($idx !== false && isset($currentFishList[$idx])) {
            $translated[] = $currentFishList[$idx];
        } else {
            $translated[] = $fish;
        }
    }
    return implode(', ', $translated);
}

try {
    $db = new PDO('sqlite:'.$dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("CREATE TABLE IF NOT EXISTS tackle (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, hersteller TEXT, kategorie TEXT, farbe TEXT, gewicht REAL, laenge REAL, preis REAL, menge INTEGER DEFAULT 1, bild TEXT, datum TEXT, box_id INTEGER DEFAULT 0)");
    $db->exec("CREATE TABLE IF NOT EXISTS boxes (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");
    try { $db->exec("ALTER TABLE tackle ADD COLUMN box_id INTEGER DEFAULT 0"); } catch (Exception $e) {}
} catch (Exception $e) { die("DB Error"); }


/* =========================
   Box Actions
   ========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['box_action'])) {
    if (($_POST['csrf_token'] ?? '') !== $_SESSION['csrf_token']) die("CSRF mismatch");
    if ($_POST['box_action'] === 'new' && !empty($_POST['box_name'])) {
        $db->prepare("INSERT INTO boxes (name) VALUES (?)")->execute([$_POST['box_name']]);
    } elseif ($_POST['box_action'] === 'update') {
        $db->prepare("UPDATE boxes SET name = ? WHERE id = ?")->execute([$_POST['box_name'], $_POST['box_id']]);
    } elseif ($_POST['box_action'] === 'delete') {
        $db->prepare("UPDATE tackle SET box_id = 0 WHERE box_id = ?")->execute([$_POST['box_id']]);
        $db->prepare("DELETE FROM boxes WHERE id = ?")->execute([$_POST['box_id']]);
    }
    header("Location: index.php?manage_boxes=1");
    exit();
}

/* =========================
   Api für Kategorie Stats
   ========================= */

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

/* =========================
   Backup / Restore
   ========================= */

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


function normalizeSearchTerms($search, $texts, $lang) {
    $terms = [ $search ];

    if ($lang === 'en') {
        // Fish EN → DE
        foreach ($texts['en']['fish'] as $i => $en) {
            if (stripos($search, $en) !== false) {
                $terms[] = $texts['de']['fish'][$i];
            }
        }

        // Categories EN → DE
        foreach ($texts['en']['cats'] as $i => $en) {
            if (stripos($search, $en) !== false) {
                $terms[] = $texts['de']['cats'][$i];
            }
        }
    }

    // doppelte entfernen
    return array_values(array_unique($terms));
}


/* =========================
   Suche
   ========================= */

if (isset($_GET['load_more'])) {
    header('Content-Type: application/json');

    $offset = (int)($_GET['offset'] ?? 0);
    $kat    = $_GET['filter_kat'] ?? null;
    $search = trim($_GET['q'] ?? '');
    $sort   = $_GET['sort'] ?? 'new';
    $box_id = (isset($_GET['box_id']) && $_GET['box_id'] !== '') ? (int)$_GET['box_id'] : null;

    // 🔍 Sprachübergreifende Suchbegriffe
    $searchVariants = [$search];

    if ($lang === 'en') {
        foreach ($texts['en']['fish'] as $i => $enFish) {
            if (stripos($enFish, $search) === 0) {
                $searchVariants[] = $texts['de']['fish'][$i];
            }
        }
foreach ($texts['en']['cats'] as $i => $enCat) {
    if (stripos($enCat, $search) === 0) {
        $searchVariants[] = $texts['de']['cats'][$i];
    }
}
    }

    $searchVariants = array_unique($searchVariants);

    $query  = "SELECT * FROM tackle WHERE 1=1";
    $params = [];

    if ($box_id !== null) {
        $query .= " AND box_id = ?";
        $params[] = $box_id;
    }

    if (is_array($kat) && count($kat)) {
        $placeholders = implode(',', array_fill(0, count($kat), '?'));
        $query .= " AND kategorie IN ($placeholders)";
        foreach ($kat as $k) {
            $params[] = $k;
        }
    } elseif (is_string($kat)) {
        $query .= " AND kategorie = ?";
        $params[] = $kat;
    }

    if ($search !== '') {
        $likeParts = [];
        foreach ($searchVariants as $s) {
            $likeParts[] = "(name LIKE ? OR hersteller LIKE ? OR farbe LIKE ? OR datum LIKE ?)";
            for ($i = 0; $i < 4; $i++) {
                $params[] = "%$s%";
            }
        }
        $query .= " AND (" . implode(" OR ", $likeParts) . ")";
    }

    if ($sort === 'abc') {
        $query .= " ORDER BY hersteller ASC, name ASC LIMIT 16 OFFSET $offset";
    } else {
        $query .= " ORDER BY id DESC LIMIT 16 OFFSET $offset";
    }

    $stmt = $db->prepare($query);
    $stmt->execute($params);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}




/* =========================
   Items aus Box löschen
   ========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_from_box'])) {
    if (($_POST['csrf_token'] ?? '') !== $_SESSION['csrf_token']) die("CSRF mismatch");

    $id = (int)$_POST['remove_from_box'];
    $db->prepare("UPDATE tackle SET box_id = 0 WHERE id = ?")->execute([$id]);

    header("Location: index.php?box_id=".(int)$_POST['current_box']);
    exit;
}


/* =========================
   Speichern, Editieren, löschen
   ========================= */

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
        } 

        $fische_raw = isset($_POST['zielfische']) ? $_POST['zielfische'] : [];
        $fische_de = [];
        foreach($fische_raw as $f_val) {
            $idx = array_search($f_val, $texts[$lang]['fish']);
            if($idx !== false) $fische_de[] = $texts['de']['fish'][$idx];
            else $fische_de[] = $f_val;
        }
        $fische = implode(', ', $fische_de);
        $v = [$_POST['name'], $_POST['hersteller'], $_POST['kategorie'], $_POST['farbe'], (float)$_POST['gewicht'], (float)$_POST['laenge'], (float)$_POST['preis'], (int)$_POST['menge'], $bild, $fische, (int)$_POST['box_id']];
        if ($_POST['action'] == 'save') $db->prepare("INSERT INTO tackle (name, hersteller, kategorie, farbe, gewicht, laenge, preis, menge, bild, datum, box_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)")->execute($v);
        else { $v[] = (int)$_POST['id']; $db->prepare("UPDATE tackle SET name=?, hersteller=?, kategorie=?, farbe=?, gewicht=?, laenge=?, preis=?, menge=?, bild=?, datum=?, box_id=? WHERE id=?")->execute($v); }
    }
    header("Location: index.php"); exit;
}

$detail_id = $_GET['id'] ?? null; $is_edit = isset($_GET['edit']);
$is_box_view = isset($_GET['box_id']);
$is_box_manager = isset($_GET['manage_boxes']);
$item = $detail_id ? ($db->query("SELECT * FROM tackle WHERE id = ".(int)$detail_id)->fetch()) : null;
if($is_box_view) $box_item = $db->query("SELECT * FROM boxes WHERE id = ".(int)$_GET['box_id'])->fetch();

$stats = $db->query("SELECT SUM(menge) as n, SUM(preis*menge) as w FROM tackle")->fetch();
$boxes = $db->query("SELECT * FROM boxes ORDER BY name ASC")->fetchAll();
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
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 10px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; }
        .grid.list-view { display: block; }
        .grid.list-view .card { display: flex; align-items: center; margin-bottom: 8px; height: 70px; }
        .grid.list-view .card-img { width: 80px; height: 100%; aspect-ratio: auto; flex-shrink: 0; }
        .grid.list-view .card-info { flex-grow: 1; padding: 0 12px; }
        .card { background: var(--card); border-radius: 8px; overflow: hidden; text-decoration: none; color: inherit; border: 1px solid rgba(255,255,255,0.05); }
        .card-img { width: 100%; aspect-ratio: 16/9; background: #000; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .card img { width: 100%; height: 100%; object-fit: cover; }
        .card-info { padding: 8px; font-size: 0.8rem; position: relative; }
        .form-label { display: block; color: var(--label); font-weight: bold; font-size: 0.75rem; margin: 4px 0 2px 0; }
        input, select { width: 100%; padding: 6px 10px; background: var(--card); border: 1px solid #334155; color: var(--text); border-radius: 6px; box-sizing: border-box; font-size: 15px; margin-bottom: 2px; }
        .btn-full { background: var(--accent); color: #000; font-weight: bold; border: none; padding: 10px; border-radius: 8px; width: 100%; cursor: pointer; margin-top: 10px; font-size: 0.95rem; }
        .detail-img-box { width: 100%; height: 300px; border-radius: 12px; overflow: hidden; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.1); cursor: pointer; background: transparent; }
        .detail-img-box img { width: 100%; height: 100%; object-fit: cover; }

        .detail-header { text-align: center; margin: 20px 0; }
        .detail-header h1 { display: inline-block; margin: 0; font-size: 1.8rem; }
        .detail-header h2 { display: inline-block; margin: 0 0 0 10px; color: var(--label); font-size: 1.8rem; font-weight: normal; }
        .mini-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 15px; }
        @media (max-width: 500px) { .mini-stats { grid-template-columns: repeat(2, 1fr); } }
        .mini-card { background: var(--card); padding: 12px 8px; border-radius: 10px; text-align: center; border: 1px solid rgba(255,255,255,0.05); display: flex; flex-direction: column; justify-content: center; }
        .mini-card label { display: block; color: var(--label); font-weight: bold; font-size: 0.8rem; text-transform: uppercase; margin-bottom: 5px; }
        .mini-card b { font-size: 1.1rem; color: var(--text); }
        .mini-card.price-hl b { color: #4ade80; }
        .small-txt { font-size: 0.85rem !important; }
        .action-btns { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 25px; }
        .action-btns a, .action-btns button { margin: 0 !important; width: 100%; height: 50px; display: flex; align-items: center; justify-content: center; text-decoration: none; border: none; font-weight: bold; border-radius: 8px; cursor: pointer; }
        .kat-bar-wrapper { position: fixed; bottom: 0; left: 0; right: 0; background: var(--bg); padding: 15px 0; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: center; z-index: 100; }
        .kat-bar { display: flex; gap: 8px; overflow-x: auto; padding: 0 15px; scrollbar-width: none; }
        .kat-btn { background: var(--card); color: #fff; padding: 8px 16px; border-radius: 20px; font-size: 0.85rem; border: 1px solid rgba(255,255,255,0.1); white-space: nowrap; cursor: pointer; font-weight: normal; }
        .kat-btn.active { background: var(--accent); color: #000; border-color: var(--accent); font-weight: normal; }
        .dropdown { position: absolute; right: 0; top: 40px; background: var(--card); border: 1px solid #334155; border-radius: 8px; display: none; flex-direction: column; z-index: 200; width: 200px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        .dropdown a, .dropdown label { padding: 12px; color: var(--text); text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.1); cursor: pointer; font-size: 0.9rem; display: flex; align-items: center; gap: 10px; }
        .top-sort { background: var(--card); border: 1px solid #334155; color: var(--text); border-radius: 6px; padding: 7px; font-size: 14px; cursor: pointer; height: 36px; width: auto; min-width: 80px; }
        .top-btn { background: var(--card); border: 1px solid #334155; color: var(--text); border-radius: 6px; padding: 7px 10px; font-size: 14px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-flex; align-items: center; height: 36px; box-sizing: border-box; }
        .top-search { background: var(--card); border: 1px solid #334155; color: var(--text); border-radius: 6px; padding: 7px 10px; font-size: 14px; height: 36px; width: 120px; margin: 0; }
        .fish-selector { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 3px; }
        .fish-chip { display: block; }
        .fish-chip input { display: none; }
        .fish-chip span { display: block; padding: 4px 10px; background: var(--card); border: 1px solid #334155; border-radius: 12px; font-size: 0.7rem; cursor: pointer; transition: 0.2s; }
        .fish-chip input:checked + span { background: var(--accent); color: #000; border-color: var(--accent); font-weight: bold; }
        summary { position: sticky; top: 0; z-index: 10; background: var(--bg); padding: 5px 0; }
        #lightbox { display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); justify-content: center; align-items: center; }
        #lightbox img { max-width: 95%; max-height: 95%; border-radius: 8px; }
        #lightbox .close { position: absolute; top: 20px; right: 30px; font-size: 40px; color: #fff; font-weight: bold; cursor: pointer; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 6px; }
        .full-width { grid-column: span 2; }
        
        /* STICKY CONTAINER FÜR STATS UND ADD BUTTON */
.stats-and-add { 
    display: grid;
    grid-template-columns: 1fr 1fr;   /* IMMER gleich breit */
    gap: 10px;

    align-items: center;
    margin-bottom: 15px;

    position: sticky;
    top: 5px;
    z-index: 98;
    background: var(--bg);
    padding: 10px 0;
}
.stats-box { 
    background: var(--card); 
    border-radius: 12px; 
    border: 1px solid rgba(255,255,255,0.05);

    height: 42px;
    padding: 0 12px;
    margin: 0;

    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    box-sizing: border-box;
    white-space: nowrap;
}

.stats-box span { 
    color: var(--label); 
    font-weight: bold; 
    font-size: 0.75rem; 
    text-transform: uppercase; 
    margin: 0;
    line-height: 1;
}

.stats-box b { 
    color: var(--text); 
    font-size: 0.9rem; 
    line-height: 1;
}

        
.add-main-btn { 
    background: var(--accent); 
    color: #000; 
    border: none; 
    border-radius: 12px; 

    height: 42px;              /* niedriger */
    padding: 0 12px;           /* GLEICH wie stats-box */

    font-weight: 900; 
    font-size: 0.9rem; 
    cursor: pointer; 
    box-shadow: 0 4px 12px rgba(56, 189, 248, 0.2); 
    white-space: nowrap;

    display: flex;
    align-items: center;
    justify-content: center;

    box-sizing: border-box;
}

/* ================= MOBILE ================= */
@media (max-width: 600px) {

    body {
        padding-top: 0;
    }

    .container {
        margin-top: 0;
    }

    .header {
        margin: 0;
        padding: 0;
        gap: 4px;
    }

    .app-title {
        font-size: 0;
    }

    .app-title::after {
        content: "🎣";
        font-size: 1.3rem;
    }

    .kat-bar {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 4px;
        overflow-x: hidden;
    }

    .kat-btn {
        height: 28px;
        padding: 0 2px;
        font-size: 0.62rem;
        line-height: 1;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-align: center;
        border-radius: 8px;
    }

    /* iOS Zoom verhindern */
    .top-search {
        font-size: 16px !important;
    }
}


@media print {

    /* 1️⃣ ALLES AUSBLENDEN */
    body * {
        visibility: hidden !important;
    }

    /* 2️⃣ NUR DAS QR-LABEL SICHTBAR */
    .print-label,
    .print-label * {
        visibility: visible !important;
    }

    /* 3️⃣ QR-LABEL POSITIONIEREN */
    .print-label {
        position: absolute;
        top: 20mm;
        left: 50%;
        transform: translateX(-50%);

        border: 1px solid #000;
        padding: 10px;
        width: 6cm;
        text-align: center;
        background: #fff;
    }

    /* 4️⃣ BUTTONS NIE DRUCKEN */
    .no-print {
        display: none !important;
    }
}


    </style>
</head>
<body class="<?= $theme ?>">
<div id="lightbox" onclick="this.style.display='none'"><span class="close">&times;</span><img id="lbImg"></div>
<div class="container">
    
    <?php if ($is_box_manager): ?>
        <div style="text-align:center;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <a href="index.php" style="color:var(--accent); text-decoration:none; font-weight:normal;"><?= $t['back'] ?></a>
                <h2 style="margin:0;">📦 <?= $t['manage_boxes'] ?></h2><div style="width:40px;"></div>
            </div>
            <div style="background:var(--card); padding:15px; border-radius:12px; margin-bottom:25px; border: 2px dashed #334155;">
                <form method="POST" style="display:flex; gap:10px;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="box_action" value="new">
                    <input type="text" name="box_name" placeholder="<?= $t['new_box_name'] ?>" required>

                    <button type="submit"
    style="background:var(--accent); border:none; padding:10px 15px;
           border-radius:8px; font-weight:normal; cursor:pointer; color:#000;">
    <?= $t['create'] ?>
</button>

                </form>
            </div>
            <?php foreach($boxes as $b): ?>
                <div style="background:var(--card); padding:15px; border-radius:12px; margin-bottom:15px; border:1px solid rgba(255,255,255,0.05); text-align:left;">
                    <form method="POST" style="display:flex; gap:10px; align-items:center;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="box_id" value="<?= $b['id'] ?>">
                        <input type="hidden" name="box_action" value="update">
                        <input type="text" name="box_name" value="<?= htmlspecialchars($b['name']) ?>" style="flex:1;">
                        <button type="submit" style="background:var(--accent); border:none; padding:8px 12px; border-radius:6px; font-weight:normal; cursor:pointer;">OK</button>
                    </form>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
<a href="?box_id=<?= $b['id'] ?>&lang=<?= $lang ?>"
   style="color:var(--label); font-size:0.8rem; text-decoration:none;">
    <?= $t['qr_content'] ?>
</a>
<form method="POST"
      onsubmit="return confirm('<?= $t['confirm'] ?>')"
      style="margin:0;">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="box_id" value="<?= $b['id'] ?>">
    <input type="hidden" name="box_action" value="delete">

    <button type="submit"
        style="background:none;
               border:none;
               color:#f87171;
               font-size:0.8rem;
               cursor:pointer;
               text-decoration:underline;">
        <?= $t['delete_box'] ?>
    </button>
</form>



                    </div>
                </div>
            <?php endforeach; ?>
        </div>

   <?php elseif ($is_box_view && $box_item): ?>
<div style="text-align:center;">

    <a href="?manage_boxes=1"
       style="display:block; margin-bottom:20px; color:var(--accent); text-decoration:none;">
        <?= $t['back'] ?>
    </a>

    <h2>📦 <?= htmlspecialchars($box_item['name']) ?></h2>

<?php
$qrUrl =
    (isset($_SERVER['HTTPS']) ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST']
    . $_SERVER['PHP_SELF']
    . '?box_id=' . $box_item['id'];
?>

    <!-- ✅ QR-BLOCK (NUR QR!) -->
    <div class="print-label" style="
        background:#fff;
        border-radius:12px;
        display:flex;
        flex-direction:column;
        align-items:center;
        gap:6px;
        margin-bottom:20px;
    ">
        <div style="font-size:1.2rem;font-weight:700;color:#0f172a;">
            <?= htmlspecialchars($box_item['name']) ?>
        </div>

        <img
            src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($qrUrl) ?>"
            alt="QR"
        >

        <div style="font-weight:500;letter-spacing:2px;color:#0f172a;">
            Tacklebox
        </div>
    </div>

    <!-- ✅ DRUCKEN BUTTON -->
<button
    class="no-print"
    onclick="window.print()"
    style="
        display:inline-block;
        margin-bottom:30px;
        padding:8px 16px;
        border-radius:8px;
        background:var(--accent);
        color:#000;
        font-weight:700;
        cursor:pointer;
        border:none;
    "
>
    🖨️ <?= $t['print_qr'] ?>
</button>



    <!-- ✅ AB HIER BOX-INHALT -->
    <h3><?= $t['box_contents'] ?></h3>

    <div class="grid <?= $view_mode == 'list' ? 'list-view' : '' ?>"
         id="tackleGrid"
         style="margin-top:15px;">
    </div>

</div>




    <?php elseif ($detail_id && $item && !$is_edit): ?>
        <a href="index.php" style="color:var(--accent); text-decoration:none; font-weight:bold; display:block; margin-bottom:15px;"><?= $t['back'] ?></a>
        <div class="detail-img-box" onclick="showLightbox('<?= $item['bild'] ? 'uploads/'.$item['bild'] : '' ?>')">
            <?php if($item['bild']): ?><img src="uploads/<?= $item['bild'] ?>"><?php else: ?>📷<?php endif; ?>
        </div>
        <div class="detail-header">
            <h1><?= htmlspecialchars($item['hersteller']) ?></h1>
            <h2><?= htmlspecialchars($item['name']) ?></h2>
        </div>
        <div class="mini-stats">
            <div class="mini-card"><label><?= $t['qty'] ?></label><b><?= $item['menge'] ?>x</b></div>
            <div class="mini-card price-hl"><label><?= $t['price'] ?></label><b><?= number_format($item['preis'], 2) ?> €</b></div>
            <div class="mini-card"><label><?= $t['weight'] ?></label><b><?= $item['gewicht'] ?>g</b></div>
            <div class="mini-card"><label><?= $t['length'] ?></label><b><?= $item['laenge'] ?>cm</b></div>
            <div class="mini-card"><label><?= $t['category'] ?></label><b class="small-txt"><?= $item['kategorie'] ?></b></div>
            <div class="mini-card"><label><?= $t['color'] ?></label><b class="small-txt"><?= htmlspecialchars($item['farbe'] ?: '-') ?></b></div>
            <div class="mini-card"><label>Box</label><b class="small-txt"><?php 
                $b_name = "Keine"; foreach($boxes as $bx) { if($bx['id'] == $item['box_id']) $b_name = $bx['name']; } echo htmlspecialchars($b_name); 
            ?></b></div>
            <div class="mini-card"><label><?= $t['date'] ?></label><b class="small-txt"><?= translateFish($item['datum'], $texts, $lang) ?></b></div>
        </div>
        <div class="action-btns">
            <a href="?id=<?= $item['id'] ?>&edit=1" style="background:#334155; color:#fff;"><?= $t['edit'] ?></a>
<form method="POST" onsubmit="return confirm('<?= $t['confirm'] ?>')" style="margin:0;">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="delete_id" value="<?= $item['id'] ?>">

    <button type="submit"
        style="
            background:#f87171;
            color:#fff;
            border:none;
            border-radius:8px;
            font-weight:bold;
            cursor:pointer;
        ">
        <?= $t['delete'] ?>
    </button>
</form>


    <?php elseif ($is_edit || isset($_GET['add'])): 
        $curr = $item ?? ['id'=>'','name'=>'','hersteller'=>'','kategorie'=>'','farbe'=>'','gewicht'=>'','laenge'=>'','preis'=>'','menge'=>1,'bild'=>'','datum'=>'','box_id'=>0];
        $curr_fische = explode(', ', $curr['datum']);
    ?>
        <a href="index.php" style="color:var(--accent); text-decoration:none; font-weight:bold; display:block; margin-bottom:15px;"><?= $t['back'] ?></a>
        <form method="POST" enctype="multipart/form-data" id="tackleForm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="<?= $is_edit ? 'edit' : 'save' ?>">
            <input type="hidden" name="id" value="<?= $curr['id'] ?>">
            <input type="hidden" name="current_bild" value="<?= $curr['bild'] ?>">
            

            
            

            <div class="form-grid">
                <div>
                    <label class="form-label"><?= $t['brand'] ?></label>
                    <input type="text" name="hersteller" id="f_brand" value="<?= htmlspecialchars($curr['hersteller']) ?>" required>
                </div>
                <div>
                    <label class="form-label"><?= $t['model'] ?></label>
                    <input type="text" name="name" id="f_name" value="<?= htmlspecialchars($curr['name']) ?>" required>
                </div>
                <div>
                    <label class="form-label"><?= $t['category'] ?></label>
                    <select name="kategorie">
                        <?php foreach($db_cats as $idx => $c): ?>
                        <option value="<?= $c ?>" <?= $curr['kategorie']==$c?'selected':'' ?>><?= $t['cats'][$idx] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Box</label>
                    <select name="box_id">
                        <option value="0">Keine Box</option>
                        <?php foreach($boxes as $bx): ?>
                        <option value="<?= $bx['id'] ?>" <?= $curr['box_id']==$bx['id']?'selected':'' ?>><?= htmlspecialchars($bx['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label"><?= $t['color'] ?></label>
                    <input type="text" name="farbe" value="<?= htmlspecialchars($curr['farbe']) ?>">
                </div>
                <div>
                    <label class="form-label"><?= $t['qty'] ?></label>
                    <input type="number" name="menge" value="<?= $curr['menge'] ?>" required>
                </div>
                <div>
                    <label class="form-label"><?= $t['weight'] ?></label>
                    <input type="number" step="0.01" name="gewicht" id="f_weight" value="<?= $curr['gewicht'] ?>">
                </div>
                <div>
                    <label class="form-label"><?= $t['length'] ?></label>
                    <input type="number" step="0.1" name="laenge" id="f_length" value="<?= $curr['laenge'] ?>">
                </div>
                <div class="full-width">
                    <label class="form-label"><?= $t['price'] ?></label>
                    <input type="number" step="0.01" name="preis" id="f_price" value="<?= $curr['preis'] ?>">
                </div>
                <div class="full-width">
                    <label class="form-label"><?= $t['date'] ?></label>
                    <div class="fish-selector">
                        <?php foreach($texts[$lang]['fish'] as $f): ?>
                        <label class="fish-chip">
                            <input type="checkbox" name="zielfische[]" value="<?= $f ?>" <?= in_array($texts['de']['fish'][array_search($f, $texts[$lang]['fish'])], $curr_fische)?'checked':'' ?>>
                            <span><?= $f ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="full-width">
                    <label class="form-label"><?= $t['image'] ?></label>
                    <input type="file" name="bild" accept="image/*">
                </div>
            </div>
            <button type="submit" class="btn-full"><?= $t['save'] ?></button>
        </form>

    <?php else: ?>
        <div class="header">
            <h1 class="app-title">TACKLEBOX</h1>
            <div style="display:flex; gap:8px; align-items:center; position:relative;">
                <input type="text" id="searchInput" class="top-search" placeholder="<?= $t['search'] ?>">
                <a href="?manage_boxes=1" class="top-btn" style="text-decoration:none;">
    📦 <?= $t['manage_boxes'] ?>
</a>

                <select id="sortSelect" class="top-sort">
                    <option value="new"><?= $t['new_sort'] ?></option>
                    <option value="abc"><?= $t['abc_sort'] ?></option>
                </select>
                <button class="top-btn" onclick="toggleMenu()">☰</button>
                <div class="dropdown" id="mainMenu">
                    <a href="?set_view=<?= $view_mode=='grid'?'list':'grid' ?>">🖼️ <?= $view_mode=='grid'?$t['view_list']:$t['view_grid'] ?></a>
                    <a href="?lang=<?= $lang=='de'?'en':'de' ?>">🌍 <?= $t['lang_btn'] ?></a>
                    <a href="?set_theme=<?= $theme=='dark'?'light':'dark' ?>">🌗 <?= $t['theme_btn'] ?></a>
                    <a href="?backup_export=1">💾 <?= $t['backup'] ?></a>
                    <label for="restoreInput">📂 <?= $t['restore'] ?></label>
                    <a href="?logout=1" style="color:#f87171;"><?= $t['logout'] ?></a>
                </div>
            </div>
        </div>

        <form id="restoreForm" method="POST" enctype="multipart/form-data" style="display:none;">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="file" name="restore_file" id="restoreInput" onchange="this.form.submit()">
        </form>

        <div class="stats-and-add">
            <div class="stats-box">
                <span><?= $t['stats_n'] ?></span><b id="totalN"><?= $stats['n']??0 ?></b>
                <span class="stats-sep"><?= $t['stats_w'] ?></span>
<b id="totalW"><?= number_format($stats['w']??0,2) ?></b><b> €</b>
            </div>
            <button onclick="location.href='?add=1'" class="add-main-btn">+ <?= $t['add'] ?></button>
        </div>

        <div class="grid <?= $view_mode == 'list' ? 'list-view' : '' ?>" id="tackleGrid"></div>
        <div id="loader" style="text-align:center; padding:20px; color:var(--label); font-size:0.9rem;">...</div>

        <div class="kat-bar-wrapper">
            <div class="kat-bar">
                <button class="kat-btn active" onclick="filterKat('all', this)"><?= $t['all'] ?></button>
                <?php foreach($db_cats as $idx => $c): ?>
                <button class="kat-btn" onclick="filterKat('<?= $c ?>', this)"><?= $t['cats'][$idx] ?></button>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>

const BAIT_CATEGORIES = [
    'Hardbaits',
    'Gummiköder',
    'Metallköder'
];

const TXT_REMOVE_BOX = "<?= $t['remove_box'] ?>";

    let offset = 0, loading = false, allLoaded = false, currentKat = 'all', searchQuery = '';
    const grid = document.getElementById('tackleGrid');
    const boxId = <?= $is_box_view ? (int)$_GET['box_id'] : 'null' ?>;

    async function loadItems(reset = false) {
        if (loading || (allLoaded && !reset) || !grid) return;
        loading = true;
        if (reset) { offset = 0; grid.innerHTML = ''; allLoaded = false; }
        
        const sort = document.getElementById('sortSelect')?.value || 'new';
        let url = `?load_more=1&offset=${offset}&q=${searchQuery}&sort=${sort}`;

if (searchQuery !== '') {
    // 🔍 Bei Suche: KEINE Kategorie filtern
} else if (currentKat === 'all') {
    // 🎣 Startansicht: nur Köder
    BAIT_CATEGORIES.forEach(k => {
        url += `&filter_kat[]=${encodeURIComponent(k)}`;
    });
} else {
    // 👉 Einzelne Kategorie
    url += `&filter_kat=${encodeURIComponent(currentKat)}`;
}


        if (boxId) url += `&box_id=${boxId}`;

        const res = await fetch(url);
        const items = await res.json();
        
        if (items.length < 16) allLoaded = true;
  items.forEach(item => {
    const div = document.createElement('div');
    div.className = 'card';
    div.onclick = () => location.href = `?id=${item.id}`;

    let g = parseFloat(item.gewicht);
    let l = parseFloat(item.laenge);

div.innerHTML = `
    <div class="card-img">
        ${item.bild ? `<img src="uploads/${item.bild}" loading="lazy">` : '📷'}
    </div>

    <div class="card-info">
        <div style="font-weight:bold; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
            ${item.hersteller}
        </div>

        <div style="color:var(--label); font-size:0.75rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
            ${item.name}
        </div>


<div style="display:flex; justify-content:space-between; align-items:center; margin-top:6px;">
    <div style="font-size:0.7rem; color:var(--label);">
        ${(() => {
            let parts = [];
            if (item.gewicht > 0) parts.push(item.gewicht + 'g');
            if (item.laenge > 0) parts.push(item.laenge + 'cm');
            return parts.join(' | ');
        })()}
    </div>

    <div style="font-size:0.75rem; font-weight:bold;">
        ${item.menge}x
    </div>
</div>


            ${boxId ? `
            <form method="POST" style="margin:0;" onclick="event.stopPropagation();">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="remove_from_box" value="${item.id}">
                <input type="hidden" name="current_box" value="${boxId}">
<button type="submit"
    style="background:#f87171;color:#fff;border:none;
           border-radius:6px;padding:4px 8px;
           font-size:0.65rem;cursor:pointer;">
    ${TXT_REMOVE_BOX}
</button>
            </form>
            ` : ''}
        </div>
    </div>
`;


    grid.appendChild(div);
});

offset += 16;
loading = false;
document.getElementById('loader').style.display = allLoaded ? 'none' : 'block';
}

    function filterKat(kat, btn) {
        currentKat = kat;
        document.querySelectorAll('.kat-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        updateStats(kat);
        loadItems(true);
    }

    async function updateStats(kat) {
        const res = await fetch(`?get_stats=${kat}`);
        const data = await res.json();
        const nElem = document.getElementById('totalN');
        const wElem = document.getElementById('totalW');
        if(nElem) nElem.innerText = data.n;
        if(wElem) wElem.innerText = data.w;
    }

    function toggleMenu() {
        const m = document.getElementById('mainMenu');
        m.style.display = m.style.display === 'flex' ? 'none' : 'flex';
    }

    function showLightbox(src) {
        if(!src) return;
        document.getElementById('lbImg').src = src;
        document.getElementById('lightbox').style.display = 'flex';
    }

    // Suche & Sortierung
    document.getElementById('searchInput')?.addEventListener('input', (e) => {
        searchQuery = e.target.value;
        loadItems(true);
    });
    document.getElementById('sortSelect')?.addEventListener('change', () => loadItems(true));

    window.onscroll = () => { if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 500) loadItems(); };
    window.onload = () => loadItems();
    
    // Close dropdown on click outside
    window.onclick = (e) => {
        if (!e.target.matches('.top-btn')) {
            const d = document.getElementById('mainMenu');
            if (d && d.style.display === 'flex') d.style.display = 'none';
        }
    };
</script>
</body>
</html>

	
