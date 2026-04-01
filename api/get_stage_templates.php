<?php
/**
 * get_stage_templates.php
 * ─────────────────────────────────────────────────────────────────
 * Returns all available crop stage options.
 * Android app fetches this to populate the "Add Stage" picker.
 *
 * GET /api/season/get_stage_templates.php
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

try {
    $stmt = $pdo->query("
        SELECT stage_key, stage_name, stage_icon, stage_color, description, sort_order
        FROM stage_templates
        WHERE active = 1
        ORDER BY sort_order ASC
    ");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by category for easy display in Android spinner/picker
    $grouped = [
        'Land Preparation' => [],
        'Planting'         => [],
        'Crop Care'        => [],
        'Pre-Harvest'      => [],
        'Harvest'          => [],
    ];

    $landPrepKeys   = ['land_clearing','soil_testing','plowing','ridging','fertilizer_basal'];
    $plantingKeys   = ['seed_procurement','nursery_setup','transplanting','direct_seeding'];
    $cropCareKeys   = ['first_irrigation','top_dressing','pest_scouting','pesticide_spray',
                       'weeding_first','weeding_second','topping','suckering','irrigation_regular'];
    $preHarvestKeys = ['crop_inspection','barn_preparation','reaping_start'];
    $harvestKeys    = ['harvesting','curing','grading','baling','delivery','post_season_review'];

    foreach ($templates as $t) {
        if (in_array($t['stage_key'], $landPrepKeys))   $grouped['Land Preparation'][] = $t;
        elseif (in_array($t['stage_key'], $plantingKeys))   $grouped['Planting'][] = $t;
        elseif (in_array($t['stage_key'], $cropCareKeys))   $grouped['Crop Care'][] = $t;
        elseif (in_array($t['stage_key'], $preHarvestKeys)) $grouped['Pre-Harvest'][] = $t;
        elseif (in_array($t['stage_key'], $harvestKeys))    $grouped['Harvest'][] = $t;
    }

    echo json_encode([
        'success'   => true,
        'templates' => $templates,
        'grouped'   => $grouped,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
