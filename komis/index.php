<?php
session_start();

// ------------------ Konfiguracja bazy ------------------
$DB_HOST = 'localhost';
$DB_NAME = 'krystian192800';
$DB_USER = 'auto_uzytkownik';
$DB_PASS = 'Koper192800';

// Połączenie z bazą
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

// Pobranie aut dostawczych z pierwszym zdjęciem
$sql = "
SELECT c.id, c.tytul, c.cena, img.image_path
FROM cars c
LEFT JOIN (
  SELECT ci.car_id, ci.image_path
  FROM car_images ci
  INNER JOIN (
    SELECT car_id, MIN(sort_order) AS min_sort
    FROM car_images
    GROUP BY car_id
  ) m ON ci.car_id = m.car_id AND ci.sort_order = m.min_sort
) AS img ON img.car_id = c.id
ORDER BY c.created_at DESC
";
$cars = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// ------------------ Kursy walut ------------------
$kurs_euro = 0.24; // 1 zł = 0.24 EUR (zmień na aktualny kurs)

// ------------------ Obsługa języka ------------------
$supported = ['pl', 'en', 'uk', 'fr'];
$lang = $_SESSION['lang'] ?? 'pl';
if (isset($_GET['lang']) && in_array($_GET['lang'], $supported, true)) {
    $lang = $_SESSION['lang'] = $_GET['lang'];
}

// ------------------ Ustalanie waluty ------------------
if ($lang === 'pl') {
    $currencySymbol = 'zł';
    $currencyCode = 'PLN';
    $priceMultiplier = 1;
} else {
    $currencySymbol = '€';
    $currencyCode = 'EUR';
    $priceMultiplier = $kurs_euro;
}

// ------------------ Tłumaczenia UI ------------------
$i18n = [
  'pl' => [
    'title' => 'Ogłoszenia Aut Dostawczych',
    'hero_h1' => 'Znajdź swoje auto',
    'hero_p' => 'Przeglądaj oferty, kliknij kafelek aby zobaczyć więcej zdjęć i detale.',
    'empty' => 'Brak ogłoszeń.',
    'contact' => 'Kontakt',
    'copy' => 'ANTruck',
    'currency' => 'zł',
    'select_language' => 'Wybierz język',
    'no_image' => 'Brak zdjęcia',
    'autos_osobowe' => 'Budowlane',
    'autos_dostawcze' => 'Samochody',
  ],
  'en' => [
    'title' => 'Delivery Cars Listings',
    'hero_h1' => 'Find your car',
    'hero_p' => 'Browse offers, click a tile to see more photos and details.',
    'empty' => 'No listings.',
    'contact' => 'Contact',
    'copy' => 'ANTruck',
    'currency' => 'EUR',
    'select_language' => 'Select language',
    'no_image' => 'No image',
    'autos_osobowe' => 'Construction',
    'autos_dostawcze' => 'Cars',
  ],
  'uk' => [
    'title' => 'Оголошення про вантажні авто',
    'hero_h1' => 'Знайдіть своє авто',
    'hero_p' => 'Переглядайте пропозиції, натисніть на плитку, щоб побачити більше фото та деталей.',
    'empty' => 'Немає оголошень.',
    'contact' => 'Контакти',
    'copy' => 'ANTruck',
    'currency' => 'EUR',
    'select_language' => 'Виберіть мову',
    'no_image' => 'Немає фото',
    'autos_osobowe' => 'Будівельні',
    'autos_dostawcze' => 'Автомобілі',
  ],
  'fr' => [
    'title' => 'Annonces voitures utilitaires',
    'hero_h1' => 'Trouvez votre voiture',
    'hero_p' => 'Parcourez les offres, cliquez sur une carte pour voir plus de photos et de détails.',
    'empty' => 'Aucune annonce.',
    'contact' => 'Contact',
    'copy' => 'ANTruck',
    'currency' => 'EUR',
    'select_language' => 'Choisir la langue',
    'no_image' => 'Pas d’image',
    'autos_osobowe' => 'De construction',
    'autos_dostawcze' => 'Voitures',
  ],
];

function t($key) {
    global $i18n, $lang;
    return $i18n[$lang][$key] ?? ($i18n['pl'][$key] ?? $key);
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars(t('title')) ?></title>
  <link rel="stylesheet" href="csskoper.css">
  <meta name="theme-color" content="#0f1221">
  <style>
    html {
      background-color: #0f1221;
      background-attachment: fixed;
      overscroll-behavior: none;
    }
    body {
      background: transparent;
    }
    .logo-buttons {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-left: 20px;
    }
    .logo-buttons a {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 8px;
        background-color: var(--primary);
        color: #fff;
        font-weight: 600;
        text-decoration: none;
        transition: 0.2s;
    }
    .logo-buttons a:hover {
        background-color: var(--primary-2);
    }
    .topbar .container {
        display: flex;
        align-items: center;
    }
  </style>
</head>
<body>
<header class="topbar">
  <div class="container">
    <a href="index.php" class="logo-link">
      <img src="logo.png" alt="ANTruck logo" class="logo-img">
    </a>

    <form class="lang-form" method="get">
      <select name="lang" aria-label="<?= htmlspecialchars(t('select_language')) ?>" onchange="this.form.submit()">
        <option value="pl" <?= $lang==='pl'?'selected':'' ?>>🇵🇱 Polski</option>
        <option value="en" <?= $lang==='en'?'selected':'' ?>>🇬🇧 English</option>
        <option value="uk" <?= $lang==='uk'?'selected':'' ?>>🇺🇦 Українська</option>
        <option value="fr" <?= $lang==='fr'?'selected':'' ?>>🇫🇷 Français</option>
      </select>
    </form>
  </div>
</header>

<section class="hero">
  <div class="container">
    <h1><?= t('hero_h1') ?></h1>
    <p><?= t('hero_p') ?></p>

    <br><br>

    <div class="logo-buttons">
      <a href="osobowe.php"><?= t('autos_osobowe') ?></a>
      <a href="index.php"><?= t('autos_dostawcze') ?></a>
    </div>
  </div>
</section>

<main class="container">
  <div class="grid">
    <?php foreach ($cars as $car): ?>
      <a class="card" href="auto.php?id=<?= (int)$car['id'] ?>">
        <div class="card-img">
          <?php $img = $car['image_path'] ?: 'https://via.placeholder.com/600x400?text='.urlencode(t('no_image')); ?>
          <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($car['tytul']) ?>">
        </div>
        <div class="card-body">
          <h3 class="card-title"><?= htmlspecialchars($car['tytul']) ?></h3>
          <div class="card-price">
            <?= number_format((float)$car['cena'] * $priceMultiplier, 0, ',', ' ') . ' ' . $currencySymbol ?>
          </div>
        </div>
      </a>
    <?php endforeach; ?>

    <?php if (empty($cars)): ?>
      <div class="empty"><?= t('empty') ?></div>
    <?php endif; ?>
  </div>
</main>

<footer class="site-footer">
  <div class="container">
    <div><?= t('contact') ?>: Andrzej2004a@op.pl • 509 465 286 • 692 869 364</div>
    
  </div>
</footer>
</body>
</html>
