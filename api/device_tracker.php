<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GMS · Device Tracker</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@400;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
  *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
  :root {
    --bg:#0a0f0a; --surface:#111a11; --border:#1f2e1f;
    --green:#3ddc68; --green-dim:#1a5e30; --amber:#f5a623;
    --red:#e84040; --blue:#4a9eff; --purple:#b47eff; --text:#c8e6c9;
    --muted:#4a6b4a; --radius:6px;
  }
  html, body { height:100%; font-family:'Space Mono',monospace; background:var(--bg); color:var(--text); }
  .shell { display:grid; grid-template-rows:56px 1fr; grid-template-columns:330px 1fr; height:100vh; }

  header {
    grid-column:1/-1; display:flex; align-items:center; gap:12px;
    padding:0 20px; background:var(--surface); border-bottom:1px solid var(--border);
  }
  .logo { font-family:'Syne',sans-serif; font-size:18px; font-weight:800; color:var(--green); }
  .logo span { color:var(--muted); }
  .hdr-stats { display:flex; gap:14px; margin-left:auto; }
  .hdr-stat  { font-size:10px; color:var(--muted); }
  .hdr-stat b { color:var(--text); }
  #countdown { font-size:10px; color:var(--muted); margin-left:8px; }

  aside {
    background:var(--surface); border-right:1px solid var(--border);
    display:flex; flex-direction:column; overflow:hidden;
  }
  .sb-head { padding:14px 16px 10px; border-bottom:1px solid var(--border); }
  .sb-head h2 { font-family:'Syne',sans-serif; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
  .sb-head p  { font-size:10px; color:var(--muted); margin-top:2px; }

  .filter-row { display:flex; gap:5px; padding:8px 12px; border-bottom:1px solid var(--border); flex-wrap:wrap; }
  .fbtn {
    font-family:'Space Mono',monospace; font-size:9px; cursor:pointer;
    border:1px solid var(--border); background:transparent; color:var(--muted);
    padding:4px 8px; border-radius:20px; transition:all .2s; white-space:nowrap;
  }
  .fbtn.active         { border-color:var(--green); color:var(--green); background:#0d200d; }
  .fbtn.f-amber.active { border-color:var(--amber); color:var(--amber); background:#1e1500; }
  .fbtn.f-red.active   { border-color:var(--red);   color:var(--red);   background:#200000; }
  .fbtn.f-blue.active  { border-color:var(--blue);  color:var(--blue);  background:#001020; }

  .layer-row {
    display:flex; gap:5px; padding:6px 12px;
    border-bottom:1px solid var(--border); flex-wrap:wrap; align-items:center;
  }
  .layer-label { font-size:9px; color:var(--muted); white-space:nowrap; }
  .lbtn {
    font-family:'Space Mono',monospace; font-size:9px; cursor:pointer;
    border:1px solid var(--border); background:transparent; color:var(--muted);
    padding:3px 7px; border-radius:3px; transition:all .2s; white-space:nowrap;
  }
  .lbtn.on { background:#0d200d; color:var(--text); border-color:var(--muted); }

  .device-list { flex:1; overflow-y:auto; }
  .device-list::-webkit-scrollbar { width:3px; }
  .device-list::-webkit-scrollbar-thumb { background:var(--border); }

  .device-card {
    display:flex; align-items:flex-start; gap:10px;
    padding:10px 14px; cursor:pointer;
    border-left:3px solid transparent; border-bottom:1px solid #0f1a0f;
    transition:background .15s, border-color .15s;
  }
  .device-card:hover    { background:rgba(61,220,104,.04); }
  .device-card.selected { background:rgba(61,220,104,.08); border-left-color:var(--green); }

  .status-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; margin-top:4px; }
  .dot-green { background:var(--green); box-shadow:0 0 6px var(--green); animation:pulse 2s infinite; }
  .dot-amber { background:var(--amber); }
  .dot-red   { background:var(--red); }
  @keyframes pulse { 0%,100%{opacity:1}50%{opacity:.4} }

  .card-body { flex:1; min-width:0; }
  .card-name { font-size:12px; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .card-sub  { font-size:10px; color:var(--muted); margin-top:3px; display:flex; gap:5px; flex-wrap:wrap; align-items:center; }

  .badge { display:inline-block; font-size:9px; padding:1px 5px; border-radius:3px; font-family:'Space Mono',monospace; }
  .b-sms    { background:#001828; color:var(--blue);   border:1px solid #003050; }
  .b-queue  { background:#1a1200; color:var(--amber);  border:1px solid #3a2800; }
  .b-live   { background:#0d200d; color:var(--green);  border:1px solid var(--green-dim); }
  .b-batt   { background:#200000; color:var(--red);    border:1px solid #400000; }
  .b-grower { background:#0e0020; color:var(--purple); border:1px solid #2a0050; }

  .search-wrap { padding:8px 12px; border-bottom:1px solid var(--border); position:relative; }
  .search-wrap input {
    width:100%; background:#0d150d; border:1px solid var(--border); color:var(--text);
    font-family:'Space Mono',monospace; font-size:11px; padding:6px 10px 6px 28px;
    border-radius:4px; outline:none; transition:border-color .2s;
  }
  .search-wrap input:focus { border-color:var(--green); }
  .search-wrap .search-icon { position:absolute; left:20px; top:50%; transform:translateY(-50%); font-size:12px; pointer-events:none; }
  .search-wrap input::placeholder { color:var(--muted); }

  .search-results {
    position:absolute; left:12px; right:12px; top:100%; z-index:2000;
    background:var(--surface); border:1px solid var(--green); border-top:none;
    border-radius:0 0 4px 4px; max-height:220px; overflow-y:auto; display:none;
  }
  .search-results::-webkit-scrollbar { width:3px; }
  .search-results::-webkit-scrollbar-thumb { background:var(--border); }
  .sr-item {
    padding:8px 12px; cursor:pointer; border-bottom:1px solid #0f1a0f;
    transition:background .15s;
  }
  .sr-item:hover { background:rgba(61,220,104,.08); }
  .sr-item:last-child { border-bottom:none; }
  .sr-name { font-size:11px; font-weight:700; }
  .sr-meta { font-size:9px; color:var(--muted); margin-top:2px; display:flex; gap:6px; }
  .sr-empty { padding:10px 12px; font-size:11px; color:var(--muted); text-align:center; }
  #map { width:100%; height:100%; background:#0d150d; }

  #detail-panel {
    position:absolute; bottom:16px; right:16px; z-index:1000;
    width:290px; background:var(--surface); border:1px solid var(--border);
    border-radius:8px; padding:14px; display:none;
    box-shadow:0 4px 24px rgba(0,0,0,.7);
    max-height:82vh; overflow-y:auto;
  }
  #detail-panel::-webkit-scrollbar { width:3px; }
  #detail-panel::-webkit-scrollbar-thumb { background:var(--border); }
  #detail-panel.visible { display:block; }

  .dp-name  { font-family:'Syne',sans-serif; font-size:15px; font-weight:800; color:var(--green); margin-bottom:10px; }
  .dp-row   { display:flex; justify-content:space-between; font-size:11px; margin-top:5px; }
  .dp-label { color:var(--muted); }
  .dp-val   { color:var(--text); font-weight:700; }
  .dp-val.warn { color:var(--red); }
  .dp-coords { font-size:10px; color:var(--muted); margin-top:8px; word-break:break-all; }
  .dp-divider { border:none; border-top:1px solid var(--border); margin:10px 0; }

  .dp-section-title {
    font-size:9px; text-transform:uppercase; letter-spacing:.5px;
    color:var(--muted); margin:10px 0 6px; font-weight:700;
  }

  .nearby-list { display:flex; flex-direction:column; gap:5px; }
  .nearby-item {
    background:#0d150d; border:1px solid var(--border); border-radius:4px;
    padding:7px 9px; cursor:pointer; transition:border-color .15s;
  }
  .nearby-item:hover             { border-color:var(--green); }
  .nearby-item.visited           { border-left:3px solid var(--green); }
  .nearby-item.unvisited         { border-left:3px solid var(--red); }
  .ni-name    { font-size:11px; font-weight:700; }
  .ni-meta    { font-size:9px; color:var(--muted); margin-top:3px; display:flex; gap:6px; flex-wrap:wrap; }
  .ni-dist    { color:var(--amber); font-weight:700; }
  .ni-visited   { color:var(--green); }
  .ni-unvisited { color:var(--red); }

  .btn-history {
    display:block; width:100%; margin-top:10px;
    font-family:'Space Mono',monospace; font-size:11px;
    background:var(--green-dim); border:1px solid var(--green); color:var(--green);
    padding:7px; border-radius:var(--radius); cursor:pointer; text-align:center;
  }
  .btn-history:hover { background:#1e4a22; }

  #geofence-alert {
    position:absolute; top:16px; left:50%; transform:translateX(-50%); z-index:1001;
    background:#200000; border:1px solid var(--red); border-radius:6px;
    padding:10px 14px; font-size:11px; color:var(--red); display:none;
    box-shadow:0 4px 16px rgba(232,64,64,.3); max-width:340px; text-align:center;
  }
  .gms-marker {
    width:30px; height:30px; border-radius:50%;
    border:2px solid var(--green); background:#0d200d;
    display:flex; align-items:center; justify-content:center; font-size:14px;
    box-shadow:0 0 10px rgba(61,220,104,.4);
  }
  .gms-marker.stale { border-color:var(--amber); box-shadow:0 0 8px rgba(245,166,35,.3); }
  .gms-marker.lost  { border-color:var(--red);   box-shadow:0 0 8px rgba(232,64,64,.3); }
  .gms-marker-selected {
    border-color:#fff !important;
    box-shadow:0 0 0 4px rgba(61,220,104,.5), 0 0 20px rgba(61,220,104,.8) !important;
    animation: officer-glow 1s ease-in-out infinite !important;
    transform: scale(1.2);
    transition: transform .2s;
  }
  @keyframes officer-glow {
    0%,100% { box-shadow:0 0 0 4px rgba(61,220,104,.5), 0 0 20px rgba(61,220,104,.8); }
    50%     { box-shadow:0 0 0 8px rgba(61,220,104,.2), 0 0 35px rgba(61,220,104,1); }
  }

  .grower-pin {
    width:22px; height:22px; border-radius:50%; border:2px solid;
    display:flex; align-items:center; justify-content:center; font-size:11px;
  }
  .grower-pin.home    { background:#0a0a20; border-color:#4a9eff; }
  .grower-pin.farm    { background:#0a200a; border-color:#3ddc68; }
  .grower-pin.seedbed { background:#1a1200; border-color:#f5a623; }
  .grower-pin.barn    { background:#200a00; border-color:#ff7043; }
  .grower-pin.visit-due { box-shadow: 0 0 6px rgba(232,64,64,.8); }
  .due-dot {
    position:absolute; top:-2px; right:-2px;
    width:7px; height:7px; border-radius:50%;
    background:var(--red); border:1px solid var(--bg);
  }
  .grower-pin { position:relative; }

  /* ── Mobile bottom nav ─────────────────────────────────────────────────────── */
  .mob-nav {
    display:none; position:fixed; bottom:0; left:0; right:0; z-index:3000;
    background:var(--surface); border-top:1px solid var(--border);
    height:56px;
  }
  .mob-nav-inner { display:flex; height:100%; }
  .mob-tab {
    flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center;
    gap:3px; font-size:9px; color:var(--muted); cursor:pointer;
    border:none; background:transparent; transition:color .2s;
  }
  .mob-tab .mob-icon { font-size:18px; line-height:1; }
  .mob-tab.active { color:var(--green); }

  /* ── Mobile slide-up panel ─────────────────────────────────────────────────── */
  .mob-sheet {
    display:none; position:fixed; left:0; right:0; bottom:56px; z-index:2500;
    background:var(--surface); border-top:2px solid var(--border);
    border-radius:16px 16px 0 0;
    max-height:70vh; overflow-y:auto;
    transform:translateY(100%); transition:transform .3s ease;
  }
  .mob-sheet.open { transform:translateY(0); }
  .mob-sheet-handle {
    width:36px; height:4px; background:var(--border); border-radius:2px;
    margin:10px auto 6px;
  }

  /* ── Mobile header simplified ──────────────────────────────────────────────── */
  @media (max-width: 768px) {
    .shell {
      grid-template-columns: 1fr;
      grid-template-rows: 48px 1fr;
    }
    header {
      padding:0 12px; height:48px; overflow:hidden;
      flex-wrap:nowrap;
    }
    .hdr-stats { display:none; }
    #countdown  { display:none; }
    header > div[style*="gap:8px"] { display:none; } /* hide nav links */

    /* Map takes full screen below header */
    .map-wrap { position:fixed; top:48px; left:0; right:0; bottom:56px; }
    #map { height:100%; }

    /* Aside becomes a sheet, hidden by default */
    aside {
      display:none; /* controlled by JS */
      position:fixed; top:48px; left:0; right:0; bottom:56px;
      z-index:2000; border-right:none; border-top:2px solid var(--border);
    }
    aside.mob-open { display:flex; }

    /* Detail panel full width at bottom on mobile */
    #detail-panel {
      bottom:60px; left:8px; right:8px; width:auto;
      max-height:55vh;
    }

    /* Geofence alert top position adjusted */
    #geofence-alert {
      top:56px; max-width:calc(100% - 24px);
    }

    /* Search results full width */
    .search-results { left:0; right:0; max-height:180px; }

    /* Mobile nav visible */
    .mob-nav { display:block; }

    /* Logo smaller */
    .logo { font-size:15px; }

    /* Mobile stats bar below header */
    .mob-stats-bar {
      display:flex !important;
      position:fixed; top:48px; left:0; right:0; z-index:1500;
      background:rgba(10,15,10,.92); border-bottom:1px solid var(--border);
      padding:4px 12px; gap:10px; overflow-x:auto; backdrop-filter:blur(4px);
    }
    .mob-stats-bar::-webkit-scrollbar { display:none; }
    .map-wrap { top:72px; } /* push map below stats bar */
  }

  /* Desktop — hide mobile elements */
  @media (min-width: 769px) {
    .mob-nav { display:none !important; }
    .mob-stats-bar { display:none !important; }
  }

  .mob-stats-bar { display:none; } /* hidden by default, shown via media query */
</style>
</head>
<body>

<?php
$devices = [];
$growers = [];
require "conn.php";
date_default_timezone_set("Africa/Harare");
$conn->query("SET time_zone = '+02:00'");
require "validate.php";

// ── Device locations ──────────────────────────────────────────────────────────
$result = $conn->query("
    SELECT dl.device_id, dl.officer_id,
           dl.latitude, dl.longitude, dl.accuracy, dl.battery_level,
           dl.source, dl.created_at,
           fo.name  AS officer_name,
           fo.phone AS officer_phone,
           TIMESTAMPDIFF(MINUTE, dl.created_at, NOW()) AS minutes_ago
    FROM device_locations dl
    INNER JOIN (
        SELECT device_id, MAX(id) AS max_id
        FROM device_locations
        GROUP BY device_id
    ) latest ON dl.id = latest.max_id
    LEFT JOIN field_officers fo ON dl.officer_id = fo.id
    ORDER BY dl.created_at DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) $devices[] = $row;
    $result->free();
}

// ── Growers with 4 separate location tables + last visit ─────────────────────
// NOTE: crop_stages and barn_curing joins removed until tables are created
$growerResult = $conn->query("
    SELECT
        g.id,
        g.grower_num,
        g.name,
        g.surname,
        ll.latitude          AS home_lat,
        ll.longitude         AS home_lng,
        gf.latitude          AS farm_lat,
        gf.longitude         AS farm_lng,
        sl.latitude          AS seedbed_lat,
        sl.longitude         AS seedbed_lng,
        bl.latitude          AS barn_lat,
        bl.longitude         AS barn_lng,
        v.last_visit,
        DATEDIFF(NOW(), v.last_visit) AS days_since_visit,
        ge.entry_count               AS geofence_today,
        ge.last_entry                AS geofence_last_entry,
        NULL                         AS crop_stage,
        NULL                         AS barn_status,
        NULL                         AS barn_temp,
        -- Latest daily weather
        wd.temp              AS w_temp,
        wd.temp_min          AS w_temp_min,
        wd.temp_max          AS w_temp_max,
        wd.humidity          AS w_humidity,
        wd.rain              AS w_rain,
        wd.wind_speed        AS w_wind,
        wd.clouds            AS w_clouds,
        wd.city              AS w_city,
        wd.datetime          AS w_datetime,
        -- Accumulated totals
        wt.rain              AS wt_rain,
        wt.temp_max          AS wt_temp_max,
        wt.temp_min          AS wt_temp_min,
        wt.humidity          AS wt_humidity
    FROM growers g
    LEFT JOIN lat_long         ll ON ll.growerid  = g.id
    LEFT JOIN grower_farm      gf ON gf.growerid  = g.id
    LEFT JOIN seedbed_location sl ON sl.growerid  = g.id
    LEFT JOIN barn_location    bl ON bl.growerid  = g.id
    LEFT JOIN (
        SELECT growerid, MAX(created_at) AS last_visit
        FROM visits
        GROUP BY growerid
    ) v ON v.growerid = g.id
    LEFT JOIN (
        SELECT growerid,
               COUNT(*)        AS entry_count,
               MAX(created_at) AS last_entry
        FROM grower_geofence_entry_point
        WHERE DATE(created_at) = CURDATE()
        GROUP BY growerid
    ) ge ON ge.growerid = g.id
    LEFT JOIN (
        SELECT w1.growerid, w1.temp, w1.temp_min, w1.temp_max,
               w1.humidity, w1.rain, w1.wind_speed, w1.clouds, w1.city, w1.datetime
        FROM weather w1
        INNER JOIN (
            SELECT growerid, MAX(datetime) AS max_dt
            FROM weather
            WHERE DATE(datetime) = CURDATE() - INTERVAL 1 DAY
            GROUP BY growerid
        ) w2 ON w1.growerid = w2.growerid AND w1.datetime = w2.max_dt
    ) wd ON wd.growerid = g.id
    LEFT JOIN (
        SELECT wt1.growerid, wt1.rain, wt1.temp_max, wt1.temp_min, wt1.humidity
        FROM grower_weather_total wt1
        INNER JOIN (
            SELECT growerid, MAX(datetime) AS max_dt
            FROM grower_weather_total
            GROUP BY growerid
        ) wt2 ON wt1.growerid = wt2.growerid AND wt1.datetime = wt2.max_dt
    ) wt ON wt.growerid = g.id
    WHERE ll.latitude  IS NOT NULL OR gf.latitude IS NOT NULL
       OR sl.latitude  IS NOT NULL OR bl.latitude IS NOT NULL
    ORDER BY g.name, g.surname
");
if ($growerResult) {
    while ($row = $growerResult->fetch_assoc()) $growers[] = $row;
    $growerResult->free();
}

$conn->close();

// ── Build flat grower pins array for JS ───────────────────────────────────────
function validCoord($lat, $lng) {
    $lat = (float)$lat; $lng = (float)$lng;
    return ($lat != 0 || $lng != 0) && $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180
        ? ['lat' => $lat, 'lng' => $lng] : null;
}

$growerPins = [];
$visitDueThreshold = 14; // days — flag as overdue after this many days
foreach ($growers as $g) {
    $visited    = !empty($g['last_visit']);
    $daysSince  = $visited ? (int)$g['days_since_visit'] : null;
    $visitDue   = !$visited || $daysSince >= $visitDueThreshold;
    $locs = [
        'home'    => validCoord($g['home_lat'],    $g['home_lng']),
        'farm'    => validCoord($g['farm_lat'],    $g['farm_lng']),
        'seedbed' => validCoord($g['seedbed_lat'], $g['seedbed_lng']),
        'barn'    => validCoord($g['barn_lat'],    $g['barn_lng']),
    ];
    foreach ($locs as $type => $coords) {
        if (!$coords) continue;
        $growerPins[] = [
            'grower_id'       => (int)$g['id'],
            'grower_num'      => $g['grower_num'],
            'name'            => $g['name'] . ' ' . $g['surname'],
            'type'            => $type,
            'lat'             => $coords['lat'],
            'lng'             => $coords['lng'],
            'visited'         => $visited,
            'visit_due'       => $visitDue,
            'last_visit'      => $g['last_visit'],
            'days_since'      => $daysSince,
            'crop_stage'      => $g['crop_stage'] ?? null,
            'barn_status'     => $g['barn_status'] ?? null,
            'barn_temp'       => $g['barn_temp'] ?? null,
            'geofence_today'  => (int)($g['geofence_today'] ?? 0),
            // Daily weather
            'w_temp'          => $g['w_temp']     !== null ? round((float)$g['w_temp'], 1)     : null,
            'w_temp_min'      => $g['w_temp_min'] !== null ? round((float)$g['w_temp_min'], 1) : null,
            'w_temp_max'      => $g['w_temp_max'] !== null ? round((float)$g['w_temp_max'], 1) : null,
            'w_humidity'      => $g['w_humidity'] !== null ? round((float)$g['w_humidity'], 0) : null,
            'w_rain'          => $g['w_rain']     !== null ? round((float)$g['w_rain'], 1)     : null,
            'w_wind'          => $g['w_wind']     !== null ? round((float)$g['w_wind'], 1)     : null,
            'w_clouds'        => $g['w_clouds']   !== null ? round((float)$g['w_clouds'], 0)   : null,
            'w_city'          => $g['w_city']     ?? null,
            'w_datetime'      => $g['w_datetime'] ?? null,
            // Accumulated totals
            'wt_rain'         => $g['wt_rain']     !== null ? round((float)$g['wt_rain'], 1)     : null,
            'wt_temp_max'     => $g['wt_temp_max'] !== null ? round((float)$g['wt_temp_max'], 1) : null,
            'wt_temp_min'     => $g['wt_temp_min'] !== null ? round((float)$g['wt_temp_min'], 1) : null,
            'wt_humidity'     => $g['wt_humidity'] !== null ? round((float)$g['wt_humidity'], 0) : null,
        ];
    }
}

// ── Stats for header ──────────────────────────────────────────────────────────
$visitDueCount    = count(array_filter($growers, fn($g) => empty($g['last_visit']) || (int)$g['days_since_visit'] >= $visitDueThreshold));
// $activeCuring — enabled once barn_curing table is created

function statusInfo($m) {
    return $m <= 30 ? ['green','Active'] : ($m <= 120 ? ['amber','Stale'] : ['red','Lost']);
}

$total        = count($devices);
$active       = count(array_filter($devices, fn($d) => $d['minutes_ago'] <= 30));
$stale        = count(array_filter($devices, fn($d) => $d['minutes_ago'] > 30 && $d['minutes_ago'] <= 120));
$lost         = count(array_filter($devices, fn($d) => $d['minutes_ago'] > 120));
$lowBatt      = count(array_filter($devices, fn($d) => $d['battery_level'] !== null && $d['battery_level'] <= 20));
$viaSms       = count(array_filter($devices, fn($d) => $d['source'] === 'sms'));
$totalGrowers = count($growers);
$unvisited    = count(array_filter($growers, fn($g) => empty($g['last_visit'])));
?>

<div class="shell">
  <header>
    <div class="logo">GMS<span>/</span>Tracker</div>
    <div class="hdr-stats">
      <div class="hdr-stat"><b style="color:var(--green)"><?= $active ?></b> Active</div>
      <div class="hdr-stat"><b style="color:var(--amber)"><?= $stale ?></b> Stale</div>
      <div class="hdr-stat"><b style="color:var(--red)"><?= $lost ?></b> Lost</div>
      <?php if ($viaSms > 0): ?><div class="hdr-stat"><b style="color:var(--blue)">📱<?= $viaSms ?></b> SMS</div><?php endif ?>
      <?php if ($lowBatt > 0): ?><div class="hdr-stat"><b style="color:var(--red)">🔋<?= $lowBatt ?></b> Low</div><?php endif ?>
      <div class="hdr-stat"><b style="color:var(--purple)"><?= $totalGrowers ?></b> Growers</div>
      <?php if ($visitDueCount > 0): ?><div class="hdr-stat"><b style="color:var(--red)">⚠️<?= $visitDueCount ?></b> Visit Due</div><?php endif ?>
    </div>
    <div style="display:flex;gap:8px;margin-left:12px;flex-wrap:wrap">
      <a href="officer_coverage.php" style="font-family:'Space Mono',monospace;font-size:10px;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:3px 8px;border-radius:4px;">📊 Coverage</a>
      <a href="officer_league.php"   style="font-family:'Space Mono',monospace;font-size:10px;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:3px 8px;border-radius:4px;">🏆 League</a>
      <a href="dead_zones.php"       style="font-family:'Space Mono',monospace;font-size:10px;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:3px 8px;border-radius:4px;">🚫 Dead Zones</a>
      <a href="route_planner.php"    style="font-family:'Space Mono',monospace;font-size:10px;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:3px 8px;border-radius:4px;">🗺 Route</a>
      <a href="reports_hub.php"      style="font-family:'Space Mono',monospace;font-size:10px;color:var(--green);text-decoration:none;border:1px solid var(--green);padding:3px 8px;border-radius:4px;">📋 Reports</a>
      <a href="grower_weather.php"   style="font-family:'Space Mono',monospace;font-size:10px;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:3px 8px;border-radius:4px;">🌦 Weather</a>
    </div>
    <span id="countdown">Auto-refresh in 60s</span>
  </header>

  <!-- Mobile stats bar (scrollable, shown below header on small screens) -->
  <div class="mob-stats-bar">
    <div class="hdr-stat"><b style="color:var(--green)"><?= $active ?></b> Active</div>
    <div class="hdr-stat"><b style="color:var(--amber)"><?= $stale ?></b> Stale</div>
    <div class="hdr-stat"><b style="color:var(--red)"><?= $lost ?></b> Lost</div>
    <div class="hdr-stat"><b style="color:var(--purple)"><?= $totalGrowers ?></b> Growers</div>
    <?php if ($visitDueCount > 0): ?><div class="hdr-stat"><b style="color:var(--red)">⚠️<?= $visitDueCount ?></b> Due</div><?php endif ?>
    <?php if ($viaSms > 0): ?><div class="hdr-stat"><b style="color:var(--blue)">📱<?= $viaSms ?></b> SMS</div><?php endif ?>
    <?php if ($lowBatt > 0): ?><div class="hdr-stat"><b style="color:var(--red)">🔋<?= $lowBatt ?></b> Low</div><?php endif ?>
  </div>

  <!-- Mobile bottom nav -->
  <nav class="mob-nav">
    <div class="mob-nav-inner">
      <button class="mob-tab active" id="mob-tab-map"     onclick="mobTab('map',this)">
        <span class="mob-icon">🗺</span>Map
      </button>
      <button class="mob-tab"        id="mob-tab-officers" onclick="mobTab('officers',this)">
        <span class="mob-icon">👮</span>Officers
      </button>
      <button class="mob-tab"        id="mob-tab-search"   onclick="mobTab('search',this)">
        <span class="mob-icon">🔍</span>Search
      </button>
      <button class="mob-tab"        id="mob-tab-layers"   onclick="mobTab('layers',this)">
        <span class="mob-icon">🗂</span>Layers
      </button>
      <button class="mob-tab"        onclick="location.href='grower_weather.php'">
        <span class="mob-icon">🌦</span>Weather
      </button>
    </div>
  </nav>

  <aside>
    <div class="sb-head">
      <h2>Devices (<?= $total ?>)</h2>
      <p>Click to locate on map</p>
    </div>

    <div class="filter-row">
      <button class="fbtn active"  onclick="doFilter('all',this)">All</button>
      <button class="fbtn f-amber" onclick="doFilter('amber',this)">Stale</button>
      <button class="fbtn f-red"   onclick="doFilter('red',this)">Lost</button>
      <button class="fbtn f-blue"  onclick="doFilter('sms',this)">Via SMS</button>
    </div>

    <!-- Grower layer toggles -->
    <div class="layer-row">
      <span class="layer-label">Layers:</span>
      <button class="lbtn on" id="lbtn-home"      onclick="toggleLayer('home',this)">🏠</button>
      <button class="lbtn on" id="lbtn-farm"      onclick="toggleLayer('farm',this)">🌱</button>
      <button class="lbtn on" id="lbtn-seedbed"   onclick="toggleLayer('seedbed',this)">🌿</button>
      <button class="lbtn on" id="lbtn-barn"      onclick="toggleLayer('barn',this)">🏚</button>
      <button class="lbtn on" id="lbtn-visited"   onclick="toggleLayer('visited',this)" style="color:var(--green)">✅</button>
      <button class="lbtn on" id="lbtn-unvisited" onclick="toggleLayer('unvisited',this)" style="color:var(--red)">❌</button>
      <button class="lbtn on" id="lbtn-due"       onclick="toggleLayer('due',this)" style="color:var(--red)">⚠️ Due</button>
    </div>

    <!-- Grower search -->
    <div class="search-wrap" id="growerSearchWrap">
      <span class="search-icon">🔍</span>
      <input type="text" id="growerSearchInput" placeholder="Search grower or officer..."
             oninput="searchGrowers(this.value)"
             onkeydown="handleSearchKey(event)"
             autocomplete="off">
      <div class="search-results" id="searchResults"></div>
    </div>

    <div class="device-list" id="deviceList">
    <?php foreach ($devices as $i => $d):
      [$color, $label] = statusInfo((int)$d['minutes_ago']);
      $name   = htmlspecialchars($d['officer_name'] ?? ('Device …' . substr($d['device_id'], -6)));
      $batt   = $d['battery_level'] !== null ? (int)$d['battery_level'] : -1;
      $ago    = (int)$d['minutes_ago'];
      $agoStr = $ago < 60 ? "{$ago}m ago" : round($ago / 60, 1) . "h ago";
      $src    = $d['source'] ?? 'realtime';
      $srcBadge = $src === 'sms'
                ? '<span class="badge b-sms">📱 SMS</span>'
                : ($src === 'offline_queue'
                    ? '<span class="badge b-queue">📦 Queued</span>'
                    : '<span class="badge b-live">🟢 Live</span>');
      $oGrowers   = count($growers);
      $oUnvisited = count(array_filter($growers, fn($g) => empty($g['last_visit'])));
    ?>
    <div class="device-card"
         data-index="<?= $i ?>"
         data-status="<?= $color ?>"
         data-source="<?= htmlspecialchars($src) ?>"
         onclick="selectDevice(<?= $i ?>)">
      <div class="status-dot dot-<?= $color ?>"></div>
      <div class="card-body">
        <div class="card-name"><?= $name ?></div>
        <div class="card-sub">
          <span><?= $label ?> · <?= $agoStr ?></span>
          <?= $srcBadge ?>
          <?php if ($batt >= 0): ?>
            <span class="badge <?= $batt <= 20 ? 'b-batt' : '' ?>">🔋<?= $batt ?>%</span>
          <?php endif ?>
          <?php if ($oGrowers > 0): ?>
            <span class="badge b-grower">👨‍🌾<?= $oGrowers ?>
              <?php if ($oUnvisited > 0): ?>&nbsp;<span style="color:var(--red)"><?= $oUnvisited ?>✗</span><?php endif ?>
            </span>
          <?php endif ?>
        </div>
      </div>
    </div>
    <?php endforeach ?>
    </div>
  </aside>

  <div class="map-wrap">
    <div id="map"></div>
    <div id="geofence-alert"></div>

    <div id="detail-panel">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
        <div class="dp-name" id="dp-name" style="margin-bottom:0">—</div>
        <button class="btn-history" onclick="viewHistory()" style="width:auto;margin-top:0;padding:5px 10px;font-size:10px;white-space:nowrap">📍 History</button>
      </div>
      <div class="dp-row"><span class="dp-label">Phone</span><span class="dp-val" id="dp-phone">—</span></div>
      <div class="dp-row"><span class="dp-label">Battery</span><span class="dp-val" id="dp-batt">—</span></div>
      <div class="dp-row"><span class="dp-label">Last Seen</span><span class="dp-val" id="dp-seen">—</span></div>
      <div class="dp-row"><span class="dp-label">Accuracy</span><span class="dp-val" id="dp-acc">—</span></div>
      <div class="dp-row"><span class="dp-label">Source</span><span class="dp-val" id="dp-src">—</span></div>
      <hr class="dp-divider">
      <div class="dp-coords" id="dp-coords">—</div>

      <div class="dp-section-title">📍 Nearest Grower Locations</div>
      <div class="nearby-list" id="dp-nearby">
        <div style="font-size:10px;color:var(--muted)">Select a device to see nearby growers</div>
      </div>
    </div>
  </div>
</div>

<script>
const devices    = <?= json_encode($devices) ?>;
const growerPins = <?= json_encode($growerPins) ?>;

let map, officerMarkers = [], growerMarkers = [], selectedIndex = null;
let layers = { home:true, farm:true, seedbed:true, barn:true, visited:true, unvisited:true, due:true, curing:true };

// ── Map init ──────────────────────────────────────────────────────────────────
map = L.map('map');
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
  { attribution:'© OpenStreetMap © CARTO', maxZoom:19 }).addTo(map);

// ── Officer markers ───────────────────────────────────────────────────────────
devices.forEach((d, i) => {
  const m   = parseInt(d.minutes_ago);
  const cls = m <= 30 ? '' : m <= 120 ? 'stale' : 'lost';
  const icon = L.divIcon({
    className: '',
    html: `<div class="gms-marker ${cls}" id="officer-marker-${i}">${cls==='lost'?'🔴':cls==='stale'?'🟡':'🟢'}</div>`,
    iconSize:[30,30], iconAnchor:[15,15], popupAnchor:[0,-18]
  });
  const mk = L.marker([parseFloat(d.latitude), parseFloat(d.longitude)], {
    icon,
    zIndexOffset: 1000  // always above grower pins
  }).addTo(map).bindPopup(`<b>${d.officer_name||d.device_id}</b><br>${d.created_at}`);
  mk.on('click', () => selectDevice(i));
  officerMarkers.push(mk);
});

// ── Grower location markers ───────────────────────────────────────────────────
const typeEmoji = { home:'🏠', farm:'🌱', seedbed:'🌿', barn:'🏚' };

growerPins.forEach(p => {
  const visitDueClass = p.visit_due ? 'visit-due' : '';
  const icon = L.divIcon({
    className: '',
    html: `<div class="grower-pin ${p.type} ${p.visited?'visited':'unvisited'} ${visitDueClass}">${typeEmoji[p.type]||'📍'}${p.visit_due?'<span class="due-dot"></span>':''}</div>`,
    iconSize:[22,22], iconAnchor:[11,11], popupAnchor:[0,-14]
  });

  const visitStr  = p.last_visit
    ? `${p.days_since >= 14 ? '⚠️' : '✅'} Last visited: ${p.last_visit} (${p.days_since}d ago)`
    : `<span style="color:#e84040">❌ Never visited</span>`;
  const stageStr  = p.crop_stage  ? `<br>🌿 Stage: <b>${p.crop_stage}</b>` : '';
  const barnStr   = p.barn_status ? `<br>🔥 Barn: <b>${p.barn_status}</b>${p.barn_temp?' · '+p.barn_temp+'°C':''}` : '';
  const geoStr    = p.geofence_today > 0
    ? `<br><span style="color:#4a9eff">📍 ${p.geofence_today} officer entry${p.geofence_today>1?'s':''} today</span>`
    : '';
  // Daily weather
  const weatherStr = p.w_temp !== null
    ? `<br><span style="color:#4a6b4a;font-size:9px">Yesterday's weather</span>` +
      `<br><span style="color:#4a9eff">🌡 ${p.w_temp}°C</span>` +
      `<span style="color:#aaa"> (${p.w_temp_min}–${p.w_temp_max}°C)</span>` +
      (p.w_rain > 0  ? ` <span style="color:#4a9eff">🌧 ${p.w_rain}mm</span>` : '') +
      (p.w_humidity  ? ` <span style="color:#aaa">💧${p.w_humidity}%</span>` : '') +
      (p.w_wind      ? ` <span style="color:#aaa">💨${p.w_wind}m/s</span>` : '')
    : '';
  // Accumulated totals
  const accumStr = p.wt_rain !== null
    ? `<br><span style="color:#f5a623">📊 Season: rain ${p.wt_rain}mm · max ${p.wt_temp_max}°C · min ${p.wt_temp_min}°C</span>`
    : '';

  const mk = L.marker([p.lat, p.lng], {icon, zIndexOffset: -500})
    .addTo(map)
    .bindPopup(
      `<b>${p.name}</b> <span style="font-size:10px;color:#aaa">#${p.grower_num}</span><br>` +
      `<span style="font-size:10px">${typeEmoji[p.type]} ${p.type.charAt(0).toUpperCase()+p.type.slice(1)} location</span><br>` +
      `${visitStr}${stageStr}${barnStr}${geoStr}${weatherStr}${accumStr}`
    );
  mk._pin = p;
  growerMarkers.push(mk);
});

// Fit map to all markers
const allMk = [...officerMarkers, ...growerMarkers];
if (allMk.length) map.fitBounds(L.featureGroup(allMk).getBounds().pad(0.15));
else map.setView([-17.8292, 31.0522], 12);

// ── Layer toggles ─────────────────────────────────────────────────────────────
function toggleLayer(type, btn) {
  layers[type] = !layers[type];
  btn.classList.toggle('on', layers[type]);
  applyLayers();
}

function applyLayers() {
  growerMarkers.forEach(mk => {
    const p = mk._pin;
    const typeOn   = layers[p.type];
    const visitOn  = p.visited ? layers.visited : layers.unvisited;
    const dueOn    = !layers.due    ? !p.visit_due   : true;
    const curingOn = !layers.curing ? p.barn_status !== 'curing' : true;
    const show = typeOn && visitOn && dueOn && curingOn;
    show ? (!map.hasLayer(mk) && mk.addTo(map)) : (map.hasLayer(mk) && map.removeLayer(mk));
  });
}

// ── Select device ─────────────────────────────────────────────────────────────
function selectDevice(i) {
  // Remove glow from previously selected officer
  if (selectedIndex !== null) {
    const prev = document.getElementById(`officer-marker-${selectedIndex}`);
    if (prev) prev.classList.remove('gms-marker-selected');
  }

  selectedIndex = i;
  const d = devices[i];
  document.querySelectorAll('.device-card').forEach(c => c.classList.remove('selected'));
  document.querySelector(`.device-card[data-index="${i}"]`)?.classList.add('selected');
  map.setView([d.latitude, d.longitude], 14, {animate:true});

  // Bring officer marker to absolute front and glow
  officerMarkers[i].setZIndexOffset(2000);
  const markerEl = document.getElementById(`officer-marker-${i}`);
  if (markerEl) markerEl.classList.add('gms-marker-selected');

  // Reset other officer markers z-index
  officerMarkers.forEach((mk, idx) => {
    if (idx !== i) mk.setZIndexOffset(1000);
  });

  officerMarkers[i].openPopup();

  document.getElementById('detail-panel').classList.add('visible');
  document.getElementById('dp-name').textContent  = d.officer_name || d.device_id;
  document.getElementById('dp-phone').textContent = d.officer_phone || '—';

  const b   = d.battery_level != null ? parseInt(d.battery_level) : -1;
  const bEl = document.getElementById('dp-batt');
  bEl.textContent = b >= 0 ? b+'%' : '—';
  bEl.className   = 'dp-val' + (b >= 0 && b <= 20 ? ' warn' : '');

  const ago = parseInt(d.minutes_ago);
  document.getElementById('dp-seen').textContent = ago < 60 ? ago+' min ago' : (ago/60).toFixed(1)+' hrs ago';
  document.getElementById('dp-acc').textContent  = d.accuracy ? '±'+Math.round(d.accuracy)+'m' : '—';

  const srcMap = {realtime:'🟢 Internet (live)', sms:'📱 Via SMS', offline_queue:'📦 Offline queue'};
  document.getElementById('dp-src').textContent    = srcMap[d.source] || d.source;
  document.getElementById('dp-coords').textContent = parseFloat(d.latitude).toFixed(6)+', '+parseFloat(d.longitude).toFixed(6);

  showNearby(parseFloat(d.latitude), parseFloat(d.longitude));
  checkGeofence(parseFloat(d.latitude), parseFloat(d.longitude), d.officer_id);
  mobCloseSidebar();
}

// ── Nearest growers ───────────────────────────────────────────────────────────
function hdist(lat1,lng1,lat2,lng2) {
  const R=6371, r=x=>x*Math.PI/180, dLa=r(lat2-lat1), dLo=r(lng2-lng1);
  const a=Math.sin(dLa/2)**2+Math.cos(r(lat1))*Math.cos(r(lat2))*Math.sin(dLo/2)**2;
  return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
}

function showNearby(oLat, oLng) {
  // Deduplicate by grower — keep shortest distance pin per grower
  const byGrower = {};
  growerPins.forEach(p => {
    const d = hdist(oLat, oLng, p.lat, p.lng);
    if (!byGrower[p.grower_id] || d < byGrower[p.grower_id].dist)
      byGrower[p.grower_id] = {...p, dist:d};
  });

  const nearby = Object.values(byGrower).sort((a,b)=>a.dist-b.dist).slice(0,8);
  const el = document.getElementById('dp-nearby');

  if (!nearby.length) {
    el.innerHTML = '<div style="font-size:10px;color:var(--muted)">No growers found</div>';
    return;
  }

  el.innerHTML = nearby.map(p => {
    const distStr  = p.dist < 1 ? Math.round(p.dist*1000)+'m' : p.dist.toFixed(1)+'km';
    const visitStr = p.visited
      ? `<span class="${p.days_since>=14?'ni-unvisited':'ni-visited'}">${p.days_since>=14?'⚠️':'✅'} ${p.days_since}d ago</span>`
      : `<span class="ni-unvisited">❌ Not visited</span>`;
    const stageStr = p.crop_stage ? `<span style="color:var(--green)">🌿 ${p.crop_stage}</span>` : '';
    const barnStr  = p.barn_status === 'curing' ? `<span style="color:var(--amber)">🔥 Curing${p.barn_temp?' '+p.barn_temp+'°C':''}</span>` : '';

    const geoStr   = p.geofence_today > 0
      ? `<span style="color:var(--blue)">📍${p.geofence_today} entry today</span>`
      : '';
    const wxStr    = p.w_temp !== null
      ? `<span style="color:var(--blue)">🌡${p.w_temp}°C${p.w_rain>0?' 🌧'+p.w_rain+'mm':''}</span>`
      : '';
    const accumWxStr = p.wt_rain !== null
      ? `<span style="color:var(--amber)">📊${p.wt_rain}mm rain</span>`
      : '';

    return `<div class="nearby-item ${p.visit_due?'unvisited':'visited'}"
                 onclick="flyToGrower(${p.grower_id}, ${p.lat}, ${p.lng})">
      <div class="ni-name">${p.name} <span style="color:var(--muted);font-size:9px">#${p.grower_num}</span></div>
      <div class="ni-meta">
        <span class="ni-dist">${distStr}</span>
        ${visitStr}
        ${stageStr}
        ${barnStr}
        ${geoStr}
        ${wxStr}
        ${accumWxStr}
      </div>
    </div>`;
  }).join('');
}

// ── Fly to grower and open popup ──────────────────────────────────────────────
function flyToGrower(growerId, lat, lng) {
  mobCloseSidebar();
  map.setView([lat, lng], 17, {animate:true});

  const target = growerMarkers.find(mk =>
    mk._pin.grower_id === growerId &&
    mk._pin.lat === lat &&
    mk._pin.lng === lng
  );

  if (target) {
    map.once('moveend', () => {
      if (!map.hasLayer(target)) target.addTo(map);
      target.openPopup();
    });
  }
}

// ── Geofence detection — officer within 500m of unvisited grower ──────────────
function checkGeofence(oLat, oLng, officerId) {
  const RADIUS_KM = 0.5; // 500 metres
  const nearby = growerPins.filter(p =>
    p.visit_due && hdist(oLat, oLng, p.lat, p.lng) <= RADIUS_KM
  );

  const alertEl = document.getElementById('geofence-alert');
  if (!nearby.length) { alertEl.style.display = 'none'; return; }

  // Deduplicate by grower
  const seen = new Set();
  const unique = nearby.filter(p => { if(seen.has(p.grower_id)) return false; seen.add(p.grower_id); return true; });

  alertEl.style.display = 'block';
  alertEl.innerHTML = `<b>⚠️ ${unique.length} unvisited grower${unique.length>1?'s':''} within 500m</b><br>` +
    unique.slice(0,3).map(p => `· ${p.name} #${p.grower_num} (${Math.round(hdist(oLat,oLng,p.lat,p.lng)*1000)}m)`).join('<br>') +
    (unique.length > 3 ? `<br><i>+${unique.length-3} more</i>` : '');
}

// ── Unified search — growers + field officers ─────────────────────────────────
const growerIndex = {};
growerPins.forEach(p => {
  if (!growerIndex[p.grower_id]) growerIndex[p.grower_id] = p;
});
const growerList = Object.values(growerIndex);

let searchHighlight = -1;

function searchGrowers(query) {
  const box = document.getElementById('searchResults');
  query = query.trim().toLowerCase();
  searchHighlight = -1;

  if (!query) { box.style.display = 'none'; return; }

  // Match growers
  const growerMatches = growerList.filter(p =>
    p.name.toLowerCase().includes(query) ||
    String(p.grower_num).toLowerCase().includes(query)
  ).slice(0, 6);

  // Match field officers
  const officerMatches = devices.filter((d, i) => {
    const name = (d.officer_name || d.device_id || '').toLowerCase();
    return name.includes(query);
  }).slice(0, 4);

  if (!growerMatches.length && !officerMatches.length) {
    box.innerHTML = '<div class="sr-empty">No results found</div>';
    box.style.display = 'block';
    return;
  }

  let html = '';

  // Officer section
  if (officerMatches.length) {
    html += `<div style="font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);padding:6px 12px 3px;background:#0d150d">Field Officers</div>`;
    html += officerMatches.map((d, i) => {
      const idx     = devices.indexOf(d);
      const m       = parseInt(d.minutes_ago);
      const status  = m <= 30 ? `<span style="color:var(--green)">🟢 Active</span>`
                    : m <= 120 ? `<span style="color:var(--amber)">🟡 Stale</span>`
                    : `<span style="color:var(--red)">🔴 Lost</span>`;
      const ago     = m < 60 ? m+'m ago' : (m/60).toFixed(1)+'h ago';
      return `<div class="sr-item" data-idx="o${idx}" onmousedown="selectOfficerResult(${idx})">
        <div class="sr-name">👮 ${d.officer_name || d.device_id}</div>
        <div class="sr-meta">${status} · ${ago}
          ${d.battery_level ? `<span>🔋${d.battery_level}%</span>` : ''}
        </div>
      </div>`;
    }).join('');
  }

  // Grower section
  if (growerMatches.length) {
    html += `<div style="font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);padding:6px 12px 3px;background:#0d150d">Growers</div>`;
    html += growerMatches.map((p, i) => {
      const visitStr = p.visited
        ? `<span style="color:var(--green)">✅ ${p.days_since}d ago</span>`
        : `<span style="color:var(--red)">❌ Not visited</span>`;
      const wxStr = p.w_temp !== null ? `<span>🌡${p.w_temp}°C</span>` : '';
      return `<div class="sr-item" data-idx="g${i}"
                   onmousedown="selectGrowerResult(${JSON.stringify(p).replace(/"/g,'&quot;')})">
        <div class="sr-name">👨‍🌾 ${p.name} <span style="color:var(--muted)">#${p.grower_num}</span></div>
        <div class="sr-meta">${visitStr} ${wxStr}</div>
      </div>`;
    }).join('');
  }

  box.innerHTML = html;
  box.style.display = 'block';
}

function handleSearchKey(e) {
  const box   = document.getElementById('searchResults');
  const items = box.querySelectorAll('.sr-item');
  if (!items.length) return;

  if (e.key === 'ArrowDown') {
    searchHighlight = Math.min(searchHighlight + 1, items.length - 1);
  } else if (e.key === 'ArrowUp') {
    searchHighlight = Math.max(searchHighlight - 1, 0);
  } else if (e.key === 'Enter' && searchHighlight >= 0) {
    items[searchHighlight].dispatchEvent(new MouseEvent('mousedown'));
    return;
  } else if (e.key === 'Escape') {
    closeSearch(); return;
  }

  items.forEach((el, i) => el.style.background = i === searchHighlight ? 'rgba(61,220,104,.1)' : '');
  if (searchHighlight >= 0) items[searchHighlight].scrollIntoView({block:'nearest'});
}

function selectOfficerResult(i) {
  closeSearch();
  selectDevice(i); // selectDevice already calls mobCloseSidebar
  document.querySelector(`.device-card[data-index="${i}"]`)?.scrollIntoView({behavior:'smooth', block:'nearest'});
}

function selectGrowerResult(p) {
  closeSearch();
  mobCloseSidebar();
  map.setView([p.lat, p.lng], 17, {animate:true});

  const target = growerMarkers.find(mk => mk._pin.grower_id === p.grower_id);
  if (target) {
    map.once('moveend', () => {
      if (!map.hasLayer(target)) target.addTo(map);
      target.openPopup();
    });
  }

  // Pulse all pins for this grower
  growerMarkers.forEach(mk => {
    if (mk._pin.grower_id === p.grower_id) {
      const el = mk.getElement();
      if (el) {
        el.style.transform = 'scale(1.4)';
        el.style.transition = 'transform .2s';
        el.style.zIndex = '9999';
        setTimeout(() => { el.style.transform = ''; el.style.zIndex = ''; }, 2000);
      }
    }
  });
}

function closeSearch() {
  document.getElementById('growerSearchInput').value = '';
  document.getElementById('searchResults').style.display = 'none';
}

// Close search when clicking outside
document.addEventListener('click', e => {
  if (!document.getElementById('growerSearchWrap').contains(e.target)) {
    document.getElementById('searchResults').style.display = 'none';
  }
});

// ── Status filter ─────────────────────────────────────────────────────────────
function doFilter(type, btn) {
  document.querySelectorAll('.fbtn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.device-card').forEach(card => {
    const show = type==='all'
      || (type==='sms'  && card.dataset.source==='sms')
      || (type!=='sms'  && card.dataset.status===type);
    card.style.display = show ? 'flex' : 'none';
  });
}

function viewHistory() {
  if (selectedIndex===null) return;
  window.location.href = 'device_history.php?device_id='+encodeURIComponent(devices[selectedIndex].device_id);
}

// ── Mobile tab navigation ─────────────────────────────────────────────────────
function mobTab(tab, btn) {
  const aside = document.querySelector('aside');
  if (window.innerWidth > 768) return;

  document.querySelectorAll('.mob-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');

  if (tab === 'map') {
    aside.classList.remove('mob-open');
    document.getElementById('detail-panel').classList.remove('visible');
  } else if (tab === 'officers') {
    aside.classList.add('mob-open');
    document.getElementById('deviceList')?.scrollTo(0, 0);
  } else if (tab === 'search') {
    aside.classList.add('mob-open');
    setTimeout(() => document.getElementById('growerSearchInput')?.focus(), 150);
  } else if (tab === 'layers') {
    aside.classList.add('mob-open');
    setTimeout(() => document.querySelector('.layer-row')?.scrollIntoView({behavior:'smooth'}), 150);
  }
}

function mobCloseSidebar() {
  if (window.innerWidth > 768) return;
  document.querySelector('aside').classList.remove('mob-open');
  document.querySelectorAll('.mob-tab').forEach(t => t.classList.remove('active'));
  document.getElementById('mob-tab-map')?.classList.add('active');
}

window.addEventListener('resize', () => {
  if (window.innerWidth > 768) {
    document.querySelector('aside').classList.remove('mob-open');
  }
});

let secs = 900;
setInterval(() => {
  secs--;
  if (secs <= 0) { location.reload(); return; }
  const m = Math.floor(secs / 60);
  const s = secs % 60;
  document.getElementById('countdown').textContent =
    m > 0 ? `Auto-refresh in ${m}m ${s.toString().padStart(2,'0')}s` : `Auto-refresh in ${s}s`;
}, 1000);
</script>
</body>
</html>
