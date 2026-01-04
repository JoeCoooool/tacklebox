<?php
/**
 * TACKLEBOX PRO - ULTIMATE MOBILE VERSION
 * Mit Kamera-Support, Auto-Resize & Proxmox-Optimierung
 */

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
session_start();

// --- PFAD SETUP ---
$basePath = __DIR__ . '/';
$uploadDir = $basePath . 'uploads/';
$dbFile = $basePath . 'tackle_app_db_9f2a.sqlite'; 
$configFile = $basePath . 'config_auth.json'; 

if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

// --- 1. SETUP & LOGIN ---
if (!file_exists($configFile)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_user'], $_POST['set_pass'])) {
        $hash = password_hash($_POST['set_pass'], PASSWORD_DEFAULT);
        file_put_contents($configFile, json_encode(['name' => $_POST['set_user'], 'pass' => $hash]));
        header("Location: index.php"); exit;
    }
    echo '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1.0"><style>body{background:#0f172a;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;} .box{background:#1e293b;padding:30px;border-radius:12px;width:90%;max-width:320px;} input{width:100%;padding:12px;margin:10px 0;background:#0f172a;border:1px solid #334155;color:#fff;border-radius:8px;box-sizing:border-box;} button{width:100%;padding:12px;background:#38bdf8;border:none;border-radius:8px;font-weight:bold;cursor:pointer;}</style></head><body><div class="box"><h2>Erstes Setup</h2><form method="POST"><input type="text" name="set_user" placeholder="Benutzername" required><input type="password" name="set_pass" placeholder="Passwort" required><button type="submit">Account erstellen</button></form></div></body></html>';
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
    echo '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1.0"><style>body{background:#0f172a;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;} .box{background:#1e293b;padding:30px;border-radius:12px;width:90%;max-width:320px;text-align:center;} input{width:100%;padding:12px;margin:10px 0;background:#0f172a;border:1px solid #334155;color:#fff;border-radius:8px;box-sizing:border-box;} button{width:100%;padding:12px;background:#38bdf8;border:none;border-radius:8px;font-weight:bold;cursor:pointer;}</style></head><body><div class="box"><h2>🎣 TackleBox</h2><form method="POST"><input type="text" name="login_user" placeholder="Benutzer" required><input type="password" name="login_pass" placeholder="Passwort" required><button type="submit">Einloggen</button></form></div></body></html>';
    exit;
}

// --- 2. BILDVERKLEINERUNGS-FUNKTION ---
function processImage($source, $target, $maxWidth = 1000) {
    list($w, $h, $type) = getimagesize($source);
    $ratio = $w / $h;
    $newW = min($w, $maxWidth);
    $newH = $newW / $ratio;
    $thumb = imagecreatetruecolor($newW, $newH);
    switch($type) {
        case IMAGETYPE_JPEG: $src = imagecreatefromjpeg($source); break;
        case IMAGETYPE_PNG:  $src = imagecreatefrompng($source); break;
        case IMAGETYPE_WEBP: $src = imagecreatefromwebp($source); break;
        default: return move_uploaded_file($source, $target);
    }
    imagecopyresampled($thumb, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
    imagejpeg($thumb, $target, 75);
    imagedestroy($thumb); imagedestroy($src);
    return true;
}

// --- 3. DATENBANK & AKTIONEN ---
$db = new PDO('sqlite:'.$dbFile);
$db->exec("CREATE TABLE IF NOT EXISTS tackle (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, hersteller TEXT, kategorie TEXT, farbe TEXT, gewicht REAL, laenge REAL, preis REAL, menge INTEGER DEFAULT 1, bild TEXT, datum TEXT)");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tackle'])) {
    $bildName = "";
    if (!empty($_FILES['bild']['name'])) {
        $bildName = time() . '.jpg';
        processImage($_FILES['bild']['tmp_name'], $uploadDir . $bildName);
    }
    $fische = isset($_POST['fische']) ? implode(', ', $_POST['fische']) : '';
    $stmt = $db->prepare("INSERT INTO tackle (name, hersteller, kategorie, farbe, gewicht, laenge, preis, menge, bild, datum) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['name'], $_POST['hersteller'], $_POST['kategorie'], $_POST['farbe'], $_POST['gewicht'], $_POST['laenge'], $_POST['preis'], $_POST['menge'], $bildName, $fische]);
    header("Location: index.php"); exit;
}

if (isset($_GET['delete'])) {
    $stmt = $db->prepare("SELECT bild FROM tackle WHERE id = ?"); $stmt->execute([$_GET['delete']]);
    $row = $stmt->fetch(); if ($row['bild']) @unlink($uploadDir . $row['bild']);
    $db->prepare("DELETE FROM tackle WHERE id = ?")->execute([$_GET['delete']]);
    header("Location: index.php"); exit;
}

$items = $db->query("SELECT * FROM tackle ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>TackleBox Pro</title>
    <style>
        :root { --bg: #0f172a; --card: #1e293b; --accent: #38bdf8; --text: #f1f5f9; }
        body { background: var(--bg); color: var(--text); font-family: sans-serif; margin: 0; padding: 15px; }
        .card { background: var(--card); padding: 15px; border-radius: 12px; margin-bottom: 15px; border: 1px solid #334155; }
        input, select { width: 100%; padding: 10px; margin: 5px 0; background: #0f172a; border: 1px solid #334155; color: #fff; border-radius: 8px; box-sizing: border-box; }
        .btn-camera { background: #334155; color: #fff; padding: 12px; border-radius: 8px; text-align: center; cursor: pointer; display: block; margin: 10px 0; font-weight: bold; }
        .btn-save { background: var(--accent); color: #000; border: none; padding: 15px; border-radius: 8px; font-weight: bold; width: 100%; font-size: 16px; cursor: pointer; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .tackle-card { background: var(--card); border-radius: 10px; overflow: hidden; border: 1px solid #334155; position: relative; }
        .tackle-card img { width: 100%; height: 120px; object-fit: cover; }
        .info { padding: 8px; font-size: 0.85rem; }
        .price { color: var(--accent); font-weight: bold; }
        .del { color: #f87171; text-decoration: none; font-size: 0.75rem; display: block; margin-top: 5px; }
        input[type="file"] { display: none; }
    </style>
</head>
<body>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2 style="margin:0;">🎣 TackleBox</h2>
        <a href="?logout=1" style="color: #94a3b8; text-decoration: none; font-size: 0.9rem;">Logout</a>
    </div>

    <div class="card">
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="name" placeholder="Modell / Name" required>
            <input type="text" name="hersteller" placeholder="Marke / Hersteller">
            <div style="display: flex; gap: 5px;">
                <input type="text" name="kategorie" placeholder="Kategorie">
                <input type="number" step="0.01" name="preis" placeholder="Preis €">
            </div>
            
            <label class="btn-camera">
                <input type="file" name="bild" accept="image/*" capture="environment" onchange="document.getElementById('file-status').innerText = '✓ Bild bereit'">
                📸 Foto aufnehmen
            </label>
            <div id="file-status" style="text-align:center; font-size:0.8rem; color:var(--accent); margin-bottom:10px;">Kein Bild gewählt</div>

            <button type="submit" name="add_tackle" class="btn-save">Speichern</button>
        </form>
    </div>

    <div class="grid">
        <?php foreach ($items as $item): ?>
            <div class="tackle-card">
                <?php if($item['bild']): ?>
                    <img src="uploads/<?php echo $item['bild']; ?>" alt="Tackle">
                <?php else: ?>
                    <div style="height:120px; background:#0f172a; display:flex; align-items:center; justify-content:center;">🎣</div>
                <?php endif; ?>
                <div class="info">
                    <div style="font-weight:bold; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div class="price"><?php echo number_format($item['preis'], 2); ?> €</div>
                    <a href="?delete=<?php echo $item['id']; ?>" class="del" onclick="return confirm('Löschen?')">🗑 Entfernen</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
