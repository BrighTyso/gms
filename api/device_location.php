<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 *  GMS — Single Location Ping Receiver
 *  File: /gms/api/device_location.php
 *
 *  Receives pings from:
 *   • Android app directly (internet, real-time)
 *   • Central sms_receiver.php (forwarded SMS pings)
 *
 *  AUTO ACCOUNT CREATION:
 *  If officer_id is sent but does not exist in field_officers,
 *  a placeholder account is created automatically so the ping
 *  is never lost. The admin can fill in the name/phone later.
 *  If no officer_id is sent, the ping is saved with officer_id = NULL.
 *
 *  POST body (JSON):
 *  {
 *    "device_id":    "a1b2c3d4",
 *    "officer_id":   42,
 *    "officer_name": "John Moyo",    ← optional, used when creating account
 *    "officer_phone":"0771234567",   ← optional, used when creating account
 *    "company_code": "ACME",
 *    "latitude":     -17.8292,
 *    "longitude":    31.0522,
 *    "accuracy":     12,
 *    "altitude":     1490,
 *    "speed":        0,
 *    "battery":      78,
 *    "timestamp":    1709123456000,
 *    "source":       "realtime"      ← realtime | sms | offline_queue
 *  }
 * ╚══════════════════════════════════════════════════════════════════╝
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

foreach (['device_id', 'latitude', 'longitude'] as $f) {
    if (!isset($data[$f]) || $data[$f] === '') {
        http_response_code(400);
        echo json_encode(['error' => "Missing: $f"]);
        exit;
    }
}

$lat = (float)$data['latitude'];
$lng = (float)$data['longitude'];

if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid coordinates']);
    exit;
}

$source = in_array($data['source'] ?? '', ['realtime', 'offline_queue', 'sms'])
        ? $data['source'] : 'realtime';

$deviceId     = substr(trim($data['device_id']), 0, 64);
$officerId    = isset($data['officer_id']) && is_numeric($data['officer_id']) ? (int)$data['officer_id'] : null;
$officerName  = isset($data['officer_name'])  ? trim($data['officer_name'])  : null;
$officerPhone = isset($data['officer_phone']) ? trim($data['officer_phone']) : null;
$companyCode  = isset($data['company_code'])  ? strtoupper(trim($data['company_code'])) : null;
$accuracy     = isset($data['accuracy'])  ? (float)$data['accuracy']       : null;
$altitude     = isset($data['altitude'])  ? (float)$data['altitude']       : null;
$speed        = isset($data['speed'])     ? (float)$data['speed']          : null;
$battery      = isset($data['battery'])   ? (int)$data['battery']          : null;
$ts           = isset($data['timestamp']) ? (int)($data['timestamp']/1000) : time();
$userid=$data['userid'];

// ── DB connection ─────────────────────────────────────────────────────────────
require "conn.php";
require "validate.php";

// ── Auto field officer account creation ──────────────────────────────────────
$accountCreated = false;

if ($officerId !== null) {

    // Check if this officer exists
    $chk = $conn->prepare("SELECT id FROM field_officers WHERE id = ? LIMIT 1");
    $chk->bind_param('i', $officerId);
    $chk->execute();
    $chk->store_result();
    $exists = $chk->num_rows > 0;
    $chk->close();

    if (!$exists) {
        // Officer not found — create a placeholder account
        // Uses name/phone from the ping if provided, otherwise uses device_id as a fallback name
        $placeholderName  = !empty($officerName)  ? $officerName  : 'Officer (device: ' . $deviceId . ')';
        $placeholderPhone = !empty($officerPhone) ? $officerPhone : null;

        $ins = $conn->prepare("
            INSERT INTO field_officers (id, name, phone, company_code, status)
            VALUES (?, ?, ?, ?, 'active')
        ");
        $ins->bind_param('isss', $officerId, $placeholderName, $placeholderPhone, $companyCode);

        if ($ins->execute()) {
            $accountCreated = true;
            error_log("GMS device_location | Auto-created field officer account: id=$officerId name='$placeholderName' device=$deviceId");
        } else {
            // INSERT failed — could be a race condition (another ping created it first)
            // Safe to continue, just log it
            error_log("GMS device_location | Auto-create officer failed (may already exist): id=$officerId err=" . $ins->error);
        }
        $ins->close();
    }
}

// ── Save location ping ────────────────────────────────────────────────────────
$stmt = $conn->prepare("
    INSERT INTO device_locations
        (device_id, officer_id, latitude, longitude, accuracy,
         altitude, speed, battery_level, device_timestamp, source)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, FROM_UNIXTIME(?), ?)
");

if (!$stmt) {
    error_log('GMS device_location prepare error: ' . $conn->error);
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    $conn->close();
    exit;
}

$stmt->bind_param('sidddddiis',
    $deviceId, $officerId, $lat, $lng,
    $accuracy, $altitude, $speed, $battery, $ts, $source
);

if (!$stmt->execute()) {
    error_log('GMS device_location execute error: ' . $stmt->error);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save location']);
    $stmt->close();
    $conn->close();
    exit;
}

$stmt->close();

// ── Prune records older than 30 days ─────────────────────────────────────────
$conn->query("DELETE FROM device_locations WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");

$conn->close();

echo json_encode([
    'status'          => 'ok',
    'source'          => $source,
    'officer_id'      => $officerId,
    'account_created' => $accountCreated,
]);