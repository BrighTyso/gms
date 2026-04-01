<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 *  GMS — Geofence & Inactivity Alert Engine
 *  File: /gms/api/geofence_check.php
 *
 *  Run via cron every 30 minutes during working hours:
 *  (add to crontab)
 *  */30 6-19 * * 1-6 php /path/to/gms/api/geofence_check.php
 *
 *  What it does:
 *  1. Geofence — officer within 500m of a visit-due grower → logs potential visit
 *  2. Inactivity — officer has no ping for 3+ hours during working hours → email alert
 *  3. Visit due — growers not visited in 14+ days → daily summary email to supervisors
 * ╚══════════════════════════════════════════════════════════════════╝
 */

require_once __DIR__ . '/../conn.php';

// ── CONFIG ────────────────────────────────────────────────────────────────────
define('GEOFENCE_RADIUS_KM',   0.5);  // 500 metres
define('VISIT_DUE_DAYS',       14);   // flag as overdue after N days
define('INACTIVITY_HOURS',     3);    // alert if no ping for N hours during work
define('SUPERVISOR_EMAIL',     'supervisor@yourcompany.co.zw');
define('FROM_EMAIL',           'gms-alerts@yourcompany.co.zw');
define('FROM_NAME',            'GMS Alert System');
// ─────────────────────────────────────────────────────────────────────────────

$hour = (int)date('H');
$isWorkingHours = $hour >= 6 && $hour <= 19;

// ── Get current active season ─────────────────────────────────────────────────
$currentSeasonId = 0;
$sr = $conn->query("SELECT id FROM seasons ORDER BY id DESC LIMIT 1");
if ($sr && $row = $sr->fetch_assoc()) {
    $currentSeasonId = (int)$row['id'];
    $sr->free();
}

// ── 1. GEOFENCE CHECK ─────────────────────────────────────────────────────────
// Get latest position of every active officer (pinged in last 2h)
$officerPositions = [];
$r = $conn->query("
    SELECT dl.officer_id, dl.latitude, dl.longitude, dl.created_at,
           fo.name AS officer_name, fo.phone AS officer_phone
    FROM device_locations dl
    INNER JOIN (
        SELECT officer_id, MAX(id) AS max_id
        FROM device_locations
        WHERE created_at >= NOW() - INTERVAL 2 HOUR
        GROUP BY officer_id
    ) latest ON dl.id = latest.max_id
    LEFT JOIN field_officers fo ON fo.id = dl.officer_id
");
if ($r) {
    while ($row = $r->fetch_assoc()) $officerPositions[] = $row;
    $r->free();
}

// Get all growers with home location (all growers, not just visit-due)
$dueGrowers = [];
$r = $conn->query("
    SELECT g.id, g.grower_num, g.name, g.surname,
           ll.latitude AS lat, ll.longitude AS lng,
           DATEDIFF(NOW(), v.last_visit) AS days_since,
           v.last_visit
    FROM growers g
    JOIN lat_long ll ON ll.growerid = g.id
    LEFT JOIN (
        SELECT growerid, MAX(created_at) AS last_visit
        FROM visits GROUP BY growerid
    ) v ON v.growerid = g.id
    WHERE ll.latitude  IS NOT NULL AND ll.longitude IS NOT NULL
      AND ll.latitude  != 0        AND ll.longitude != 0
");
if ($r) {
    while ($row = $r->fetch_assoc()) $dueGrowers[] = $row;
    $r->free();
}

// Haversine distance
function haversine($lat1, $lng1, $lat2, $lng2) {
    $R = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat/2)*sin($dLat/2) + cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLng/2)*sin($dLng/2);
    return $R * 2 * atan2(sqrt($a), sqrt(1-$a));
}

// Match officers to nearby growers and log to grower_geofence_entry_point
$geofenceEvents = [];
$now            = date('Y-m-d H:i:s');
$nowTs          = (string)time();

foreach ($officerPositions as $officer) {
    $officerId = (int)$officer['officer_id'];

    foreach ($dueGrowers as $grower) {
        $growerId = (int)$grower['id'];
        $dist     = haversine(
            (float)$officer['latitude'], (float)$officer['longitude'],
            (float)$grower['lat'],       (float)$grower['lng']
        );

        if ($dist > GEOFENCE_RADIUS_KM) continue;

        // Dedup — only log once per officer+grower per day
        $check = $conn->query("
            SELECT id FROM grower_geofence_entry_point
            WHERE userid   = $officerId
              AND growerid = $growerId
              AND DATE(created_at) = CURDATE()
            LIMIT 1
        ");
        if ($check && $check->num_rows > 0) {
            $check->free();
            continue; // already logged today
        }
        if ($check) $check->free();

        $distM      = round($dist * 1000);
        $officerLat = $officer['latitude'];
        $officerLng = $officer['longitude'];
        $desc       = "Auto: officer within {$distM}m of grower #{$grower['grower_num']}";

        $stmt = $conn->prepare("
            INSERT INTO grower_geofence_entry_point
                (userid, seasonid, growerid, entry_time, latitude, longitude,
                 sync, created_at, datetimes, description)
            VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param('iiissssss',
                $officerId, $currentSeasonId, $growerId,
                $now, $officerLat, $officerLng,
                $now, $nowTs, $desc
            );
            $stmt->execute();
            $stmt->close();
        }

        $geofenceEvents[] = [
            'officer'    => $officer['officer_name'],
            'grower'     => $grower['name'] . ' ' . $grower['surname'],
            'grower_num' => $grower['grower_num'],
            'dist_m'     => $distM,
            'days_since' => $grower['days_since'],
        ];
    }
}

// ── 2. INACTIVITY CHECK ───────────────────────────────────────────────────────
$inactiveOfficers = [];
if ($isWorkingHours) {
    $r = $conn->query("
        SELECT fo.id AS userid, fo.name, fo.phone,
               MAX(dl.created_at) AS last_ping,
               TIMESTAMPDIFF(HOUR, MAX(dl.created_at), NOW()) AS hours_silent
        FROM field_officers fo
        LEFT JOIN device_locations dl ON dl.officer_id = fo.id
        GROUP BY fo.id, fo.name, fo.phone
        HAVING last_ping IS NULL
            OR TIMESTAMPDIFF(HOUR, MAX(dl.created_at), NOW()) >= " . INACTIVITY_HOURS . "
    ");
    if ($r) {
        while ($row = $r->fetch_assoc()) $inactiveOfficers[] = $row;
        $r->free();
    }
}

// ── 3. VISIT DUE SUMMARY (once per day at 7AM) ───────────────────────────────
$visitDueSummary = [];
if ($hour === 7) {
    $r = $conn->query("
        SELECT g.grower_num, g.name, g.surname,
               DATEDIFF(NOW(), v.last_visit) AS days_since,
               v.last_visit
        FROM growers g
        LEFT JOIN (
            SELECT growerid, MAX(created_at) AS last_visit
            FROM visits GROUP BY growerid
        ) v ON v.growerid = g.id
        WHERE v.last_visit IS NULL OR DATEDIFF(NOW(), v.last_visit) >= " . VISIT_DUE_DAYS . "
        ORDER BY days_since DESC
        LIMIT 50
    ");
    if ($r) {
        while ($row = $r->fetch_assoc()) $visitDueSummary[] = $row;
        $r->free();
    }
}

$conn->close();

// ── Send email alerts ─────────────────────────────────────────────────────────
$shouldEmail = !empty($inactiveOfficers) || !empty($geofenceEvents) || !empty($visitDueSummary);

if ($shouldEmail) {
    $subject = buildSubject($inactiveOfficers, $geofenceEvents, $visitDueSummary);
    $body    = buildEmailBody($inactiveOfficers, $geofenceEvents, $visitDueSummary);
    sendEmail(SUPERVISOR_EMAIL, $subject, $body);
}

// Log run
$eventCount = count($geofenceEvents);
$inactCount = count($inactiveOfficers);
$dueCount   = count($visitDueSummary);
error_log("GMS GeofenceCheck | geofence=$eventCount inactive=$inactCount due=$dueCount");

// ── Email helpers ─────────────────────────────────────────────────────────────
function buildSubject($inactive, $geofence, $due) {
    $parts = [];
    if (!empty($inactive))  $parts[] = count($inactive)  . ' inactive officer' . (count($inactive)>1?'s':'');
    if (!empty($geofence))  $parts[] = count($geofence)  . ' geofence event'   . (count($geofence)>1?'s':'');
    if (!empty($due))       $parts[] = count($due)        . ' visits due';
    return 'GMS Alert: ' . implode(', ', $parts);
}

function buildEmailBody($inactive, $geofence, $due) {
    $html = '<!DOCTYPE html><html><body style="font-family:monospace;background:#0a0f0a;color:#c8e6c9;padding:20px;">';
    $html .= '<h2 style="color:#3ddc68;border-bottom:1px solid #1f2e1f;padding-bottom:10px">GMS Alert — ' . date('d M Y H:i') . '</h2>';

    // Inactive officers
    if (!empty($inactive)) {
        $html .= '<h3 style="color:#e84040;margin-top:20px">⚠️ Inactive Officers (' . count($inactive) . ')</h3>';
        $html .= '<table style="width:100%;border-collapse:collapse;margin-top:8px">';
        $html .= '<tr style="color:#4a6b4a;font-size:11px"><th align="left">Officer</th><th align="left">Phone</th><th align="left">Last Ping</th><th align="left">Silent For</th></tr>';
        foreach ($inactive as $o) {
            $silent = $o['hours_silent'] !== null ? $o['hours_silent'].'h' : 'Never pinged';
            $html .= "<tr style='border-top:1px solid #1f2e1f'>
                <td style='padding:6px 0'>{$o['name']}</td>
                <td style='color:#4a9eff'>{$o['phone']}</td>
                <td style='color:#4a6b4a'>" . ($o['last_ping'] ?? '—') . "</td>
                <td style='color:#e84040;font-weight:bold'>$silent</td>
            </tr>";
        }
        $html .= '</table>';
    }

    // Geofence events
    if (!empty($geofence)) {
        $html .= '<h3 style="color:#f5a623;margin-top:24px">📍 Geofence Events — Officer Near Unvisited Grower (' . count($geofence) . ')</h3>';
        $html .= '<table style="width:100%;border-collapse:collapse;margin-top:8px">';
        $html .= '<tr style="color:#4a6b4a;font-size:11px"><th align="left">Officer</th><th align="left">Grower</th><th align="left">Distance</th><th align="left">Days Since Visit</th></tr>';
        foreach ($geofence as $e) {
            $days = $e['days_since'] !== null ? $e['days_since'].'d ago' : 'Never';
            $html .= "<tr style='border-top:1px solid #1f2e1f'>
                <td style='padding:6px 0'>{$e['officer']}</td>
                <td>{$e['grower']} <span style='color:#4a6b4a'>#{$e['grower_num']}</span></td>
                <td style='color:#f5a623'>{$e['dist_m']}m</td>
                <td style='color:#e84040'>$days</td>
            </tr>";
        }
        $html .= '</table>';
    }

    // Visit due summary
    if (!empty($due)) {
        $html .= '<h3 style="color:#f5a623;margin-top:24px">📋 Growers Overdue for Visit (' . count($due) . ')</h3>';
        $html .= '<table style="width:100%;border-collapse:collapse;margin-top:8px">';
        $html .= '<tr style="color:#4a6b4a;font-size:11px"><th align="left">Grower</th><th align="left">Last Visit</th><th align="left">Days Overdue</th></tr>';
        foreach ($due as $g) {
            $days = $g['days_since'] !== null ? $g['days_since'] : '∞';
            $color = $g['days_since'] !== null && $g['days_since'] > 30 ? '#e84040' : '#f5a623';
            $html .= "<tr style='border-top:1px solid #1f2e1f'>
                <td style='padding:6px 0'>{$g['name']} {$g['surname']} <span style='color:#4a6b4a'>#{$g['grower_num']}</span></td>
                <td style='color:#4a6b4a'>" . ($g['last_visit'] ? date('d M Y', strtotime($g['last_visit'])) : 'Never') . "</td>
                <td style='color:$color;font-weight:bold'>{$days}d</td>
            </tr>";
        }
        $html .= '</table>';
    }

    $html .= '<p style="margin-top:24px;color:#4a6b4a;font-size:10px">GMS · ' . date('Y') . ' · Auto-generated alert</p>';
    $html .= '</body></html>';
    return $html;
}

function sendEmail($to, $subject, $htmlBody) {
    $boundary = md5(uniqid());
    $headers  = implode("\r\n", [
        'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>',
        'Reply-To: ' . FROM_EMAIL,
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        'X-Mailer: GMS-AlertEngine/1.0',
    ]);

    $body = "--$boundary\r\n"
          . "Content-Type: text/html; charset=UTF-8\r\n\r\n"
          . $htmlBody . "\r\n"
          . "--$boundary--";

    $sent = mail($to, $subject, $body, $headers);
    error_log("GMS GeofenceCheck | email to=$to subject='$subject' sent=" . ($sent?'yes':'no'));
    return $sent;
}