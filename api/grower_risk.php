<?php
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

// ── Filters ────────────────────────────────────────────────────────────────────
$selOfficer = isset($_GET['officer_id'])&&$_GET['officer_id']!==''?(int)$_GET['officer_id']:null;
$selRisk    = $_GET['risk']??'all'; // all, red, amber, green
$selArea    = isset($_GET['area'])&&$_GET['area']!=='' ? $conn->real_escape_string($_GET['area']) : '';
$search     = isset($_GET['q'])&&$_GET['q']!=='' ? $conn->real_escape_string(trim($_GET['q'])) : '';

// Fetch officers for dropdown
$allOfficers=[];
$r=$conn->query("SELECT id,name FROM field_officers ORDER BY name");
if($r){while($row=$r->fetch_assoc()) $allOfficers[]=$row; $r->free();}

// Fetch areas
$allAreas=[];
$r=$conn->query("SELECT DISTINCT area FROM growers WHERE area IS NOT NULL AND area!='' ORDER BY area");
if($r){while($row=$r->fetch_assoc()) $allAreas[]=$row['area']; $r->free();}

// ── Risk Scorecard Query ───────────────────────────────────────────────────────
// Score components (max 100):
//   Days since last visit  : 0-30 pts (>60d=30, 30-60d=20, 14-30d=10, <14d=0)
//   Unverified loan value  : 0-25 pts (>$500=25, >$200=15, >$0=8, none=0)
//   Transplanting survival : 0-20 pts (<50%=20, <75%=12, <90%=6, >=90%=0)
//   Rollover amount        : 0-15 pts (>$300=15, >$100=10, >$0=5, none=0)
//   Loan to working capital: 0-10 pts (>2x=10, >1x=6, >0.5x=3, ok=0)

$officerJoin = $selOfficer
    ? "AND gfo.field_officerid=(SELECT userid FROM field_officers WHERE id={$selOfficer} LIMIT 1)"
    : "";
$areaWhere = $selArea ? "AND g.area='{$selArea}'" : "";
$searchWhere = $search ? "AND (g.name LIKE '%{$search}%' OR g.surname LIKE '%{$search}%' OR g.grower_num LIKE '%{$search}%')" : "";

$sql = "
SELECT
    g.id AS grower_id,
    g.grower_num,
    CONCAT(g.name,' ',g.surname) AS grower_name,
    g.area,
    fo.name AS officer_name,

    -- Visit data
    MAX(v.created_at) AS last_visit,
    DATEDIFF(NOW(), MAX(v.created_at)) AS days_since_visit,
    COUNT(DISTINCT CONCAT(v.growerid,'-',DATE(STR_TO_DATE(v.created_at,'%Y-%m-%d')))) AS total_visits,

    -- Loan data
    COALESCE(SUM(l.product_total_cost),0) AS total_loan_value,
    COALESCE(SUM(CASE WHEN l.verified=0 THEN l.product_total_cost ELSE 0 END),0) AS unverified_value,
    COUNT(DISTINCT l.id) AS loan_count,

    -- Working capital
    MAX(wc.amount) AS working_capital,

    -- Rollover
    COALESCE(SUM(ro.amount),0) AS rollover_amount,

    -- Transplanting
    ROUND(AVG(tt.transplant_survival_rate),1) AS avg_survival,
    MAX(tt.transplant_vigor) AS vigor,

    -- ── Risk Score Calculation ────────────────────────────────────────────
    (
        -- Visit score (0-30)
        CASE
            WHEN MAX(v.created_at) IS NULL THEN 30
            WHEN DATEDIFF(NOW(),MAX(v.created_at)) > 60 THEN 30
            WHEN DATEDIFF(NOW(),MAX(v.created_at)) > 30 THEN 20
            WHEN DATEDIFF(NOW(),MAX(v.created_at)) > 14 THEN 10
            ELSE 0
        END
        +
        -- Unverified loan score (0-25)
        CASE
            WHEN COALESCE(SUM(CASE WHEN l.verified=0 THEN l.product_total_cost ELSE 0 END),0) > 500 THEN 25
            WHEN COALESCE(SUM(CASE WHEN l.verified=0 THEN l.product_total_cost ELSE 0 END),0) > 200 THEN 15
            WHEN COALESCE(SUM(CASE WHEN l.verified=0 THEN l.product_total_cost ELSE 0 END),0) > 0   THEN 8
            ELSE 0
        END
        +
        -- Survival rate score (0-20)
        CASE
            WHEN AVG(tt.transplant_survival_rate) IS NULL THEN 10
            WHEN AVG(tt.transplant_survival_rate) < 50 THEN 20
            WHEN AVG(tt.transplant_survival_rate) < 75 THEN 12
            WHEN AVG(tt.transplant_survival_rate) < 90 THEN 6
            ELSE 0
        END
        +
        -- Rollover score (0-15)
        CASE
            WHEN COALESCE(SUM(ro.amount),0) > 300 THEN 15
            WHEN COALESCE(SUM(ro.amount),0) > 100 THEN 10
            WHEN COALESCE(SUM(ro.amount),0) > 0   THEN 5
            ELSE 0
        END
        +
        -- Loan vs working capital score (0-10)
        CASE
            WHEN MAX(wc.amount) IS NULL OR MAX(wc.amount) = 0 THEN 5
            WHEN COALESCE(SUM(l.product_total_cost),0) / MAX(wc.amount) > 2 THEN 10
            WHEN COALESCE(SUM(l.product_total_cost),0) / MAX(wc.amount) > 1 THEN 6
            WHEN COALESCE(SUM(l.product_total_cost),0) / MAX(wc.amount) > 0.5 THEN 3
            ELSE 0
        END
    ) AS risk_score

FROM growers g
JOIN grower_field_officer gfo ON gfo.growerid=g.id AND gfo.seasonid={$seasonId} {$officerJoin}
JOIN field_officers fo ON fo.userid=gfo.field_officerid
LEFT JOIN visits v ON v.growerid=g.id AND v.seasonid={$seasonId}
LEFT JOIN loans l ON l.growerid=g.id AND l.seasonid={$seasonId}
LEFT JOIN working_capital wc ON wc.growerid=g.id AND wc.seasonid={$seasonId}
LEFT JOIN rollover ro ON ro.growerid=g.id AND ro.seasonid={$seasonId}
LEFT JOIN tobacco_transplanting tt ON tt.growerid=g.id AND tt.seasonid={$seasonId}
WHERE 1=1 {$areaWhere} {$searchWhere}
GROUP BY g.id, g.grower_num, g.name, g.surname, g.area, fo.name
ORDER BY risk_score DESC
";

$growers=[];
$r=$conn->query($sql);
while($r&&$row=$r->fetch_assoc()) $growers[]=$row;

// Apply risk filter in PHP
if($selRisk!=='all'){
    $growers=array_filter($growers,function($g) use($selRisk){
        $s=(int)$g['risk_score'];
        if($selRisk==='red')   return $s>=60;
        if($selRisk==='amber') return $s>=30&&$s<60;
        if($selRisk==='green') return $s<30;
        return true;
    });
}

// Summary counts
$redCount   =count(array_filter($growers,fn($g)=>(int)$g['risk_score']>=60));
$amberCount =count(array_filter($growers,fn($g)=>(int)$g['risk_score']>=30&&(int)$g['risk_score']<60));
$greenCount =count(array_filter($growers,fn($g)=>(int)$g['risk_score']<30));
$totalCount =count($growers);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>GMS · Grower Risk Scorecard</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --bg:#080c08;--surface:#0f160f;--surface2:#162016;--border:#1c2e1c;--border2:#243824;
    --green:#3ddc68;--amber:#f5a623;--red:#e84040;--blue:#4a9eff;--muted:#4a6b4a;--dim:#7a9e7a;
    --text:#c8e6c9;--radius:8px;--radius2:5px;
}
html,body{font-family:'Space Mono',monospace;background:var(--bg);color:var(--text);min-height:100vh;font-size:13px;}
header{display:flex;align-items:center;gap:10px;padding:0 20px;height:56px;background:var(--surface);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100;}
.logo{font-family:'Syne',sans-serif;font-size:18px;font-weight:900;color:var(--green);}
.logo span{color:var(--muted);}
.back{font-size:10px;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:4px 10px;border-radius:var(--radius2);transition:all .2s;}
.back:hover{color:var(--green);border-color:var(--green);}
.page{max-width:1400px;margin:0 auto;padding:24px 20px 60px;}
.section-title{font-family:'Syne',sans-serif;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin:24px 0 12px;padding-bottom:8px;border-bottom:1px solid var(--border);}

/* KPI */
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px;}
.kpi{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px 18px;cursor:pointer;transition:all .2s;text-decoration:none;display:block;}
.kpi:hover{transform:translateY(-2px);}
.kpi-val{font-family:'Syne',sans-serif;font-size:32px;font-weight:900;line-height:1;margin:6px 0 4px;}
.kpi-label{font-size:9px;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);}
.kpi-sub{font-size:10px;color:var(--dim);margin-top:4px;}
.kpi.red{border-color:rgba(232,64,64,.4);} .kpi.red .kpi-val{color:var(--red);}
.kpi.amber{border-color:rgba(245,166,35,.4);} .kpi.amber .kpi-val{color:var(--amber);}
.kpi.green{border-color:rgba(61,220,104,.4);} .kpi.green .kpi-val{color:var(--green);}
@media(max-width:800px){.kpi-grid{grid-template-columns:repeat(2,1fr);}}

/* Filter bar */
.filter-bar{display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px;margin-bottom:24px;}
.filter-bar label{font-size:9px;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);display:block;margin-bottom:4px;}
select,input[type=text]{background:var(--surface2);border:1px solid var(--border2);color:var(--text);border-radius:var(--radius2);padding:6px 10px;font-family:'Space Mono',monospace;font-size:11px;outline:none;}
select:focus,input:focus{border-color:var(--green);}
.btn{padding:6px 16px;border-radius:var(--radius2);border:none;cursor:pointer;font-family:'Space Mono',monospace;font-size:11px;font-weight:700;transition:all .2s;}
.btn-primary{background:var(--green);color:#000;}
.btn-ghost{background:transparent;border:1px solid var(--border);color:var(--muted);}
.btn-ghost:hover{border-color:var(--green);color:var(--green);}

/* Risk filters */
.risk-tabs{display:flex;gap:8px;margin-bottom:16px;}
.risk-tab{padding:5px 14px;border-radius:20px;font-size:10px;font-weight:700;cursor:pointer;border:1px solid var(--border);color:var(--muted);text-decoration:none;transition:all .2s;}
.risk-tab.active-all{background:var(--surface2);color:var(--text);border-color:var(--border2);}
.risk-tab.active-red{background:rgba(232,64,64,.15);color:var(--red);border-color:rgba(232,64,64,.4);}
.risk-tab.active-amber{background:rgba(245,166,35,.12);color:var(--amber);border-color:rgba(245,166,35,.3);}
.risk-tab.active-green{background:rgba(61,220,104,.12);color:var(--green);border-color:rgba(61,220,104,.3);}

/* Table */
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;}
table{width:100%;border-collapse:collapse;font-size:11px;}
th{text-align:left;padding:9px 12px;font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);border-bottom:1px solid var(--border);background:var(--surface2);white-space:nowrap;}
td{padding:10px 12px;border-bottom:1px solid rgba(28,46,28,.5);color:var(--dim);vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:rgba(61,220,104,.02);color:var(--text);}
.mono{font-family:'Space Mono',monospace;}

/* Risk badge */
.risk-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:10px;font-size:10px;font-weight:700;}
.risk-red{background:rgba(232,64,64,.12);color:var(--red);border:1px solid rgba(232,64,64,.3);}
.risk-amber{background:rgba(245,166,35,.1);color:var(--amber);border:1px solid rgba(245,166,35,.25);}
.risk-green{background:rgba(61,220,104,.1);color:var(--green);border:1px solid rgba(61,220,104,.25);}

/* Score bar */
.score-wrap{display:flex;align-items:center;gap:8px;}
.score-bar{width:70px;height:6px;background:var(--surface2);border-radius:3px;overflow:hidden;}
.score-fill{height:100%;border-radius:3px;}

/* Component dots */
.comp-dots{display:flex;gap:3px;}
.comp-dot{width:10px;height:10px;border-radius:2px;}

@keyframes fadeUp{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
.fade-up{animation:fadeUp .3s ease forwards;}
::-webkit-scrollbar{width:4px;height:4px;}
::-webkit-scrollbar-thumb{background:var(--border2);}
</style>
</head>
<body>
<header>
    <div class="logo">GMS<span>/</span>Risk</div>
    <a href="reports_hub.php" class="back">← Reports</a>
    <a href="bi_overview.php" class="back">📊 BI</a>
    <?php if($redCount>0): ?>
    <span style="background:rgba(232,64,64,.12);color:var(--red);border:1px solid rgba(232,64,64,.25);padding:3px 10px;border-radius:12px;font-size:10px;">⚠ <?=$redCount?> high risk growers</span>
    <?php endif; ?>
    <div style="margin-left:auto;font-size:10px;color:var(--muted);">Season: <?=htmlspecialchars($seasonName)?></div>
</header>

<div class="page">
    <div style="margin-bottom:20px;" class="fade-up">
        <div style="font-family:'Syne',sans-serif;font-size:24px;font-weight:900;letter-spacing:-.5px;">🎯 Grower Risk Scorecard</div>
        <div style="font-size:11px;color:var(--muted);margin-top:4px;">Composite risk from visits · loans · transplanting · rollover · working capital</div>
    </div>

    <!-- KPIs -->
    <div class="kpi-grid fade-up">
        <a href="?risk=red<?=$selOfficer?"&officer_id={$selOfficer}":""?>" class="kpi red">
            <div style="font-size:20px;margin-bottom:6px;">🔴</div>
            <div class="kpi-val"><?=$redCount?></div>
            <div class="kpi-label">High Risk</div>
            <div class="kpi-sub">Score ≥ 60 · Immediate action</div>
        </a>
        <a href="?risk=amber<?=$selOfficer?"&officer_id={$selOfficer}":""?>" class="kpi amber">
            <div style="font-size:20px;margin-bottom:6px;">🟡</div>
            <div class="kpi-val"><?=$amberCount?></div>
            <div class="kpi-label">Medium Risk</div>
            <div class="kpi-sub">Score 30–59 · Monitor closely</div>
        </a>
        <a href="?risk=green<?=$selOfficer?"&officer_id={$selOfficer}":""?>" class="kpi green">
            <div style="font-size:20px;margin-bottom:6px;">🟢</div>
            <div class="kpi-val"><?=$greenCount?></div>
            <div class="kpi-label">Low Risk</div>
            <div class="kpi-sub">Score < 30 · On track</div>
        </a>
        <div class="kpi" style="cursor:default;">
            <div style="font-size:20px;margin-bottom:6px;">👥</div>
            <div class="kpi-val" style="color:var(--blue);"><?=$totalCount?></div>
            <div class="kpi-label">Total Growers</div>
            <div class="kpi-sub">Season <?=htmlspecialchars($seasonName)?></div>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="filter-bar fade-up">
        <div>
            <label>Search Grower</label>
            <input type="text" name="q" placeholder="Name or grower #" value="<?=htmlspecialchars($search)?>" style="width:180px;">
        </div>
        <div>
            <label>Field Officer</label>
            <select name="officer_id">
                <option value="">All Officers</option>
                <?php foreach($allOfficers as $o): ?>
                <option value="<?=$o['id']?>" <?=$selOfficer==$o['id']?'selected':''?>><?=htmlspecialchars($o['name'])?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Area</label>
            <select name="area">
                <option value="">All Areas</option>
                <?php foreach($allAreas as $a): ?>
                <option value="<?=htmlspecialchars($a)?>" <?=$selArea===$a?'selected':''?>><?=htmlspecialchars($a)?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <input type="hidden" name="risk" value="<?=htmlspecialchars($selRisk)?>">
        <button type="submit" class="btn btn-primary">Apply</button>
        <a href="grower_risk.php" class="btn btn-ghost">Reset</a>
    </form>

    <!-- Risk tabs -->
    <div class="risk-tabs fade-up">
        <?php
        $tabs=[['all','All','active-all'],['red','🔴 High Risk','active-red'],['amber','🟡 Medium','active-amber'],['green','🟢 Low Risk','active-green']];
        foreach($tabs as [$val,$label,$cls]):
            $active=$selRisk===$val?$cls:'';
            $href='?risk='.$val.($selOfficer?"&officer_id={$selOfficer}":"").($selArea?"&area=".urlencode($selArea):"");
        ?>
        <a href="<?=$href?>" class="risk-tab <?=$active?>"><?=$label?></a>
        <?php endforeach; ?>
    </div>

    <!-- Scorecard table -->
    <div class="card fade-up">
    <div style="overflow-x:auto;">
    <table>
        <thead><tr>
            <th>#</th><th>Grower</th><th>Grower #</th><th>Area</th><th>Officer</th>
            <th>Risk Score</th><th>Risk</th>
            <th title="Visit score">🗓 Visit</th>
            <th title="Loan score">💰 Loan</th>
            <th title="Transplanting score">🌱 Crop</th>
            <th title="Rollover score">🔄 Rollover</th>
            <th title="Capital score">📊 Capital</th>
            <th>Last Visit</th><th>Unverified $</th><th>Rollover $</th><th>Survival %</th>
        </tr></thead>
        <tbody>
        <?php
        $rank=0;
        foreach($growers as $row):
            $rank++;
            $score=(int)$row['risk_score'];
            $riskClass=$score>=60?'risk-red':($score>=30?'risk-amber':'risk-green');
            $riskLabel=$score>=60?'High':($score>=30?'Medium':'Low');
            $barCol=$score>=60?'var(--red)':($score>=30?'var(--amber)':'var(--green)');
            $barW=min(100,$score);

            // Component scores
            $dv=$row['days_since_visit']??999;
            $vScore=$row['last_visit']===null?30:($dv>60?30:($dv>30?20:($dv>14?10:0)));
            $uv=(float)$row['unverified_value'];
            $lScore=$uv>500?25:($uv>200?15:($uv>0?8:0));
            $sr=$row['avg_survival']!==null?(float)$row['avg_survival']:null;
            $cScore=$sr===null?10:($sr<50?20:($sr<75?12:($sr<90?6:0)));
            $rv=(float)$row['rollover_amount'];
            $rScore=$rv>300?15:($rv>100?10:($rv>0?5:0));
            $wc=$row['working_capital']!==null?(float)$row['working_capital']:0;
            $lv=(float)$row['total_loan_value'];
            $wScore=($wc==0)?5:($lv/$wc>2?10:($lv/$wc>1?6:($lv/$wc>0.5?3:0)));

            $compColor=fn($s,$max)=>$s>=$max*.7?'var(--red)':($s>=$max*.4?'var(--amber)':'var(--green)');
        ?>
        <tr>
            <td style="color:var(--muted);font-size:10px;"><?=$rank?></td>
            <td style="font-weight:700;color:var(--text);white-space:nowrap;"><?=htmlspecialchars($row['grower_name'])?></td>
            <td class="mono" style="font-size:10px;"><?=htmlspecialchars($row['grower_num'])?></td>
            <td style="font-size:10px;"><?=htmlspecialchars($row['area'])?></td>
            <td style="font-size:10px;white-space:nowrap;"><?=htmlspecialchars($row['officer_name'])?></td>
            <td>
                <div class="score-wrap">
                    <div class="score-bar"><div class="score-fill" style="width:<?=$barW?>%;background:<?=$barCol?>;"></div></div>
                    <span class="mono" style="font-size:11px;font-weight:700;color:<?=$barCol?>"><?=$score?></span>
                </div>
            </td>
            <td><span class="risk-badge <?=$riskClass?>"><?=$riskLabel?></span></td>
            <!-- Component breakdown dots -->
            <td><div style="font-family:'Space Mono',monospace;font-size:10px;color:<?=$compColor($vScore,30)?>"><?=$vScore?>/30</div></td>
            <td><div style="font-family:'Space Mono',monospace;font-size:10px;color:<?=$compColor($lScore,25)?>"><?=$lScore?>/25</div></td>
            <td><div style="font-family:'Space Mono',monospace;font-size:10px;color:<?=$compColor($cScore,20)?>"><?=$cScore?>/20</div></td>
            <td><div style="font-family:'Space Mono',monospace;font-size:10px;color:<?=$compColor($rScore,15)?>"><?=$rScore?>/15</div></td>
            <td><div style="font-family:'Space Mono',monospace;font-size:10px;color:<?=$compColor($wScore,10)?>"><?=$wScore?>/10</div></td>
            <td style="font-size:10px;color:<?=($dv>30?'var(--red)':($dv>14?'var(--amber)':'var(--muted)'))?>">
                <?=$row['last_visit']?date('d M Y',strtotime($row['last_visit'])):'Never'?>
            </td>
            <td class="mono" style="color:<?=$uv>0?'var(--amber)':'var(--muted)'?>">
                <?=$uv>0?'$'.number_format($uv,2):'—'?>
            </td>
            <td class="mono" style="color:<?=$rv>0?'var(--red)':'var(--muted)'?>">
                <?=$rv>0?'$'.number_format($rv,2):'—'?>
            </td>
            <td class="mono" style="color:<?=$sr!==null?($sr<75?'var(--red)':'var(--green)'):'var(--muted)'?>">
                <?=$sr!==null?$sr.'%':'N/A'?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($growers)): ?>
        <tr><td colspan="16" style="text-align:center;padding:48px;color:var(--muted);">No growers found for selected filters</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
    </div>

    <!-- Score legend -->
    <div style="margin-top:20px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px;" class="fade-up">
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:12px;">Score Components</div>
        <div style="display:flex;gap:24px;flex-wrap:wrap;font-size:10px;color:var(--dim);line-height:2;">
            <span>🗓 <b>Visit (0–30)</b>: >60d=30 · 30-60d=20 · 14-30d=10 · &lt;14d=0</span>
            <span>💰 <b>Loan (0–25)</b>: Unverified value &gt;$500=25 · &gt;$200=15 · &gt;$0=8</span>
            <span>🌱 <b>Crop (0–20)</b>: Survival &lt;50%=20 · &lt;75%=12 · &lt;90%=6</span>
            <span>🔄 <b>Rollover (0–15)</b>: &gt;$300=15 · &gt;$100=10 · &gt;$0=5</span>
            <span>📊 <b>Capital (0–10)</b>: Loans&gt;2×WC=10 · &gt;1×=6 · &gt;0.5×=3</span>
        </div>
    </div>
</div>
</body>
</html>
