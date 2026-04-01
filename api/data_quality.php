<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GMS · Data Quality</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
  *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
  :root {
    --bg:#0a0f0a; --surface:#111a11; --border:#1f2e1f;
    --green:#3ddc68; --green-dim:#1a5e30; --amber:#f5a623;
    --red:#e84040; --blue:#4a9eff; --text:#c8e6c9; --muted:#4a6b4a;
  }
  html,body { height:100%; font-family:'Space Mono',monospace; background:var(--bg); color:var(--text); }
  .shell { display:grid; grid-template-rows:56px 1fr; height:100vh; }

  header {
    display:flex; align-items:center; gap:12px; padding:0 20px;
    background:var(--surface); border-bottom:1px solid var(--border);
  }
  .logo { font-family:'Syne',sans-serif; font-size:18px; font-weight:800; color:var(--green); }
  .logo span { color:var(--muted); }
  .back { font-size:11px; color:var(--muted); text-decoration:none; border:1px solid var(--border); padding:4px 10px; border-radius:4px; }
  .back:hover { color:var(--green); border-color:var(--green); }
  .hdr-stat { font-size:10px; color:var(--muted); }
  .hdr-stat b { color:var(--text); }

  .content { display:grid; grid-template-columns:1fr 1fr; grid-template-rows:1fr 1fr; gap:0; overflow:hidden; }

  .panel {
    background:var(--surface); border-right:1px solid var(--border);
    border-bottom:1px solid var(--border); display:flex; flex-direction:column; overflow:hidden;
  }
  .panel:nth-child(2) { border-right:none; }
  .panel:nth-child(3) { border-bottom:none; }
  .panel:nth-child(4) { border-right:none; border-bottom:none; }

  .panel-head {
    padding:12px 16px; border-bottom:1px solid var(--border);
    display:flex; align-items:center; gap:8px; flex-shrink:0;
  }
  .panel-head h3 { font-family:'Syne',sans-serif; font-size:12px; font-weight:700; text-transform:uppercase; }
  .panel-count {
    font-size:10px; padding:2px 7px; border-radius:10px; font-weight:700;
    background:#200000; color:var(--red); border:1px solid #400000;
  }
  .panel-count.ok { background:#0d200d; color:var(--green); border-color:var(--green-dim); }
  .panel-count.warn { background:#1e1500; color:var(--amber); border-color:#3a2800; }

  .panel-body { flex:1; overflow-y:auto; }
  .panel-body::-webkit-scrollbar { width:3px; }
  .panel-body::-webkit-scrollbar-thumb { background:var(--border); }

  .issue-row {
    display:flex; align-items:flex-start; gap:10px; padding:10px 14px;
    border-bottom:1px solid #0f1a0f; cursor:pointer; transition:background .15s;
  }
  .issue-row:hover { background:rgba(61,220,104,.04); }
  .issue-icon { font-size:16px; flex-shrink:0; margin-top:1px; }
  .issue-body { flex:1; min-width:0; }
  .issue-name { font-size:11px; font-weight:700; }
  .issue-meta { font-size:10px; color:var(--muted); margin-top:3px; display:flex; gap:8px; flex-wrap:wrap; }
  .issue-tag  {
    font-size:9px; padding:1px 5px; border-radius:3px;
    background:#200000; color:var(--red); border:1px solid #400000;
  }
  .issue-tag.warn { background:#1e1500; color:var(--amber); border-color:#3a2800; }

  .map-panel { position:relative; }
  #dq-map { width:100%; height:100%; }

  .empty-state { padding:20px 16px; font-size:11px; color:var(--muted); text-align:center; }
  .empty-state b { display:block; font-size:14px; color:var(--green); margin-bottom:6px; }
</style>
</head>
<body>

<?php
require "conn.php";
require "validate.php";

// Zimbabwe bounding box
define('ZW_LAT_MIN', -22.5);
define('ZW_LAT_MAX', -15.5);
define('ZW_LNG_MIN',  25.2);
define('ZW_LNG_MAX',  33.1);

// ── 1. Zero / null coordinates ────────────────────────────────────────────────
$zeroCoords = [];
foreach (['lat_long' => 'Home', 'grower_farm' => 'Farm', 'seedbed_location' => 'Seedbed', 'barn_location' => 'Barn'] as $table => $label) {
    $r = $conn->query("
        SELECT g.id, g.grower_num, g.name, g.surname,
               t.latitude, t.longitude, '$label' AS loc_type
        FROM $table t
        JOIN growers g ON g.id = t.growerid
        WHERE t.latitude = 0 AND t.longitude = 0
           OR t.latitude IS NULL OR t.longitude IS NULL
    ");
    if ($r) { while ($row = $r->fetch_assoc()) $zeroCoords[] = $row; $r->free(); }
}

// ── 2. Out-of-Zimbabwe coordinates ───────────────────────────────────────────
$outOfZim = [];
foreach (['lat_long' => 'Home', 'grower_farm' => 'Farm', 'seedbed_location' => 'Seedbed', 'barn_location' => 'Barn'] as $table => $label) {
    $r = $conn->query("
        SELECT g.id, g.grower_num, g.name, g.surname,
               t.latitude, t.longitude, '$label' AS loc_type
        FROM $table t
        JOIN growers g ON g.id = t.growerid
        WHERE t.latitude  IS NOT NULL AND t.longitude IS NOT NULL
          AND t.latitude  != 0        AND t.longitude != 0
          AND (t.latitude  < " . ZW_LAT_MIN . " OR t.latitude  > " . ZW_LAT_MAX . "
            OR t.longitude < " . ZW_LNG_MIN . " OR t.longitude > " . ZW_LNG_MAX . ")
    ");
    if ($r) { while ($row = $r->fetch_assoc()) $outOfZim[] = $row; $r->free(); }
}

// ── 3. Duplicate coordinates (same lat/lng, different growers) ────────────────
$duplicates = [];
foreach (['lat_long' => 'Home', 'grower_farm' => 'Farm', 'seedbed_location' => 'Seedbed', 'barn_location' => 'Barn'] as $table => $label) {
    $r = $conn->query("
        SELECT t.latitude, t.longitude, '$label' AS loc_type,
               COUNT(*) AS dup_count,
               GROUP_CONCAT(g.grower_num ORDER BY g.grower_num SEPARATOR ', ') AS grower_nums,
               GROUP_CONCAT(CONCAT(g.name,' ',g.surname) ORDER BY g.grower_num SEPARATOR ' | ') AS grower_names
        FROM $table t
        JOIN growers g ON g.id = t.growerid
        WHERE t.latitude IS NOT NULL AND t.longitude IS NOT NULL
          AND t.latitude != 0 AND t.longitude != 0
        GROUP BY ROUND(t.latitude,5), ROUND(t.longitude,5)
        HAVING COUNT(*) > 1
    ");
    if ($r) { while ($row = $r->fetch_assoc()) $duplicates[] = $row; $r->free(); }
}

// ── 4. Growers with NO locations at all ──────────────────────────────────────
$noLocation = [];
$r = $conn->query("
    SELECT g.id, g.grower_num, g.name, g.surname
    FROM growers g
    WHERE NOT EXISTS (SELECT 1 FROM lat_long         WHERE growerid = g.id AND latitude  IS NOT NULL)
      AND NOT EXISTS (SELECT 1 FROM grower_farm      WHERE growerid = g.id AND latitude  IS NOT NULL)
      AND NOT EXISTS (SELECT 1 FROM seedbed_location WHERE growerid = g.id AND latitude  IS NOT NULL)
      AND NOT EXISTS (SELECT 1 FROM barn_location    WHERE growerid = g.id AND latitude  IS NOT NULL)
    ORDER BY g.name, g.surname
    LIMIT 200
");
if ($r) { while ($row = $r->fetch_assoc()) $noLocation[] = $row; $r->free(); }

$conn->close();

$totalIssues = count($zeroCoords) + count($outOfZim) + count($duplicates) + count($noLocation);

// All bad pins for map
$badPins = [];
foreach ($outOfZim as $b) {
    $badPins[] = ['lat'=>(float)$b['latitude'],'lng'=>(float)$b['longitude'],
                  'name'=>$b['name'].' '.$b['surname'],'num'=>$b['grower_num'],
                  'type'=>$b['loc_type'],'issue'=>'Outside Zimbabwe'];
}
foreach ($duplicates as $d) {
    $badPins[] = ['lat'=>(float)$d['latitude'],'lng'=>(float)$d['longitude'],
                  'name'=>$d['grower_names'],'num'=>$d['grower_nums'],
                  'type'=>$d['loc_type'],'issue'=>'Duplicate ('.$d['dup_count'].' growers)'];
}
?>

<div class="shell">
  <header>
    <div class="logo">GMS<span>/</span>Data Quality</div>
    <a href="device_tracker.php" class="back">← Tracker</a>
    <div style="margin-left:auto;display:flex;gap:16px">
      <div class="hdr-stat">Total issues: <b style="color:<?= $totalIssues>0?'var(--red)':'var(--green)' ?>"><?= $totalIssues ?></b></div>
      <div class="hdr-stat">No location: <b style="color:var(--amber)"><?= count($noLocation) ?></b></div>
      <div class="hdr-stat">Duplicates: <b style="color:var(--amber)"><?= count($duplicates) ?></b></div>
      <div class="hdr-stat">Bad coords: <b style="color:var(--red)"><?= count($zeroCoords)+count($outOfZim) ?></b></div>
    </div>
  </header>

  <div class="content">

    <!-- Panel 1: Zero / invalid coordinates -->
    <div class="panel">
      <div class="panel-head">
        <h3>Zero / Invalid Coords</h3>
        <span class="panel-count <?= count($zeroCoords)==0?'ok':'' ?>"><?= count($zeroCoords) ?></span>
      </div>
      <div class="panel-body">
        <?php if (empty($zeroCoords)): ?>
          <div class="empty-state"><b>✅</b>No invalid coordinates found</div>
        <?php else: foreach ($zeroCoords as $b): ?>
        <div class="issue-row">
          <div class="issue-icon">📍</div>
          <div class="issue-body">
            <div class="issue-name"><?= htmlspecialchars($b['name'].' '.$b['surname']) ?> <span style="color:var(--muted);font-size:9px">#<?= $b['grower_num'] ?></span></div>
            <div class="issue-meta">
              <span><?= $b['loc_type'] ?></span>
              <span class="issue-tag">lat: <?= $b['latitude'] ?? 'NULL' ?> · lng: <?= $b['longitude'] ?? 'NULL' ?></span>
            </div>
          </div>
        </div>
        <?php endforeach; endif ?>
      </div>
    </div>

    <!-- Panel 2: Duplicate coordinates -->
    <div class="panel">
      <div class="panel-head">
        <h3>Duplicate Coordinates</h3>
        <span class="panel-count warn <?= count($duplicates)==0?'ok':'' ?>"><?= count($duplicates) ?></span>
      </div>
      <div class="panel-body">
        <?php if (empty($duplicates)): ?>
          <div class="empty-state"><b>✅</b>No duplicate coordinates found</div>
        <?php else: foreach ($duplicates as $d): ?>
        <div class="issue-row" onclick="flyTo(<?= $d['latitude'] ?>,<?= $d['longitude'] ?>)">
          <div class="issue-icon">⚠️</div>
          <div class="issue-body">
            <div class="issue-name"><?= htmlspecialchars($d['grower_names']) ?></div>
            <div class="issue-meta">
              <span><?= $d['loc_type'] ?></span>
              <span class="issue-tag warn"><?= $d['dup_count'] ?> growers at same point</span>
              <span style="font-size:9px;color:var(--muted)">#<?= htmlspecialchars($d['grower_nums']) ?></span>
            </div>
          </div>
        </div>
        <?php endforeach; endif ?>
      </div>
    </div>

    <!-- Panel 3: Outside Zimbabwe -->
    <div class="panel">
      <div class="panel-head">
        <h3>Outside Zimbabwe</h3>
        <span class="panel-count <?= count($outOfZim)==0?'ok':'' ?>"><?= count($outOfZim) ?></span>
      </div>
      <div class="panel-body">
        <?php if (empty($outOfZim)): ?>
          <div class="empty-state"><b>✅</b>All coordinates within Zimbabwe</div>
        <?php else: foreach ($outOfZim as $b): ?>
        <div class="issue-row" onclick="flyTo(<?= $b['latitude'] ?>,<?= $b['longitude'] ?>)">
          <div class="issue-icon">🌍</div>
          <div class="issue-body">
            <div class="issue-name"><?= htmlspecialchars($b['name'].' '.$b['surname']) ?> <span style="color:var(--muted);font-size:9px">#<?= $b['grower_num'] ?></span></div>
            <div class="issue-meta">
              <span><?= $b['loc_type'] ?></span>
              <span class="issue-tag"><?= $b['latitude'] ?>, <?= $b['longitude'] ?></span>
            </div>
          </div>
        </div>
        <?php endforeach; endif ?>
      </div>
    </div>

    <!-- Panel 4: No location recorded -->
    <div class="panel map-panel">
      <div class="panel-head">
        <h3>No Location Recorded</h3>
        <span class="panel-count warn <?= count($noLocation)==0?'ok':'' ?>"><?= count($noLocation) ?></span>
      </div>
      <div class="panel-body">
        <?php if (empty($noLocation)): ?>
          <div class="empty-state"><b>✅</b>All growers have at least one location</div>
        <?php else: foreach ($noLocation as $g): ?>
        <div class="issue-row">
          <div class="issue-icon">👨‍🌾</div>
          <div class="issue-body">
            <div class="issue-name"><?= htmlspecialchars($g['name'].' '.$g['surname']) ?> <span style="color:var(--muted);font-size:9px">#<?= $g['grower_num'] ?></span></div>
            <div class="issue-meta">
              <span class="issue-tag warn">No GPS recorded</span>
            </div>
          </div>
        </div>
        <?php endforeach; endif ?>
      </div>
    </div>

  </div>
</div>

<script>
const badPins = <?= json_encode($badPins) ?>;

// We don't show a full map here — flyTo just opens Google Maps for the bad coord
function flyTo(lat, lng) {
  window.open(`https://www.google.com/maps?q=${lat},${lng}`, '_blank');
}
</script>
</body>
</html>
