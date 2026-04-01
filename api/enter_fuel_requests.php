<?php
require "conn.php";
require "validate.php";

$data=array();
// 2. Capture GET variables (using null coalescing to avoid errors)
$field_officer_id   = $_GET['field_officer_id'] ?? null;
$week_start_date    = $_GET['week_start_date'] ?? '';
$week_end_date      = $_GET['week_end_date'] ?? '';
$status             = $_GET['status'] ?? 'PENDING';
$total_distance_km  = $_GET['total_distance_km'] ?? 0;
$total_fuel_litres  = $_GET['total_fuel_litres'] ?? 0;
$fuel_rate          = $_GET['fuel_rate'] ?? 0;
$is_km_per_litre    = $_GET['is_km_per_litre'] ?? 1;
$officer_notes      = $_GET['officer_notes'] ?? null;
$manager_notes      = $_GET['manager_notes'] ?? null;
$optimised_route_json = $_GET['optimised_route_json'] ?? null;
$seasonid           = $_GET['seasonid'] ?? null;
$created_at         = $_GET['created_at'] ?? date('Y-m-d H:i:s');
$datetimes          = $_GET['datetimes'] ?? date('Y-m-d H:i:s');
$sqliteid = $_GET['id'];
$no_of_growers=$_GET['no_of_growers'];
// Basic Validation for NOT NULL fields
if (!$field_officer_id || !$week_start_date || !$week_end_date || !$seasonid) {
    die("Error: Missing required fields (field_officer_id, dates, or seasonid).");
}

// 3. Prepare the SQL Statement
$sql = "INSERT INTO fuel_requests (
            field_officer_id, week_start_date, week_end_date, status, 
            total_distance_km, total_fuel_litres, fuel_rate, is_km_per_litre, 
            officer_notes, manager_notes, optimised_route_json, seasonid, 
            created_at, datetimes,no_of_growers
        ) VALUES ( $field_officer_id, 
        '$week_start_date', 
        '$week_end_date', 
        '$status', 
        $total_distance_km, 
        $total_fuel_litres, 
        $fuel_rate, 
        $is_km_per_litre, 
        '$officer_notes', 
        '$manager_notes', 
        '$optimised_route_json', 
        $seasonid, 
        '$created_at', 
        '$datetimes',
        $no_of_growers)";


if ($conn->query($sql)===TRUE) {
   
          $temp=array("id"=>$sqliteid);
          array_push($data,$temp);

   }else{
        $temp=array("id"=>$conn->error);
        array_push($data,$temp);
   }


// $stmt = $conn->prepare($sql);

// if ($stmt) {
//     /* 4. Bind parameters 
//        Types: i = integer, d = double/decimal, s = string/text
//        Mapping: "iss s ddd i sss i ss" (14 parameters)
//     */
//     $stmt->bind_param(
//         "isssdddisssiss", 
        // $field_officer_id, 
        // $week_start_date, 
        // $week_end_date, 
        // $status, 
        // $total_distance_km, 
        // $total_fuel_litres, 
        // $fuel_rate, 
        // $is_km_per_litre, 
        // $officer_notes, 
        // $manager_notes, 
        // $optimised_route_json, 
        // $seasonid, 
        // $created_at, 
        // $datetimes,
        // $no_of_growers
//     );

//     // 5. Execute and Check Results
//     if ($stmt->execute()) {
//         // echo "Fuel request recorded successfully. ID: " . $stmt->insert_id;

//         $temp=array("id"=>$sqliteid);
//           array_push($data,$temp);

//     } else {
//         // Handle duplicate key errors (officer + week_start)
//         if ($conn->errno === 1062) {
//             //echo "Error: A request already exists for this officer for this week.";

//              $temp=array("id"=>$conn->error);
//                    array_push($data,$temp);

//         } else {
//             //echo "Execution Error: " . $stmt->error;
//             $temp=array("id"=>$conn->error);
//                    array_push($data,$temp);
//         }
//     }

//     $stmt->close();
// } else {
//     //echo "Prepare Error: " . $conn->error;

//     $temp=array("id"=>$conn->error);
//                    array_push($data,$temp);
// }


echo json_encode($data);

?>