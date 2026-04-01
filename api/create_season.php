<?php
/**
 * create_season.php
 * ─────────────────────────────────────────────────────────────────
 * Creates a new season with its planned stages.
 * Called from Android when officer sets up a grower's season plan.
 *
 * POST /api/season/create_season.php
 * Body (JSON):
 * {
 *   "grower_id":    "GRW001",
 *   "officer_id":   "OFF001",
 *   "season_name":  "2024 Main Season",
 *   "crop_type":    "Tobacco",
 *   "crop_variety": "Virginia K1",
 *   "land_size":    2.5,
 *   "stages": [
 *     { "stage_key": "land_clearing", "stage_name": "Land Clearing",
 *       "stage_icon": "🌿", "stage_color": "#795548",
 *       "planned_date": "2024-07-01", "reminder_days": 3, "sort_order": 1 },
 *     ...
 *   ]
 * }
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

$body = json_decode(file_get_contents('php://input'), true);

$growerId    = $body['grower_id']    ?? '';
$officerId   = $body['officer_id']   ?? '';
$seasonName  = $body['season_name']  ?? '';
$cropType    = $body['crop_type']    ?? '';
$cropVariety = $body['crop_variety'] ?? '';
$landSize    = $body['land_size']    ?? null;
$stages      = $body['stages']       ?? [];

if (empty($growerId) || empty($officerId) || empty($seasonName) || empty($stages)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $pdo->beginTransaction();

    // ── Create season ────────────────────────────────────────────
    $stmt = $pdo->prepare("
        INSERT INTO seasons (grower_id, officer_id, season_name, crop_type, crop_variety, land_size_acres, status)
        VALUES (:grower_id, :officer_id, :season_name, :crop_type, :crop_variety, :land_size, 'planned')
    ");
    $stmt->execute([
        ':grower_id'    => $growerId,
        ':officer_id'   => $officerId,
        ':season_name'  => $seasonName,
        ':crop_type'    => $cropType,
        ':crop_variety' => $cropVariety,
        ':land_size'    => $landSize,
    ]);
    $seasonId = $pdo->lastInsertId();

    // ── Insert each stage ────────────────────────────────────────
    $stmtStage = $pdo->prepare("
        INSERT INTO season_stages
            (season_id, grower_id, officer_id, stage_key, stage_name, stage_icon,
             stage_color, planned_date, reminder_days, status, sort_order)
        VALUES
            (:season_id, :grower_id, :officer_id, :stage_key, :stage_name, :stage_icon,
             :stage_color, :planned_date, :reminder_days, 'upcoming', :sort_order)
    ");

    foreach ($stages as $stage) {
        $stmtStage->execute([
            ':season_id'     => $seasonId,
            ':grower_id'     => $growerId,
            ':officer_id'    => $officerId,
            ':stage_key'     => $stage['stage_key'],
            ':stage_name'    => $stage['stage_name'],
            ':stage_icon'    => $stage['stage_icon'],
            ':stage_color'   => $stage['stage_color'],
            ':planned_date'  => $stage['planned_date'],
            ':reminder_days' => $stage['reminder_days'] ?? 2,
            ':sort_order'    => $stage['sort_order']    ?? 0,
        ]);
    }

    // ── Activate season ──────────────────────────────────────────
    $pdo->prepare("UPDATE seasons SET status = 'active' WHERE id = :id")
        ->execute([':id' => $seasonId]);

    $pdo->commit();

    echo json_encode([
        'success'    => true,
        'season_id'  => $seasonId,
        'message'    => 'Season created with ' . count($stages) . ' stages',
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to create season']);
}
