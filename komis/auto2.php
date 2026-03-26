<?php
session_start();

$DB_HOST = 'localhost';
$DB_NAME = 'krystian192800';
$DB_USER = 'auto_uzytkownik';
$DB_PASS = 'Koper192800';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo "Błąd połączenia z bazą: " . htmlspecialchars($e->getMessage());
    exit;
}

// Pobranie ID auta z GET
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    echo "Nie znaleziono ogłoszenia.";
    exit;
}

// Pobranie danych auta osobowego
$stmt = $pdo->prepare("SELECT * FROM cars_osobowe WHERE id = ?");
$stmt->execute([$id]);
$car = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$car) {
    http_response_code(404);
    echo "Nie znaleziono ogłoszenia.";
    exit;
}

// Pobranie wszystkich zdjęć auta osobowego
$imgStmt = $pdo->prepare("SELECT image_path FROM car_images_osobowe WHERE car_id = ? ORDER BY sort_order ASC, id ASC");
$imgStmt->execute([$id]);
$images = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

// Dane kontaktowe w stopce (stałe)
$SITE_EMAIL = 'Andrzej2004a@op.p';
$PHONE1 = '509 465 286';
$PHONE2 = '692 869 364';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($car['tytul']) ?> - szczegóły</title>
  <link rel="stylesheet" href="csskoper.css">
</head>
<body>
  <header class="topbar">
    <div class="container">
      <a href="osobowe.php" class="logo-link">
        <img src="logo.png" alt="ANTruck logo" class="logo-img">
      </a>
     
    </div>
  </header>

  <main class="container detail">
    <div class="detail-header">
      <h1><?= htmlspecialchars($car['tytul']) ?></h1>
      <div class="price-lg"><?= number_format((float)$car['cena'], 0, ',', ' ') ?> zł</div>
    </div>

    <div class="gallery">
      <?php if (!empty($images)): ?>
        <div class="slider">
          <?php foreach ($images as $idx => $img): ?>
            <img class="slide <?= $idx === 0 ? 'active' : '' ?>" src="<?= htmlspecialchars($img) ?>" alt="Zdjęcie auta">
          <?php endforeach; ?>
          <button class="nav prev" aria-label="Poprzednie">‹</button>
          <button class="nav next" aria-label="Następne">›</button>
        </div>
        <div class="thumbs">
          <?php foreach ($images as $idx => $img): ?>
            <img class="thumb <?= $idx === 0 ? 'active' : '' ?>" data-idx="<?= $idx ?>" src="<?= htmlspecialchars($img) ?>" alt="Miniaturka">
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="no-img">Brak zdjęć</div>
      <?php endif; ?>
    </div>

    <section class="details">
      <h2>Detale</h2>
      <div class="spec-grid">
        <div><span>Rok</span><strong><?= (int)$car['rok'] ?></strong></div>
        <div><span>Przebieg</span><strong><?= number_format((int)$car['przebieg'], 0, ',', ' ') ?> km</strong></div>
        <div><span>Pojemność</span><strong><?= number_format((int)$car['pojemnosc'], 0, ',', ' ') ?> cm³</strong></div>
        <div><span>Moc</span><strong><?= (int)$car['moc'] ?> KM</strong></div>
        <div><span>Skrzynia biegów</span><strong><?= htmlspecialchars($car['skrzynia']) ?></strong></div>
      </div>

      <h3>Opis</h3>
      <p class="opis"><?= nl2br(htmlspecialchars($car['opis'])) ?></p>

      <h3>Lokalizacja</h3>
      <p class="adres"><?= htmlspecialchars($car['adres']) ?></p>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container">
      <div>Kontakt: <?= htmlspecialchars($SITE_EMAIL) ?> • <?= htmlspecialchars($PHONE1) ?> • <?= htmlspecialchars($PHONE2) ?></div>
     
    </div>
  </footer>

  <script>
    // Prosty slider
    const slides = Array.from(document.querySelectorAll('.slide'));
    const thumbs = Array.from(document.querySelectorAll('.thumb'));
    const prevBtn = document.querySelector('.nav.prev');
    const nextBtn = document.querySelector('.nav.next');
    let idx = 0;

    function show(i) {
      if (!slides.length) return;
      idx = (i + slides.length) % slides.length;
      slides.forEach((s, k) => s.classList.toggle('active', k === idx));
      thumbs.forEach((t, k) => t.classList.toggle('active', k === idx));
    }
    prevBtn && prevBtn.addEventListener('click', () => show(idx - 1));
    nextBtn && nextBtn.addEventListener('click', () => show(idx + 1));
    thumbs.forEach(t => t.addEventListener('click', () => show(parseInt(t.dataset.idx, 10))));
  </script>
</body>
</html>
