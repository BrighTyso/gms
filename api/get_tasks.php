<?php
/**
 * get_tasks.php
 * ─────────────────────────────────────────────────────────────────
 * Returns tasks filtered by role:
 *   Officer  → their assigned tasks
 *   Supervisor → all tasks they created with assignment statuses
 *
 * GET /api/tasks/get_tasks.php?officer_id=OFF001&status=pending
 * GET /api/tasks/get_tasks.php?supervisor_id=SUP001
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

$officerId    = $_GET['officer_id']    ?? '';
$supervisorId = $_GET['supervisor_id'] ?? '';
$statusFilter = $_GET['status']        ?? '';  // optional filter
$today        = date('Y-m-d');

try {
    // ── AUTO-UPDATE overdue assignments ───────────────────────────
    $pdo->prepare("
        UPDATE task_assignments ta
        JOIN supervisor_tasks st ON st.id = ta.task_id
        SET ta.status = 'overdue'
        WHERE st.due_date < :today
          AND ta.status IN ('pending','seen','in_progress')
          AND st.status = 'active'
    ")->execute([':today' => $today]);

    // ══════════════════════════════════════════════════════════════
    //  OFFICER VIEW — their assigned tasks
    // ══════════════════════════════════════════════════════════════
    if (!empty($officerId)) {

        $sql = "
            SELECT
                st.id, st.title, st.description, st.task_type,
                st.grower_id, st.grower_name, st.stage_id, st.stage_name,
                st.report_type, st.due_date, st.due_time, st.priority,
                ta.id            AS assignment_id,
                ta.status        AS assignment_status,
                ta.seen_at, ta.started_at, ta.completed_at,
                ta.completion_notes,
                DATEDIFF(st.due_date, CURDATE()) AS days_until,
                CASE
                    WHEN ta.status = 'completed'  THEN 5
                    WHEN ta.status = 'overdue'    THEN 1
                    WHEN ta.status = 'in_progress'THEN 2
                    WHEN ta.status = 'seen'       THEN 3
                    ELSE 4
                END AS sort_priority
            FROM task_assignments ta
            JOIN supervisor_tasks st ON st.id = ta.task_id
            WHERE ta.officer_id = :officer_id
              AND st.status = 'active'
        ";

        $params = [':officer_id' => $officerId];

        if (!empty($statusFilter)) {
            $sql .= " AND ta.status = :status";
            $params[':status'] = $statusFilter;
        }

        $sql .= " ORDER BY sort_priority ASC, st.due_date ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Mark pending tasks as 'seen' automatically when fetched
        $pendingIds = array_column(
            array_filter($tasks, fn($t) => $t['assignment_status'] === 'pending'),
            'assignment_id'
        );
        if (!empty($pendingIds)) {
            $ph = implode(',', array_fill(0, count($pendingIds), '?'));
            $pdo->prepare("
                UPDATE task_assignments
                SET status = 'seen', seen_at = NOW()
                WHERE id IN ($ph) AND status = 'pending'
            ")->execute($pendingIds);
        }

        // Summary counts
        $summary = ['total'=>0,'pending'=>0,'in_progress'=>0,'overdue'=>0,'completed'=>0];
        foreach ($tasks as $t) {
            $summary['total']++;
            $s = $t['assignment_status'];
            if (isset($summary[$s])) $summary[$s]++;
        }

        echo json_encode([
            'success' => true,
            'role'    => 'officer',
            'tasks'   => $tasks,
            'summary' => $summary,
        ]);

    // ══════════════════════════════════════════════════════════════
    //  SUPERVISOR VIEW — all tasks they created
    // ══════════════════════════════════════════════════════════════
    } elseif (!empty($supervisorId)) {

        $stmt = $pdo->prepare("
            SELECT
                st.*,
                DATEDIFF(st.due_date, CURDATE()) AS days_until,
                COUNT(ta.id)                              AS total_assigned,
                SUM(ta.status = 'completed')              AS completed_count,
                SUM(ta.status = 'overdue')                AS overdue_count,
                SUM(ta.status IN ('pending','seen'))      AS pending_count,
                SUM(ta.status = 'in_progress')            AS in_progress_count
            FROM supervisor_tasks st
            LEFT JOIN task_assignments ta ON ta.task_id = st.id
            WHERE st.supervisor_id = :supervisor_id
              AND st.status = 'active'
            GROUP BY st.id
            ORDER BY
                CASE WHEN st.due_date < CURDATE() THEN 0 ELSE 1 END,
                st.due_date ASC
        ");
        $stmt->execute([':supervisor_id' => $supervisorId]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // For each task get officer breakdown
        foreach ($tasks as &$task) {
            $aStmt = $pdo->prepare("
                SELECT officer_id, officer_name, status,
                       seen_at, completed_at, completion_notes
                FROM task_assignments
                WHERE task_id = :task_id
                ORDER BY officer_name
            ");
            $aStmt->execute([':task_id' => $task['id']]);
            $task['assignments'] = $aStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode([
            'success' => true,
            'role'    => 'supervisor',
            'tasks'   => $tasks,
        ]);

    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'officer_id or supervisor_id required']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
