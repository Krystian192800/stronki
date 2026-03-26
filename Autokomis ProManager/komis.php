<?php 
include("config.php"); 


$res_count = $conn->query("SELECT COUNT(*) as total FROM auta");
$stats_total = $res_count->fetch_assoc()['total'];

$res_newest = $conn->query("SELECT marka, model FROM auta ORDER BY id DESC LIMIT 1");
$last_car = $res_newest->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Autokomisu v2</title>
    <style>
        :root {
            --primary: #2563eb;
            --secondary: #64748b;
            --success: #22c55e;
            --danger: #ef4444;
            --dark: #0f172a;
            --light: #f8fafc;
            --accent: #3b82f6;
        }

        body { 
            font-family: 'Inter', system-ui, -apple-system, sans-serif; 
            background: #f1f5f9; 
            margin: 0; 
            color: var(--dark);
            line-height: 1.5;
        }

        
        header { 
            background: linear-gradient(135deg, var(--dark) 0%, #1e293b 100%); 
            color: white; 
            padding: 50px 20px 80px; 
            text-align: center;
        }

        .container { width: 95%; max-width: 1200px; margin: -50px auto 40px; }

       
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            border-left: 5px solid var(--primary);
        }

        .stat-info h3 { margin: 0; font-size: 12px; color: var(--secondary); text-transform: uppercase; }
        .stat-info p { margin: 5px 0 0; font-size: 20px; font-weight: 800; }

        .menu { 
            display: flex; 
            gap: 10px; 
            justify-content: center; 
            background: white;
            padding: 8px;
            border-radius: 50px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
        }

        .menu button { 
            background: transparent; 
            border: none;
            color: var(--secondary); 
            padding: 10px 25px; 
            font-weight: 600;
            cursor: pointer; 
            border-radius: 40px; 
            transition: all 0.3s; 
        }

        .menu button.active { background: var(--primary); color: white; }

       
        .card { 
            background: white; 
            padding: 30px; 
            border-radius: 16px; 
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); 
            animation: slideUp 0.4s ease-out;
        }

        .grid-form { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); 
            gap: 20px; 
        }

        .input-group { display: flex; flex-direction: column; gap: 8px; }
        .input-group label { font-size: 13px; font-weight: 600; color: #475569; }

        input { 
            padding: 12px; 
            border-radius: 8px; 
            border: 1px solid #cbd5e1; 
            background: #f8fafc;
            transition: 0.2s;
        }

        input:focus { border-color: var(--primary); outline: none; background: white; box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); }

        .btn-main {
            background: var(--primary);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            grid-column: 1 / -1;
        }

        .btn-main:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); }

     
        .table-container { margin-top: 30px; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th { background: #f8fafc; padding: 15px; text-align: left; font-size: 13px; color: var(--secondary); border-bottom: 1px solid #e2e8f0; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        tr:last-child td { border-bottom: none; }

        .tag { padding: 4px 8px; background: #e2e8f0; border-radius: 4px; font-size: 11px; font-weight: 700; }

        .hidden { display: none; }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        footer { text-align: center; color: var(--secondary); font-size: 13px; padding: 40px 0; }
    </style>

    <script>
        function pokazSekcje(nazwa, element) {
            document.querySelectorAll('.card').forEach(c => c.classList.add('hidden'));
            document.querySelectorAll('.menu button').forEach(b => b.classList.remove('active'));
            
            document.getElementById(nazwa).classList.remove('hidden');
            element.classList.add('active');
        }

        window.onload = function() {
            <?php 
                $active = 'szukajSekcja';
                if(isset($_POST['dodaj'])) $active = 'dodajSekcja';
                if(isset($_POST['usun_vin'])) $active = 'usunSekcja';
            ?>
            const startSection = "<?php echo $active; ?>";
            const btnIdx = startSection === 'szukajSekcja' ? 0 : (startSection === 'dodajSekcja' ? 1 : 2);
            pokazSekcje(startSection, document.querySelectorAll('.menu button')[btnIdx]);
        };
    </script>
</head>
<body>

<header>
    <h1 style="font-size: 2.5rem; margin-bottom: 10px;">Autokomis ProManager</h1>
    <p style="opacity: 0.8;">Witaj w panelu administracyjnym Twojej floty</p>
</header>

<div class="container">
    <div class="stats-grid">
        <div class="stat-card">
            <div style="font-size: 30px;">🚘</div>
            <div class="stat-info">
                <h3>Wszystkie auta</h3>
                <p><?php echo $stats_total; ?> szt.</p>
            </div>
        </div>
        <div class="stat-card" style="border-left-color: var(--success);">
            <div style="font-size: 30px;">✨</div>
            <div class="stat-info">
                <h3>Ostatnio dodany</h3>
                <p><?php echo ($last_car) ? $last_car['marka'].' '.$last_car['model'] : 'Brak danych'; ?></p>
            </div>
        </div>
        <div class="stat-card" style="border-left-color: #f59e0b;">
            <div style="font-size: 30px;">📅</div>
            <div class="stat-info">
                <h3>Dzisiejsza data</h3>
                <p><?php echo date("d.m.Y"); ?></p>
            </div>
        </div>
    </div>

    <div class="menu">
        <button onclick="pokazSekcje('szukajSekcja', this)">🔍 Szukaj</button>
        <button onclick="pokazSekcje('dodajSekcja', this)">➕ Nowe Auto</button>
        <button onclick="pokazSekcje('usunSekcja', this)">🗑️ Usuń</button>
    </div>

    <div id="szukajSekcja" class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h2 style="margin:0">Wyszukiwarka pojazdów</h2>
            <span style="font-size: 12px; color: var(--secondary);">Wypełnij dowolne pola</span>
        </div>
        
        <form method="GET" action="" class="grid-form">
            <div class="input-group"><label>Numer VIN</label><input type="text" name="vin" placeholder="np. WVWZZZ..." value="<?php echo $_GET['vin'] ?? ''; ?>"></div>
            <div class="input-group"><label>Marka</label><input type="text" name="marka" placeholder="np. BMW" value="<?php echo $_GET['marka'] ?? ''; ?>"></div>
            <div class="input-group"><label>Model</label><input type="text" name="model" value="<?php echo $_GET['model'] ?? ''; ?>"></div>
            <div class="input-group"><label>Rok od</label><input type="number" name="rocznik" value="<?php echo $_GET['rocznik'] ?? ''; ?>"></div>
            <button type="submit" name="szukaj" class="btn-main">Filtruj bazę pojazdów</button>
        </form>

        <?php
        if (isset($_GET['szukaj'])) {
            $where = [];
            if (!empty($_GET['vin'])) $where[] = "vin LIKE '%".$conn->real_escape_string($_GET['vin'])."%'";
            if (!empty($_GET['marka'])) $where[] = "marka LIKE '%".$conn->real_escape_string($_GET['marka'])."%'";
            if (!empty($_GET['model'])) $where[] = "model LIKE '%".$conn->real_escape_string($_GET['model'])."%'";

            $sql = "SELECT * FROM auta";
            if ($where) $sql .= " WHERE " . implode(" AND ", $where);
            $sql .= " ORDER BY id DESC";

            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                echo "<div class='table-container'><table><tr><th>Pojazd</th><th>VIN / Rejestracja</th><th>Specyfikacja</th></tr>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td><strong>{$row['marka']} {$row['model']}</strong><br><span style='font-size:12px; color:gray'>Rok: {$row['rocznik']}</span></td>
                            <td><code>{$row['vin']}</code><br><span class='tag'>{$row['nr_rejestracyjny']}</span></td>
                            <td>{$row['pojemnosc']}L | {$row['konie']} KM</td>
                          </tr>";
                }
                echo "</table></div>";
            } else { echo "<p style='text-align:center; padding: 40px; color: var(--secondary);'>Brak wyników do wyświetlenia.</p>"; }
        } else {
           
            echo "<h3 style='margin-top:40px; font-size: 15px;'>Ostatnio dodane do bazy:</h3>";
            $recent = $conn->query("SELECT * FROM auta ORDER BY id DESC LIMIT 3");
            if ($recent && $recent->num_rows > 0) {
                echo "<div class='table-container'><table>";
                while($r = $recent->fetch_assoc()) {
                    echo "<tr><td>{$r['marka']} {$r['model']} ({$r['rocznik']})</td><td>{$r['vin']}</td></tr>";
                }
                echo "</table></div>";
            }
        }
        ?>
    </div>

    <div id="dodajSekcja" class="card hidden">
        <h2>➕ Rejestracja nowego pojazdu</h2>
        <form method="POST" action="" class="grid-form">
            <div class="input-group"><label>VIN</label><input type="text" name="vin" required></div>
            <div class="input-group"><label>Nr rejestracyjny</label><input type="text" name="nr_rej" required></div>
            <div class="input-group"><label>Marka</label><input type="text" name="marka" required></div>
            <div class="input-group"><label>Model</label><input type="text" name="model" required></div>
            <div class="input-group"><label>Pojemność (L)</label><input type="number" step="0.1" name="pojemnosc"></div>
            <div class="input-group"><label>Moc (KM)</label><input type="number" name="konie"></div>
            <div class="input-group"><label>Rocznik</label><input type="number" name="rocznik"></div>
            <div class="input-group"><label>Data rej.</label><input type="date" name="data_rej"></div>
            <button type="submit" name="dodaj" class="btn-main" style="background: var(--success)">Zapisz pojazd w systemie</button>
        </form>
        <?php
        if (isset($_POST['dodaj'])) {
            $sql = "INSERT INTO auta (vin, nr_rejestracyjny, marka, model, pojemnosc, konie, rocznik, data_pierwszej_rejestracji) 
                    VALUES ('{$_POST['vin']}', '{$_POST['nr_rej']}', '{$_POST['marka']}', '{$_POST['model']}', '{$_POST['pojemnosc']}', '{$_POST['konie']}', '{$_POST['rocznik']}', '{$_POST['data_rej']}')";
            if ($conn->query($sql)) echo "<p style='color:var(--success); font-weight:bold;'>✅ Auto dodane pomyślnie!</p>";
        }
        ?>
    </div>

    <div id="usunSekcja" class="card hidden">
        <h2>🗑️ Usuwanie z ewidencji</h2>
        <p style="color: var(--secondary); margin-bottom: 20px;">Wyszukaj auto, które chcesz trwale usunąć z bazy danych.</p>
        <form method="GET" action="" class="grid-form">
            <input type="text" name="del_vin" placeholder="Wpisz VIN lub markę..." style="grid-column: 1 / span 2;">
            <button type="submit" name="usun_szukaj" class="btn-main" style="background:var(--dark); margin:0;">Szukaj</button>
        </form>
        <?php
        if (isset($_GET['usun_szukaj'])) {
            $s = $conn->real_escape_string($_GET['del_vin']);
            $res = $conn->query("SELECT * FROM auta WHERE vin LIKE '%$s%' OR marka LIKE '%$s%'");
            if ($res->num_rows > 0) {
                echo "<div class='table-container'><table>";
                while ($row = $res->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['marka']} {$row['model']}</td>
                            <td style='text-align:right'>
                                <form method='POST' onsubmit='return confirm(\"Usunąć ten pojazd?\")'>
                                    <input type='hidden' name='usun_vin' value='{$row['vin']}'>
                                    <button type='submit' style='background:none; border:none; color:var(--danger); cursor:pointer; font-weight:600;'>USUŃ</button>
                                </form>
                            </td>
                          </tr>";
                }
                echo "</table></div>";
            }
        }
        if (isset($_POST['usun_vin'])) {
            $v = $conn->real_escape_string($_POST['usun_vin']);
            $conn->query("DELETE FROM auta WHERE vin='$v'");
            echo "<p style='color:var(--danger);'>Auto zostało usunięte.</p>";
        }
        ?>
    </div>
</div>

<footer>
    &copy; 2026 Autokomis ProManager.
</footer>

</body>
</html>