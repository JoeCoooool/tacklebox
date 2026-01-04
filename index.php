<?php
/**
 * TACKLEBOX ULTIMATE - FULL VERSION
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
    echo '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1.0"><style>body{background:#0f172a;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;} .box{background:#1e293b;padding:30px;border-radius:12px;width:90%;max-width:320px;box-shadow:0 10px 25px rgba(0,0,0,0.3);} input{width:100%;padding:12px;margin:10px 0;background:#0f172a;border:1px solid #334155;color:#fff;border-radius:8px;box-sizing:border-box;font-size:16px;} button{width:100%;padding:12px;background:#38bdf8;border:none;border-radius:8px;font-weight:bold;cursor:pointer;margin-top:10px;}</style></head><body><div class="box"><h2>Erstes Setup</h2><p>Lege deinen Account fest:</p><form method="POST"><input type="text" name="set_user" placeholder="Benutzername" required><input type="password" name="set_pass" placeholder="Passwort" required><button type="submit">Account erstellen</button></form></div></body></html>';
    exit;
}

// --- 2. LOGIN LOGIK ---
$user_data = json_decode(file_get_contents($configFile), true);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_user'], $_POST['login_pass'])) {
    if ($_POST['login_user'] === $user_data['name'] && password_verify($_POST['login_pass'], $user_data['pass'])) {
        $_SESSION['logged_in'] = true; header("Location: index.php"); exit;
    } else { $error = "Falsche Daten!"; }
}
if (isset($_GET['logout'])) { session_destroy(); header("Location: index.php"); exit; }
if (!isset($_SESSION['logged_in'])) {
    echo '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1.0"><style>body{background:#0f172a;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;} .box{background:#1e293b;padding:30px;border-radius:12px;width:90%;max-width:320px;box-shadow:0 10px 25px rgba(0,0,0,0.3);text-align:center;} input{width:100%;padding:12px;margin:10px 0;background:#0f172a;border:1px solid #334155;color:#fff;border-radius:8px;box-sizing:border-box;font-size:16px;} button{width:100%;padding:12px;background:#38bdf8;border:none;border-radius:8px;font-weight:bold;cursor:pointer;margin-top:10px;}</style></head><body><div class="box"><h2>🎣 TackleBox Login</h2>';
    if(isset($error)) echo '<p style="color:red">'.$error.'</p>';
    echo '<form method="POST"><input type="text" name="login_user" placeholder="Benutzername" required><input type="password" name="login_pass" placeholder="Passwort" required><button type="submit">Einloggen</button></form></div></body></html>';
    exit;
}

// --- 3. DATENBANK & FUNKTIONEN ---
try {
    $db = new PDO('sqlite:'.$dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("CREATE TABLE IF NOT EXISTS tackle (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, hersteller TEXT, kategorie TEXT, farbe TEXT, gewicht REAL, laenge REAL, preis REAL, menge INTEGER DEFAULT 1, bild TEXT, datum TEXT)");
} catch (Exception $e) { die("Datenbankfehler."); }

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// --- 4. SPEICHERN / LÖSCHEN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'delete') {
        $db->prepare("DELETE FROM tackle WHERE id = ?")->execute([$_POST['id']]);
    } else {
        $bild = $_POST['current_bild'] ?? "";
        if (!empty($_FILES['bild']['name'])) {
            $bild = time().'_'.$_FILES['bild']['name'];
            move_uploaded_file($_FILES['bild']['tmp_name'], "uploads/".$bild);
        }
        $v = [$_POST['name'], $_POST['hersteller'], $_POST['kategorie'], $_POST['farbe'], (float)$_POST['gewicht'], (float)$_POST['laenge'], (float)$_POST['preis'], (int)$_POST['menge'], $bild, $_POST['fische'] ?? ''];
        if ($_POST['action'] == 'save') {
            $db->prepare("INSERT INTO tackle (name, hersteller, kategorie, farbe, gewicht, laenge, preis, menge, bild, datum) VALUES (?,?,?,?,?,?,?,?,?,?)")->execute($v);
        }
    }
    header("Location: index.php"); exit;
}

$stats = $db->query("SELECT SUM(menge) as n, SUM(preis*menge) as w FROM tackle")->fetch();
$items = $db->query("SELECT * FROM tackle ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TackleBox</title>
    <style>
        :root { --bg: #0f172a; --card: #1e293b; --accent: #38bdf8; --text: #f8fafc; }
        body { font-family: sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 10px; }
        .header { display: flex; justify-content: space-between; align-items: center; }
        .stats { display: flex; gap: 10px; margin: 15px 0; }
        .stat-card { background: var(--card); padding: 10px; border-radius: 8px; flex: 1; text-align: center; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; }
        .card { background: var(--card); border-radius: 8px; overflow: hidden; font-size: 0.8rem; }
        .card img { width: 100%; aspect-ratio: 1/1; object-fit: cover; background: #000; }
        .card-body { padding: 8px; }
        .form-box { background: var(--card); padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        input, select { width: 100%; padding: 10px; margin: 5px 0; background: #0f172a; border: 1px solid #334155; color: #fff; border-radius: 5px; box-sizing: border-box; }
        button { background: var(--accent); color: #000; border: none; padding: 10px; width: 100%; border-radius: 5px; font-weight: bold; cursor: pointer; }
        .logout { color: #f87171; text-decoration: none; font-size: 0.8rem; }
    </style>
</head>
<body>

<div class="header">
    <h2>🎣 TackleBox</h2>
    <a href="?logout=1" class="logout">Logout</a>
</div>

<div class="stats">
    <div class="stat-card">Menge: <br><b><?= (int)$stats['n'] ?></b></div>
    <div class="stat-card">Wert: <br><b><?= number_format($stats['w'], 2) ?> €</b></div>
</div>

<details class="form-box">
    <summary>➕ Neuen Köder hinzufügen</summary>
    <form method="POST" enctype="multipart/form-data" style="margin-top:10px;">
        <input type="hidden" name="action" value="save">
        <input type="text" name="hersteller" placeholder="Marke (z.B. Keitech)" required>
        <input type="text" name="name" placeholder="Modell (z.B. Easy Shiner)" required>
        <input type="text" name="farbe" placeholder="Farbe">
        <select name="kategorie">
            <option>Gummiköder</option><option>Hardbaits</option><option>Ruten</option><option>Rollen</option><option>Zubehör</option>
        </select>
        <input type="number" step="0.01" name="preis" placeholder="Preis (€)">
        <input type="number" name="menge" placeholder="Anzahl" value="1">
        <input type="file" name="bild" accept="image/*">
        <button type="submit">Speichern</button>
    </form>
</details>

<div class="grid">
    <?php foreach ($items as $i): ?>
    <div class="card">
        <?php if($i['bild']): ?><img src="uploads/<?= $i['bild'] ?>"><?php else: ?><div style="aspect-ratio:1/1; display:flex; align-items:center; justify-content:center;">No Image</div><?php endif; ?>
        <div class="card-body">
            <b><?= htmlspecialchars($i['hersteller']) ?></b><br>
            <?= htmlspecialchars($i['name']) ?><br>
            <span style="color:var(--accent)"><?= number_format($i['preis'], 2) ?> €</span>
            <form method="POST" style="margin-top:5px;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $i['id'] ?>">
                <button type="submit" style="background:#f87171; padding:3px; font-size:0.7rem;">Löschen</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>

</body>
</html>
