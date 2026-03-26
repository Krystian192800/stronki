<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Wybierz rodzaj ogłoszenia</title>
  <link rel="stylesheet" href="csskoper.css">
  <style>
    .choice-container {
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      gap: 2rem;
      height: 80vh;
      text-align: center;
    }
    .choice-container h1 {
      font-size: 2rem;
      margin-bottom: 1rem;
    }
    .choice-buttons {
      display: flex;
      gap: 2rem;
    }
    .choice-buttons a {
      padding: 1rem 2rem;
      background-color: #007BFF;
      color: #fff;
      text-decoration: none;
      border-radius: 12px;
      transition: background-color 0.3s;
      font-size: 1.2rem;
    }
    .choice-buttons a:hover {
      background-color: #0056b3;
    }
  </style>
</head>
<body>
<header class="topbar">
  <div class="container">
    <div class="logo">ANTruck</div>
    <nav class="nav">
      <a class="btn btn-ghost" href="index.php">Strona główna</a>
    </nav>
  </div>
</header>

<main class="choice-container">
  <h1>Wybierz rodzaj ogłoszenia</h1>
  <div class="choice-buttons">
    <a href="dodaj.php">Auta dostawcze</a>
    <a href="dodaj2.php">Budowlane</a>
  </div>
</main>

<footer class="site-footer">
  <div class="container">
    <div>Kontakt: kontakt@example.com • 509 465 286 • 692 869 364</div>
  
  </div>
</footer>
</body>
</html>
