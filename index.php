<?php
/**
 * TACKLEBOX PRO - Proxmox Version
 * KOMPLETT OHNE SCAN-FUNKTION
 */

// --- 1. PFAD & ORDNER SETUP ---
$basePath = __DIR__ . '/';
$uploadDir = $basePath . 'uploads/';
$dbFile = $basePath . 'tackle_app_db_9f2a.sqlite';
$configFile = $basePath . 'config_auth.json';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// --- 2. SESSION & SECURITY ---
session_start();
if (isset($_SESSION['logged_in'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
        session_unset(); session_destroy(); header("Location: index.php"); exit;
    }
    $_SESSION['last_activity'] = time();
}

// --- 3. ERST-SETUP / LOGIN ---
if (!file_exists($configFile)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_user'], $_POST['set_pass'])) {
        $hash = password_hash($_POST['set_pass'], PASSWORD_DEFAULT);
        file_put_contents($configFile, json_encode(['name' => $_POST['set_user'], 'pass' => $hash]));
        header("Location: index.php"); exit;
    }
    echo '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1.0"><style>body{background:#0f172a;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;} .box{background:#1e293b;padding:30px;border-radius:12px;width:90%;max-width:320px;box-shadow:0 10px 25px rgba(0,0,0,0.3);} input{width:100%;padding:12px;margin:10px 0;background:#0f172a;border:1px solid #334155;color:#fff;border-radius:8px;box-sizing:border-box;font-size:16px;} button{width:100%;padding:12px;background:#38bdf8;border:none;border-radius:8px;font-weight:bold;cursor:pointer;margin-top:10px;}</style></head><body><div class="box"><h2>Erstes Setup</h2><form method="POST"><input type="text" name="set_user" placeholder="Benutzername" required><input type="password" name="set_pass" placeholder="Passwort" required><button type="submit">Account erstellen</button></form></div></body></html>';
    exit;
}

$user_data = json_decode(file_get_contents($configFile), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_user'], $_POST['login_pass'])) {
    if ($_POST['login_user'] === $user_data['name'] && password_verify($_POST['login_pass'], $user_data['pass'])) {
        session_regenerate_id(true); $_SESSION['logged_in'] = true; header("Location: index.php"); exit;
    } else { $error = "Falsche Daten!"; }
}

if (isset($_GET['logout'])) { session_destroy(); header("Location: index.php"); exit; }
if (!isset($_SESSION['logged_in'])) {
    echo '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1.0"><style>body{background:#0f172a;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;} .box{background:#1e293b;padding:30px;border-radius:12px;width:90%;max-width:320px;text-align:center;} input{width:100%;padding:12px;margin:10px 0;background:#0f172a;border:1px solid #334155;color:#fff;border-radius:8px;box-sizing:border-box;} button{width:100%;padding:12px;background:#38bdf8;border:none;border-radius:8px;font-weight:bold;cursor:pointer;}</style></head><body><div class="box"><h2>🎣 TackleBox Login</h2><form method="POST"><input type="text" name="login_user" placeholder="Benutzer" required><input type="password" name="login_pass" placeholder="Passwort" required><button type="submit">Einloggen</button></form></div></body></html>';
    exit;
}

// --- 4. DATENBANK ---
$db = new PDO('sqlite:'.$dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS tackle (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, hersteller TEXT, kategorie TEXT, farbe TEXT, gewicht REAL, laenge REAL, preis REAL, menge INTEGER DEFAULT 1, bild TEXT, datum TEXT)");

// --- 5. AKTIONEN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tackle'])) {
    $bildName = "";
    if (!empty($_FILES['bild']['name'])) {
        $ext = pathinfo($_FILES['bild']['name'], PATHINFO_EXTENSION);
        $bildName = time() . '_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['bild']['tmp_name'], $uploadDir . $bildName);
    }
    $stmt = $db->prepare("INSERT INTO tackle (name, hersteller, kategorie, farbe, gewicht, laenge, preis, menge, bild, datum) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['name'], $_POST['hersteller'], $_POST['kategorie'], $_POST['farbe'], $_POST['gewicht'], $_POST['laenge'], $_POST['preis'], $_POST['menge'], $bildName, date('d.m.Y')]);
    header("Location: index.php"); exit;
}

if (isset($_GET['delete'])) {
    $stmt = $db->prepare("SELECT bild FROM tackle WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $row = $stmt->fetch();
    if ($row && $row['bild'] && file_exists($uploadDir . $row['bild'])) unlink($uploadDir . $row['bild']);
    $stmt = $db->prepare("DELETE FROM tackle WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: index.php"); exit;
}

$items = $db->query("SELECT * FROM tackle ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TackleBox Pro</title>
    <style>
        :root { --bg: #0f172a; --card: #1e293b; --accent: #38bdf8; --text: #f1f5f9; }
        body { background: var(--bg); color: var(--text); font-family: sans-serif; margin: 0; padding: 15px; }
        .container { max-width: 900px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card { background: var(--card); padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        input { width: 100%; padding: 12px; margin: 8px 0; background: #0f172a; border: 1px solid #334155; color: #fff; border-radius: 8px; box-sizing: border-box; }
        button { background: var(--accent); color: #000; border: none; padding: 14px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; font-size: 16px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; }
        .tackle-card { background: var(--card); border-radius: 12px; overflow: hidden; border: 1px solid #334155; }
        .tackle-card img { width: 100%; height: 180px; object-fit: cover; }
        .info { padding: 12px; }
        .price { color: var(--accent); font-size: 1.2rem; font-weight: bold; }
        .delete-btn { color: #f87171; text-decoration: none; font-size: 0.85rem; display: block; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎣 TackleBox Pro</h1>
            <a href="?logout=1" style="color: #94a3b8; text-decoration: none;">Abmelden</a>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Neuer Köder</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="text" name="name" placeholder="Name (z.B. Zanderkant Kauli)" required>
                <div style="display: flex; gap: 8px;">
                    <input type="text" name="hersteller" placeholder="Hersteller">
                    <input type="text" name="kategorie" placeholder="Kategorie">
                </div>
                <div style="display: flex; gap: 8px;">
                    <input type="text" name="farbe" placeholder="Farbe">
                    <input type="number" step="0.01" name="preis" placeholder="Preis €">
                </div>
                <div style="display: flex; gap: 8px;">
                    <input type="number" step="0.1" name="gewicht" placeholder="Gewicht g">
                    <input type="number" step="0.1" name="laenge" placeholder="Länge cm">
                </div>
                <input type="number" name="menge" value="1" placeholder="Menge">
                <input type="file" name="bild" accept="image/*" style="border:none; padding-left:0;">
                <button type="submit" name="add_tackle">Speichern</button>
            </form>
        </div>

        <div class="grid">
            <?php foreach ($items as $item): ?>
                <div class="tackle-card">
                    <?php if($item['bild']): ?>
                        <img src="uploads/<?php echo $item['bild']; ?>" alt="Köder">
                    <?php endif; ?>
                    <div class="info">
                        <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                        <span style="font-size: 0.9rem; color: #94a3b8;">
                            <?php echo htmlspecialchars($item['hersteller']); ?> | <?php echo htmlspecialchars($item['kategorie']); ?>
                        </span><br>
                        <div class="price"><?php echo number_format($item['preis'], 2, ',', '.'); ?> €</div>
                        <a href="?delete=<?php echo $item['id']; ?>" class="delete-btn" onclick="return confirm('Wirklich löschen?')">🗑 Löschen</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
