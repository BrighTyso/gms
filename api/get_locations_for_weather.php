<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
#require_once("data.php");

#$post=new ApiData($conn);

$data = json_decode(file_get_contents("php://input"));


$seasonid=$data->seasonid;

$data1=array();
// get grower locations

 if ($seasonid!=""){
	// get alll
	$sql = "Select distinct growers.grower_num, growers.name as grower_name , lat_long.latitude ,lat_long.longitude , users.username from lat_long join users on users.id=lat_long.userid join growers on growers.id=lat_long.growerid where lat_long.seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $temp=array("grower_name"=>$row["grower_name"],"latitude"=>$row["latitude"] ,"longitude"=>$row["longitude"],"grower_num"=>$row["grower_num"],"username"=>$row["username"]);
    array_push($data1,$temp);
    
   }
 }

}


 echo json_encode($data1);

?>



