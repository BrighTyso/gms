<?php
/**
 * send_stage_reminders.php
 * ─────────────────────────────────────────────────────────────────
 * CRON JOB — runs every morning at 7:00 AM
 * Finds all stages due in the next X days and sends reminders via:
 *   1. Push notification (FCM → Android app)
 *   2. WhatsApp message (wa.me deep link URL sent via API)
 *   3. Email to supervisor
 *
 * CRON SETUP:
 *   0 7 * * * php /var/www/html/cron/send_stage_reminders.php >> /var/log/season_reminders.log 2>&1
 *
 * Also run at noon for overdue alerts:
 *   0 12 * * * php /var/www/html/cron/send_stage_reminders.php --overdue >> /var/log/season_reminders.log 2>&1
 */

require_once __DIR__ . '/../../config/db.php';

// ── Config ────────────────────────────────────────────────────────
define('FCM_SERVER_KEY',   'YOUR_FCM_SERVER_KEY');      // Firebase Cloud Messaging
define('WHATSAPP_NUMBER',  '254700000000');              // Your WhatsApp business number
define('FROM_EMAIL',       'noreply@yourapp.com');
define('SUPERVISOR_EMAIL', 'supervisor@yourorg.com');
define('APP_NAME',         'GrowerSync');

$overdueOnly = in_array('--overdue', $argv ?? []);

log_msg("========== Season Reminder Cron Started ==========");
log_msg("Mode: " . ($overdueOnly ? "OVERDUE ONLY" : "ALL DUE STAGES"));
log_msg("Time: " . date('Y-m-d H:i:s'));

// ── Find stages needing reminders ─────────────────────────────────
$today = date('Y-m-d');

if ($overdueOnly) {
    // Only find overdue stages (planned_date already passed, not completed)
    $query = "
        SELECT ss.*, s.season_name, s.crop_type, s.crop_variety,
               o.name AS officer_name, o.phone AS officer_phone,
               o.fcm_token AS officer_fcm_token,
               o.supervisor_email,
               g.name AS grower_name, g.location AS grower_location
        FROM season_stages ss
        JOIN seasons s  ON s.id  = ss.season_id
        JOIN officers o ON o.id  = ss.officer_id
        JOIN growers g  ON g.id  = ss.grower_id
        WHERE ss.planned_date < :today
          AND ss.status NOT IN ('completed', 'skipped')
          AND s.status = 'active'
        ORDER BY ss.planned_date ASC
    ";
    $params = [':today' => $today];
} else {
    // Find stages due within their reminder window
    $query = "
        SELECT ss.*, s.season_name, s.crop_type, s.crop_variety,
               o.name AS officer_name, o.phone AS officer_phone,
               o.fcm_token AS officer_fcm_token,
               o.supervisor_email,
               g.name AS grower_name, g.location AS grower_location
        FROM season_stages ss
        JOIN seasons s  ON s.id  = ss.season_id
        JOIN officers o ON o.id  = ss.officer_id
        JOIN growers g  ON g.id  = ss.grower_id
        WHERE ss.planned_date BETWEEN :today AND DATE_ADD(:today2, INTERVAL ss.reminder_days DAY)
          AND ss.status NOT IN ('completed', 'skipped')
          AND s.status = 'active'
        ORDER BY ss.planned_date ASC
    ";
    $params = [':today' => $today, ':today2' => $today];
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$stages = $stmt->fetchAll(PDO::FETCH_ASSOC);

log_msg("Found " . count($stages) . " stages needing reminders");

$sent = 0;
$skipped = 0;
$failed = 0;

foreach ($stages as $stage) {
    $daysUntil = (int) ((strtotime($stage['planned_date']) - strtotime($today)) / 86400);
    $isOverdue = $daysUntil < 0;

    log_msg("\n-- Stage: {$stage['stage_name']} | Grower: {$stage['grower_name']} "
          . "| Officer: {$stage['officer_name']} | Days: $daysUntil");

    // ── 1. Push Notification (FCM) ────────────────────────────────
    if (!empty($stage['officer_fcm_token'])) {
        if (!alreadySent($pdo, $stage['id'], 'officer', 'push')) {
            $pushResult = sendPushNotification(
                $stage['officer_fcm_token'],
                $stage,
                $daysUntil,
                $isOverdue
            );
            logReminder($pdo, $stage['id'], 'officer', $stage['officer_phone'], 'push', $pushResult);
            $pushResult ? $sent++ : $failed++;
        } else {
            log_msg("  [PUSH] Already sent today — skipping");
            $skipped++;
        }
    }

    // ── 2. WhatsApp Message ───────────────────────────────────────
    if (!empty($stage['officer_phone'])) {
        if (!alreadySent($pdo, $stage['id'], 'whatsapp', 'whatsapp')) {
            $waResult = sendWhatsAppReminder(
                $stage['officer_phone'],
                $stage,
                $daysUntil,
                $isOverdue
            );
            logReminder($pdo, $stage['id'], 'whatsapp', $stage['officer_phone'], 'whatsapp', $waResult);
            $waResult ? $sent++ : $failed++;
        } else {
            log_msg("  [WHATSAPP] Already sent today — skipping");
            $skipped++;
        }
    }

    // ── 3. Email to Supervisor ────────────────────────────────────
    $supervisorEmail = $stage['supervisor_email'] ?? SUPERVISOR_EMAIL;
    if (!empty($supervisorEmail)) {
        // Supervisors only get overdue alerts (not upcoming reminders — too noisy)
        if ($isOverdue && !alreadySent($pdo, $stage['id'], 'supervisor', 'email')) {
            $emailResult = sendSupervisorEmail($supervisorEmail, $stage, $daysUntil);
            logReminder($pdo, $stage['id'], 'supervisor', $supervisorEmail, 'email', $emailResult);
            $emailResult ? $sent++ : $failed++;
        } elseif (!$isOverdue) {
            log_msg("  [EMAIL] Upcoming stage — supervisor email skipped (not overdue)");
        } else {
            log_msg("  [EMAIL] Already sent today — skipping");
            $skipped++;
        }
    }
}

log_msg("\n========== Summary ==========");
log_msg("Sent:    $sent");
log_msg("Skipped: $skipped (already sent today)");
log_msg("Failed:  $failed");
log_msg("=====================================\n");


// ══════════════════════════════════════════════════════════════════
//  DELIVERY FUNCTIONS
// ══════════════════════════════════════════════════════════════════

/**
 * Send push notification via Firebase Cloud Messaging (FCM)
 */
function sendPushNotification(string $fcmToken, array $stage, int $daysUntil, bool $isOverdue): bool {
    $title = $isOverdue
        ? "⚠️ OVERDUE: {$stage['stage_name']}"
        : "📅 Upcoming: {$stage['stage_name']}";

    $body = $isOverdue
        ? "{$stage['stage_icon']} {$stage['grower_name']} — Was due " . abs($daysUntil) . " day(s) ago"
        : "{$stage['stage_icon']} {$stage['grower_name']} — Due in $daysUntil day(s) on {$stage['planned_date']}";

    $payload = [
        'to' => $fcmToken,
        'notification' => [
            'title' => $title,
            'body'  => $body,
            'sound' => 'default',
            'badge' => '1',
        ],
        'data' => [
            'type'       => 'stage_reminder',
            'stage_id'   => (string) $stage['id'],
            'season_id'  => (string) $stage['season_id'],
            'grower_id'  => $stage['grower_id'],
            'stage_key'  => $stage['stage_key'],
            'is_overdue' => $isOverdue ? 'true' : 'false',
        ],
        'priority' => $isOverdue ? 'high' : 'normal',
    ];

    $ch = curl_init('https://fcm.googleapis.com/fcm/send');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: key=' . FCM_SERVER_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT    => 10,
    ]);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        log_msg("  [PUSH] cURL error: $error");
        return false;
    }

    $result = json_decode($response, true);
    $success = isset($result['success']) && $result['success'] == 1;
    log_msg("  [PUSH] " . ($success ? "✅ Sent" : "❌ Failed: " . json_encode($result)));
    return $success;
}

/**
 * Send WhatsApp reminder via Twilio / 360dialog / direct wa.me URL
 *
 * Option A (Twilio — recommended for production):
 *   Replace the curl call below with Twilio's WhatsApp API
 *
 * Option B (Simple wa.me link logged to DB — officer sends manually):
 *   Store the wa.me link for manual dispatch from a web dashboard
 */
function sendWhatsAppReminder(string $phone, array $stage, int $daysUntil, bool $isOverdue): bool {
    $emoji  = $stage['stage_icon'];
    $grower = $stage['grower_name'];
    $stageName = $stage['stage_name'];
    $date   = date('d M Y', strtotime($stage['planned_date']));
    $crop   = $stage['crop_type'];
    $season = $stage['season_name'];

    if ($isOverdue) {
        $days = abs($daysUntil);
        $message = "⚠️ *OVERDUE STAGE ALERT* ⚠️\n\n"
                 . "$emoji *$stageName* for grower *$grower*\n"
                 . "📅 Was due: *$date* ($days day(s) ago)\n"
                 . "🌾 Crop: $crop | $season\n\n"
                 . "Please action this immediately and mark complete in the app.";
    } else {
        $message = "📅 *Stage Reminder — " . APP_NAME . "*\n\n"
                 . "$emoji *$stageName* coming up for *$grower*\n"
                 . "📆 Due Date: *$date* (in $daysUntil day(s))\n"
                 . "🌾 Crop: $crop | $season\n\n"
                 . "Ensure this is completed on time. Mark done in the app when finished.";
    }

    // ── Option A: Send via Twilio WhatsApp API ───────────────────
    // $accountSid = 'YOUR_TWILIO_SID';
    // $authToken  = 'YOUR_TWILIO_TOKEN';
    // $fromNumber = 'whatsapp:+14155238886'; // Twilio sandbox or your number
    // $url = "https://api.twilio.com/2010-04-01/Accounts/$accountSid/Messages.json";
    // ... Twilio API call here ...

    // ── Option B: Log wa.me URL to database for dashboard dispatch ─
    $encodedMsg = urlencode($message);
    $waUrl = "https://wa.me/{$phone}?text={$encodedMsg}";

    // Store in DB so supervisor dashboard can send with one click
    global $pdo;
    $pdo->prepare("
        INSERT INTO whatsapp_queue (phone, message, wa_url, stage_id, created_at)
        VALUES (:phone, :message, :wa_url, :stage_id, NOW())
        ON DUPLICATE KEY UPDATE message = VALUES(message), wa_url = VALUES(wa_url)
    ")->execute([
        ':phone'    => $phone,
        ':message'  => $message,
        ':wa_url'   => $waUrl,
        ':stage_id' => $stage['id'],
    ]);

    log_msg("  [WHATSAPP] ✅ Message queued for $phone");
    return true;
}

/**
 * Email supervisor about overdue stages
 */
function sendSupervisorEmail(string $email, array $stage, int $daysUntil): bool {
    $days     = abs($daysUntil);
    $subject  = "⚠️ OVERDUE CROP STAGE: {$stage['stage_name']} — {$stage['grower_name']}";

    $statusColor = $daysUntil < 0 ? '#c0392b' : '#e67e22';
    $statusLabel = $daysUntil < 0 ? "OVERDUE by $days day(s)" : "DUE in $daysUntil day(s)";

    $message = "
    <html><body style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto'>
        <div style='background:#c0392b;color:white;padding:20px;border-radius:8px 8px 0 0'>
            <h2 style='margin:0'>⚠️ Crop Stage Alert — " . APP_NAME . "</h2>
        </div>
        <div style='border:1px solid #ddd;border-top:none;padding:20px;border-radius:0 0 8px 8px'>
            <p>A crop stage requires your attention:</p>
            <table style='width:100%;border-collapse:collapse;margin:15px 0'>
                <tr style='background:#f8f8f8'>
                    <td style='padding:10px;border:1px solid #ddd;font-weight:bold'>Stage</td>
                    <td style='padding:10px;border:1px solid #ddd'>{$stage['stage_icon']} {$stage['stage_name']}</td>
                </tr>
                <tr>
                    <td style='padding:10px;border:1px solid #ddd;font-weight:bold'>Grower</td>
                    <td style='padding:10px;border:1px solid #ddd'>{$stage['grower_name']}</td>
                </tr>
                <tr style='background:#f8f8f8'>
                    <td style='padding:10px;border:1px solid #ddd;font-weight:bold'>Location</td>
                    <td style='padding:10px;border:1px solid #ddd'>{$stage['grower_location']}</td>
                </tr>
                <tr>
                    <td style='padding:10px;border:1px solid #ddd;font-weight:bold'>Season</td>
                    <td style='padding:10px;border:1px solid #ddd'>{$stage['season_name']} — {$stage['crop_type']}</td>
                </tr>
                <tr style='background:#f8f8f8'>
                    <td style='padding:10px;border:1px solid #ddd;font-weight:bold'>Officer</td>
                    <td style='padding:10px;border:1px solid #ddd'>{$stage['officer_name']}</td>
                </tr>
                <tr>
                    <td style='padding:10px;border:1px solid #ddd;font-weight:bold'>Planned Date</td>
                    <td style='padding:10px;border:1px solid #ddd'>" . date('d M Y', strtotime($stage['planned_date'])) . "</td>
                </tr>
                <tr style='background:#fff3cd'>
                    <td style='padding:10px;border:1px solid #ddd;font-weight:bold'>Status</td>
                    <td style='padding:10px;border:1px solid #ddd;color:$statusColor;font-weight:bold'>$statusLabel</td>
                </tr>
            </table>
            <p style='color:#666'>Please follow up with <strong>{$stage['officer_name']}</strong>
            to ensure this stage is actioned.</p>
            <p style='color:#999;font-size:12px'>Sent by " . APP_NAME . " — " . date('d M Y H:i') . "</p>
        </div>
    </body></html>";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . APP_NAME . " <" . FROM_EMAIL . ">\r\n";

    $result = mail($email, $subject, $message, $headers);
    log_msg("  [EMAIL] " . ($result ? "✅ Sent to $email" : "❌ Failed to $email"));
    return $result;
}


// ══════════════════════════════════════════════════════════════════
//  HELPERS
// ══════════════════════════════════════════════════════════════════

function alreadySent(PDO $pdo, int $stageId, string $recipientType, string $method): bool {
    $stmt = $pdo->prepare("
        SELECT 1 FROM stage_reminder_log
        WHERE stage_id        = :stage_id
          AND recipient_type  = :recipient_type
          AND delivery_method = :method
          AND DATE(sent_at)   = CURDATE()
        LIMIT 1
    ");
    $stmt->execute([
        ':stage_id'       => $stageId,
        ':recipient_type' => $recipientType,
        ':method'         => $method,
    ]);
    return (bool) $stmt->fetch();
}

function logReminder(PDO $pdo, int $stageId, string $recipientType,
                     string $recipient, string $method, bool $success): void {
    $pdo->prepare("
        INSERT INTO stage_reminder_log
            (stage_id, recipient_type, recipient, delivery_method, status, sent_at)
        VALUES
            (:stage_id, :recipient_type, :recipient, :method, :status, NOW())
    ")->execute([
        ':stage_id'       => $stageId,
        ':recipient_type' => $recipientType,
        ':recipient'      => $recipient,
        ':method'         => $method,
        ':status'         => $success ? 'sent' : 'failed',
    ]);
}

function log_msg(string $msg): void {
    echo "[" . date('H:i:s') . "] " . $msg . "\n";
}
