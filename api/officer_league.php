<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GMS · Officer League</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  :root{
    --bg:#0a0f0a;--surface:#111a11;--border:#1f2e1f;
    --green:#3ddc68;--green-dim:#1a5e30;--amber:#f5a623;
    --red:#e84040;--blue:#4a9eff;--purple:#b47eff;
    --gold:#ffd700;--silver:#c0c0c0;--bronze:#cd7f32;
    --text:#c8e6c9;--muted:#4a6b4a;--radius:6px;
  }
  html,body{font-family:'Space Mono',monospace;background:var(--bg);color:var(--text);min-height:100%}

  header{display:flex;align-items:center;gap:10px;padding:0 20px;height:56px;background:var(--surface);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100;flex-wrap:wrap}
  .logo{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:var(--green)}
  .logo span{color:var(--muted)}
  .back{font-size:11px;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:4px 10px;border-radius:4px}
  .back:hover{color:var(--green);border-color:var(--green)}
  select{background:var(--surface);border:1px solid var(--border);color:var(--text);font-family:'Space Mono',monospace;font-size:11px;padding:4px 8px;border-radius:4px}

  .content{padding:24px;max-width:1300px;margin:0 auto}

  /* Podium */
  .podium{display:flex;justify-content:center;align-items:flex-end;gap:12px;margin-bottom:32px;padding:20px 0}
  .podium-slot{display:flex;flex-direction:column;align-items:center;gap:8px}
  .podium-avatar{width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px;border:3px solid;position:relative}
  .podium-avatar .crown{position:absolute;top:-14px;font-size:18px}
  .podium-name{font-size:11px;font-weight:700;text-align:center;max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .podium-score{font-family:'Syne',sans-serif;font-size:18px;font-weight:800}
  .podium-block{border-radius:4px 4px 0 0;width:90px;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-size:20px;font-weight:800;color:var(--bg)}
  .p1 .podium-avatar{border-color:var(--gold);background:#1a1400}
  .p1 .podium-score{color:var(--gold)}
  .p1 .podium-block{background:var(--gold);height:80px}
  .p2 .podium-avatar{border-color:var(--silver);background:#141414}
  .p2 .podium-score{color:var(--silver)}
  .p2 .podium-block{background:var(--silver);height:60px}
  .p3 .podium-avatar{border-color:var(--bronze);background:#1a0e00}
  .p3 .podium-score{color:var(--bronze)}
  .p3 .podium-block{background:var(--bronze);height:44px}

  /* Tier badges */
  .tier{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700}
  .tier-gold{background:#1a1400;color:var(--gold);border:1px solid #3a2e00}
  .tier-silver{background:#141414;color:var(--silver);border:1px solid #303030}
  .tier-bronze{background:#1a0e00;color:var(--bronze);border:1px solid #3a1e00}
  .tier-iron{background:#0d0d0d;color:var(--muted);border:1px solid var(--border)}

  /* Scoring breakdown */
  .score-breakdown{display:flex;gap:4px;flex-wrap:wrap;margin-top:4px}
  .score-pill{font-size:9px;padding:1px 5px;border-radius:3px;border:1px solid}
  .sp-visits{background:#0d200d;color:var(--green);border-color:var(--green-dim)}
  .sp-conv{background:#001020;color:var(--blue);border-color:#003050}
  .sp-dist{background:#1e1500;color:var(--amber);border-color:#3a2800}
  .sp-active{background:#0e0020;color:var(--purple);border-color:#2a0050}
  .sp-geo{background:#001828;color:#7ec8ff;border-color:#003050}

  /* Main league table */
  .league-table{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:20px}
  .lt-head{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
  .lt-head h2{font-family:'Syne',sans-serif;font-size:14px;font-weight:800}

  table{width:100%;border-collapse:collapse;font-size:11px}
  th{text-align:left;padding:9px 14px;font-size:9px;text-transform:uppercase;letter-spacing:.4px;color:var(--muted);border-bottom:1px solid var(--border);background:#0d150d;white-space:nowrap;cursor:pointer}
  th:hover{color:var(--text)}
  th.sorted{color:var(--green)}
  th.sorted::after{content:' ▼';font-size:8px}
  td{padding:10px 14px;border-bottom:1px solid #0f1a0f;vertical-align:middle}
  tr:last-child td{border-bottom:none}
  tr:hover td{background:rgba(61,220,104,.03)}

  .rank-num{font-family:'Syne',sans-serif;font-size:16px;font-weight:800;width:30px;text-align:center}
  .rank-1{color:var(--gold)}
  .rank-2{color:var(--silver)}
  .rank-3{color:var(--bronze)}
  .rank-n{color:var(--muted)}

  .officer-cell{display:flex;flex-direction:column;gap:3px}
  .officer-cell b{font-size:12px}

  /* Score bar */
  .score-bar-wrap{display:flex;align-items:center;gap:8px}
  .score-bar{height:8px;background:var(--border);border-radius:4px;flex:1;max-width:100px;overflow:hidden}
  .score-bar-fill{height:100%;border-radius:4px;transition:width .5s}
  .score-num{font-family:'Syne',sans-serif;font-size:13px;font-weight:800;min-width:36px;text-align:right}

  /* Metric cells */
  .metric{display:flex;flex-direction:column;gap:1px}
  .metric-val{font-size:12px;font-weight:700}
  .metric-sub{font-size:9px;color:var(--muted)}

  /* Trend arrow */
  .trend-up{color:var(--green)}
  .trend-dn{color:var(--red)}
  .trend-eq{color:var(--muted)}

  /* Info cards row */
  .info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:20px}
  .info-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px;text-align:center}
  .ic-val{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;margin:4px 0}
  .ic-label{font-size:9px;text-transform:uppercase;letter-spacing:.4px;color:var(--muted)}
  .ic-sub{font-size:10px;color:var(--muted);margin-top:2px}

  /* Scoring legend */
  .legend-box{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-bottom:20px}
  .legend-title{font-size:11px;font-weight:700;margin-bottom:10px;color:var(--green)}
  .legend-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:8px}
  .legend-row{display:flex;align-items:center;gap:8px;font-size:10px;color:var(--muted)}
  .legend-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}

  @media(max-width:768px){
    .podium{gap:6px}
    .podium-block{width:70px}
    table{font-size:10px}
    td,th{padding:7px 8px}
  }
</style>
</head>
<body>
<?php
require "conn.php";
require "validate.php";

$days = isset($_GET['days']) ? min((int)$_GET['days'], 90) : 30;

// ── Season + assignment check ─────────────────────────────────────────────────
$seasonId = 0;
$r = $conn->query("SELECT id FROM seasons WHERE active = 1 LIMIT 1");
if($r && $row=$r->fetch_assoc()){ $seasonId=(int)$row['id']; $r->free(); }

$hasAssignments = false;
if($seasonId){
    $r = $conn->query("SELECT COUNT(*) AS cnt FROM grower_field_officer WHERE seasonid=$seasonId");
    if($r && $row=$r->fetch_assoc()){ $hasAssignments = $row['cnt'] > 0; $r->free(); }
}

// Assignment join — when assignments exist, restrict visits/geofence to assigned growers only
$assignJoin    = $hasAssignments ? "JOIN grower_field_officer gfo ON gfo.growerid=v.growerid AND gfo.field_officerid=fo.userid AND gfo.seasonid=$seasonId" : "";
$assignGeoJoin = $hasAssignments ? "JOIN grower_field_officer gfo ON gfo.growerid=ge.growerid AND gfo.field_officerid=ge.userid AND gfo.seasonid=$seasonId" : "";

// ── Haversine ─────────────────────────────────────────────────────────────────
function hdist($lat1,$lng1,$lat2,$lng2){
    $R=6371;$dLat=deg2rad($lat2-$lat1);$dLng=deg2rad($lng2-$lng1);
    $a=sin($dLat/2)*sin($dLat/2)+cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLng/2)*sin($dLng/2);
    return $R*2*atan2(sqrt($a),sqrt(1-$a));
}

// ── 1. Core visit metrics ─────────────────────────────────────────────────────
// If assignments exist: count visits only for assigned growers
// If no assignments: count all visits
$officerStats = [];
$r = $conn->query("
    SELECT
        fo.id,
        fo.name,
        COUNT(DISTINCT v.id)                                AS total_visits,
        COUNT(DISTINCT v.growerid)                          AS unique_growers,
        COUNT(DISTINCT DATE(v.created_at))                  AS active_days,
        SUM(CASE WHEN v.created_at >= NOW()-INTERVAL 7 DAY THEN 1 ELSE 0 END) AS visits_7d,
        SUM(CASE WHEN v.created_at BETWEEN NOW()-INTERVAL 14 DAY AND NOW()-INTERVAL 7 DAY THEN 1 ELSE 0 END) AS visits_prev_7d
    FROM field_officers fo
    LEFT JOIN visits v ON v.userid=fo.userid AND v.created_at >= NOW()-INTERVAL $days DAY
    $assignJoin
    GROUP BY fo.id, fo.name
    ORDER BY fo.name
");
if($r){while($row=$r->fetch_assoc()) $officerStats[$row['id']]=$row; $r->free();}

// ── 2. GPS metrics from device_locations ─────────────────────────────────────
$r = $conn->query("
    SELECT
        officer_id,
        COUNT(*)                                            AS total_pings,
        COUNT(DISTINCT DATE(created_at))                    AS ping_days,
        MAX(created_at)                                     AS last_ping,
        SUM(CASE WHEN HOUR(created_at) BETWEEN 6 AND 18 THEN 1 ELSE 0 END) AS work_pings
    FROM device_locations
    WHERE created_at >= NOW()-INTERVAL $days DAY
      AND officer_id IS NOT NULL
    GROUP BY officer_id
");
if($r){
    while($row=$r->fetch_assoc()){
        $oid=$row['officer_id'];
        if(isset($officerStats[$oid])){
            $officerStats[$oid]['total_pings'] = (int)$row['total_pings'];
            $officerStats[$oid]['ping_days']   = (int)$row['ping_days'];
            $officerStats[$oid]['last_ping']   = $row['last_ping'];
            $officerStats[$oid]['work_pings']  = (int)$row['work_pings'];
        }
    }
    $r->free();
}

// ── 3. Distance covered per officer ──────────────────────────────────────────
$r = $conn->query("
    SELECT officer_id, latitude, longitude
    FROM device_locations
    WHERE created_at >= NOW()-INTERVAL $days DAY
      AND officer_id IS NOT NULL
    ORDER BY officer_id, created_at ASC
");
$prevPing = []; $distByOfficer = [];
if($r){
    while($row=$r->fetch_assoc()){
        $oid=(int)$row['officer_id'];
        if(isset($prevPing[$oid])){
            $distByOfficer[$oid] = ($distByOfficer[$oid]??0) + hdist($prevPing[$oid][0],$prevPing[$oid][1],(float)$row['latitude'],(float)$row['longitude']);
        }
        $prevPing[$oid]=[(float)$row['latitude'],(float)$row['longitude']];
    }
    $r->free();
}
foreach($distByOfficer as $oid=>$dist){
    if(isset($officerStats[$oid])) $officerStats[$oid]['distance_km']=round($dist,1);
}

// ── 4. Geofence entries (officer near grower) ─────────────────────────────────
$r = $conn->query("
    SELECT
        userid AS officer_id,
        COUNT(*)                    AS geo_total,
        COUNT(DISTINCT growerid)    AS geo_unique_growers
    FROM grower_geofence_entry_point
    WHERE created_at >= NOW()-INTERVAL $days DAY
    GROUP BY userid
");
if($r){
    while($row=$r->fetch_assoc()){
        $oid=(int)$row['officer_id'];
        if(isset($officerStats[$oid])){
            $officerStats[$oid]['geo_total']          = (int)$row['geo_total'];
            $officerStats[$oid]['geo_unique_growers']  = (int)$row['geo_unique_growers'];
        }
    }
    $r->free();
}

// ── 5. Conversion rate — visited / geo near ───────────────────────────────────
// If assignments exist: only count assigned growers in near/visited
$r = $conn->query("
    SELECT ge.userid AS officer_id,
           COUNT(DISTINCT ge.growerid) AS near_count,
           COUNT(DISTINCT v.growerid)  AS visited_of_near
    FROM grower_geofence_entry_point ge
    $assignGeoJoin
    LEFT JOIN visits v ON v.growerid=ge.growerid
                       AND v.userid=ge.userid
                       AND DATE(v.created_at) = DATE(ge.created_at)
    WHERE ge.created_at >= NOW()-INTERVAL $days DAY
    GROUP BY ge.userid
");
if($r){
    while($row=$r->fetch_assoc()){
        $oid=(int)$row['officer_id'];
        if(isset($officerStats[$oid])){
            $near = (int)$row['near_count'];
            $vis  = (int)$row['visited_of_near'];
            $officerStats[$oid]['near_count']       = $near;
            $officerStats[$oid]['visited_of_near']  = $vis;
            $officerStats[$oid]['conversion_pct']   = $near>0 ? round(($vis/$near)*100) : 0;
        }
    }
    $r->free();
}

$conn->close();

// ── 6. Calculate composite score (0–100) ─────────────────────────────────────
// Scoring weights:
//   Visits logged          30 pts  (normalised vs top performer)
//   Unique growers visited 25 pts  (normalised)
//   Conversion rate        20 pts  (% visits / near → direct %)
//   Active days            15 pts  (normalised)
//   Distance covered       10 pts  (normalised)

$allOfficers = array_values($officerStats);
$maxVisits   = max(1, max(array_column($allOfficers, 'total_visits')  ?: [1]));
$maxUnique   = max(1, max(array_column($allOfficers, 'unique_growers') ?: [1]));
$maxDays     = max(1, max(array_column($allOfficers, 'active_days')   ?: [1]));
$maxDist     = max(1, max(array_column($allOfficers, 'distance_km')   ?: [1]));

foreach($officerStats as &$o){
    $visits  = (int)($o['total_visits']   ?? 0);
    $unique  = (int)($o['unique_growers'] ?? 0);
    $conv    = (int)($o['conversion_pct'] ?? 0);
    $days_a  = (int)($o['active_days']    ?? 0);
    $dist    = (float)($o['distance_km']  ?? 0);

    $s_visits = round(($visits / $maxVisits)  * 30);
    $s_unique = round(($unique / $maxUnique)  * 25);
    $s_conv   = round(($conv   / 100)         * 20);
    $s_days   = round(($days_a / $maxDays)    * 15);
    $s_dist   = round(($dist   / $maxDist)    * 10);

    $o['score']          = $s_visits + $s_unique + $s_conv + $s_days + $s_dist;
    $o['score_visits']   = $s_visits;
    $o['score_unique']   = $s_unique;
    $o['score_conv']     = $s_conv;
    $o['score_days']     = $s_days;
    $o['score_dist']     = $s_dist;

    // Tier
    $o['tier'] = $o['score'] >= 80 ? 'gold'
               :($o['score'] >= 55 ? 'silver'
               :($o['score'] >= 30 ? 'bronze' : 'iron'));

    // Trend (7d vs prev 7d)
    $curr = (int)($o['visits_7d']      ?? 0);
    $prev = (int)($o['visits_prev_7d'] ?? 0);
    $o['trend'] = $curr > $prev ? 'up' : ($curr < $prev ? 'dn' : 'eq');
    $o['trend_diff'] = $curr - $prev;
}
unset($o);

// Sort by score desc
usort($allOfficers, fn($a,$b) => $b['score'] - $a['score']);

// Assign ranks
foreach($allOfficers as $i => &$o){ $o['rank'] = $i + 1; } unset($o);

// Summary stats
$totalVisits   = array_sum(array_column($allOfficers, 'total_visits'));
$avgScore      = count($allOfficers) ? round(array_sum(array_column($allOfficers, 'score')) / count($allOfficers)) : 0;
$topConversion = max(array_column($allOfficers, 'conversion_pct') ?: [0]);
$totalDist     = round(array_sum(array_column($allOfficers, 'distance_km')));

$tierLabels = ['gold'=>'🥇 Gold','silver'=>'🥈 Silver','bronze'=>'🥉 Bronze','iron'=>'⚙️ Iron'];
$tierColors = ['gold'=>'var(--gold)','silver'=>'var(--silver)','bronze'=>'var(--bronze)','iron'=>'var(--muted)'];
?>

<header>
  <div class="logo">GMS<span>/</span>League</div>
  <a href="officer_coverage.php" class="back">← Coverage</a>
  <a href="visit_backlog.php"    class="back">📋 Backlog</a>
  <a href="route_planner.php"    class="back">🗺 Route</a>
  <select onchange="location.href='?days='+this.value" style="margin-left:8px">
    <option value="7"  <?=$days==7?'selected':''?>>Last 7 days</option>
    <option value="14" <?=$days==14?'selected':''?>>Last 14 days</option>
    <option value="30" <?=$days==30?'selected':''?>>Last 30 days</option>
    <option value="90" <?=$days==90?'selected':''?>>Last 90 days</option>
  </select>
  <div style="margin-left:auto;font-size:10px;color:var(--muted)">
    <?=count($allOfficers)?> officers · <?=$days?> day period ·
    <?php if($hasAssignments): ?>
    <span style="color:var(--green)">📋 Assigned growers (season <?=$seasonId?>)</span>
    <?php else: ?>
    <span style="color:var(--amber)">📂 All growers (no assignments found)</span>
    <?php endif?>
  </div>
</header>

<div class="content">

  <!-- Summary stats -->
  <div class="info-grid">
    <div class="info-card">
      <div class="ic-label">Total Visits</div>
      <div class="ic-val" style="color:var(--green)"><?=$totalVisits?></div>
      <div class="ic-sub">across all officers</div>
    </div>
    <div class="info-card">
      <div class="ic-label">Avg Score</div>
      <div class="ic-val" style="color:var(--amber)"><?=$avgScore?>/100</div>
      <div class="ic-sub">team performance</div>
    </div>
    <div class="info-card">
      <div class="ic-label">Best Conversion</div>
      <div class="ic-val" style="color:var(--blue)"><?=$topConversion?>%</div>
      <div class="ic-sub">near → visit logged</div>
    </div>
    <div class="info-card">
      <div class="ic-label">Total Distance</div>
      <div class="ic-val" style="color:var(--purple)"><?=$totalDist?>km</div>
      <div class="ic-sub">all officers combined</div>
    </div>
  </div>

  <!-- Podium (top 3) -->
  <?php if(count($allOfficers) >= 2):
    $p = $allOfficers;
    $top = $p[0]; $sec = $p[1] ?? null; $thr = $p[2] ?? null;
  ?>
  <div class="podium">
    <?php if($sec): ?>
    <div class="podium-slot p2">
      <div class="podium-avatar">👤</div>
      <div class="podium-name"><?=htmlspecialchars($sec['name'])?></div>
      <div class="podium-score"><?=$sec['score']?></div>
      <span class="tier tier-silver">🥈 Silver</span>
      <div class="podium-block">2</div>
    </div>
    <?php endif?>
    <div class="podium-slot p1">
      <div class="podium-avatar"><span class="crown">👑</span>🏆</div>
      <div class="podium-name"><?=htmlspecialchars($top['name'])?></div>
      <div class="podium-score"><?=$top['score']?></div>
      <span class="tier tier-gold">🥇 Gold</span>
      <div class="podium-block">1</div>
    </div>
    <?php if($thr): ?>
    <div class="podium-slot p3">
      <div class="podium-avatar">👤</div>
      <div class="podium-name"><?=htmlspecialchars($thr['name'])?></div>
      <div class="podium-score"><?=$thr['score']?></div>
      <span class="tier tier-bronze">🥉 Bronze</span>
      <div class="podium-block">3</div>
    </div>
    <?php endif?>
  </div>
  <?php endif?>

  <!-- Scoring legend -->
  <div class="legend-box">
    <div class="legend-title">📊 How scores are calculated</div>
    <div class="legend-grid">
      <div class="legend-row"><div class="legend-dot" style="background:var(--green)"></div><b>Visits logged</b> — 30 pts max · visits recorded in system</div>
      <div class="legend-row"><div class="legend-dot" style="background:var(--blue)"></div><b>Unique growers</b> — 25 pts max · distinct growers visited</div>
      <div class="legend-row"><div class="legend-dot" style="background:#4a9eff)"></div><b>Conversion rate</b> — 20 pts max · % of nearby growers actually visited</div>
      <div class="legend-row"><div class="legend-dot" style="background:var(--purple)"></div><b>Active days</b> — 15 pts max · days with field activity</div>
      <div class="legend-row"><div class="legend-dot" style="background:var(--amber)"></div><b>Distance covered</b> — 10 pts max · km travelled in field</div>
    </div>
  </div>

  <!-- Full league table -->
  <div class="league-table">
    <div class="lt-head">
      <h2>🏆 Full League Table</h2>
      <span style="font-size:10px;color:var(--muted)">Last <?=$days?> days</span>
    </div>
    <table id="leagueTable">
      <thead>
        <tr>
          <th onclick="sortTable(0)" class="sorted">#</th>
          <th onclick="sortTable(1)">Officer</th>
          <th onclick="sortTable(2)">Score</th>
          <th onclick="sortTable(3)">Visits</th>
          <th onclick="sortTable(4)">Unique Growers</th>
          <th onclick="sortTable(5)">Near Growers</th>
          <th onclick="sortTable(6)">Conversion</th>
          <th onclick="sortTable(7)">Active Days</th>
          <th onclick="sortTable(8)">Distance</th>
          <th onclick="sortTable(9)">7d Trend</th>
          <th>Tier</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($allOfficers as $o):
        $rankCls = $o['rank']===1?'rank-1':($o['rank']===2?'rank-2':($o['rank']===3?'rank-3':'rank-n'));
        $tier    = $o['tier'];
        $tColor  = $tierColors[$tier];
        $tLabel  = $tierLabels[$tier];
        $scorePct= $o['score'];
        $scoreCol= $scorePct>=80?'var(--gold)':($scorePct>=55?'var(--silver)':($scorePct>=30?'var(--bronze)':'var(--muted)'));
        $convCol = ($o['conversion_pct']??0)>=50?'var(--green)':(($o['conversion_pct']??0)>=25?'var(--amber)':'var(--red)');
        $trendIcon = $o['trend']==='up'?'▲':($o['trend']==='dn'?'▼':'—');
        $trendCls  = 'trend-'.$o['trend'];
        $trendDiff = $o['trend_diff']>0?'+'.$o['trend_diff']:$o['trend_diff'];
      ?>
      <tr>
        <td><div class="rank-num <?=$rankCls?>"><?=$o['rank']?></div></td>
        <td>
          <div class="officer-cell">
            <b><?=htmlspecialchars($o['name'])?></b>
            <div class="score-breakdown">
              <span class="score-pill sp-visits">V:<?=$o['score_visits']?></span>
              <span class="score-pill sp-conv">C:<?=$o['score_conv']?></span>
              <span class="score-pill sp-active">D:<?=$o['score_days']?></span>
              <span class="score-pill sp-dist">K:<?=$o['score_dist']?></span>
            </div>
          </div>
        </td>
        <td>
          <div class="score-bar-wrap">
            <div class="score-bar"><div class="score-bar-fill" style="width:<?=$scorePct?>%;background:<?=$scoreCol?>"></div></div>
            <div class="score-num" style="color:<?=$scoreCol?>"><?=$scorePct?></div>
          </div>
        </td>
        <td>
          <div class="metric">
            <div class="metric-val" style="color:var(--green)"><?=(int)($o['total_visits']??0)?></div>
            <div class="metric-sub"><?=(int)($o['visits_7d']??0)?> this week</div>
          </div>
        </td>
        <td>
          <div class="metric">
            <div class="metric-val" style="color:var(--blue)"><?=(int)($o['unique_growers']??0)?></div>
            <div class="metric-sub">distinct growers</div>
          </div>
        </td>
        <td>
          <div class="metric">
            <div class="metric-val" style="color:#7ec8ff"><?=(int)($o['near_count']??0)?></div>
            <div class="metric-sub"><?=(int)($o['geo_unique_growers']??0)?> unique</div>
          </div>
        </td>
        <td>
          <div class="metric">
            <div class="metric-val" style="color:<?=$convCol?>"><?=(int)($o['conversion_pct']??0)?>%</div>
            <div class="metric-sub"><?=(int)($o['visited_of_near']??0)?>/<?=(int)($o['near_count']??0)?> near</div>
          </div>
        </td>
        <td>
          <div class="metric">
            <div class="metric-val" style="color:var(--purple)"><?=(int)($o['active_days']??0)?></div>
            <div class="metric-sub">of <?=$days?> days</div>
          </div>
        </td>
        <td>
          <div class="metric">
            <div class="metric-val" style="color:var(--amber)"><?=(float)($o['distance_km']??0)?>km</div>
            <div class="metric-sub"><?=(int)($o['total_pings']??0)?> pings</div>
          </div>
        </td>
        <td>
          <div class="metric">
            <div class="metric-val <?=$trendCls?>"><?=$trendIcon?> <?=$trendDiff?></div>
            <div class="metric-sub">vs prev week</div>
          </div>
        </td>
        <td><span class="tier tier-<?=$tier?>"><?=$tLabel?></span></td>
        <td>
          <a href="officer_report.php?officer_id=<?=$o['id']?>&days=<?=$days?>" style="font-family:'Space Mono',monospace;font-size:10px;color:var(--green);text-decoration:none;border:1px solid var(--green-dim);padding:2px 7px;border-radius:3px">Report →</a>
        </td>
      </tr>
      <?php endforeach?>
      </tbody>
    </table>
  </div>

</div>

<script>
// Simple client-side table sort
let sortCol = 0, sortAsc = true;
function sortTable(col){
  const table = document.getElementById('leagueTable');
  const tbody = table.querySelector('tbody');
  const rows  = Array.from(tbody.querySelectorAll('tr'));
  sortAsc = sortCol === col ? !sortAsc : false;
  sortCol = col;

  rows.sort((a,b)=>{
    const aVal = a.cells[col]?.textContent?.trim() || '';
    const bVal = b.cells[col]?.textContent?.trim() || '';
    const aNum = parseFloat(aVal); const bNum = parseFloat(bVal);
    const cmp  = isNaN(aNum)||isNaN(bNum) ? aVal.localeCompare(bVal) : aNum-bNum;
    return sortAsc ? cmp : -cmp;
  });

  rows.forEach(r => tbody.appendChild(r));
  document.querySelectorAll('th').forEach((th,i)=>{
    th.className = i===col ? 'sorted' : '';
  });
}
</script>
</body>
</html>
