<?php
/**
 * bi_shared.php
 * GMS Business Intelligence - Shared Foundation
 * Include at the top of every BI page
 */

ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Database Connection ───────────────────────────────────────────────────────
require "conn.php";
// Reconnect if conn was closed by a previously included file
if (!isset($conn) || $conn->connect_errno) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
}
$conn->query("SET time_zone = '+02:00'");

// ── Active Season ─────────────────────────────────────────────────────────────
$activeSeason = null;
$res = $conn->query("SELECT id, name FROM seasons WHERE active = 1 LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    $activeSeason = $row;
}
$activeSeasonId   = $activeSeason['id']   ?? null;
$activeSeasonName = $activeSeason['name'] ?? 'N/A';

// ── View Mode (executive / operational) ──────────────────────────────────────
if (isset($_GET['view_mode']) && in_array($_GET['view_mode'], ['executive', 'operational'])) {
    $_SESSION['bi_view_mode'] = $_GET['view_mode'];
}
$viewMode = $_SESSION['bi_view_mode'] ?? 'executive';

// ── Filter Helpers ────────────────────────────────────────────────────────────
function bi_get_seasons($conn) {
    $rows = [];
    $r = $conn->query("SELECT id, name FROM seasons ORDER BY id DESC");
    while ($r && $row = $r->fetch_assoc()) $rows[] = $row;
    return $rows;
}

function bi_get_field_officers($conn) {
    $rows = [];
    $r = $conn->query("SELECT id, name FROM field_officers ORDER BY name ASC");
    while ($r && $row = $r->fetch_assoc()) $rows[] = $row;
    return $rows;
}

function bi_get_clusters($conn) {
    $rows = [];
    $r = $conn->query("SELECT DISTINCT cluster FROM growers WHERE cluster IS NOT NULL ORDER BY cluster ASC");
    while ($r && $row = $r->fetch_assoc()) $rows[] = $row['cluster'];
    return $rows;
}

// ── Safe Filter Input ─────────────────────────────────────────────────────────
function bi_filter_int($key, $default = null) {
    $v = $_GET[$key] ?? $_POST[$key] ?? null;
    return ($v !== null && $v !== '') ? (int)$v : $default;
}
function bi_filter_str($key, $default = '') {
    $v = $_GET[$key] ?? $_POST[$key] ?? $default;
    return htmlspecialchars(strip_tags(trim($v)));
}

// ── Chart.js Color Palette ────────────────────────────────────────────────────
define('BI_COLORS', json_encode([
    'primary'   => '#10B981',   // emerald
    'secondary' => '#3B82F6',   // blue
    'warning'   => '#F59E0B',   // amber
    'danger'    => '#EF4444',   // red
    'purple'    => '#8B5CF6',
    'teal'      => '#14B8A6',
    'gray'      => '#6B7280',
    'light'     => 'rgba(16,185,129,0.15)',
]));

// ── KPI Status Badge ──────────────────────────────────────────────────────────
/**
 * Returns ['label'=>'On Track','class'=>'badge-success'] etc.
 * $value   = actual percentage or number
 * $target  = target value
 * $higher_is_better = true for recovery rate; false for default rate
 */
function bi_status($value, $target, $higher_is_better = true) {
    $ratio = $target > 0 ? $value / $target : 0;
    if ($higher_is_better) {
        if ($ratio >= 0.95) return ['label' => 'On Track',  'class' => 'bi-badge-success'];
        if ($ratio >= 0.75) return ['label' => 'At Risk',   'class' => 'bi-badge-warning'];
        return                     ['label' => 'Critical',  'class' => 'bi-badge-danger'];
    } else {
        if ($ratio <= 1.05) return ['label' => 'On Track',  'class' => 'bi-badge-success'];
        if ($ratio <= 1.25) return ['label' => 'At Risk',   'class' => 'bi-badge-warning'];
        return                     ['label' => 'Critical',  'class' => 'bi-badge-danger'];
    }
}

// ── Shared HTML Head (call inside <head>) ─────────────────────────────────────
function bi_html_head($title = 'GMS Business Intelligence') {
    echo <<<HTML
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$title} | GMS BI</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
/* ── BI Design System ─────────────────────────────────────── */
:root {
    --bi-bg:          #0B0F1A;
    --bi-surface:     #111827;
    --bi-surface2:    #1A2235;
    --bi-border:      rgba(255,255,255,0.07);
    --bi-primary:     #10B981;
    --bi-primary-dim: rgba(16,185,129,0.12);
    --bi-blue:        #3B82F6;
    --bi-amber:       #F59E0B;
    --bi-red:         #EF4444;
    --bi-purple:      #8B5CF6;
    --bi-text:        #F1F5F9;
    --bi-text-muted:  #64748B;
    --bi-text-dim:    #94A3B8;
    --bi-radius:      12px;
    --bi-radius-sm:   8px;
    --bi-font:        'DM Sans', sans-serif;
    --bi-mono:        'DM Mono', monospace;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: var(--bi-font);
    background: var(--bi-bg);
    color: var(--bi-text);
    font-size: 14px;
    line-height: 1.6;
    min-height: 100vh;
}

/* ── Layout ── */
.bi-wrap          { max-width: 1400px; margin: 0 auto; padding: 0 24px 48px; }
.bi-grid-2        { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.bi-grid-3        { display: grid; grid-template-columns: repeat(3,1fr); gap: 20px; }
.bi-grid-4        { display: grid; grid-template-columns: repeat(4,1fr); gap: 20px; }
@media(max-width:1100px){ .bi-grid-4{ grid-template-columns:repeat(2,1fr); } }
@media(max-width:700px) { .bi-grid-4,.bi-grid-2,.bi-grid-3{ grid-template-columns:1fr; } }

/* ── Topbar ── */
.bi-topbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 24px;
    background: var(--bi-surface);
    border-bottom: 1px solid var(--bi-border);
    position: sticky; top: 0; z-index: 100;
    backdrop-filter: blur(12px);
}
.bi-topbar-logo { display:flex; align-items:center; gap:10px; text-decoration:none; }
.bi-topbar-logo .logo-mark {
    width:34px; height:34px; border-radius:8px;
    background: linear-gradient(135deg, var(--bi-primary), #059669);
    display:flex; align-items:center; justify-content:center;
    font-weight:700; font-size:15px; color:#fff;
}
.bi-topbar-logo .logo-text { font-weight:600; font-size:15px; color:var(--bi-text); }
.bi-topbar-logo .logo-sub  { font-size:11px; color:var(--bi-text-muted); }

.bi-topbar-nav { display:flex; align-items:center; gap:6px; }
.bi-topbar-nav a {
    padding:6px 14px; border-radius:6px; font-size:13px; font-weight:500;
    color:var(--bi-text-muted); text-decoration:none; transition:all .2s;
}
.bi-topbar-nav a:hover, .bi-topbar-nav a.active {
    background:var(--bi-primary-dim); color:var(--bi-primary);
}

/* ── View Toggle ── */
.bi-view-toggle {
    display:flex; background:var(--bi-surface2);
    border:1px solid var(--bi-border); border-radius:8px; overflow:hidden;
}
.bi-view-toggle a {
    padding:6px 16px; font-size:12px; font-weight:500;
    color:var(--bi-text-muted); text-decoration:none; transition:all .2s;
}
.bi-view-toggle a.active {
    background:var(--bi-primary); color:#fff;
}

/* ── Page Header ── */
.bi-page-header { padding: 32px 0 24px; }
.bi-page-header h1 { font-size:26px; font-weight:700; letter-spacing:-0.5px; }
.bi-page-header p  { color:var(--bi-text-muted); margin-top:4px; font-size:13px; }
.bi-season-badge {
    display:inline-flex; align-items:center; gap:6px;
    background:var(--bi-primary-dim); color:var(--bi-primary);
    border:1px solid rgba(16,185,129,0.25);
    padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600;
    margin-top:8px;
}

/* ── Filter Bar ── */
.bi-filter-bar {
    display:flex; align-items:center; gap:12px; flex-wrap:wrap;
    background:var(--bi-surface); border:1px solid var(--bi-border);
    border-radius:var(--bi-radius); padding:14px 18px; margin-bottom:24px;
}
.bi-filter-bar label { font-size:11px; color:var(--bi-text-muted); font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
.bi-select {
    background:var(--bi-surface2); border:1px solid var(--bi-border);
    color:var(--bi-text); border-radius:var(--bi-radius-sm);
    padding:7px 12px; font-size:13px; font-family:var(--bi-font);
    cursor:pointer; outline:none; transition:border-color .2s;
}
.bi-select:focus { border-color:var(--bi-primary); }
.bi-btn {
    padding:7px 18px; border-radius:var(--bi-radius-sm);
    border:none; cursor:pointer; font-family:var(--bi-font);
    font-size:13px; font-weight:600; transition:all .2s;
}
.bi-btn-primary { background:var(--bi-primary); color:#fff; }
.bi-btn-primary:hover { background:#059669; }
.bi-btn-ghost {
    background:transparent; color:var(--bi-text-muted);
    border:1px solid var(--bi-border);
}
.bi-btn-ghost:hover { border-color:var(--bi-primary); color:var(--bi-primary); }

/* ── Card ── */
.bi-card {
    background:var(--bi-surface); border:1px solid var(--bi-border);
    border-radius:var(--bi-radius); padding:20px;
    transition: border-color .2s, transform .2s;
}
.bi-card:hover { border-color:rgba(16,185,129,0.3); }
.bi-card-title {
    font-size:11px; font-weight:600; color:var(--bi-text-muted);
    text-transform:uppercase; letter-spacing:.7px; margin-bottom:14px;
    display:flex; align-items:center; gap:8px;
}
.bi-card-title .dot {
    width:6px; height:6px; border-radius:50%;
    background:var(--bi-primary);
}

/* ── KPI Card ── */
.bi-kpi {
    background:var(--bi-surface); border:1px solid var(--bi-border);
    border-radius:var(--bi-radius); padding:20px 22px;
    position:relative; overflow:hidden; transition:all .25s;
}
.bi-kpi::before {
    content:''; position:absolute; top:0; left:0; right:0; height:2px;
    background:linear-gradient(90deg,var(--bi-primary),transparent);
}
.bi-kpi:hover { transform:translateY(-2px); border-color:rgba(16,185,129,0.35); }
.bi-kpi-label  { font-size:11px; font-weight:600; color:var(--bi-text-muted); text-transform:uppercase; letter-spacing:.7px; }
.bi-kpi-value  { font-size:32px; font-weight:700; letter-spacing:-1px; margin:6px 0 4px; line-height:1; }
.bi-kpi-sub    { font-size:12px; color:var(--bi-text-muted); }
.bi-kpi-trend  { font-size:12px; font-weight:600; margin-top:8px; }
.bi-kpi-trend.up   { color:var(--bi-primary); }
.bi-kpi-trend.down { color:var(--bi-red); }
.bi-kpi-icon {
    position:absolute; right:18px; top:18px;
    font-size:28px; opacity:0.12;
}

/* ── Badges ── */
.bi-badge {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600;
}
.bi-badge-success { background:rgba(16,185,129,0.15); color:#10B981; border:1px solid rgba(16,185,129,0.3); }
.bi-badge-warning { background:rgba(245,158,11,0.15); color:#F59E0B; border:1px solid rgba(245,158,11,0.3); }
.bi-badge-danger  { background:rgba(239,68,68,0.15);  color:#EF4444; border:1px solid rgba(239,68,68,0.3);  }
.bi-badge-info    { background:rgba(59,130,246,0.15); color:#3B82F6; border:1px solid rgba(59,130,246,0.3); }

/* ── Chart Container ── */
.bi-chart-wrap { position:relative; width:100%; }

/* ── Table ── */
.bi-table-wrap { overflow-x:auto; border-radius:var(--bi-radius); }
.bi-table {
    width:100%; border-collapse:collapse; font-size:13px;
}
.bi-table th {
    background:var(--bi-surface2); color:var(--bi-text-muted);
    font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.5px;
    padding:10px 14px; text-align:left; border-bottom:1px solid var(--bi-border);
    white-space:nowrap;
}
.bi-table td {
    padding:11px 14px; border-bottom:1px solid var(--bi-border);
    color:var(--bi-text-dim); vertical-align:middle;
}
.bi-table tr:last-child td { border-bottom:none; }
.bi-table tbody tr:hover td { background:var(--bi-surface2); color:var(--bi-text); }
.bi-table .rank { font-family:var(--bi-mono); font-size:12px; color:var(--bi-text-muted); }

/* ── Section title ── */
.bi-section-title {
    font-size:13px; font-weight:600; color:var(--bi-text-dim);
    margin-bottom:14px; display:flex; align-items:center; gap:10px;
}
.bi-section-title::after {
    content:''; flex:1; height:1px; background:var(--bi-border);
}

/* ── Loading/Empty ── */
.bi-empty {
    text-align:center; padding:48px 24px;
    color:var(--bi-text-muted); font-size:13px;
}
.bi-empty .icon { font-size:32px; margin-bottom:10px; opacity:.4; }

/* ── Scrollbar ── */
::-webkit-scrollbar { width:6px; height:6px; }
::-webkit-scrollbar-track { background:var(--bi-bg); }
::-webkit-scrollbar-thumb { background:var(--bi-surface2); border-radius:3px; }

/* ── Animations ── */
@keyframes fadeUp {
    from { opacity:0; transform:translateY(16px); }
    to   { opacity:1; transform:translateY(0); }
}
.bi-animate { animation: fadeUp .4s ease forwards; }
.bi-animate-delay-1 { animation-delay:.08s; opacity:0; }
.bi-animate-delay-2 { animation-delay:.16s; opacity:0; }
.bi-animate-delay-3 { animation-delay:.24s; opacity:0; }
.bi-animate-delay-4 { animation-delay:.32s; opacity:0; }
</style>
HTML;
}

// ── Shared Topbar HTML ────────────────────────────────────────────────────────
function bi_topbar($activePage = 'overview') {
    global $viewMode;
    $pages = [
        'overview'         => ['label' => 'Overview',        'href' => 'bi_overview.php'],
        'grower'           => ['label' => 'Grower Perf.',    'href' => 'bi_grower_performance.php'],
        'officer'          => ['label' => 'Field Officers',  'href' => 'bi_field_officer.php'],
        'loans'            => ['label' => 'Loans',           'href' => 'bi_loans.php'],
        'crop'             => ['label' => 'Crop & Harvest',  'href' => 'bi_crop_harvest.php'],
    ];
    $execActive = $viewMode === 'executive' ? 'active' : '';
    $opActive   = $viewMode === 'operational' ? 'active' : '';
    echo '<div class="bi-topbar">';
    echo '  <a class="bi-topbar-logo" href="bi_overview.php">';
    echo '    <div class="logo-mark">BI</div>';
    echo '    <div><div class="logo-text">GMS Intelligence</div><div class="logo-sub">Core Africa Group</div></div>';
    echo '  </a>';
    echo '  <nav class="bi-topbar-nav">';
    foreach ($pages as $key => $p) {
        $cls = $activePage === $key ? 'active' : '';
        echo "    <a href='{$p['href']}' class='{$cls}'>{$p['label']}</a>";
    }
    echo '  </nav>';
    echo '  <div class="bi-view-toggle">';
    echo "    <a href='?view_mode=executive' class='{$execActive}'>Executive</a>";
    echo "    <a href='?view_mode=operational' class='{$opActive}'>Operational</a>";
    echo '  </div>';
    echo '</div>';
}

// ── Filter Bar Builder ────────────────────────────────────────────────────────
/**
 * $filters: array of which filters to show
 * e.g. ['season','officer','cluster']
 */
function bi_filter_bar($conn, $filters = ['season'], $actionUrl = '') {
    global $activeSeasonId;
    $seasons  = in_array('season',  $filters) ? bi_get_seasons($conn)        : [];
    $officers = in_array('officer', $filters) ? bi_get_field_officers($conn)  : [];
    $clusters = in_array('cluster', $filters) ? bi_get_clusters($conn)        : [];

    $selSeason  = bi_filter_int('season_id', $activeSeasonId);
    $selOfficer = bi_filter_int('officer_id');
    $selCluster = bi_filter_str('cluster');

    echo "<form method='GET' action='{$actionUrl}' class='bi-filter-bar'>";
    if (in_array('season', $filters)) {
        echo "<div><label>Season</label><br>";
        echo "<select name='season_id' class='bi-select'>";
        foreach ($seasons as $s) {
            $sel = $s['id'] == $selSeason ? 'selected' : '';
            echo "<option value='{$s['id']}' {$sel}>{$s['name']}</option>";
        }
        echo "</select></div>";
    }
    if (in_array('officer', $filters)) {
        echo "<div><label>Field Officer</label><br>";
        echo "<select name='officer_id' class='bi-select'>";
        echo "<option value=''>All Officers</option>";
        foreach ($officers as $o) {
            $sel = $o['id'] == $selOfficer ? 'selected' : '';
            echo "<option value='{$o['id']}' {$sel}>{$o['name']}</option>";
        }
        echo "</select></div>";
    }
    if (in_array('cluster', $filters)) {
        echo "<div><label>Cluster</label><br>";
        echo "<select name='cluster' class='bi-select'>";
        echo "<option value=''>All Clusters</option>";
        foreach ($clusters as $c) {
            $sel = $c === $selCluster ? 'selected' : '';
            echo "<option value='{$c}' {$sel}>{$c}</option>";
        }
        echo "</select></div>";
    }
    echo "<button type='submit' class='bi-btn bi-btn-primary' style='margin-top:18px'>Apply</button>";
    echo "<a href='?' class='bi-btn bi-btn-ghost' style='margin-top:18px'>Reset</a>";
    echo "</form>";
}

// ── Chart.js Defaults (call once per page in <script>) ────────────────────────
function bi_chart_defaults() {
    echo <<<JS
<script>
Chart.defaults.color          = '#64748B';
Chart.defaults.borderColor    = 'rgba(255,255,255,0.06)';
Chart.defaults.font.family    = "'DM Sans', sans-serif";
Chart.defaults.font.size      = 12;
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.pointStyleWidth = 8;
Chart.defaults.plugins.tooltip.backgroundColor = '#1A2235';
Chart.defaults.plugins.tooltip.borderColor     = 'rgba(255,255,255,0.1)';
Chart.defaults.plugins.tooltip.borderWidth     = 1;
Chart.defaults.plugins.tooltip.padding         = 10;
Chart.defaults.plugins.tooltip.titleColor      = '#F1F5F9';
Chart.defaults.plugins.tooltip.bodyColor       = '#94A3B8';
</script>
JS;
}
