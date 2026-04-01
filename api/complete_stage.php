<?php
/**
 * complete_stage.php
 * ─────────────────────────────────────────────────────────────────
 * Officer marks a crop stage as done from the Android app.
 *
 * POST /api/season/complete_stage.php
 * Body: { "stage_id": 5, "officer_id": "OFF001",
 *          "actual_date": "2024-08-03", "notes": "All went well" }
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

$body      = json_decode(file_get_contents('php://input'), true);
$stageId   = $body['stage_id']    ?? 0;
$officerId = $body['officer_id']  ?? '';
$actualDate= $body['actual_date'] ?? date('Y-m-d');
$notes     = $body['notes']       ?? '';

if (!$stageId || empty($officerId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'stage_id and officer_id required']);
    exit;
}

try {
    // ── Mark stage complete ──────────────────────────────────────
    $stmt = $pdo->prepare("
        UPDATE season_stages
        SET status           = 'completed',
            actual_date      = :actual_date,
            completion_notes = :notes,
            updated_at       = NOW()
        WHERE id         = :stage_id
          AND officer_id = :officer_id
    ");
    $stmt->execute([
        ':stage_id'   => $stageId,
        ':officer_id' => $officerId,
        ':actual_date'=> $actualDate,
        ':notes'      => $notes,
    ]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Stage not found or not yours']);
        exit;
    }

    // ── Fetch stage info for response ────────────────────────────
    $stage = $pdo->prepare("
        SELECT ss.*, s.season_name, s.crop_type
        FROM season_stages ss
        JOIN seasons s ON s.id = ss.season_id
        WHERE ss.id = :stage_id
    ");
    $stage->execute([':stage_id' => $stageId]);
    $stageData = $stage->fetch(PDO::FETCH_ASSOC);

    // ── Check if season is fully complete ────────────────────────
    $remaining = $pdo->prepare("
        SELECT COUNT(*) FROM season_stages
        WHERE season_id = :season_id
          AND status NOT IN ('completed', 'skipped')
    ");
    $remaining->execute([':season_id' => $stageData['season_id']]);
    $remainingCount = (int) $remaining->fetchColumn();

    if ($remainingCount === 0) {
        $pdo->prepare("UPDATE seasons SET status = 'completed' WHERE id = :id")
            ->execute([':id' => $stageData['season_id']]);
    }

    echo json_encode([
        'success'          => true,
        'message'          => $stageData['stage_name'] . ' marked complete',
        'stage'            => $stageData,
        'season_complete'  => $remainingCount === 0,
        'remaining_stages' => $remainingCount,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
