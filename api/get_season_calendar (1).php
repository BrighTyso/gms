<?php
/**
 * get_season_calendar.php
 * ─────────────────────────────────────────────────────────────────
 * Returns the full season plan for a grower — all stages with
 * their status, planned dates, and completion info.
 * Called by Android to render the planting calendar screen.
 *
 * GET /api/season/get_season_calendar.php?grower_id=GRW001&officer_id=OFF001
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

$growerId  = $_GET['grower_id']  ?? '';
$officerId = $_GET['officer_id'] ?? '';

if (empty($growerId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'grower_id required']);
    exit;
}

try {
    // ── Get active season ────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT * FROM seasons
        WHERE grower_id = :grower_id
          AND status = 'active'
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([':grower_id' => $growerId]);
    $season = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$season) {
        echo json_encode(['success' => true, 'season' => null, 'stages' => []]);
        exit;
    }

    // ── Auto-update stage statuses based on today's date ────────
    $today = date('Y-m-d');
    $pdo->prepare("
        UPDATE season_stages
        SET status = CASE
            WHEN actual_date IS NOT NULL              THEN 'completed'
            WHEN planned_date < :today               THEN 'overdue'
            WHEN planned_date BETWEEN :today AND DATE_ADD(:today2, INTERVAL reminder_days DAY)
                                                     THEN 'due_soon'
            ELSE 'upcoming'
        END
        WHERE season_id = :season_id
          AND status != 'skipped'
    ")->execute([
        ':today'     => $today,
        ':today2'    => $today,
        ':season_id' => $season['id'],
    ]);

    // ── Fetch all stages ─────────────────────────────────────────
    $stmtStages = $pdo->prepare("
        SELECT
            ss.*,
            DATEDIFF(ss.planned_date, CURDATE()) AS days_until,
            CASE
                WHEN ss.actual_date IS NOT NULL THEN 'completed'
                WHEN ss.planned_date < CURDATE() THEN 'overdue'
                WHEN ss.planned_date BETWEEN CURDATE()
                     AND DATE_ADD(CURDATE(), INTERVAL ss.reminder_days DAY) THEN 'due_soon'
                ELSE 'upcoming'
            END AS computed_status
        FROM season_stages ss
        WHERE ss.season_id = :season_id
        ORDER BY ss.sort_order ASC, ss.planned_date ASC
    ");
    $stmtStages->execute([':season_id' => $season['id']]);
    $stages = $stmtStages->fetchAll(PDO::FETCH_ASSOC);

    // ── Summary counts ───────────────────────────────────────────
    $summary = [
        'total'     => count($stages),
        'completed' => 0,
        'due_soon'  => 0,
        'overdue'   => 0,
        'upcoming'  => 0,
    ];
    foreach ($stages as $s) {
        $summary[$s['computed_status']] = ($summary[$s['computed_status']] ?? 0) + 1;
    }

    // ── Season progress % ────────────────────────────────────────
    $progress = $summary['total'] > 0
        ? round(($summary['completed'] / $summary['total']) * 100)
        : 0;

    echo json_encode([
        'success'  => true,
        'season'   => $season,
        'stages'   => $stages,
        'summary'  => $summary,
        'progress' => $progress,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
