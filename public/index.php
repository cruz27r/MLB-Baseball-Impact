<?php
require __DIR__ . '/../app/db.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>MLB Impact – Prototype</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="/styles.css" />
</head>
<body>
<header>
  <div class="container">
    <h1>MLB Impact (Foreign vs USA)</h1>
    <div class="badge">Prototype • PHP + MySQL</div>
  </div>
</header>
<main>
  <div class="container">
    <div class="card">
      <h2>Story Preview</h2>
      <p>Placeholders where charts/tables will render once data is loaded. After you run the loader, refresh to see real numbers.</p>
      <div class="placeholder">[Chart: Roster Share by Origin, 1987–Present]</div>
    </div>

    <div class="card">
      <h2>Quick Stat (Sample Query)</h2>
      <?php
      try {
        $stmt = $pdo->query("
          SELECT COALESCE(NULLIF(TRIM(birth_country),''),'Unknown') AS birth_country,
                 COUNT(*) AS players
          FROM staging_people
          GROUP BY birth_country
          ORDER BY players DESC
          LIMIT 10;
        ");
        $rows = $stmt->fetchAll();
        if (!$rows) {
          echo "<p>No data yet. Run <code>scripts/load_mysql.sh</code> first.</p>";
        } else {
          echo '<table><thead><tr><th>Birth Country</th><th>Players</th></tr></thead><tbody>';
          foreach ($rows as $r) {
            echo '<tr><td>'.htmlspecialchars($r['birth_country']).'</td><td>'.(int)$r['players'].'</td></tr>';
          }
          echo '</tbody></table>';
        }
      } catch (Throwable $e) {
        echo "<p>Waiting for staging tables…</p>";
      }
      ?>
    </div>

    <div class="card">
      <h2>Planned Sections</h2>
      <ul>
        <li>Composition over time (USA vs Foreign)</li>
        <li>WAR share vs roster share (Impact Index)</li>
        <li>Awards share (MVP, Cy Young, ROY, ASG)</li>
        <li>Championship contribution</li>
        <li>Contracts (optional)</li>
      </ul>
    </div>
  </div>
</main>
</body>
</html>
