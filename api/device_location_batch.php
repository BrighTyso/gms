<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 *  GMS — Batch Location Ping Receiver
 *  File: /gms/api/device_location_batch.php
 *
 *  Called by Android when it reconnects and flushes its offline
 *  SQLite queue in one batch POST (up to 1000 pings at once).
 *
 *  POST body (JSON):
 *  {
 *    "pings": [
 *      {
 *        "device_id":    "a1b2c3d4",
 *        "officer_id":   42,
 *        "company_code": "ACME",
 *        "latitude":     -17.8292,
 *        "longitude":    31.0522,
 *        "accuracy":     12,
 *        "altitude":     1490,
 *        "speed":        0,
 *        "battery":      78,
 *        "timestamp":    1709123456000,
 *        "queued":       1
 *      },
 *      ...
 *    ]
 *  }
 * ╚══════════════════════════════════════════════════════════════════╝
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['pings']) || !is_array($data['pings'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid pings array']);
    exit;
}

if (count($data['pings']) > 1000) {
    http_response_code(400);
    echo json_encode(['error' => 'Too many pings (max 1000 per batch)']);
    exit;
}

// ── DB connection ─────────────────────────────────────────────────────────────
require "conn.php";
date_default_timezone_set("Africa/Harare");
$conn->query("SET time_zone = '+02:00'");
require "validate.php";




// ── Prepare statement once, reuse per ping ────────────────────────────────────
$stmt = $conn->prepare("
    INSERT INTO device_locations
        (device_id, officer_id, latitude, longitude, accuracy,
         altitude, speed, battery_level, device_timestamp, source)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, FROM_UNIXTIME(?), ?)
");

if (!$stmt) {
    error_log('GMS batch prepare error: ' . $conn->error);
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    $conn->close();
    exit;
}

$saved = $failed = 0;

$conn->begin_transaction();

foreach ($data['pings'] as $p) {
    if (empty($p['device_id']) || !isset($p['latitude'], $p['longitude'])) {
        $failed++; continue;
    }

    $lat = (float)$p['latitude'];
    $lng = (float)$p['longitude'];

    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        $failed++; continue;
    }

    $deviceId  = substr(trim($p['device_id']), 0, 64);
    $officerId = isset($p['officer_id']) ? (int)$p['officer_id']      : null;
    $accuracy  = isset($p['accuracy'])  ? (float)$p['accuracy']       : null;
    $altitude  = isset($p['altitude'])  ? (float)$p['altitude']       : null;
    $speed     = isset($p['speed'])     ? (float)$p['speed']          : null;
    $battery   = isset($p['battery'])   ? (int)$p['battery']          : null;
    $ts        = isset($p['timestamp']) ? (int)($p['timestamp']/1000) : time();
    $source    = !empty($p['queued'])   ? 'offline_queue'             : 'realtime';



   $found=0;
    $sql = "Select * from field_officers where userid=$officerId limit 1";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) { 
     
      $found=$row["id"];
   
       
     }

   }
   //else{


//     $grower_farm_sql = "INSERT INTO field_officers(userid,seasonid,grower_num,name,surname,phone,id_num,area,province,created_at) VALUES ($userid,$seasonid,'$grower_num','$name','$surname','$phone','$id_num','$area','$province','$created_at')";
//          //$sql = "select * from login";
//          if ($conn->query($grower_farm_sql)===TRUE) {

//          }else{
//           $temp=array("response"=>$conn->error,"hh"=>"kkk");
//           array_push($data,$temp);
//          }


//    }


    $stmt->bind_param('sidddddiis',
        $deviceId, $found, $lat, $lng,
        $accuracy, $altitude, $speed, $battery, $ts, $source
    );

    if ($stmt->execute()) {
        $saved++;
    } else {
        //echo $stmt->error;
        error_log('GMS batch row error: ' . $stmt->error);
        $failed++;
    }
}

$conn->commit();
$stmt->close();

// ── Prune records older than 30 days ─────────────────────────────────────────
$conn->query("DELETE FROM device_locations WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");

$conn->close();

echo json_encode([
    'status' => 'ok',
    'saved'  => $saved,
    'failed' => $failed,
    'total'  => count($data['pings']),
]);