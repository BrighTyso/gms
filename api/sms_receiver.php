<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 *  GMS — Central SMS Receiver  (Telerivet Webhook)
 *  File: /gms/api/sms_receiver.php
 *
 *  Lives on YOUR central hub server.
 *  The Android app sends the target URL inside the SMS itself.
 *  No routing table needed — zero config when adding new clients.
 *
 *  NOTE: This file does NOT connect to a database.
 *  Only the client's sms_location_receiver.php saves to DB.
 *
 * ──────────────────────────────────────────────────────────────────
 *  SMS FORMAT (from Android SmsWorker.java):
 *  GMS|clientdomain.com|deviceId|officerId|lat|lng|accuracy|battery|ts
 *
 *  Field positions:
 *  [0] GMS          — prefix, identifies this as a GMS ping
 *  [1] domain       — client domain only e.g. core-africa.co.zw
 *  [2] deviceId     — Android device ID
 *  [3] officerId    — logged-in officer's user ID
 *  [4] latitude     — GPS latitude
 *  [5] longitude    — GPS longitude
 *  [6] accuracy     — GPS accuracy in metres
 *  [7] battery      — battery percentage
 *  [8] timestamp    — Unix timestamp (ms)
 *
 *  Example SMS:
 *  GMS|core-africa.co.zw|abc123|42|-17.8292|31.0522|10|85|1710000000000
 *
 *  Server constructs full URL as:
 *  https://{domain}/gms/api/sms_location_receiver.php
 *
 * ──────────────────────────────────────────────────────────────────
 *  TELERIVET SETUP:
 *  1. Install Telerivet Gateway app on your Android phone
 *  2. Log into telerivet.com → your project
 *  3. Go to Services → Add Service → Webhook API
 *  4. Webhook URL:
 *       https://yourhubdomain.com/gms/api/sms_receiver.php
 *  5. Set a Webhook Secret → copy it to WEBHOOK_SECRET below
 *
 * ──────────────────────────────────────────────────────────────────
 *  ANDROID SIDE — SmsWorker.java:
 *  // Extract domain from connectionUrl saved at login
 *  String domain = Uri.parse(prefs.getString("connectionUrl","")).getHost();
 *  String message = "GMS|" + domain + "|" + deviceId + "|"
 *                 + officerId + "|" + lat + "|" + lng + "|"
 *                 + accuracy + "|" + battery + "|" + timestamp;
 *
 * ──────────────────────────────────────────────────────────────────
 *  ADDING A NEW CLIENT:
 *  Nothing to change here.
 *  Just deploy sms_location_receiver.php on the client server.
 *  The Android app already knows its own connectionUrl from login.
 * ╚══════════════════════════════════════════════════════════════════╝
 */

header('Content-Type: application/json');

// ── Optional: Telerivet webhook secret (leave empty to skip check) ────────────
define('WEBHOOK_SECRET', 'R67274XDN6WKH26EAU4RXRUTKDEMRG4L'); // e.g. 'mysecret123'

// ── Parse Telerivet webhook POST ──────────────────────────────────────────────
$raw     = json_decode(file_get_contents('php://input'), true) ?? [];
$smsText = '';
$smsFrom = '';

if (!empty($_POST['content'])) {
    // Telerivet webhook — form-encoded POST (confirmed format)
    $smsText = trim($_POST['content']);
    $smsFrom = trim($_POST['from_number_e164'] ?? $_POST['from_number'] ?? '');
} elseif (!empty($raw['content'])) {
    // Telerivet JSON body fallback
    $smsText = trim($raw['content']);
    $smsFrom = trim($raw['from_number_e164'] ?? $raw['from_number'] ?? '');
} elseif (!empty($raw['results'][0]['text'])) {
    // Infobip legacy format (backward compat)
    $smsText = trim($raw['results'][0]['text']);
    $smsFrom = trim($raw['results'][0]['from'] ?? '');
}

// ── Telerivet secret check ────────────────────────────────────────────────────
if (!empty(WEBHOOK_SECRET)) {
    $receivedSecret = $raw['secret'] ?? $_POST['secret'] ?? '';
    if ($receivedSecret !== WEBHOOK_SECRET) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid webhook secret']);
        exit;
    }
}

error_log("GMS SMSHub | from=$smsFrom | text=$smsText");

if (empty($smsText)) {
    echo json_encode(['status' => 'ignored', 'reason' => 'empty text']);
    exit;
}

if (strpos($smsText, 'GMS|') !== 0) {
    echo json_encode(['status' => 'ignored', 'reason' => 'not a GMS ping']);
    exit;
}

// ── Parse SMS fields ──────────────────────────────────────────────────────────
// Format: GMS|clientdomain.com|deviceId|officerId|lat|lng|accuracy|battery|ts
$parts = explode('|', $smsText);

if (count($parts) < 9) {
    error_log("GMS SMSHub | malformed SMS from $smsFrom — got " . count($parts) . " fields: $smsText");
    http_response_code(400);
    echo json_encode(['error' => 'Malformed GMS ping — expected 9 fields']);
    exit;
}

[, $domain, $deviceId, $officerId, $lat, $lng, $accuracy, $battery, $timestamp] = $parts;

$domain    = strtolower(trim($domain));
$deviceId  = substr(trim($deviceId), 0, 64);
$officerId = trim($officerId);
$lat       = (float)$lat;
$lng       = (float)$lng;
$accuracy  = (int)$accuracy;
$battery   = (int)$battery;
$timestamp = (int)$timestamp;

// ── Validate coordinates ──────────────────────────────────────────────────────
if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid coordinates']);
    exit;
}

// ── Validate domain — strip any accidental scheme or path the app may send ───
// Handles: "core-africa.co.zw", "http://core-africa.co.zw", "core-africa.co.zw/anything"
$domain = preg_replace('#^https?://#i', '', $domain); // strip scheme if present
$domain = explode('/', $domain)[0];                   // strip any path
$domain = explode('?', $domain)[0];                   // strip any query string

if (empty($domain) || !preg_match('/^[a-z0-9.\-]+\.[a-z]{2,}$/', $domain)) {
    error_log("GMS SMSHub | invalid domain '$domain' from $smsFrom");
    http_response_code(400);
    echo json_encode(['error' => 'Invalid domain in SMS']);
    exit;
}

// ── Construct full target URL from domain ─────────────────────────────────────
// Try HTTPS first — fall back to HTTP if HTTPS fails (e.g. no SSL cert on subdomain)
$targetUrlHttps = 'https://' . $domain . '/gms/api/sms_location_receiver.php';
$targetUrlHttp  = 'http://'  . $domain . '/gms/api/sms_location_receiver.php';
$targetUrl      = $targetUrlHttps;

error_log("GMS SMSHub | routing → $targetUrl | officer=$officerId | lat=$lat | lng=$lng");

// ── Forward to client server ──────────────────────────────────────────────────
$payload = json_encode([
    'device_id'  => $deviceId,
    'officer_id' => is_numeric($officerId) ? (int)$officerId : null,
    'latitude'   => $lat,
    'longitude'  => $lng,
    'accuracy'   => $accuracy,
    'battery'    => $battery,
    'timestamp'  => $timestamp,
    'source'     => 'sms',
    'from'       => $smsFrom,
]);

$result = forwardToClient($targetUrl, $payload);

// If HTTPS failed, retry with HTTP
if (!$result['success'] && $targetUrl === $targetUrlHttps) {
    error_log("GMS SMSHub | HTTPS failed — retrying with HTTP → $targetUrlHttp");
    $targetUrl = $targetUrlHttp;
    $result    = forwardToClient($targetUrl, $payload);
}

if ($result['success']) {
    error_log("GMS SMSHub | forwarded OK → HTTP " . $result['code']);
    echo json_encode([
        'status'       => 'ok',
        'forwarded_to' => $targetUrl,
        'http_code'    => $result['code'],
        'lat'          => $lat,
        'lng'          => $lng,
    ]);
} else {
    error_log("GMS SMSHub | forward FAILED → " . $result['error']);
    http_response_code(502);
    echo json_encode([
        'status'   => 'forward_failed',
        'url'      => $targetUrl,
        'error'    => $result['error'],
        'http_code'=> $result['code'],
        'response' => $result['response'], // actual body from sms_location_receiver
    ]);
}

// ── cURL forward helper ───────────────────────────────────────────────────────
function forwardToClient(string $url, string $json): array {
    $isHttps   = str_starts_with($url, 'https://');
    $isTestEnv = str_contains($url, 'test.') || str_contains($url, 'dev.') || str_contains($url, 'localhost');

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $json,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-GMS-Source: sms-hub',
        ],
        // Disable SSL verification for test/dev subdomains without valid certs
        CURLOPT_SSL_VERIFYPEER => $isHttps && !$isTestEnv,
        CURLOPT_SSL_VERIFYHOST => $isHttps && !$isTestEnv ? 2 : 0,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'error' => $error, 'code' => 0];
    }

    // Log full response body for debugging
    error_log("GMS SMSHub | cURL response HTTP=$httpCode body=" . substr($response, 0, 500));

    return [
        'success'  => $httpCode >= 200 && $httpCode < 300,
        'code'     => $httpCode,
        'response' => $response,
        'error'    => '',
    ];
}