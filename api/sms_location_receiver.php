<?php
error_log("GMS SMSReceiver | hit by: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));


/**
 * ╔══════════════════════════════════════════════════════════════════╗
 *  GMS — Client SMS Location Receiver
 *  File: /gms/api/sms_location_receiver.php
 *
 *  Lives on EACH CLIENT'S own server.
 *
 *  FLOW:
 *  Android (no internet)
 *       ↓ SMS → GMS|clientdomain.com|deviceId|officerId|lat|lng|accuracy|battery|ts
 *  Telerivet Gateway phone
 *       ↓ HTTP POST
 *  sms_receiver.php  (central hub server)
 *       ↓ reads domain → constructs URL → cURL forward
 *  THIS FILE  ← you are here
 *       ↓
 *  MySQL → visible in device_tracker.php
 *
 * ──────────────────────────────────────────────────────────────────
 *  SETUP:
 *  1. Drop this file in your /api/ folder
 *  2. Set ALLOWED_HUB_IP to your hub server's IP (recommended)
 *  3. No routing table or company code needed — domain handles routing
 * ╚══════════════════════════════════════════════════════════════════╝
 */

header('Content-Type: application/json');



// ── Temporary debug — remove after fix ───────────────────────────
ini_set('display_errors', 1);
error_reporting(E_ALL);
try {
    require "conn.php";
    date_default_timezone_set("Africa/Harare");
    $conn->query("SET time_zone = '+02:00'");
    require "validate.php";
    echo json_encode(['debug' => 'conn.php loaded OK']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['debug_error' => $e->getMessage(), 'file' => $e->getFile()]);
    exit;
}
//exit; // remove this line once conn.php is confirmed
// ── End debug ─────────────────────────────────────────────────────

// ═══════════════════════════════════════════════════════════════════
//  ★  CONFIG  ★
// ═══════════════════════════════════════════════════════════════════

/**
 * IP of your central hub server.
 * Only that IP can POST to this endpoint.
 * Set to '' to disable during initial testing.
 */
define('ALLOWED_HUB_IP', ''); // e.g. '196.12.34.56'

// ═══════════════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ── IP whitelist ──────────────────────────────────────────────────────────────
if (!empty(ALLOWED_HUB_IP)) {
    $callerIp = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR'] ?? '')[0]);

    if ($callerIp !== ALLOWED_HUB_IP) {
        error_log("GMS SMSReceiver | Blocked IP: $callerIp");
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
}

// ── Parse body ────────────────────────────────────────────────────────────────
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or empty JSON body']);
    exit;
}

foreach (['device_id', 'latitude', 'longitude'] as $field) {
    if (!isset($data[$field]) || $data[$field] === '') {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit;
    }
}

// ── Validate coordinates ──────────────────────────────────────────────────────
$lat      = (float)$data['latitude'];
$lng      = (float)$data['longitude'];
$deviceId = substr(trim($data['device_id']), 0, 64);

if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    http_response_code(400);
    echo json_encode(['error' => 'Coordinates out of valid range']);
    exit;
}

if (empty($deviceId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid device_id']);
    exit;
}

// ── Sanitize fields ───────────────────────────────────────────────────────────
$officerId = isset($data['officer_id']) && is_numeric($data['officer_id']) ? (int)$data['officer_id']        : null;
$accuracy  = isset($data['accuracy'])   && is_numeric($data['accuracy'])   ? (float)$data['accuracy']        : null;
$altitude  = isset($data['altitude'])   && is_numeric($data['altitude'])   ? (float)$data['altitude']        : null;
$speed     = isset($data['speed'])      && is_numeric($data['speed'])      ? (float)$data['speed']           : null;
$battery   = isset($data['battery'])    && is_numeric($data['battery'])    ? (int)$data['battery']           : null;
$ts        = isset($data['timestamp'])  && is_numeric($data['timestamp'])  ? (int)($data['timestamp'] / 1000): time();
$source    = in_array($data['source'] ?? '', ['realtime', 'offline_queue', 'sms']) ? $data['source'] : 'sms';
$smsFrom   = substr(trim($data['from'] ?? ''), 0, 20); // sender phone number from Telerivet

// ── DB connection ─────────────────────────────────────────────────────────────
// require "conn.php";
// require "validate.php";

// ── Insert ────────────────────────────────────────────────────────────────────
$stmt = $conn->prepare("INSERT INTO device_locations
        (device_id, officer_id, latitude, longitude, accuracy,
         battery_level, device_timestamp, source)
    VALUES (?, ?, ?, ?, ?, ?, FROM_UNIXTIME(?), ?)
");

if (!$stmt) {
    error_log('GMS sms_location_receiver prepare error: ' . $conn->error);
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    $conn->close();
    exit;
}

$stmt->bind_param('sidddiis',
    $deviceId, $officerId, $lat, $lng,
    $accuracy, $battery, $ts, $source
);

if (!$stmt->execute()) {
    error_log('GMS sms_location_receiver execute error: ' . $stmt->error);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save location']);
    $stmt->close();
    $conn->close();
    exit;
}else{

}

$stmt->close();

$battStr = $battery !== null ? "{$battery}%" : 'unknown';
error_log("GMS SMS ping saved | device=$deviceId officer=$officerId lat=$lat lng=$lng battery=$battStr from=$smsFrom");

// ── Prune old records ─────────────────────────────────────────────────────────
$conn->query("DELETE FROM device_locations WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");

$conn->close();

echo json_encode([
    'status'     => 'ok',
    'device_id'  => $deviceId,
    'officer_id' => $officerId,
    'lat'        => $lat,
    'lng'        => $lng,
    'battery'    => $battery,
    'source'     => $source,
    'saved_at'   => date('Y-m-d H:i:s'),
]);