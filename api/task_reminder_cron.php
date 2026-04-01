<?php
/**
 * task_reminder_cron.php
 * ─────────────────────────────────────────────────────────────────
 * Runs daily. Finds due-soon + overdue tasks and:
 *   - Marks them as overdue in the DB
 *   - Sends push notification to officer
 *   - Sends WhatsApp reminder to officer
 *   - Sends email to supervisor (overdue only)
 *
 * CRON SETUP:
 *   Morning reminders (due soon):
 *     0 7 * * * php /var/www/html/cron/task_reminder_cron.php
 *
 *   Overdue check at noon:
 *     0 12 * * * php /var/www/html/cron/task_reminder_cron.php --overdue
 */

//require_once __DIR__ . '/../../config/db.php';
require "conn.php";
require "validate.php";

define('FCM_KEY',      'YOUR_FCM_SERVER_KEY');
define('FROM_EMAIL',   'noreply@yourapp.com');
define('APP_NAME',     'GrowerSync');
define('WA_NUMBER',    '254700000000');

$overdueOnly = in_array('--overdue', $argv ?? []);
$today       = date('Y-m-d');

log_it("===== Task Reminder Cron =====");
log_it("Mode: " . ($overdueOnly ? 'OVERDUE ONLY' : 'ALL DUE'));
log_it("Date: $today");

// ── Step 1: Mark overdue assignments ──────────────────────────────
$pdo->prepare("
    UPDATE task_assignments ta
    JOIN supervisor_tasks st ON st.id = ta.task_id
    SET ta.status = 'overdue'
    WHERE st.due_date < :today
      AND ta.status IN ('pending','seen','in_progress')
      AND st.status = 'active'
")->execute([':today' => $today]);

// ── Step 2: Fetch assignments needing reminders ───────────────────
if ($overdueOnly) {
    $sql = "
        SELECT ta.id AS assignment_id, ta.officer_id, ta.officer_name, ta.status,
               st.id AS task_id, st.title, st.description, st.task_type,
               st.due_date, st.priority, st.grower_name, st.supervisor_id,
               o.phone AS officer_phone, o.fcm_token AS officer_fcm,
               sup.email AS supervisor_email, sup.fcm_token AS supervisor_fcm,
               sup.name AS supervisor_name,
               DATEDIFF(:today2, st.due_date) AS days_overdue
        FROM task_assignments ta
        JOIN supervisor_tasks st ON st.id = ta.task_id
        JOIN officers o           ON o.id  = ta.officer_id
        LEFT JOIN supervisors sup ON sup.id = st.supervisor_id
        WHERE ta.status = 'overdue'
          AND st.status = 'active'
        ORDER BY days_overdue DESC
    ";
    $params = [':today' => $today, ':today2' => $today];
} else {
    // Due within 2 days and not yet complete
    $sql = "
        SELECT ta.id AS assignment_id, ta.officer_id, ta.officer_name, ta.status,
               st.id AS task_id, st.title, st.description, st.task_type,
               st.due_date, st.priority, st.grower_name, st.supervisor_id,
               o.phone AS officer_phone, o.fcm_token AS officer_fcm,
               sup.email AS supervisor_email, sup.fcm_token AS supervisor_fcm,
               sup.name AS supervisor_name,
               DATEDIFF(st.due_date, :today2) AS days_until
        FROM task_assignments ta
        JOIN supervisor_tasks st ON st.id = ta.task_id
        JOIN officers o           ON o.id  = ta.officer_id
        LEFT JOIN supervisors sup ON sup.id = st.supervisor_id
        WHERE st.due_date BETWEEN :today AND DATE_ADD(:today3, INTERVAL 2 DAY)
          AND ta.status NOT IN ('completed','overdue')
          AND st.status = 'active'
        ORDER BY st.due_date ASC
    ";
    $params = [':today' => $today, ':today2' => $today, ':today3' => $today];
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

log_it("Found " . count($assignments) . " assignments to process");

$sent = 0; $skipped = 0; $failed = 0;

foreach ($assignments as $a) {
    $isOverdue   = $a['status'] === 'overdue';
    $daysOverdue = $a['days_overdue'] ?? 0;
    $daysUntil   = $a['days_until']   ?? 0;
    $reminderType = $isOverdue ? 'overdue' : 'due_soon';

    log_it("\n Task: {$a['title']} | Officer: {$a['officer_name']} | Overdue: " . ($isOverdue ? 'YES' : 'NO'));

    // ── Push Notification to Officer ─────────────────────────────
    if (!empty($a['officer_fcm'])) {
        if (!alreadySent($pdo, $a['assignment_id'], $reminderType)) {
            $title = $isOverdue
                ? "⚠️ OVERDUE TASK — {$a['title']}"
                : "📋 Task Due Soon — {$a['title']}";
            $body = $isOverdue
                ? "This task was due {$daysOverdue} day(s) ago. Please complete ASAP."
                : "Due in {$daysUntil} day(s): " . date('d M Y', strtotime($a['due_date']));

            $result = sendPush($a['officer_fcm'], $title, $body, [
                'type'          => 'task_reminder',
                'task_id'       => (string)$a['task_id'],
                'assignment_id' => (string)$a['assignment_id'],
                'is_overdue'    => $isOverdue ? 'true' : 'false',
            ]);
            logReminder($pdo, $a['assignment_id'], $reminderType);
            $result ? $sent++ : $failed++;
            log_it("  [PUSH] " . ($result ? '✅ Sent' : '❌ Failed'));
        } else { $skipped++; log_it("  [PUSH] Already sent today"); }
    }

    // ── WhatsApp Message to Officer ───────────────────────────────
    if (!empty($a['officer_phone'])) {
        $message = buildWhatsAppMessage($a, $isOverdue, $daysOverdue, $daysUntil);
        $waUrl   = "https://wa.me/{$a['officer_phone']}?text=" . urlencode($message);

        // Queue for dashboard or send via Twilio
        queueWhatsApp($pdo, $a['officer_phone'], $message, $waUrl, $a['task_id']);
        log_it("  [WHATSAPP] ✅ Queued for {$a['officer_phone']}");
        $sent++;
    }

    // ── Email to Supervisor (overdue only) ────────────────────────
    if ($isOverdue && !empty($a['supervisor_email'])) {
        $emailResult = sendOverdueEmail($a, $daysOverdue);
        log_it("  [EMAIL] " . ($emailResult ? '✅ Sent' : '❌ Failed') . " to {$a['supervisor_email']}");
        $emailResult ? $sent++ : $failed++;

        // Also push supervisor
        if (!empty($a['supervisor_fcm'])) {
            sendPush($a['supervisor_fcm'],
                "⚠️ Officer Task Overdue",
                "{$a['officer_name']}: '{$a['title']}' is {$daysOverdue} day(s) overdue",
                ['type' => 'officer_overdue', 'task_id' => (string)$a['task_id']]
            );
        }
    }
}

log_it("\n===== Summary =====");
log_it("Sent:    $sent");
log_it("Skipped: $skipped");
log_it("Failed:  $failed");

// ══════════════════════════════════════════════════════════════════
//  HELPERS
// ══════════════════════════════════════════════════════════════════

function buildWhatsAppMessage(array $a, bool $overdue, int $daysOverdue, int $daysUntil): string {
    $due = date('d M Y', strtotime($a['due_date']));
    $priority = strtoupper($a['priority']);

    if ($overdue) {
        return "⚠️ *OVERDUE TASK — " . APP_NAME . "*\n\n"
             . "📋 *{$a['title']}*\n"
             . "📅 Was due: *$due* ($daysOverdue day(s) ago)\n"
             . "🔴 Priority: $priority\n"
             . (!empty($a['grower_name']) ? "👤 Grower: {$a['grower_name']}\n" : "")
             . "\nPlease complete this task immediately and mark done in the app.";
    }
    return "📋 *Task Reminder — " . APP_NAME . "*\n\n"
         . "*{$a['title']}*\n"
         . "📅 Due: *$due* (in $daysUntil day(s))\n"
         . "🟡 Priority: $priority\n"
         . (!empty($a['description']) ? "📝 {$a['description']}\n" : "")
         . "\nMark complete in the app once done.";
}

function sendOverdueEmail(array $a, int $daysOverdue): bool {
    $due     = date('d M Y', strtotime($a['due_date']));
    $subject = "⚠️ OVERDUE TASK: {$a['title']} — {$a['officer_name']}";

    $priorityColor = match($a['priority']) {
        'urgent' => '#c0392b',
        'high'   => '#e74c3c',
        'medium' => '#e67e22',
        default  => '#27ae60',
    };

    $growerRow = !empty($a['grower_name'])
        ? "<tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold'>Grower</td>
           <td style='padding:8px;border:1px solid #ddd'>{$a['grower_name']}</td></tr>"
        : '';

    $html = "
    <html><body style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto'>
      <div style='background:#c0392b;color:white;padding:20px;border-radius:8px 8px 0 0'>
        <h2 style='margin:0'>⚠️ Overdue Task Alert</h2>
        <p style='margin:5px 0 0'>An assigned task has not been completed on time</p>
      </div>
      <div style='border:1px solid #ddd;border-top:none;padding:20px;border-radius:0 0 8px 8px'>
        <table style='width:100%;border-collapse:collapse;margin:15px 0'>
          <tr style='background:#f8f8f8'>
            <td style='padding:8px;border:1px solid #ddd;font-weight:bold'>Task</td>
            <td style='padding:8px;border:1px solid #ddd'>{$a['title']}</td>
          </tr>
          <tr>
            <td style='padding:8px;border:1px solid #ddd;font-weight:bold'>Officer</td>
            <td style='padding:8px;border:1px solid #ddd'>{$a['officer_name']}</td>
          </tr>
          $growerRow
          <tr style='background:#f8f8f8'>
            <td style='padding:8px;border:1px solid #ddd;font-weight:bold'>Was Due</td>
            <td style='padding:8px;border:1px solid #ddd'>$due</td>
          </tr>
          <tr>
            <td style='padding:8px;border:1px solid #ddd;font-weight:bold'>Days Overdue</td>
            <td style='padding:8px;border:1px solid #ddd;color:#c0392b;font-weight:bold'>$daysOverdue day(s)</td>
          </tr>
          <tr style='background:#f8f8f8'>
            <td style='padding:8px;border:1px solid #ddd;font-weight:bold'>Priority</td>
            <td style='padding:8px;border:1px solid #ddd;color:$priorityColor;font-weight:bold'>" . strtoupper($a['priority']) . "</td>
          </tr>
          <tr>
            <td style='padding:8px;border:1px solid #ddd;font-weight:bold'>Description</td>
            <td style='padding:8px;border:1px solid #ddd'>{$a['description']}</td>
          </tr>
        </table>
        <p>Please follow up with <strong>{$a['officer_name']}</strong> immediately.</p>
        <p style='color:#999;font-size:12px'>" . APP_NAME . " — " . date('d M Y H:i') . "</p>
      </div>
    </body></html>";

    $headers  = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . APP_NAME . " <" . FROM_EMAIL . ">\r\n";
    return mail($a['supervisor_email'], $subject, $html, $headers);
}

function sendPush(string $token, string $title, string $body, array $data = []): bool {
    $ch = curl_init('https://fcm.googleapis.com/fcm/send');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: key=' . FCM_KEY, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode([
            'to'           => $token,
            'notification' => ['title' => $title, 'body' => $body, 'sound' => 'default'],
            'data'         => $data,
            'priority'     => 'high',
        ]),
        CURLOPT_TIMEOUT => 5,
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return ($result['success'] ?? 0) == 1;
}

function queueWhatsApp(PDO $pdo, string $phone, string $message, string $waUrl, int $taskId): void {
    $pdo->prepare("
        INSERT INTO whatsapp_queue (phone, message, wa_url, task_id, created_at)
        VALUES (:phone, :message, :wa_url, :task_id, NOW())
        ON DUPLICATE KEY UPDATE message = VALUES(message), wa_url = VALUES(wa_url)
    ")->execute([':phone'=>$phone,':message'=>$message,':wa_url'=>$waUrl,':task_id'=>$taskId]);
}

function alreadySent(PDO $pdo, int $assignId, string $type): bool {
    $stmt = $pdo->prepare("
        SELECT 1 FROM task_reminder_log
        WHERE assignment_id = :id AND reminder_type = :type
          AND DATE(sent_at) = CURDATE()
        LIMIT 1
    ");
    $stmt->execute([':id' => $assignId, ':type' => $type]);
    return (bool)$stmt->fetch();
}

function logReminder(PDO $pdo, int $assignId, string $type): void {
    $pdo->prepare("
        INSERT IGNORE INTO task_reminder_log (assignment_id, reminder_type)
        VALUES (:id, :type)
    ")->execute([':id' => $assignId, ':type' => $type]);
}

function log_it(string $msg): void {
    echo "[" . date('H:i:s') . "] $msg\n";
}
