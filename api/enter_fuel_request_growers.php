<?php
require "conn.php";
require "validate.php";

$data=array();
// 2. Capture GET variables
// Using ?? null to prevent "Undefined index" notices
$growerid        = 0;
$grower_num= $_GET['grower_num'];
$latitude        = $_GET['latitude'] ?? 0.0;
$longitude       = $_GET['longitude'] ?? 0.0;
$planned_day     = $_GET['planned_day'] ?? '';
$leg_distance_km = $_GET['leg_distance_km'] ?? 0.0;
$visit_order     = $_GET['visit_order'] ?? 0;
$userid          = $_GET['userid'] ?? null;
$seasonid        = $_GET['seasonid'] ?? null;
$created_at      = $_GET['created_at'] ?? date('Y-m-d H:i:s');
$datetimes       = $_GET['datetimes'] ?? date('Y-m-d H:i:s');
$sqliteid= $_GET['sqliteid'];
 

$sql = "Select * from growers where grower_num='$grower_num' limit 1";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) { 
     
     $growerid=$row["id"];
   
       
     }

   }else{

        $grower_farm_sql = "INSERT INTO growers(userid,seasonid,grower_num,name,surname,phone,id_num,area,province,created_at) VALUES ($userid,$seasonid,'$grower_num','$name','$surname','$phone','$id_num','$area','$province','$created_at')";
         //$sql = "select * from login";
         if ($conn->query($grower_farm_sql)===TRUE) {

         }else{
          $temp=array("response"=>$conn->error,"hh"=>"kkk");
          array_push($data,$temp);
         }

     }




$sql = "Select * from growers where grower_num='$grower_num'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $growerid=$row["id"];
  
    
   }

 }


$found=0;

$sql = "Select * from fuel_request_growers where growerid=$growerid and created_at='$created_at'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $found=$row["id"];
  
    
   }

 }




if ($growerid>0 && $found==0) {
    // code...

// 3. Prepare the SQL Statement
$sql = "INSERT INTO fuel_request_growers 
        (growerid, latitude, longitude, planned_day, leg_distance_km, visit_order, userid, seasonid, created_at, datetimes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt) {
    /* 4. Bind parameters 
       The string "iddsdiisss" maps the data types:
       i = integer, d = double (decimal), s = string
    */
    $stmt->bind_param(
        "iddsdiisss", 
        $growerid, 
        $latitude, 
        $longitude, 
        $planned_day, 
        $leg_distance_km, 
        $visit_order, 
        $userid, 
        $seasonid, 
        $created_at, 
        $datetimes
    );

    // 5. Execute
    if ($stmt->execute()) {
        $temp=array("id"=>$sqliteid);
          array_push($data,$temp);
    } else {
        //echo "Execute failed: " . $stmt->error;
    }

    $stmt->close();
} else {
   // echo "Prepare failed: " . $conn->error;
}

}

echo json_encode($data);
?>