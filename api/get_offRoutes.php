<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
#require_once("data.php");

#$post=new ApiData($conn);

$data = json_decode(file_get_contents("php://input"));

$description=$data->description;
$seasonid=$data->seasonid;
$startDate=$data->startDate;
$endDate=$data->endDate;

$data1=array();
// get grower locations

if ($description!="" && $seasonid!="") {
	

$sql = "Select road_blocks.created_at,latitude,longitude,time,username from off_route join users on users.id=off_route.userid where  users.username='$description' and off_route.created_at between '$startDate' and '$endDate' ORDER BY off_route.id";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("created_at"=>$row["created_at"],"latitude"=>$row["latitude"] ,"longitude"=>$row["longitude"],"time"=>$row["time"],"username"=>$row["username"]);
    array_push($data1,$temp);
    
   }
 }


}else if ($description=="" ){
	// get alll
	$sql = "Select road_blocks.created_at,latitude,longitude,time,username from off_routes join users on users.id=off_route.userid where off_route.created_at between '$startDate' and '$endDate' ORDER BY off_route.id";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("created_at"=>$row["created_at"],"latitude"=>$row["latitude"] ,"longitude"=>$row["longitude"],"time"=>$row["time"],"username"=>$row["username"]);
    array_push($data1,$temp);
    
   }
 }

}


 echo json_encode($data1);

?>



