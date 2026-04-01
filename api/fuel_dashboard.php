<?php
/**
 * fuel_dashboard.php
 * GMS — Distance & Fuel Management Hub
 * Tables: distance, fuel_requests, fuel_request_growers, field_officers, users, seasons
 */
ob_start();
if(session_status()===PHP_SESSION_NONE) session_start();
require "conn.php";
require "validate.php";
date_default_timezone_set('Africa/Harare');
$conn->query("SET time_zone = '+02:00'");

// ── Active season ──────────────────────────────────────────────────────────────
$seasonId=0; $seasonName='—';
$r=$conn->query("SELECT id,name FROM seasons WHERE active=1 LIMIT 1");
if($r&&$row=$r->fetch_assoc()){$seasonId=(int)$row['id'];$seasonName=$row['name'];$r->free();}

// ── Fuel rate config (L/100km) ─────────────────────────────────────────────────
// Stored in session so manager can adjust live
if(isset($_GET['fuel_rate'])) $_SESSION['fuel_rate']=(float)$_GET['fuel_rate'];
if(isset($_GET['fuel_price'])) $_SESSION['fuel_price']=(float)$_GET['fuel_price'];
$fuelRate  = $_SESSION['fuel_rate']  ?? 10.0;  // L/100km default
$fuelPrice = $_SESSION['fuel_price'] ?? 1.50;  // $/L default

// ── Officer filter ─────────────────────────────────────────────────────────────
$selOfficer = isset($_GET['officer_id']) && $_GET['officer_id']!=='' ? (int)$_GET['officer_id'] : null;
$officerWhere = $selOfficer ? "AND d.userid=(SELECT userid FROM field_officers WHERE id={$selOfficer} LIMIT 1)" : "";
$officerWhereJoin = $selOfficer ? "AND fo.id={$selOfficer}" : "";

// Fetch officers for dropdown
$allOfficers=[];
$r=$conn->query("SELECT id,name FROM field_officers ORDER BY name ASC");
if($r){while($row=$r->fetch_assoc()) $allOfficers[]=$row; $r->free();}

// ── Date range ──────────────────────────────────────────────────────────────────
// Mode: 'week' (prev/next nav) or 'custom' (date picker)
$dateMode = $_GET['date_mode'] ?? 'week';
if($dateMode==='custom' && !empty($_GET['date_from']) && !empty($_GET['date_to'])){
    $dateFrom = date('Y-m-d', strtotime($_GET['date_from']));
    $dateTo   = date('Y-m-d', strtotime($_GET['date_to']));
    // Clamp: ensure from <= to
    if($dateFrom > $dateTo) [$dateFrom,$dateTo]=[$dateTo,$dateFrom];
    $monday = $dateFrom;
    $sunday = $dateTo;
    $weekOffset = 0;
} else {
    $dateMode  = 'week';
    $weekOffset=(int)($_GET['week']??0);
    $monday    = date('Y-m-d', strtotime("monday this week +{$weekOffset} week"));
    $sunday    = date('Y-m-d', strtotime("sunday this week +{$weekOffset} week"));
    $dateFrom  = $monday;
    $dateTo    = $sunday;
}

// ── KPIs ───────────────────────────────────────────────────────────────────────
// Total distance this season
$totalKm=0; $totalOfficers=0;
$r=$conn->query("
    SELECT COUNT(DISTINCT d.userid) AS officers, ROUND(COALESCE(SUM(d.distance),0)/1000,2) AS km
    FROM distance d WHERE d.seasonid={$seasonId} {$officerWhere}
");
if($r&&$row=$r->fetch_assoc()){$totalKm=(float)$row['km'];$totalOfficers=(int)$row['officers'];}

// Pending fuel requests
$pendingCount=0;
$r=$conn->query("SELECT COUNT(*) AS cnt FROM fuel_requests WHERE status='PENDING' AND seasonid={$seasonId}");
if($r&&$row=$r->fetch_assoc()){$pendingCount=(int)$row['cnt'];}

// Total fuel this season (approved requests)
$totalFuelL=0; $totalFuelCost=0;
$r=$conn->query("
    SELECT COALESCE(SUM(total_fuel_litres),0) AS litres
    FROM fuel_requests WHERE status='APPROVED' AND seasonid={$seasonId}
");
if($r&&$row=$r->fetch_assoc()){$totalFuelL=(float)$row['litres'];$totalFuelCost=round($totalFuelL*$fuelPrice,2);}

// This week's distance per officer
$weeklyRows=[];
$r=$conn->query("
    SELECT fo.id, fo.name AS officer_name,
        ROUND(COALESCE(SUM(d.distance),0)/1000,2) AS km_week,
        ROUND(COALESCE(SUM(d.distance),0)/1000*{$fuelRate}/100,2) AS litres_needed,
        ROUND(COALESCE(SUM(d.distance),0)/1000*{$fuelRate}/100*{$fuelPrice},2) AS cost,
        (SELECT status FROM fuel_requests fwr
         WHERE fwr.field_officer_id=fo.userid
           AND fwr.week_start_date='{$monday}' LIMIT 1) AS request_status,
        (SELECT id FROM fuel_requests fwr
         WHERE fwr.field_officer_id=fo.userid
           AND fwr.week_start_date='{$monday}' LIMIT 1) AS request_id
    FROM field_officers fo
    LEFT JOIN distance d ON d.userid=fo.userid
        AND DATE(STR_TO_DATE(d.created_at,'%Y-%m-%d'))>='{$monday}'
        AND DATE(STR_TO_DATE(d.created_at,'%Y-%m-%d'))<='{$sunday}'
    WHERE 1=1 {$officerWhereJoin}
    GROUP BY fo.id, fo.name, fo.userid
    ORDER BY km_week DESC
");
while($r&&$row=$r->fetch_assoc()) $weeklyRows[]=$row;

// ── Season officer league (distance) ──────────────────────────────────────────
$leagueRows=[];
$r=$conn->query("
    SELECT fo.id, fo.name AS officer_name,
        ROUND(COALESCE(SUM(d.distance),0)/1000,2) AS total_km,
        COUNT(DISTINCT DATE(STR_TO_DATE(d.created_at,'%Y-%m-%d'))) AS active_days,
        ROUND(COALESCE(SUM(d.distance),0)/1000*{$fuelRate}/100,2) AS fuel_litres,
        ROUND(COALESCE(SUM(d.distance),0)/1000*{$fuelRate}/100*{$fuelPrice},2) AS fuel_cost,
        ROUND(COALESCE(SUM(d.distance),0)/1000/NULLIF(COUNT(DISTINCT DATE(STR_TO_DATE(d.created_at,'%Y-%m-%d'))),0),1) AS avg_km_day
    FROM field_officers fo
    LEFT JOIN distance d ON d.userid=fo.userid AND d.seasonid={$seasonId}
    WHERE 1=1 {$officerWhereJoin}
    GROUP BY fo.id, fo.name
    ORDER BY total_km DESC
");
while($r&&$row=$r->fetch_assoc()) $leagueRows[]=$row;

// ── Anomaly detection ──────────────────────────────────────────────────────────
$anomalies=[];
// Zero km days (officer logged 0 but had visits)
$r=$conn->query("
    SELECT fo.name AS officer_name, DATE(STR_TO_DATE(d.created_at,'%Y-%m-%d')) AS day,
        ROUND(d.distance/1000,2) AS km,
        COUNT(v.id) AS visits,
        'Zero km with visits' AS anomaly_type
    FROM distance d
    JOIN field_officers fo ON fo.userid=d.userid
    JOIN visits v ON v.userid=d.userid
        AND DATE(STR_TO_DATE(v.created_at,'%Y-%m-%d'))=DATE(STR_TO_DATE(d.created_at,'%Y-%m-%d'))
    WHERE d.seasonid={$seasonId} AND d.distance=0 {$officerWhere}
    GROUP BY fo.name, day, d.distance HAVING visits>0
    ORDER BY day DESC LIMIT 20
");
while($r&&$row=$r->fetch_assoc()){$row['severity']='warning';$anomalies[]=$row;}

// Unrealistic distance (>300km in one day)
$r=$conn->query("
    SELECT fo.name AS officer_name, DATE(STR_TO_DATE(d.created_at,'%Y-%m-%d')) AS day,
        ROUND(d.distance/1000,2) AS km, 0 AS visits,
        'Unrealistic distance (>300km)' AS anomaly_type
    FROM distance d
    JOIN field_officers fo ON fo.userid=d.userid
    WHERE d.seasonid={$seasonId} AND d.distance>300000 {$officerWhere}
    ORDER BY d.distance DESC LIMIT 20
");
while($r&&$row=$r->fetch_assoc()){$row['severity']='critical';$anomalies[]=$row;}

// ── Fuel request approval action ───────────────────────────────────────────────
if(isset($_POST['action'])&&isset($_POST['request_id'])){
    $rid=(int)$_POST['request_id'];
    $notes=$conn->real_escape_string($_POST['notes']??'');
    if($_POST['action']==='approve'){
        $conn->query("UPDATE fuel_requests SET status='APPROVED',manager_notes='{$notes}',datetimes=NOW() WHERE id={$rid}");
    } elseif($_POST['action']==='reject'){
        $conn->query("UPDATE fuel_requests SET status='REJECTED',manager_notes='{$notes}',datetimes=NOW() WHERE id={$rid}");
    }
    header("Location: fuel_dashboard.php?week={$weekOffset}&fuel_rate={$fuelRate}&fuel_price={$fuelPrice}&officer_id=" . ($selOfficer ?? '') . "&date_mode={$dateMode}");
    exit;
}

// ── Pending requests detail ────────────────────────────────────────────────────
$pendingRequests=[];
$pendingOfficerWhere = $selOfficer ? "AND fwr.field_officer_id=(SELECT userid FROM field_officers WHERE id={$selOfficer} LIMIT 1)" : "";
$r=$conn->query("
    SELECT fwr.*,
        CONCAT(u.name,' ',u.surname) AS officer_name,
        ROUND(fwr.total_fuel_litres*{$fuelPrice},2) AS estimated_cost
    FROM fuel_requests fwr
    JOIN users u ON u.id=fwr.field_officer_id
    WHERE fwr.status='PENDING' AND fwr.seasonid={$seasonId} {$pendingOfficerWhere}
    ORDER BY fwr.created_at ASC
");
while($r&&$row=$r->fetch_assoc()) $pendingRequests[]=$row;

// ── Weekly trend (last 8 weeks) ────────────────────────────────────────────────
$trendWeeks=[]; $trendKm=[]; $trendFuel=[]; $trendVisits=[];
for($i=7;$i>=0;$i--){
    $wStart=date('Y-m-d',strtotime("monday this week -{$i} week"));
    $wEnd  =date('Y-m-d',strtotime("sunday this week -{$i} week"));

    // Distance for this week (apply officer filter)
    $distOfficerWhere = $selOfficer ? "AND userid=(SELECT userid FROM field_officers WHERE id={$selOfficer} LIMIT 1)" : "";
    $r=$conn->query("
        SELECT ROUND(COALESCE(SUM(distance),0)/1000,2) AS km FROM distance
        WHERE DATE(STR_TO_DATE(created_at,'%Y-%m-%d'))>='{$wStart}'
          AND DATE(STR_TO_DATE(created_at,'%Y-%m-%d'))<='{$wEnd}'
          {$distOfficerWhere}
    ");
    $km=0;
    if($r&&$row=$r->fetch_assoc()) $km=(float)$row['km'];

    // Distinct visits this week (growerid + date = one unique visit per grower per day)
    $visitOfficerWhere = $selOfficer ? "AND v.userid=(SELECT userid FROM field_officers WHERE id={$selOfficer} LIMIT 1)" : "";
    $r2=$conn->query("
        SELECT COUNT(*) AS visits FROM (
            SELECT v.growerid, DATE(STR_TO_DATE(v.created_at,'%Y-%m-%d')) AS visit_day
            FROM visits v
            WHERE DATE(STR_TO_DATE(v.created_at,'%Y-%m-%d'))>='{$wStart}'
              AND DATE(STR_TO_DATE(v.created_at,'%Y-%m-%d'))<='{$wEnd}'
              {$visitOfficerWhere}
            GROUP BY v.growerid, visit_day
        ) t
    ");
    $visits=0;
    if($r2&&$row=$r2->fetch_assoc()) $visits=(int)$row['visits'];

    $trendWeeks[]='W'.date('W',strtotime($wStart));
    $trendKm[]=$km;
    $trendFuel[]=round($km*$fuelRate/100,1);
    $trendVisits[]=$visits;
}

$trendWeeksJson =json_encode($trendWeeks);
$trendKmJson    =json_encode($trendKm);
$trendFuelJson  =json_encode($trendFuel);
$trendVisitsJson=json_encode($trendVisits);

// ══════════════════════════════════════════════════════════════════════════════
// NEW ANALYSIS QUERIES
// ══════════════════════════════════════════════════════════════════════════════

// ── A. Route vs Actual: planned growers vs visited per request ────────────────
$routeVsActual = [];
$r = $conn->query("
    SELECT
        CONCAT(u.name,' ',u.surname) AS officer_name,
        fr.week_start_date,
        fr.week_end_date,
        fr.status,
        fr.id AS request_id,
        COUNT(DISTINCT frg.growerid)                         AS planned_growers,
        COUNT(DISTINCT CASE
            WHEN v.growerid IS NOT NULL THEN frg.growerid
        END)                                                  AS visited_growers,
        ROUND(COUNT(DISTINCT CASE WHEN v.growerid IS NOT NULL THEN frg.growerid END)
            / NULLIF(COUNT(DISTINCT frg.growerid),0)*100,1)   AS completion_pct
    FROM fuel_requests fr
    JOIN users u ON u.id = fr.field_officer_id
    LEFT JOIN fuel_request_growers frg ON frg.userid = fr.field_officer_id
        AND frg.seasonid = fr.seasonid
        AND frg.planned_day IN ('MON','TUE','WED','THU','FRI','SAT','SUN')
    LEFT JOIN visits v ON v.growerid = frg.growerid
        AND v.userid = fr.field_officer_id
        AND DATE(STR_TO_DATE(v.created_at,'%Y-%m-%d')) >= STR_TO_DATE(fr.week_start_date,'%Y-%m-%d')
        AND DATE(STR_TO_DATE(v.created_at,'%Y-%m-%d')) <= STR_TO_DATE(fr.week_end_date,'%Y-%m-%d')
    WHERE fr.seasonid = {$seasonId}
    " . ($selOfficer ? "AND fr.field_officer_id=(SELECT userid FROM field_officers WHERE id={$selOfficer} LIMIT 1)" : "") . "
    GROUP BY fr.id, u.name, u.surname, fr.week_start_date, fr.week_end_date, fr.status
    ORDER BY STR_TO_DATE(fr.week_start_date,'%Y-%m-%d') DESC
    LIMIT 40
");
while($r && $row = $r->fetch_assoc()) $routeVsActual[] = $row;

// ── B. Cost per Visit ─────────────────────────────────────────────────────────
$costPerVisit = [];
$r = $conn->query("
    SELECT
        CONCAT(u.name,' ',u.surname) AS officer_name,
        COUNT(DISTINCT fr.id)                                  AS requests,
        COALESCE(SUM(fr.total_fuel_litres),0)                  AS total_litres,
        ROUND(COALESCE(SUM(fr.total_fuel_litres),0)*{$fuelPrice},2) AS total_cost,
        COUNT(DISTINCT CONCAT(v.growerid,'-',DATE(STR_TO_DATE(v.created_at,'%Y-%m-%d')))) AS actual_visits,
        ROUND(
            COALESCE(SUM(fr.total_fuel_litres),0)*{$fuelPrice} /
            NULLIF(COUNT(DISTINCT CONCAT(v.growerid,'-',DATE(STR_TO_DATE(v.created_at,'%Y-%m-%d')))),0)
        ,2) AS cost_per_visit
    FROM fuel_requests fr
    JOIN users u ON u.id = fr.field_officer_id
    LEFT JOIN visits v ON v.userid = fr.field_officer_id
        AND v.seasonid = fr.seasonid
    WHERE fr.seasonid = {$seasonId} AND fr.status = 'APPROVED'
    " . ($selOfficer ? "AND fr.field_officer_id=(SELECT userid FROM field_officers WHERE id={$selOfficer} LIMIT 1)" : "") . "
    GROUP BY fr.field_officer_id, u.name, u.surname
    ORDER BY cost_per_visit ASC
");
while($r && $row = $r->fetch_assoc()) $costPerVisit[] = $row;

// ── C. Compliance Score: who submits requests, who doesn't ───────────────────
// Weeks in season so far
$weeksSinceStart = 1;
$r = $conn->query("SELECT DATEDIFF(NOW(), MIN(STR_TO_DATE(created_at,'%Y-%m-%d')))/7 AS wks FROM fuel_requests WHERE seasonid={$seasonId}");
if($r && $row = $r->fetch_assoc()) $weeksSinceStart = max(1, round($row['wks']));

$complianceRows = [];
$r = $conn->query("
    SELECT
        fo.id AS officer_id,
        fo.name AS officer_name,
        COUNT(fr.id)                                                AS requests_submitted,
        SUM(CASE WHEN fr.status='APPROVED' THEN 1 ELSE 0 END)      AS approved,
        SUM(CASE WHEN fr.status='REJECTED' THEN 1 ELSE 0 END)      AS rejected,
        SUM(CASE WHEN fr.status='PENDING'  THEN 1 ELSE 0 END)      AS pending,
        ROUND(COUNT(fr.id)/{$weeksSinceStart}*100,1)                AS submission_rate,
        MAX(STR_TO_DATE(fr.week_start_date,'%Y-%m-%d'))             AS last_submission
    FROM field_officers fo
    LEFT JOIN fuel_requests fr ON fr.field_officer_id = fo.userid
        AND fr.seasonid = {$seasonId}
    " . ($selOfficer ? "WHERE fo.id={$selOfficer}" : "") . "
    GROUP BY fo.id, fo.name
    ORDER BY submission_rate DESC
");
while($r && $row = $r->fetch_assoc()) $complianceRows[] = $row;

// ── D. Planned vs Actual Distance ─────────────────────────────────────────────
$distVsActual = [];
$r = $conn->query("
    SELECT
        CONCAT(u.name,' ',u.surname) AS officer_name,
        fr.week_start_date,
        fr.total_distance_km                                    AS planned_km,
        ROUND(COALESCE(SUM(d.distance),0)/1000,2)               AS actual_km,
        ROUND(COALESCE(SUM(d.distance),0)/1000 - fr.total_distance_km,2) AS diff_km,
        ROUND((COALESCE(SUM(d.distance),0)/1000 - fr.total_distance_km)
            / NULLIF(fr.total_distance_km,0)*100,1)             AS diff_pct
    FROM fuel_requests fr
    JOIN users u ON u.id = fr.field_officer_id
    LEFT JOIN distance d ON d.userid = fr.field_officer_id
        AND DATE(STR_TO_DATE(d.created_at,'%Y-%m-%d')) >= STR_TO_DATE(fr.week_start_date,'%Y-%m-%d')
        AND DATE(STR_TO_DATE(d.created_at,'%Y-%m-%d')) <= STR_TO_DATE(fr.week_end_date,'%Y-%m-%d')
    WHERE fr.seasonid = {$seasonId} AND fr.status = 'APPROVED'
    " . ($selOfficer ? "AND fr.field_officer_id=(SELECT userid FROM field_officers WHERE id={$selOfficer} LIMIT 1)" : "") . "
    GROUP BY fr.id, u.name, u.surname, fr.week_start_date, fr.total_distance_km
    ORDER BY STR_TO_DATE(fr.week_start_date,'%Y-%m-%d') DESC
    LIMIT 40
");
while($r && $row = $r->fetch_assoc()) $distVsActual[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>GMS · Fuel & Distance</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800;900&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --bg:#080c08;--surface:#0f160f;--surface2:#162016;--border:#1c2e1c;--border2:#243824;
    --green:#3ddc68;--green-dim:rgba(61,220,104,.12);--green2:#2ab854;
    --amber:#f5a623;--red:#e84040;--blue:#4a9eff;--purple:#b47eff;
    --text:#c8e6c9;--muted:#4a6b4a;--dim:#7a9e7a;
    --radius:8px;--radius2:5px;
}
html,body{font-family:'Space Mono',monospace;background:var(--bg);color:var(--text);min-height:100vh;font-size:13px;}

/* ── Header ── */
header{display:flex;align-items:center;gap:10px;padding:0 20px;height:56px;background:var(--surface);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100;backdrop-filter:blur(8px);}
.logo{font-family:'Syne',sans-serif;font-size:18px;font-weight:900;color:var(--green);letter-spacing:-0.5px;}
.logo span{color:var(--muted);}
.back{font-size:10px;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:4px 10px;border-radius:var(--radius2);transition:all .2s;}
.back:hover{color:var(--green);border-color:var(--green);}
.season-pill{margin-left:auto;font-size:10px;color:var(--muted);background:var(--surface2);border:1px solid var(--border);padding:4px 10px;border-radius:12px;}

/* ── Layout ── */
.page{max-width:1300px;margin:0 auto;padding:24px 20px 60px;}
.section-title{font-family:'Syne',sans-serif;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin:28px 0 12px;padding-bottom:8px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}

/* ── KPI Grid ── */
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:4px;}
.kpi{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px 18px;position:relative;overflow:hidden;transition:border-color .2s;}
.kpi::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--green),transparent);}
.kpi:hover{border-color:var(--border2);}
.kpi-icon{font-size:22px;margin-bottom:8px;opacity:.8;}
.kpi-val{font-family:'Syne',sans-serif;font-size:28px;font-weight:900;line-height:1;margin-bottom:4px;}
.kpi-label{font-size:9px;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);}
.kpi-sub{font-size:10px;color:var(--dim);margin-top:4px;}

/* ── Week nav ── */
.week-nav{display:flex;align-items:center;gap:10px;margin-bottom:16px;}
.week-nav h2{font-family:'Syne',sans-serif;font-size:14px;font-weight:800;}
.nav-btn{background:var(--surface2);border:1px solid var(--border);color:var(--text);padding:5px 12px;border-radius:var(--radius2);cursor:pointer;font-family:'Space Mono',monospace;font-size:11px;transition:all .2s;}
.nav-btn:hover{border-color:var(--green);color:var(--green);}

/* ── Config bar ── */
.config-bar{display:flex;align-items:center;gap:14px;flex-wrap:wrap;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:12px 16px;margin-bottom:24px;}
.config-bar label{font-size:9px;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);}
.config-input{background:var(--surface2);border:1px solid var(--border2);color:var(--text);border-radius:var(--radius2);padding:5px 10px;font-family:'Space Mono',monospace;font-size:12px;width:90px;outline:none;}
.config-input:focus{border-color:var(--green);}
.btn{padding:6px 16px;border-radius:var(--radius2);border:none;cursor:pointer;font-family:'Space Mono',monospace;font-size:11px;font-weight:700;transition:all .2s;}
.btn-primary{background:var(--green);color:#000;}
.btn-primary:hover{background:var(--green2);}
.btn-approve{background:rgba(61,220,104,.15);color:var(--green);border:1px solid rgba(61,220,104,.3);}
.btn-approve:hover{background:rgba(61,220,104,.25);}
.btn-reject{background:rgba(232,64,64,.1);color:var(--red);border:1px solid rgba(232,64,64,.25);}
.btn-reject:hover{background:rgba(232,64,64,.2);}

/* ── Cards / tables ── */
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-bottom:16px;}
.card-title{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--dim);margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.card-title::before{content:'';width:6px;height:6px;border-radius:50%;background:var(--green);flex-shrink:0;}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}
@media(max-width:900px){.grid-2,.grid-3{grid-template-columns:1fr;}}

table{width:100%;border-collapse:collapse;font-size:11px;}
th{text-align:left;padding:8px 12px;font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);border-bottom:1px solid var(--border);background:var(--surface2);white-space:nowrap;}
td{padding:9px 12px;border-bottom:1px solid rgba(28,46,28,.6);color:var(--dim);vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:rgba(61,220,104,.025);color:var(--text);}
.mono{font-family:'Space Mono',monospace;}
.rank{color:var(--muted);font-size:10px;}

/* ── Status badges ── */
.badge{display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:10px;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;}
.badge-pending{background:rgba(245,166,35,.12);color:var(--amber);border:1px solid rgba(245,166,35,.25);}
.badge-approved{background:rgba(61,220,104,.12);color:var(--green);border:1px solid rgba(61,220,104,.25);}
.badge-rejected{background:rgba(232,64,64,.1);color:var(--red);border:1px solid rgba(232,64,64,.2);}
.badge-none{background:var(--surface2);color:var(--muted);border:1px solid var(--border);}
.badge-critical{background:rgba(232,64,64,.12);color:var(--red);border:1px solid rgba(232,64,64,.25);}
.badge-warning{background:rgba(245,166,35,.1);color:var(--amber);border:1px solid rgba(245,166,35,.2);}

/* ── Progress bar ── */
.bar-wrap{width:80px;height:5px;background:var(--surface2);border-radius:3px;overflow:hidden;display:inline-block;}
.bar-fill{height:100%;border-radius:3px;background:var(--green);}

/* ── Approval modal ── */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:200;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:var(--surface);border:1px solid var(--border2);border-radius:var(--radius);padding:24px;width:440px;max-width:95vw;}
.modal h3{font-family:'Syne',sans-serif;font-size:15px;font-weight:800;color:var(--green);margin-bottom:16px;}
.modal textarea{width:100%;background:var(--surface2);border:1px solid var(--border2);color:var(--text);border-radius:var(--radius2);padding:10px;font-family:'Space Mono',monospace;font-size:11px;resize:vertical;min-height:80px;outline:none;}
.modal textarea:focus{border-color:var(--green);}
.modal-actions{display:flex;gap:10px;margin-top:14px;justify-content:flex-end;}
.modal-cancel{background:transparent;border:1px solid var(--border);color:var(--muted);cursor:pointer;padding:6px 14px;border-radius:var(--radius2);font-family:'Space Mono',monospace;font-size:11px;}

/* ── Animations ── */
@keyframes fadeUp{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:translateY(0);}}
.fade-up{animation:fadeUp .35s ease forwards;}
.delay-1{animation-delay:.06s;opacity:0;}
.delay-2{animation-delay:.12s;opacity:0;}
.delay-3{animation-delay:.18s;opacity:0;}
.delay-4{animation-delay:.24s;opacity:0;}

::-webkit-scrollbar{width:4px;height:4px;}
::-webkit-scrollbar-thumb{background:var(--border2);border-radius:2px;}
</style>
</head>
<body>

<header>
    <div class="logo">GMS<span>/</span>Fuel</div>
    <a href="reports_hub.php" class="back">← Reports</a>
    <a href="bi_overview.php" class="back">📊 BI</a>
    <?php if($pendingCount>0): ?>
    <span style="background:rgba(245,166,35,.15);color:var(--amber);border:1px solid rgba(245,166,35,.3);padding:3px 10px;border-radius:12px;font-size:10px;">⚠ <?=$pendingCount?> pending approval</span>
    <?php endif; ?>
    <div class="season-pill">⛽ Season <?=htmlspecialchars($seasonName)?> · <?=date('d M Y')?></div>
</header>

<div class="page">

    <!-- Page title -->
    <div style="margin-bottom:24px;" class="fade-up">
        <div style="font-family:'Syne',sans-serif;font-size:24px;font-weight:900;letter-spacing:-0.5px;">⛽ Distance &amp; Fuel Management</div>
        <div style="font-size:11px;color:var(--muted);margin-top:4px;">Weekly allocation · approval workflow · anomaly detection</div>
    </div>

    <!-- Filter & Config bar -->
    <form method="GET" class="config-bar fade-up delay-1" id="filter-form">
        <input type="hidden" name="week" value="<?=$weekOffset?>">
        <input type="hidden" name="date_mode" value="<?=$dateMode?>" id="date-mode-input">

        <!-- Officer filter -->
        <div>
            <label>Field Officer</label><br>
            <select name="officer_id" class="config-input" style="width:160px;">
                <option value="">All Officers</option>
                <?php foreach($allOfficers as $o): ?>
                <option value="<?=$o['id']?>" <?=$selOfficer==$o['id']?'selected':''?>><?=htmlspecialchars($o['name'])?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Date mode toggle -->
        <div>
            <label>Date Mode</label><br>
            <div style="display:flex;gap:0;border:1px solid var(--border2);border-radius:var(--radius2);overflow:hidden;margin-top:4px;">
                <button type="button" onclick="setDateMode('week')" id="btn-week"
                    style="padding:5px 12px;font-family:'Space Mono',monospace;font-size:10px;border:none;cursor:pointer;transition:all .2s;background:<?=$dateMode==='week'?'var(--green)':'var(--surface2)'?>;color:<?=$dateMode==='week'?'#000':'var(--muted)'?>;">
                    Week Nav
                </button>
                <button type="button" onclick="setDateMode('custom')" id="btn-custom"
                    style="padding:5px 12px;font-family:'Space Mono',monospace;font-size:10px;border:none;cursor:pointer;transition:all .2s;background:<?=$dateMode==='custom'?'var(--green)':'var(--surface2)'?>;color:<?=$dateMode==='custom'?'#000':'var(--muted)'?>;">
                    Custom Range
                </button>
            </div>
        </div>

        <!-- Custom date range (shown when mode=custom) -->
        <div id="custom-dates" style="display:<?=$dateMode==='custom'?'flex':'none'?>;gap:8px;align-items:flex-end;">
            <div>
                <label>From</label><br>
                <input type="date" name="date_from" class="config-input" style="width:140px;" value="<?=$dateFrom?>">
            </div>
            <div>
                <label>To</label><br>
                <input type="date" name="date_to" class="config-input" style="width:140px;" value="<?=$dateTo?>">
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:8px;">
            <div style="display:flex;gap:8px;">
                <div>
                    <label>Fuel Rate (L/100km)</label><br>
                    <input type="number" name="fuel_rate" class="config-input" value="<?=$fuelRate?>" step="0.1" min="1" max="30">
                </div>
                <div>
                    <label>Fuel Price ($/L)</label><br>
                    <input type="number" name="fuel_price" class="config-input" value="<?=$fuelPrice?>" step="0.01" min="0.1">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Apply Filters</button>
        </div>

        <div style="font-size:10px;color:var(--muted);">
            Mixed fleet rates:<br>
            <span style="color:var(--dim);">Motorcycle ~12 · 4x4 ~10 L/100km</span>
        </div>
    </form>

    <!-- KPIs -->
    <div class="section-title fade-up delay-1">Season Overview</div>
    <div class="kpi-grid fade-up delay-2" style="margin-bottom:24px;">
        <div class="kpi">
            <div class="kpi-icon">🛣️</div>
            <div class="kpi-val" style="color:var(--green)"><?=number_format($totalKm,0)?></div>
            <div class="kpi-label">Total km this season</div>
            <div class="kpi-sub"><?=$totalOfficers?> officers active</div>
        </div>
        <div class="kpi">
            <div class="kpi-icon">⛽</div>
            <div class="kpi-val" style="color:var(--amber)"><?=number_format($totalFuelL,0)?>L</div>
            <div class="kpi-label">Fuel allocated (approved)</div>
            <div class="kpi-sub">@ $<?=$fuelPrice?>/L</div>
        </div>
        <div class="kpi">
            <div class="kpi-icon">💵</div>
            <div class="kpi-val" style="color:var(--blue)">$<?=number_format($totalFuelCost,0)?></div>
            <div class="kpi-label">Fuel cost this season</div>
            <div class="kpi-sub">Approved requests only</div>
        </div>
        <div class="kpi">
            <div class="kpi-icon">⏳</div>
            <div class="kpi-val" style="color:<?=$pendingCount>0?'var(--amber)':'var(--green)'?>"><?=$pendingCount?></div>
            <div class="kpi-label">Pending approval</div>
            <div class="kpi-sub"><?=$pendingCount>0?'Action required':'All clear'?></div>
        </div>
    </div>

    <!-- Trends -->
    <div class="section-title fade-up">Weekly Trends — Last 8 Weeks</div>
    <div class="grid-2 fade-up delay-1" style="margin-bottom:16px;">
        <div class="card">
            <div class="card-title">Distance (km) vs Visits — by Week</div>
            <canvas id="kmTrend" height="220"></canvas>
        </div>
        <div class="card">
            <div class="card-title">Fuel Required (L) vs Visits — by Week</div>
            <canvas id="fuelTrend" height="220"></canvas>
        </div>
    </div>
    <!-- Efficiency chart: km per visit -->
    <div class="card fade-up delay-2" style="margin-bottom:24px;">
        <div class="card-title">km per Visit — Efficiency Trend (lower = more efficient)</div>
        <canvas id="effTrend" height="140"></canvas>
    </div>

    <!-- This week: officer allocation -->
    <div class="section-title fade-up">
        <span>This Week's Fuel Allocation</span>
        <div style="display:flex;align-items:center;gap:8px;">
            <button class="nav-btn" onclick="location.href='?week=<?=$weekOffset-1?>&fuel_rate=<?=$fuelRate?>&fuel_price=<?=$fuelPrice?>&officer_id=<?=$selOfficer?>&date_mode=week'">◀</button>
            <span style="font-size:11px;color:var(--dim);"><?=date('d M',strtotime($monday))?> – <?=date('d M Y',strtotime($sunday))?></span>
            <button class="nav-btn" onclick="location.href='?week=<?=$weekOffset+1?>&fuel_rate=<?=$fuelRate?>&fuel_price=<?=$fuelPrice?>&officer_id=<?=$selOfficer?>&date_mode=week'">▶</button>
            <?php if($weekOffset!==0): ?><a href="?week=0&fuel_rate=<?=$fuelRate?>&fuel_price=<?=$fuelPrice?>&officer_id=<?=$selOfficer?>&date_mode=week" class="back">This week</a><?php endif; ?>
        </div>
    </div>
    <div class="card fade-up" style="margin-bottom:24px;">
        <div class="card-title">Officer Distance → Fuel Entitlement</div>
        <div style="overflow-x:auto;">
        <table>
            <thead><tr>
                <th>#</th><th>Field Officer</th><th>Km This Week</th>
                <th>Litres Needed</th><th>Est. Cost</th>
                <th>Max Daily km</th><th>Request Status</th><th>Action</th>
            </tr></thead>
            <tbody>
            <?php foreach($weeklyRows as $i=>$row):
                $maxKm=$row['km_week']>0?$row['km_week']:0;
                $barW =min(100,round($maxKm/300*100));
                $status=$row['request_status']??null;
            ?>
            <tr>
                <td class="rank"><?=$i+1?></td>
                <td style="font-weight:700;color:var(--text);"><?=htmlspecialchars($row['officer_name'])?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div class="bar-wrap"><div class="bar-fill" style="width:<?=$barW?>%;"></div></div>
                        <span class="mono"><?=number_format($row['km_week'],1)?></span>
                    </div>
                </td>
                <td class="mono" style="color:var(--amber);"><?=number_format($row['litres_needed'],1)?>L</td>
                <td class="mono" style="color:var(--blue);">$<?=number_format($row['cost'],2)?></td>
                <td class="mono"><?=$row['km_week']>0?number_format($row['km_week']/5,1):'-'?></td>
                <td>
                    <?php if($status==='APPROVED'): ?>
                        <span class="badge badge-approved">✓ Approved</span>
                    <?php elseif($status==='REJECTED'): ?>
                        <span class="badge badge-rejected">✕ Rejected</span>
                    <?php elseif($status==='PENDING'): ?>
                        <span class="badge badge-pending">⏳ Pending</span>
                    <?php else: ?>
                        <span class="badge badge-none">No request</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($status==='PENDING'&&$row['request_id']): ?>
                    <button class="btn btn-approve" onclick="openModal(<?=$row['request_id']?>,'approve','<?=htmlspecialchars($row['officer_name'])?>',<?=$row['litres_needed']?>,<?=$row['cost']?>)">Approve</button>
                    <button class="btn btn-reject" onclick="openModal(<?=$row['request_id']?>,'reject','<?=htmlspecialchars($row['officer_name'])?>',<?=$row['litres_needed']?>,<?=$row['cost']?>)" style="margin-left:4px;">Reject</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; if(empty($weeklyRows)): ?>
            <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--muted);">No distance data for this week</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Pending requests detail -->
    <?php if(!empty($pendingRequests)): ?>
    <div class="section-title fade-up">Pending Fuel Requests</div>
    <div class="card fade-up" style="margin-bottom:24px;">
        <div class="card-title">Submitted — Awaiting Manager Approval</div>
        <div style="overflow-x:auto;">
        <table>
            <thead><tr>
                <th>Officer</th><th>Week</th><th>Distance</th><th>Litres</th>
                <th>Est. Cost</th><th>Submitted</th><th>Notes</th><th>Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach($pendingRequests as $req): ?>
            <tr>
                <td style="font-weight:700;color:var(--text);"><?=htmlspecialchars($req['officer_name'])?></td>
                <td class="mono" style="font-size:10px;"><?=date('d M',strtotime($req['week_start_date']))?> – <?=date('d M',strtotime($req['week_end_date']))?></td>
                <td class="mono"><?=number_format($req['total_distance_km'],1)?> km</td>
                <td class="mono" style="color:var(--amber);"><?=number_format($req['total_fuel_litres'],1)?>L</td>
                <td class="mono" style="color:var(--blue);">$<?=number_format($req['estimated_cost'],2)?></td>
                <td style="font-size:10px;color:var(--muted);"><?=date('d M H:i',strtotime($req['created_at']))?></td>
                <td style="font-size:10px;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?=htmlspecialchars($req['officer_notes']??'')?>">
                    <?=htmlspecialchars(substr($req['officer_notes']??'—',0,40))?>
                </td>
                <td>
                    <button class="btn btn-approve" onclick="openModal(<?=$req['id']?>,'approve','<?=htmlspecialchars($req['officer_name'])?>',<?=$req['total_fuel_litres']?>,<?=$req['estimated_cost']?>)">✓ Approve</button>
                    <button class="btn btn-reject" onclick="openModal(<?=$req['id']?>,'reject','<?=htmlspecialchars($req['officer_name'])?>',<?=$req['total_fuel_litres']?>,<?=$req['estimated_cost']?>)" style="margin-left:4px;">✕ Reject</button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Season Distance League -->
    <div class="section-title fade-up">Season Distance League</div>
    <div class="card fade-up" style="margin-bottom:24px;">
        <div class="card-title">Officer km · Fuel · Cost · Efficiency</div>
        <div style="overflow-x:auto;">
        <table>
            <thead><tr>
                <th>#</th><th>Field Officer</th><th>Total km</th><th>Active Days</th>
                <th>Avg km/Day</th><th>Fuel (L)</th><th>Fuel Cost</th><th>Efficiency</th>
            </tr></thead>
            <tbody>
            <?php
            $maxKm=max(array_column($leagueRows,'total_km')?:[1]);
            foreach($leagueRows as $i=>$row):
                $barW=min(100,round($row['total_km']/$maxKm*100));
                $effColor=$row['avg_km_day']>60?'var(--green)':($row['avg_km_day']>30?'var(--amber)':'var(--red)');
            ?>
            <tr>
                <td class="rank"><?=$i+1?></td>
                <td style="font-weight:700;color:var(--text);"><?=htmlspecialchars($row['officer_name'])?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div class="bar-wrap"><div class="bar-fill" style="width:<?=$barW?>%;"></div></div>
                        <span class="mono"><?=number_format($row['total_km'],0)?></span>
                    </div>
                </td>
                <td class="mono"><?=$row['active_days']?></td>
                <td class="mono" style="color:<?=$effColor?>;"><?=number_format($row['avg_km_day'],1)?></td>
                <td class="mono" style="color:var(--amber);"><?=number_format($row['fuel_litres'],1)?>L</td>
                <td class="mono" style="color:var(--blue);">$<?=number_format($row['fuel_cost'],2)?></td>
                <td>
                    <?php if($row['avg_km_day']>60): ?><span class="badge badge-approved">High</span>
                    <?php elseif($row['avg_km_day']>30): ?><span class="badge badge-warning">Medium</span>
                    <?php else: ?><span class="badge badge-rejected">Low</span><?php endif; ?>
                </td>
            </tr>
            <?php endforeach; if(empty($leagueRows)): ?>
            <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--muted);">No distance data this season</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Anomaly Detection -->
    <div class="section-title fade-up">
        <span>🚨 Anomaly Detection</span>
        <span style="font-size:10px;color:var(--muted);"><?=count($anomalies)?> issue<?=count($anomalies)!=1?'s':''?> found</span>
    </div>
    <div class="card fade-up" style="margin-bottom:24px;">
        <div class="card-title">Zero km with visits · Unrealistic distances (>300km/day)</div>
        <?php if(empty($anomalies)): ?>
        <div style="text-align:center;padding:32px;color:var(--muted);">✅ No anomalies detected this season</div>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table>
            <thead><tr><th>Officer</th><th>Date</th><th>Distance</th><th>Visits</th><th>Issue</th><th>Severity</th></tr></thead>
            <tbody>
            <?php foreach($anomalies as $a): ?>
            <tr>
                <td style="font-weight:700;color:var(--text);"><?=htmlspecialchars($a['officer_name'])?></td>
                <td class="mono" style="font-size:10px;"><?=date('d M Y',strtotime($a['day']))?></td>
                <td class="mono" style="color:<?=$a['km']==0?'var(--red)':'var(--amber)';?>"><?=number_format($a['km'],1)?> km</td>
                <td class="mono"><?=$a['visits']?></td>
                <td style="font-size:10px;color:var(--dim);"><?=htmlspecialchars($a['anomaly_type'])?></td>
                <td><span class="badge badge-<?=$a['severity']?>"><?=strtoupper($a['severity'])?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>


    <!-- ══════════════════════════════════════════════════════════════════════
         ROUTE VS ACTUAL
    ═══════════════════════════════════════════════════════════════════════ -->
    <div class="section-title fade-up">
        <span>🗺 Route vs Actual — Planned Growers vs Visited</span>
        <span style="font-size:10px;color:var(--muted);"><?=count($routeVsActual)?> requests</span>
    </div>
    <div class="card fade-up" style="margin-bottom:24px;">
        <div class="card-title">Did officers visit the growers they planned?</div>
        <div style="overflow-x:auto;">
        <table>
            <thead><tr>
                <th>Officer</th><th>Week</th><th>Planned</th>
                <th>Visited</th><th>Skipped</th><th>Completion</th><th>Request Status</th>
            </tr></thead>
            <tbody>
            <?php foreach($routeVsActual as $row):
                $pct   = (float)$row['completion_pct'];
                $skip  = $row['planned_growers'] - $row['visited_growers'];
                $col   = $pct>=90?'var(--green)':($pct>=60?'var(--amber)':'var(--red)');
                $barW  = min(100,max(0,$pct));
                $sBadge= $row['status']==='APPROVED'?'badge-approved':($row['status']==='REJECTED'?'badge-rejected':'badge-pending');
            ?>
            <tr>
                <td style="font-weight:700;color:var(--text);"><?=htmlspecialchars($row['officer_name'])?></td>
                <td class="mono" style="font-size:10px;"><?=date('d M',strtotime($row['week_start_date']))?> – <?=date('d M',strtotime($row['week_end_date']))?></td>
                <td class="mono"><?=$row['planned_growers']?></td>
                <td class="mono" style="color:var(--green);"><?=$row['visited_growers']?></td>
                <td class="mono" style="color:<?=$skip>0?'var(--red)':'var(--muted)'?>"><?=$skip?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div class="bar-wrap" style="width:100px;"><div class="bar-fill" style="width:<?=$barW?>%;background:<?=$col?>;"></div></div>
                        <span class="mono" style="color:<?=$col?>"><?=$pct?>%</span>
                    </div>
                </td>
                <td><span class="badge <?=$sBadge?>"><?=$row['status']?></span></td>
            </tr>
            <?php endforeach; if(empty($routeVsActual)): ?>
            <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--muted);">No fuel requests found for this season</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════
         PLANNED VS ACTUAL DISTANCE
    ═══════════════════════════════════════════════════════════════════════ -->
    <div class="section-title fade-up">📐 Planned vs Actual Distance — Over/Under Driving</div>
    <div class="card fade-up" style="margin-bottom:24px;">
        <div class="card-title">Approved requests: planned km (fuel_requests) vs actual km (distance table)</div>
        <div style="overflow-x:auto;">
        <table>
            <thead><tr>
                <th>Officer</th><th>Week</th><th>Planned km</th>
                <th>Actual km</th><th>Difference</th><th>Variance</th><th>Flag</th>
            </tr></thead>
            <tbody>
            <?php foreach($distVsActual as $row):
                $diff    = (float)$row['diff_km'];
                $diffPct = (float)$row['diff_pct'];
                $absP    = abs($diffPct);
                $flag    = $absP > 50 ? 'critical' : ($absP > 20 ? 'warning' : 'ok');
                $diffCol = $diff > 0 ? 'var(--amber)' : ($diff < 0 ? 'var(--blue)' : 'var(--muted)');
                $arrow   = $diff > 0 ? '▲' : ($diff < 0 ? '▼' : '—');
            ?>
            <tr>
                <td style="font-weight:700;color:var(--text);"><?=htmlspecialchars($row['officer_name'])?></td>
                <td class="mono" style="font-size:10px;"><?=date('d M',strtotime($row['week_start_date']))?></td>
                <td class="mono"><?=number_format($row['planned_km'],1)?></td>
                <td class="mono"><?=number_format($row['actual_km'],1)?></td>
                <td class="mono" style="color:<?=$diffCol?>"><?=$arrow?> <?=number_format(abs($diff),1)?> km</td>
                <td class="mono" style="color:<?=$diffCol?>"><?=$diff>=0?'+':''?><?=$diffPct?>%</td>
                <td>
                    <?php if($flag==='critical'): ?><span class="badge badge-critical">Review</span>
                    <?php elseif($flag==='warning'): ?><span class="badge badge-warning">Check</span>
                    <?php else: ?><span class="badge badge-approved">OK</span><?php endif; ?>
                </td>
            </tr>
            <?php endforeach; if(empty($distVsActual)): ?>
            <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--muted);">No approved requests with distance data</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════
         COST PER VISIT
    ═══════════════════════════════════════════════════════════════════════ -->
    <div class="section-title fade-up">💵 Cost per Visit — Fuel ROI by Officer</div>
    <div class="card fade-up" style="margin-bottom:24px;">
        <div class="card-title">Approved fuel cost ÷ actual distinct visits — lower is better</div>
        <div style="overflow-x:auto;">
        <table>
            <thead><tr>
                <th>#</th><th>Officer</th><th>Requests</th><th>Fuel (L)</th>
                <th>Fuel Cost</th><th>Actual Visits</th><th>Cost / Visit</th><th>Efficiency</th>
            </tr></thead>
            <tbody>
            <?php
            $minCpv = !empty($costPerVisit) ? min(array_filter(array_column($costPerVisit,'cost_per_visit'))) : 1;
            $maxCpv = !empty($costPerVisit) ? max(array_column($costPerVisit,'cost_per_visit')) : 1;
            foreach($costPerVisit as $i=>$row):
                $cpv   = (float)$row['cost_per_visit'];
                $range = max(1,$maxCpv-$minCpv);
                $effPct= $maxCpv>0 ? round((1-($cpv-$minCpv)/$range)*100) : 100;
                $col   = $effPct>=70?'var(--green)':($effPct>=40?'var(--amber)':'var(--red)');
            ?>
            <tr>
                <td class="rank"><?=$i+1?></td>
                <td style="font-weight:700;color:var(--text);"><?=htmlspecialchars($row['officer_name'])?></td>
                <td class="mono"><?=$row['requests']?></td>
                <td class="mono" style="color:var(--amber);"><?=number_format($row['total_litres'],1)?>L</td>
                <td class="mono" style="color:var(--blue);">$<?=number_format($row['total_cost'],2)?></td>
                <td class="mono" style="color:var(--green);"><?=number_format($row['actual_visits'])?></td>
                <td class="mono" style="font-weight:700;color:<?=$col?>;">
                    <?=$cpv>0?'$'.number_format($cpv,2):'—'?>
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div class="bar-wrap"><div class="bar-fill" style="width:<?=$effPct?>%;background:<?=$col?>;"></div></div>
                        <span class="mono" style="font-size:10px;color:<?=$col?>"><?=$effPct?>%</span>
                    </div>
                </td>
            </tr>
            <?php endforeach; if(empty($costPerVisit)): ?>
            <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--muted);">No approved requests this season</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════
         COMPLIANCE SCORE
    ═══════════════════════════════════════════════════════════════════════ -->
    <div class="section-title fade-up">
        <span>📋 Fuel Request Compliance</span>
        <span style="font-size:10px;color:var(--muted);"><?=$weeksSinceStart?> weeks in season</span>
    </div>
    <div class="card fade-up" style="margin-bottom:24px;">
        <div class="card-title">Who submits weekly fuel requests vs who goes dark</div>
        <div style="overflow-x:auto;">
        <table>
            <thead><tr>
                <th>#</th><th>Officer</th><th>Submitted</th><th>Approved</th>
                <th>Rejected</th><th>Pending</th><th>Submission Rate</th><th>Last Submitted</th><th>Status</th>
            </tr></thead>
            <tbody>
            <?php foreach($complianceRows as $i=>$row):
                $rate = (float)$row['submission_rate'];
                $col  = $rate>=80?'var(--green)':($rate>=50?'var(--amber)':'var(--red)');
                $barW = min(100,max(0,$rate));
                $last = $row['last_submission'] ? date('d M Y',strtotime($row['last_submission'])) : 'Never';
                $weeksSince = $row['last_submission']
                    ? floor((time()-strtotime($row['last_submission']))/604800)
                    : 99;
            ?>
            <tr>
                <td class="rank"><?=$i+1?></td>
                <td style="font-weight:700;color:var(--text);"><?=htmlspecialchars($row['officer_name'])?></td>
                <td class="mono"><?=$row['requests_submitted']?></td>
                <td class="mono" style="color:var(--green);"><?=$row['approved']?></td>
                <td class="mono" style="color:var(--red);"><?=$row['rejected']?></td>
                <td class="mono" style="color:var(--amber);"><?=$row['pending']?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div class="bar-wrap"><div class="bar-fill" style="width:<?=$barW?>%;background:<?=$col?>;"></div></div>
                        <span class="mono" style="color:<?=$col?>"><?=$rate?>%</span>
                    </div>
                </td>
                <td style="font-size:10px;color:<?=$weeksSince>2?'var(--red)':'var(--muted)'?>">
                    <?=$last?><?=$weeksSince>2?' ('.$weeksSince.'w ago)':''?>
                </td>
                <td>
                    <?php if($rate>=80): ?><span class="badge badge-approved">Compliant</span>
                    <?php elseif($rate>=50): ?><span class="badge badge-warning">Irregular</span>
                    <?php elseif($row['requests_submitted']==0): ?><span class="badge badge-critical">Never</span>
                    <?php else: ?><span class="badge badge-rejected">Non-compliant</span><?php endif; ?>
                </td>
            </tr>
            <?php endforeach; if(empty($complianceRows)): ?>
            <tr><td colspan="9" style="text-align:center;padding:32px;color:var(--muted);">No officers found</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

</div><!-- /.page -->

<!-- Approval Modal -->
<div class="modal-overlay" id="modal">
    <div class="modal">
        <h3 id="modal-title">Approve Request</h3>
        <div id="modal-summary" style="font-size:11px;color:var(--dim);margin-bottom:14px;line-height:1.8;"></div>
        <label style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);">Manager Notes</label>
        <textarea id="modal-notes" placeholder="Optional notes for the officer..."></textarea>
        <form method="POST" id="modal-form">
            <input type="hidden" name="request_id" id="modal-rid">
            <input type="hidden" name="action" id="modal-action">
            <input type="hidden" name="notes" id="modal-notes-hidden">
        </form>
        <div class="modal-actions">
            <button class="modal-cancel" onclick="closeModal()">Cancel</button>
            <button class="btn" id="modal-confirm-btn" onclick="submitModal()">Confirm</button>
        </div>
    </div>
</div>

<script>
Chart.defaults.color='#4a6b4a';
Chart.defaults.borderColor='rgba(28,46,28,.6)';
Chart.defaults.font.family="'Space Mono',monospace";
Chart.defaults.font.size=10;
Chart.defaults.plugins.tooltip.backgroundColor='#162016';
Chart.defaults.plugins.tooltip.borderColor='rgba(61,220,104,.2)';
Chart.defaults.plugins.tooltip.borderWidth=1;
Chart.defaults.plugins.legend.display=false;

const trendLabels  = <?=$trendWeeksJson?>;
const trendKm      = <?=$trendKmJson?>;
const trendFuel    = <?=$trendFuelJson?>;
const trendVisits  = <?=$trendVisitsJson?>;

// Efficiency: km per visit (null when 0 visits)
const trendEff = trendKm.map((km,i)=>
    trendVisits[i]>0 ? Math.round((km/trendVisits[i])*10)/10 : null
);

const dualAxisOpts = (yLabel, yUnit, y2Label) => ({
    responsive:true,
    interaction:{mode:'index',intersect:false},
    plugins:{
        legend:{display:true,labels:{color:'#7a9e7a',font:{family:"'Space Mono',monospace",size:10},usePointStyle:true,pointStyleWidth:8}},
        tooltip:{backgroundColor:'#162016',borderColor:'rgba(61,220,104,.2)',borderWidth:1,
            callbacks:{label:ctx=>` ${ctx.dataset.label}: ${ctx.parsed.y}${ctx.datasetIndex===0?yUnit:' visits'}`}}
    },
    scales:{
        y:{
            type:'linear',position:'left',beginAtZero:true,
            grid:{color:'rgba(28,46,28,.8)'},
            ticks:{color:'#4a6b4a',callback:v=>v+yUnit}
        },
        y2:{
            type:'linear',position:'right',beginAtZero:true,
            grid:{drawOnChartArea:false},
            ticks:{color:'#4a9eff',callback:v=>v+' v'}
        }
    }
});

// Chart 1: km vs visits
new Chart(document.getElementById('kmTrend'),{
    type:'line',
    data:{
        labels:trendLabels,
        datasets:[
            {
                label:'Distance (km)',data:trendKm,yAxisID:'y',
                borderColor:'#3ddc68',backgroundColor:'rgba(61,220,104,.07)',
                borderWidth:2,pointRadius:4,pointBackgroundColor:'#3ddc68',tension:0.4,fill:true
            },
            {
                label:'Visits',data:trendVisits,yAxisID:'y2',
                borderColor:'#4a9eff',backgroundColor:'rgba(74,158,255,.0)',
                borderWidth:2,pointRadius:4,pointBackgroundColor:'#4a9eff',
                tension:0.4,fill:false,borderDash:[4,3]
            }
        ]
    },
    options:dualAxisOpts('km','km','Visits')
});

// Chart 2: fuel vs visits
new Chart(document.getElementById('fuelTrend'),{
    type:'bar',
    data:{
        labels:trendLabels,
        datasets:[
            {
                label:'Fuel (L)',data:trendFuel,yAxisID:'y',
                backgroundColor:'rgba(245,166,35,.55)',borderColor:'#f5a623',
                borderWidth:1,borderRadius:4,order:2
            },
            {
                label:'Visits',data:trendVisits,yAxisID:'y2',
                type:'line',
                borderColor:'#4a9eff',backgroundColor:'rgba(74,158,255,.0)',
                borderWidth:2,pointRadius:4,pointBackgroundColor:'#4a9eff',
                tension:0.4,fill:false,borderDash:[4,3],order:1
            }
        ]
    },
    options:dualAxisOpts('L','L','Visits')
});

// Chart 3: km per visit efficiency
new Chart(document.getElementById('effTrend'),{
    type:'line',
    data:{
        labels:trendLabels,
        datasets:[{
            label:'km per visit',data:trendEff,
            borderColor:'#b47eff',backgroundColor:'rgba(180,126,255,.07)',
            borderWidth:2,pointRadius:4,pointBackgroundColor:'#b47eff',
            tension:0.4,fill:true,
            spanGaps:true
        }]
    },
    options:{
        responsive:true,
        plugins:{
            legend:{display:false},
            tooltip:{backgroundColor:'#162016',borderColor:'rgba(180,126,255,.3)',borderWidth:1,
                callbacks:{label:ctx=>` ${ctx.parsed.y} km per visit`}}
        },
        scales:{
            y:{beginAtZero:true,grid:{color:'rgba(28,46,28,.8)'},
               ticks:{color:'#4a6b4a',callback:v=>v+' km/v'}},
            x:{grid:{display:false}}
        }
    }
});

// Modal
let currentAction='approve';
function openModal(rid,action,name,litres,cost){
    currentAction=action;
    document.getElementById('modal-rid').value=rid;
    document.getElementById('modal-action').value=action;
    document.getElementById('modal-notes').value='';
    document.getElementById('modal-title').textContent=
        action==='approve'?'Approve Fuel Request':'Reject Fuel Request';
    document.getElementById('modal-title').style.color=
        action==='approve'?'var(--green)':'var(--red)';
    document.getElementById('modal-summary').innerHTML=
        `Officer: <b style="color:var(--text)">${name}</b><br>`+
        `Fuel: <b style="color:var(--amber)">${litres.toFixed(1)}L</b> &nbsp;·&nbsp; `+
        `Est. Cost: <b style="color:var(--blue)">$${cost.toFixed(2)}</b>`;
    const btn=document.getElementById('modal-confirm-btn');
    btn.textContent=action==='approve'?'✓ Approve':'✕ Reject';
    btn.style.background=action==='approve'?'var(--green)':'var(--red)';
    btn.style.color=action==='approve'?'#000':'#fff';
    document.getElementById('modal').classList.add('open');
}
function closeModal(){document.getElementById('modal').classList.remove('open');}
function submitModal(){
    document.getElementById('modal-notes-hidden').value=document.getElementById('modal-notes').value;
    document.getElementById('modal-form').submit();
}
document.getElementById('modal').addEventListener('click',function(e){
    if(e.target===this) closeModal();
});

// Date mode toggle
function setDateMode(mode){
    document.getElementById('date-mode-input').value=mode;
    const custom=document.getElementById('custom-dates');
    const btnWeek=document.getElementById('btn-week');
    const btnCustom=document.getElementById('btn-custom');
    if(mode==='custom'){
        custom.style.display='flex';
        btnCustom.style.background='var(--green)';btnCustom.style.color='#000';
        btnWeek.style.background='var(--surface2)';btnWeek.style.color='var(--muted)';
    } else {
        custom.style.display='none';
        btnWeek.style.background='var(--green)';btnWeek.style.color='#000';
        btnCustom.style.background='var(--surface2)';btnCustom.style.color='var(--muted)';
    }
}
</script>
</body>
</html>
