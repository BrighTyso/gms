<?php
/**
 * update_task.php
 * ─────────────────────────────────────────────────────────────────
 * Officer: update their assignment status (start / complete)
 * Supervisor: cancel a task
 *
 * POST /api/tasks/update_task.php
 *
 * Officer completing:
 * { "action": "complete", "assignment_id": 5,
 *   "officer_id": "OFF001", "notes": "Done. Grower visited." }
 *
 * Officer starting:
 * { "action": "start", "assignment_id": 5, "officer_id": "OFF001" }
 *
 * Supervisor cancelling:
 * { "action": "cancel", "task_id": 3, "supervisor_id": "SUP001" }
 */
header('Content-Type: application/json');
//require_once __DIR__ . '/../../config/db.php';
require "conn.php";
require "validate.php";

$body   = json_decode(file_get_contents('php://input'), true);
$action = $body['action'] ?? '';

try {
    switch ($action) {

        // ── Officer marks task as started ─────────────────────────
        case 'start':
            $assignId  = $body['assignment_id'] ?? 0;
            $officerId = $body['officer_id']    ?? '';

            if (!$assignId || !$officerId) {
                respondError('assignment_id and officer_id required'); break;
            }

            $pdo->prepare("
                UPDATE task_assignments
                SET status = 'in_progress', started_at = NOW()
                WHERE id = :id AND officer_id = :officer_id
                  AND status IN ('pending','seen')
            ")->execute([':id' => $assignId, ':officer_id' => $officerId]);

            // Notify supervisor that officer started
            notifySupervisor($pdo, $assignId, 'started');

            echo json_encode(['success' => true, 'message' => 'Task marked in progress']);
            break;

        // ── Officer marks task as complete ────────────────────────
        case 'complete':
            $assignId  = $body['assignment_id'] ?? 0;
            $officerId = $body['officer_id']    ?? '';
            $notes     = $body['notes']         ?? '';
            $photo     = $body['photo_path']    ?? null;

            if (!$assignId || !$officerId) {
                respondError('assignment_id and officer_id required'); break;
            }

            $pdo->prepare("
                UPDATE task_assignments
                SET status           = 'completed',
                    completed_at     = NOW(),
                    completion_notes = :notes,
                    completion_photo = :photo
                WHERE id = :id AND officer_id = :officer_id
            ")->execute([
                ':id'        => $assignId,
                ':officer_id'=> $officerId,
                ':notes'     => $notes,
                ':photo'     => $photo,
            ]);

            // Get task info for notification
            $task = getTaskByAssignment($pdo, $assignId);

            // Push supervisor: officer completed
            notifySupervisor($pdo, $assignId, 'completed');

            // Check if ALL officers completed — notify supervisor
            checkAllCompleted($pdo, $task['task_id'], $task['supervisor_fcm_token'] ?? '');

            echo json_encode([
                'success' => true,
                'message' => 'Task marked complete ✅',
                'task'    => $task,
            ]);
            break;

        // ── Supervisor cancels a task ─────────────────────────────
        case 'cancel':
            $taskId       = $body['task_id']       ?? 0;
            $supervisorId = $body['supervisor_id'] ?? '';

            if (!$taskId || !$supervisorId) {
                respondError('task_id and supervisor_id required'); break;
            }

            $pdo->prepare("
                UPDATE supervisor_tasks
                SET status = 'cancelled'
                WHERE id = :id AND supervisor_id = :sup_id
            ")->execute([':id' => $taskId, ':sup_id' => $supervisorId]);

            // Notify all assigned officers the task was cancelled
            $offTokens = $pdo->prepare("
                SELECT o.fcm_token
                FROM task_assignments ta
                JOIN officers o ON o.id = ta.officer_id
                WHERE ta.task_id = :task_id AND o.fcm_token IS NOT NULL
            ");
            $offTokens->execute([':task_id' => $taskId]);
            $tokens = $offTokens->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tokens as $token) {
                sendPush($token, '🚫 Task Cancelled',
                         'A task assigned to you has been cancelled by your supervisor.',
                         ['type' => 'task_cancelled', 'task_id' => (string)$taskId]);
            }

            echo json_encode(['success' => true, 'message' => 'Task cancelled']);
            break;

        default:
            respondError('Invalid action. Use: start, complete, cancel');
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

// ── Helpers ───────────────────────────────────────────────────────

function getTaskByAssignment(PDO $pdo, int $assignId): array {
    $stmt = $pdo->prepare("
        SELECT ta.*, st.title, st.task_type, st.due_date,
               st.supervisor_id,
               o.fcm_token AS officer_fcm,
               sup.fcm_token AS supervisor_fcm_token
        FROM task_assignments ta
        JOIN supervisor_tasks st ON st.id = ta.task_id
        JOIN officers o ON o.id = ta.officer_id
        LEFT JOIN supervisors sup ON sup.id = st.supervisor_id
        WHERE ta.id = :id
    ");
    $stmt->execute([':id' => $assignId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function notifySupervisor(PDO $pdo, int $assignId, string $event): void {
    $task = getTaskByAssignment($pdo, $assignId);
    if (empty($task['supervisor_fcm_token'])) return;

    $messages = [
        'started'   => ['📌 Task Started',    "{$task['officer_name']} started: {$task['title']}"],
        'completed' => ['✅ Task Completed',   "{$task['officer_name']} completed: {$task['title']}"],
    ];

    [$title, $body] = $messages[$event] ?? ['Task Update', 'A task was updated'];
    sendPush($task['supervisor_fcm_token'], $title, $body,
             ['type' => 'task_update', 'task_id' => (string)($task['task_id'] ?? '')]);
}

function checkAllCompleted(PDO $pdo, int $taskId, string $supervisorToken): void {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM task_assignments
        WHERE task_id = :task_id AND status != 'completed'
    ");
    $stmt->execute([':task_id' => $taskId]);
    $remaining = (int) $stmt->fetchColumn();

    if ($remaining === 0 && !empty($supervisorToken)) {
        $task = $pdo->prepare("SELECT title FROM supervisor_tasks WHERE id = :id");
        $task->execute([':id' => $taskId]);
        $title = $task->fetchColumn();
        sendPush($supervisorToken, '🎉 All Officers Completed Task',
                 "All officers have completed: $title",
                 ['type' => 'task_all_done', 'task_id' => (string)$taskId]);
    }
}

function sendPush(string $token, string $title, string $body, array $data = []): void {
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
    curl_exec($ch);
    curl_close($ch);
}

function respondError(string $msg): void {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $msg]);
}
