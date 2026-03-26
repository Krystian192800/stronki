<?php
session_start();


$DB_HOST = 'localhost';
$DB_NAME = 'krystian192800';
$DB_USER = 'auto_uzytkownik';
$DB_PASS = 'Koper192800';
$MAX_FILE_SIZE = 5 * 1024 * 1024;
$ALLOWED_MIME = [
    'image/jpeg'=>'jpg',
    'image/png'=>'png',
    'image/webp'=>'webp',
    'image/gif'=>'gif'
];
$UPLOAD_DIR = __DIR__.'/uploads/';


try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
    );
} catch (Throwable $e) {
    http_response_code(500);
    exit("Błąd połączenia z bazą: ".htmlspecialchars($e->getMessage()));
}


function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// sprawdzenie liczby użytkowników
$usersCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// ------------------ Rejestracja pierwszego admina ------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register' && $usersCount === 0) {
    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        $errors[] = "Błędny token CSRF.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        $errors = [];
        if ($username === '' || $email === '' || $password === '') $errors[] = "Wszystkie pola są wymagane.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Nieprawidłowy adres e-mail.";
        if ($password !== $password2) $errors[] = "Hasła się nie zgadzają.";
        if (strlen($password) < 6) $errors[] = "Hasło powinno mieć min. 6 znaków.";

        if (empty($errors)) {
            // Sprawdź czy użytkownik już istnieje
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = "Użytkownik o tej nazwie lub emailu już istnieje.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
                $_SESSION['user_id'] = (int)$pdo->lastInsertId();
                $_SESSION['username'] = $username;
                // Wygeneruj nowy token po rejestracji
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header("Location: dodaj.php");
                exit;
            }
        }
    }
}

// ------------------ Logowanie ------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login' && $usersCount > 0) {
    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        $login_error = "Błędny token CSRF.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $stmt = $pdo->prepare("SELECT id, password_hash, username FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            // Wygeneruj nowy token po logowaniu
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header("Location: dodaj.php");
            exit;
        } else {
            $login_error = "Nieprawidłowy login lub hasło.";
        }
    }
}

// ------------------ Wylogowanie ------------------
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: dodaj.php");
    exit;
}

// ------------------ Dodawanie ogłoszenia ------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add' && isLoggedIn()) {
    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        header("Location: index.php?status=error");
        exit;
    }

    $tytul = trim($_POST['tytul'] ?? '');
    $cena = (float)str_replace([' ', ','], ['', '.'], $_POST['cena'] ?? '0');
    $rok = (int)($_POST['rok'] ?? 0);
    $przebieg = (int)($_POST['przebieg'] ?? 0);
    $pojemnosc = (float)str_replace(',', '.', $_POST['pojemnosc'] ?? '0'); // Zmienione na float
    $moc = (int)($_POST['moc'] ?? 0);
    $skrzynia = ($_POST['skrzynia'] ?? 'manualna');
    $opis = trim($_POST['opis'] ?? '');
    $adres = trim($_POST['adres'] ?? '');

    $errs = [];
    if ($tytul === '' || $opis === '' || $adres === '') $errs[] = "Uzupełnij wszystkie pola tekstowe.";
    if ($cena <= 0) $errs[] = "Podaj prawidłową cenę.";
    if ($rok < 1950 || $rok > (int)date('Y') + 1) $errs[] = "Podaj prawidłowy rok.";
    if ($pojemnosc <= 0) $errs[] = "Podaj prawidłową pojemność.";
    if (!in_array($skrzynia, ['manualna', 'automatyczna'], true)) $errs[] = "Nieprawidłowa skrzynia biegów.";

    // Upload plików
    $files = $_FILES['images'] ?? null;
    $paths = [];
    if ($files && is_array($files['name'])) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $errs[] = "Błąd uploadu pliku: " . (int)$files['error'][$i];
                continue;
            }
            if ($files['size'][$i] > $MAX_FILE_SIZE) {
                $errs[] = "Plik " . htmlspecialchars($files['name'][$i]) . " jest za duży (max 5MB).";
                continue;
            }
            $tmp = $files['tmp_name'][$i];
            $mime = $finfo->file($tmp);
            if (!isset($ALLOWED_MIME[$mime])) {
                $errs[] = "Niedozwolony format pliku: " . htmlspecialchars($files['name'][$i]);
                continue;
            }
            $ext = $ALLOWED_MIME[$mime];
            if (!is_dir($UPLOAD_DIR)) {
                @mkdir($UPLOAD_DIR, 0755, true);
            }
            $name = uniqid('img_', true) . '.' . $ext;
            $dest = $UPLOAD_DIR . $name;
            if (!move_uploaded_file($tmp, $dest)) {
                $errs[] = "Nie udało się zapisać pliku " . htmlspecialchars($files['name'][$i]);
            } else {
                $paths[] = 'uploads/' . $name;
            }
        }
    }

    if (!empty($errs)) {
        $form_error = implode("<br>", array_map('htmlspecialchars', $errs));
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO cars (user_id, tytul, cena, rok, przebieg, pojemnosc, moc, skrzynia, opis, adres)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $_SESSION['user_id'], $tytul, $cena, $rok, $przebieg, $pojemnosc, $moc, $skrzynia, $opis, $adres
]);
            $carId = (int)$pdo->lastInsertId();

            if (!empty($paths)) {
                $ins = $pdo->prepare("INSERT INTO car_images (car_id, image_path, sort_order) VALUES (?, ?, ?)");
                $sort = 1;
                foreach ($paths as $p) {
                    $ins->execute([$carId, $p, $sort++]);
                }
            }

            $pdo->commit();
            header("Location: index.php?status=added");
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
            foreach ($paths as $p) {
                $fp = __DIR__ . '/' . $p;
                if (is_file($fp)) @unlink($fp);
            }
            $form_error = "Błąd zapisu: " . htmlspecialchars($e->getMessage());
        }
    }
}

// ------------------ Usuwanie ogłoszenia ------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete' && isLoggedIn()) {
    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        header("Location: index.php?status=error");
        exit;
    }
    $carId = (int)($_POST['car_id'] ?? 0);
    if ($carId > 0) {
        $st = $pdo->prepare("SELECT image_path FROM car_images WHERE car_id = ?");
        $st->execute([$carId]);
        $toDelete = $st->fetchAll(PDO::FETCH_COLUMN);

        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM car_images WHERE car_id = ?")->execute([$carId]);
            $pdo->prepare("DELETE FROM cars WHERE id = ?")->execute([$carId]);
            $pdo->commit();

            foreach ($toDelete as $p) {
                $fp = __DIR__ . '/' . $p;
                if (str_starts_with($p, 'uploads/') && is_file($fp)) {
                    @unlink($fp);
                }
            }
            header("Location: index.php?status=deleted");
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
            header("Location: index.php?status=error");
            exit;
        }
    }
}

// ------------------ Pobranie ogłoszeń ------------------
$cars = [];
if (isLoggedIn()) {
    $stmt = $pdo->query("SELECT id, tytul, cena, created_at FROM cars ORDER BY created_at DESC");
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Odśwież token na początku każdej sesji
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dodaj ogłoszenie</title>
  <link rel="stylesheet" href="csskoper.css">
</head>
<body>
<header class="topbar">
    <div class="container">
        <div class="logo">ANTruck</div>
        <nav class="nav">
            <a class="btn btn-ghost" href="index.php">Strona główna</a>
            <?php if (isLoggedIn()): ?>
                <a class="btn btn-ghost" href="dodaj.php?logout=1">Wyloguj (<?= htmlspecialchars($_SESSION['username'] ?? '') ?>)</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="container">
<?php if ($usersCount === 0): ?>
    <h1>Utwórz konto administratora</h1>
    <form class="form" action="dodaj.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <input type="hidden" name="action" value="register">
        <div class="form-row"><label>Nazwa użytkownika</label><input type="text" name="username" required></div>
        <div class="form-row"><label>E-mail</label><input type="email" name="email" required></div>
        <div class="form-row"><label>Hasło</label><input type="password" name="password" required></div>
        <div class="form-row"><label>Powtórz hasło</label><input type="password" name="password2" required></div>
        <button class="btn btn-primary">Utwórz administratora</button>
    </form>
    <?php if (!empty($errors)): ?>
        <div class="alert danger"><?= implode("<br>", array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>

<?php elseif (!isLoggedIn()): ?>
    <h1>Logowanie administratora</h1>
    <form class="form" action="dodaj.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <input type="hidden" name="action" value="login">
        <div class="form-row"><label>Nazwa użytkownika</label><input type="text" name="username" required></div>
        <div class="form-row"><label>Hasło</label><input type="password" name="password" required></div>
        <button class="btn btn-primary">Zaloguj</button>
    </form>
    <?php if (!empty($login_error)): ?>
        <div class="alert danger"><?= htmlspecialchars($login_error) ?></div>
    <?php endif; ?>

<?php else: ?>
    <h1>Dodaj ogłoszenie</h1>
    <?php if (!empty($form_error)): ?>
        <div class="alert danger"><?= $form_error ?></div>
    <?php endif; ?>

    <form class="form" action="dodaj.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <input type="hidden" name="action" value="add">

        <div class="form-row"><label>Tytuł</label><input type="text" name="tytul" required></div>
        

        <div class="form-grid-3">
            <div class="form-row"><label>Cena (zł)</label><input type="number" step="1" min="0" name="cena" required></div>
            <div class="form-row"><label>Rok</label><input type="number" min="1950" max="<?= (int)date('Y') + 1 ?>" name="rok" required></div>
            <div class="form-row"><label>Przebieg (km)</label><input type="number" min="0" name="przebieg" required></div>
        </div>

        <div class="form-grid-3">
            <div class="form-row"><label>Pojemność (l)</label><input type="number" step="0.1" min="0.1" name="pojemnosc" required></div>
            <div class="form-row"><label>Moc (KM)</label><input type="number" min="1" name="moc" required></div>
            <div class="form-row"><label>Skrzynia biegów</label>
                <select name="skrzynia" required>
                    <option value="manualna">manualna</option>
                    <option value="automatyczna">automatyczna</option>
                </select>
            </div>
        </div>

        <div class="form-row"><label>Pełny opis</label><textarea name="opis" rows="6" required></textarea></div>
        <div class="form-row"><label>Adres (lokalizacja)</label><input type="text" name="adres" required></div>
        <div class="form-row"><label>Zdjęcia</label><input type="file" name="images[]" accept="image/*" multiple></div>
        <button class="btn btn-primary">Zamieść post</button>
    </form>

    <hr>
    <h2>Twoje ogłoszenia</h2>
    <div class="list">
        <?php foreach ($cars as $c): ?>
            <div class="list-row">
                <div class="list-title">
                    <a href="auto.php?id=<?= (int)$c['id'] ?>" target="_blank"><?= htmlspecialchars($c['tytul']) ?></a>
                    <span class="muted"> • <?= number_format((float)$c['cena'], 0, ',', ' ') ?> zł</span>
                </div>
                <form method="post" onsubmit="return confirm('Na pewno usunąć to ogłoszenie?')">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="car_id" value="<?= (int)$c['id'] ?>">
                    <button class="btn btn-danger btn-sm">Usuń</button>
                </form>
            </div>
        <?php endforeach; ?>
        <?php if (empty($cars)): ?>
            <p class="muted">Brak ogłoszeń.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>
</main>

<footer class="site-footer">
    <div class="container">
        <div>Kontakt: kontakt@example.com • 509 465 286 • 692 869 364</div>
      
    </div>
</footer>
</body>
</html>
