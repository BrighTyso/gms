<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
#require_once("data.php");

#$post=new ApiData($conn);

$data = json_decode(file_get_contents("php://input"));

$userid=$data->userid;
$seasonid=$data->seasonid;
$created_at=$data->created_at;
//$startDate=$data->startDate;
//$endDate=$data->endDate;

$data1=array();
// get grower locations

if ($userid!="" && $seasonid!="") {
	

$sql = "Select visits.created_at,latitude,longitude,visits.datetime,username from visits join users on users.id=visits.userid where  users.id=$userid and visits.created_at='$created_at' ORDER BY visits.datetime desc";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("created_at"=>$row["created_at"],"latitude"=>$row["latitude"] ,"longitude"=>$row["longitude"],"time"=>$row["datetime"],"username"=>$row["username"]);
    array_push($data1,$temp);
    
   }
 }


}else if ($userid=="" ){
	// get alll
	$sql = "Select road_blocks.created_at,latitude,longitude,time,username from road_blocks join users on users.id=road_blocks.userid where  road_blocks.created_at='$created_at' ORDER BY road_blocks.created_at,road_blocks.id,road_blocks.time";
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



