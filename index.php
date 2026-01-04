<?php
/**
 * TACKLEBOX PRO - FULL VERSION (GRID, FILTERS, STATS, LIGHTBOX, AUTO-SETUP)
 */

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
session_start();

$dbFile = 'tackle_app_db_9f2a.sqlite'; 
$configFile = 'config_auth.json'; 

// --- 1. SETUP / ERSTER START ---
if (!file_exists($configFile)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_user'], $_POST['set_pass'])) {
        $hash = password_hash($_POST['set_pass'], PASSWORD_DEFAULT);
        file_put_contents($configFile, json_encode(['name' => $_POST['set_user'], 'pass' => $hash]));
        header("Location: index.php"); exit;
    }
    echo '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1.0"><style>body{background:#0f172a;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;} .box{background:#1e293b;padding:30px;border-radius:12px;width:90%;max-width:320px;box-shadow:0 10px 25px rgba(0,0,0,0.3);} input{width:100%;padding:12px;margin:10px 0;background:#0f172a;border:1px solid #334155;color:#fff;border-radius:8px;box-sizing:border-box;font-size:16px;} button{width:100%;padding:12px;background:#38bdf8;border:none;border-radius:8px;font-weight:bold;cursor:pointer;margin-top:10px;}</style></head><body><div class="box"><h2>🎣 TackleBox Setup</h2><p style="color:#94a3b8;font-size:0.9rem;">Erstelle deinen Admin-Account:</p><form method="POST"><input type="text" name="set_user" placeholder="Benutzername" required><input type="password" name="set_pass" placeholder="Passwort" required><button type="submit">Account erstellen</button></form></div></body></html>';
    exit;
}

$user_data = json_decode(file_get_contents($configFile), true);

// --- 2. LOGIN LOGIK ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_user'], $_POST['login_pass'])) {
    if ($_POST['login_user'] === $user_data['name'] && password_verify($_POST['login_pass'], $user_data['pass'])) {
        $_SESSION['logged_in'] = true; header("Location: index.php"); exit;
    } else { $error = "Falsche Daten!"; }
}
if (isset($_GET['logout'])) { session_destroy(); header("Location: index.php"); exit; }
if (!isset($_SESSION['logged_in'])) {
    echo '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1.0"><style>body{background:#0f172a;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;} .box{background:#1e293b;padding:30px;border-radius:12px;width:90%;max-width:320px;box-shadow:0 10px 25px rgba(0,0,0,0.3);text-align:center;} input{width:100%;padding:12px;margin:10px 0;background:#0f172a;border:1px solid #334155;color:#fff;border-radius:8px;box-sizing:border-box;font-size:16px;} button{width:100%;padding:12px;background:#38bdf8;border:none;border-radius:8px;font-weight:bold;cursor:pointer;margin-top:10px;}</style></head><body><div class="box"><h2>🎣 Login</h2>';
    if(isset($error)) echo '<p style="color:#f87171;">'.$error.'</p>';
    echo '<form method="POST"><input type="text" name="login_user" placeholder="Benutzername" required><input type="password" name="login_pass" placeholder="Passwort" required><button type="submit">Einloggen</button></form></div></body></html>';
    exit;
}

// --- 3. DATENBANK ---
try {
    $db = new PDO('sqlite:'.$dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("CREATE TABLE IF NOT EXISTS tackle (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, hersteller TEXT, kategorie TEXT, farbe TEXT, gewicht REAL, laenge REAL, preis REAL, menge INTEGER DEFAULT 1, bild TEXT, datum TEXT)");
} catch (Exception $e) { die("Datenbankfehler."); }

// --- 4. AJAX API (Stats & Load More) ---
if (isset($_GET['get_stats'])) {
    $kat = $_GET['get_stats'];
    $sql = ($kat === 'all') ? "SELECT SUM(menge) as n, SUM(preis*menge) as w FROM tackle" : "SELECT SUM(menge) as n, SUM(preis*menge) as w FROM tackle WHERE kategorie = ?";
    $st = $db->prepare($sql);
    ($kat === 'all') ? $st->execute() : $st->execute([$kat]);
    $res = $st->fetch(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode(['n' => (int)($res['n']??0), 'w' => number_format($res['w']??0, 2, '.', '')]);
    exit;
}

if (isset($_GET['load_more'])) {
    $offset = (int)($_GET['offset'] ?? 0);
    $kat = $_GET['filter_kat'] ?? 'all';
    $search = $_GET['q'] ?? '';
    $query = "SELECT * FROM tackle WHERE 1=1";
    $params = [];
    if ($kat !== 'all') { $query .= " AND kategorie = ?"; $params[] = $kat; }
    if ($search !== '') { $query .= " AND (name LIKE ? OR hersteller LIKE ?)"; $term = "%$search%"; $params[]=$term; $params[]=$term; }
    $query .= " ORDER BY id DESC LIMIT 16 OFFSET $offset";
    $stmt = $db->prepare($query); $stmt->execute($params);
    header('Content-Type: application/json');
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// --- 5. POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'save') {
        $bild = "";
        if (!empty($_FILES['bild']['name'])) {
            if(!is_dir('uploads')) mkdir('uploads', 0755, true);
            $bild = bin2hex(random_bytes(8)).'_'.$_FILES['bild']['name'];
            move_uploaded_file($_FILES['bild']['tmp_name'], "uploads/".$bild);
        }
        $db->prepare("INSERT INTO tackle (name, hersteller, kategorie, farbe, gewicht, laenge, preis, menge, bild, datum) VALUES (?,?,?,?,?,?,?,?,?,?)")
           ->execute([$_POST['name'], $_POST['hersteller'], $_POST['kategorie'], $_POST['farbe'], $_POST['gewicht'], $_POST['laenge'], $_POST['preis'], $_POST['menge'], $bild, $_POST['fische']]);
    }
    if ($_POST['action'] == 'delete') {
        $db->prepare("DELETE FROM tackle WHERE id = ?")->execute([$_POST['id']]);
    }
    header("Location: index.php"); exit;
}

$cats = ["Gummiköder", "Hardbaits", "Angelruten", "Rollen", "Zubehör"];
$fish = ["Hecht", "Zander", "Barsch", "Forelle", "Wels"];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TackleBox Pro</title>
    <style>
        :root { --bg: #0f172a; --card: #1e293b; --accent: #38bdf8; --text: #f8fafc; --label: #94a3b8; }
        body { font-family: sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 10px 10px 80px 10px; }
        .container { max-width: 800px; margin: auto; }
        .stats-header { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 15px 0; }
        .stat-box { background: var(--card); padding: 10px; border-radius: 10px; text-align: center; border: 1px solid rgba(255,255,255,0.05); }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; }
        .card { background: var(--card); border-radius: 10px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05); cursor: pointer; }
        .card img { width: 100%; aspect-ratio: 1/1; object-fit: cover; background: #000; }
        .card-info { padding: 8px; font-size: 0.85rem; }
        .form-box { background: var(--card); padding: 15px; border-radius: 12px; margin-bottom: 20px; }
        input, select { width: 100%; padding: 12px; margin: 5px 0; background: var(--bg); border: 1px solid #334155; color: #fff; border-radius: 8px; box-sizing: border-box; font-size: 16px; }
        button { background: var(--accent); color: #000; border: none; padding: 12px; border-radius: 8px; font-weight: bold; width: 100%; cursor: pointer; }
        .kat-nav { position: fixed; bottom: 0; left: 0; right: 0; background: var(--bg); padding: 15px; border-top: 1px solid #334155; display: flex; gap: 8px; overflow-x: auto; z-index: 100; }
        .kat-btn { background: var(--card); padding: 8px 15px; border-radius: 20px; white-space: nowrap; font-size: 0.8rem; border: 1px solid #334155; }
        .kat-btn.active { background: var(--accent); color: #000; }
        #lightbox { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); z-index: 1000; justify-content: center; align-items: center; }
        #lightbox img { max-width: 95%; max-height: 90%; }
    </style>
</head>
<body>

<div id="lightbox" onclick="this.style.display='none'"><img id="lbImg"></div>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2 style="margin:0;">🎣 TackleBox</h2>
        <a href="?logout=1" style="color:#f87171; text-decoration:none; font-size:0.8rem;">Logout</a>
    </div>

    <div class="stats-header">
        <div class="stat-box"><span style="color:var(--label); font-size:0.7rem;">MENGE</span><br><b id="stat_n">0</b></div>
        <div class="stat-box"><span style="color:var(--label); font-size:0.7rem;">WERT</span><br><b id="stat_w">0.00 €</b></div>
    </div>

    <details class="form-box">
        <summary style="font-weight:bold; cursor:pointer;">➕ Neuen Köder hinzufügen</summary>
        <form method="POST" enctype="multipart/form-data" style="margin-top:15px;">
            <input type="hidden" name="action" value="save">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                <input type="text" name="hersteller" placeholder="Marke" required>
                <input type="text" name="name" placeholder="Modell" required>
            </div>
            <select name="kategorie">
                <?php foreach($cats as $c) echo "<option>$c</option>"; ?>
            </select>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px;">
                <input type="number" step="0.01" name="gewicht" placeholder="g">
                <input type="number" step="0.1" name="laenge" placeholder="cm">
                <input type="number" name="menge" value="1">
            </div>
            <input type="number" step="0.01" name="preis" placeholder="Preis €">
            <input type="text" name="farbe" placeholder="Farbe / Dekor">
            <input type="file" name="bild" accept="image/*" style="border:none;">
            <button type="submit">Speichern</button>
        </form>
    </details>

    <input type="text" id="search" placeholder="Suche..." onkeyup="doSearch()" style="margin-bottom:15px;">

    <div class="grid" id="mainGrid"></div>
    <div id="sentinel" style="height:20px;"></div>
</div>

<div class="kat-nav">
    <div class="kat-btn active" onclick="setKat('all', this)">Alle</div>
    <?php foreach($cats as $c) echo "<div class='kat-btn' onclick=\"setKat('$c', this)\">$c</div>"; ?>
</div>

<script>
let offset = 0, currentKat = 'all', currentSearch = '', loading = false, allLoaded = false;

async function loadStats() {
    const r = await fetch('index.php?get_stats=' + currentKat);
    const d = await r.json();
    document.getElementById('stat_n').innerText = d.n;
    document.getElementById('stat_w').innerText = d.w + ' €';
}

async function loadMore() {
    if (loading || allLoaded) return;
    loading = true;
    const r = await fetch(`index.php?load_more=1&offset=${offset}&filter_kat=${currentKat}&q=${currentSearch}`);
    const data = await r.json();
    if (data.length < 16) allLoaded = true;
    
    const grid = document.getElementById('mainGrid');
    data.forEach(i => {
        const div = document.createElement('div');
        div.className = 'card';
        div.onclick = () => { if(i.bild) { document.getElementById('lbImg').src='uploads/'+i.bild; document.getElementById('lightbox').style.display='flex'; } };
        div.innerHTML = `
            ${i.bild ? '<img src="uploads/'+i.bild+'" loading="lazy">' : '<div style="aspect-ratio:1/1; background:#000;"></div>'}
            <div class="card-info">
                <b>${i.hersteller}</b><br>${i.name}<br>
                <span style="color:var(--accent)">${parseFloat(i.preis).toFixed(2)} €</span>
                <form method="POST" style="margin-top:5px;"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${i.id}"><button type="submit" style="background:#f87171; padding:4px; font-size:0.6rem; color:#fff;">LÖSCHEN</button></form>
            </div>`;
        grid.appendChild(div);
    });
    offset += 16;
    loading = false;
}

function doSearch() {
    currentSearch = document.getElementById('search').value;
    resetGrid();
}

function setKat(k, btn) {
    currentKat = k;
    document.querySelectorAll('.kat-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    resetGrid();
    loadStats();
}

function resetGrid() {
    offset = 0; allLoaded = false;
    document.getElementById('mainGrid').innerHTML = '';
    loadMore();
}

const observer = new IntersectionObserver(e => { if(e[0].isIntersecting) loadMore(); });
observer.observe(document.getElementById('sentinel'));

loadStats();
loadMore();
</script>
</body>
</html>
