<?php
/**
 * create_task.php
 * ─────────────────────────────────────────────────────────────────
 * Supervisor creates a task and assigns it to one or all officers.
 * Sends immediate push notification to assigned officers.
 *
 * POST /api/tasks/create_task.php
 * Body (JSON):
 * {
 *   "supervisor_id": "SUP001",
 *   "title":         "Visit John Mwangi",
 *   "description":   "Check crop condition after rain",
 *   "task_type":     "visit_grower",       // visit_grower|crop_stage|submit_report|custom
 *   "assign_to":     "single",             // single|all
 *   "officer_ids":   ["OFF001"],           // ignored if assign_to=all
 *   "grower_id":     "GRW001",             // for visit_grower
 *   "grower_name":   "John Mwangi",
 *   "stage_id":      null,                 // for crop_stage
 *   "stage_name":    null,
 *   "report_type":   null,                 // for submit_report
 *   "due_date":      "2024-08-10",
 *   "due_time":      "17:00",              // optional
 *   "priority":      "high"
 * }
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

$body = json_decode(file_get_contents('php://input'), true);

// ── Validate required fields ──────────────────────────────────────
$required = ['supervisor_id','title','task_type','assign_to','due_date'];
foreach ($required as $field) {
    if (empty($body[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "$field is required"]);
        exit;
    }
}

$supervisorId = $body['supervisor_id'];
$assignTo     = $body['assign_to'];  // 'single' or 'all'
$officerIds   = $body['officer_ids'] ?? [];

try {
    $pdo->beginTransaction();

    // ── 1. Create the task ────────────────────────────────────────
    $stmt = $pdo->prepare("
        INSERT INTO supervisor_tasks
            (supervisor_id, title, description, task_type, assign_to,
             grower_id, grower_name, stage_id, stage_name, report_type,
             due_date, due_time, priority)
        VALUES
            (:supervisor_id, :title, :description, :task_type, :assign_to,
             :grower_id, :grower_name, :stage_id, :stage_name, :report_type,
             :due_date, :due_time, :priority)
    ");
    $stmt->execute([
        ':supervisor_id' => $supervisorId,
        ':title'         => $body['title'],
        ':description'   => $body['description']  ?? '',
        ':task_type'     => $body['task_type'],
        ':assign_to'     => $assignTo,
        ':grower_id'     => $body['grower_id']    ?? null,
        ':grower_name'   => $body['grower_name']  ?? null,
        ':stage_id'      => $body['stage_id']     ?? null,
        ':stage_name'    => $body['stage_name']   ?? null,
        ':report_type'   => $body['report_type']  ?? null,
        ':due_date'      => $body['due_date'],
        ':due_time'      => $body['due_time']     ?? null,
        ':priority'      => $body['priority']     ?? 'medium',
    ]);
    $taskId = $pdo->lastInsertId();

    // ── 2. Resolve which officers to assign ───────────────────────
    if ($assignTo === 'all') {
        $offStmt = $pdo->query("SELECT id, name FROM officers WHERE active = 1");
        $officers = $offStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Fetch names for given IDs
        $placeholders = implode(',', array_fill(0, count($officerIds), '?'));
        $offStmt = $pdo->prepare(
            "SELECT id, name FROM officers WHERE id IN ($placeholders)");
        $offStmt->execute($officerIds);
        $officers = $offStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (empty($officers)) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'No officers found to assign']);
        exit;
    }

    // ── 3. Create assignments ─────────────────────────────────────
    $assignStmt = $pdo->prepare("
        INSERT INTO task_assignments (task_id, officer_id, officer_name, status)
        VALUES (:task_id, :officer_id, :officer_name, 'pending')
    ");

    $fcmTokens = [];
    foreach ($officers as $officer) {
        $assignStmt->execute([
            ':task_id'      => $taskId,
            ':officer_id'   => $officer['id'],
            ':officer_name' => $officer['name'],
        ]);

        // Collect FCM tokens for push notifications
        $tokenStmt = $pdo->prepare(
            "SELECT fcm_token FROM officers WHERE id = ? AND fcm_token IS NOT NULL");
        $tokenStmt->execute([$officer['id']]);
        $token = $tokenStmt->fetchColumn();
        if ($token) $fcmTokens[] = $token;
    }

    $pdo->commit();

    // ── 4. Send push notifications to all assigned officers ───────
    $notifsSent = 0;
    if (!empty($fcmTokens)) {
        $priorityLabel = ucfirst($body['priority'] ?? 'medium');
        $dueFormatted  = date('d M Y', strtotime($body['due_date']));
        $notifsSent    = sendBulkPushNotification(
            $fcmTokens,
            "📋 New Task Assigned",
            "{$body['title']} — Due: $dueFormatted [$priorityLabel]",
            ['type' => 'new_task', 'task_id' => (string)$taskId]
        );
    }

    echo json_encode([
        'success'        => true,
        'task_id'        => $taskId,
        'assigned_count' => count($officers),
        'notifs_sent'    => $notifsSent,
        'message'        => 'Task created and assigned to ' . count($officers) . ' officer(s)',
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

// ── FCM Helper ────────────────────────────────────────────────────
function sendBulkPushNotification(array $tokens, string $title,
                                   string $body, array $data = []): int {
    $sent = 0;
    foreach ($tokens as $token) {
        $payload = json_encode([
            'to'           => $token,
            'notification' => ['title' => $title, 'body' => $body, 'sound' => 'default'],
            'data'         => $data,
            'priority'     => 'high',
        ]);
        $ch = curl_init('https://fcm.googleapis.com/fcm/send');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: key=YOUR_FCM_SERVER_KEY',
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT    => 5,
        ]);
        $result = json_decode(curl_exec($ch), true);
        curl_close($ch);
        if (($result['success'] ?? 0) == 1) $sent++;
    }
    return $sent;
}
