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
$created_at=$data->created_at;

$data1=array();
// get grower locations

if ($description!="" && $seasonid!="") {
	

$sql = "Select growers.grower_num, growers.name as grower_name , barn_location.latitude ,barn_location.longitude , users.username from barn_location join users on users.id=barn_location.userid join growers on growers.id=barn_location.growerid where users.name='$description'  or users.surname='$description' or users.username='$description' or growers.province='$description' or growers.grower_num='$description' and  barn_location.seasonid=$seasonid";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("grower_name"=>$row["grower_name"],"lat"=>$row["latitude"] ,"long"=>$row["longitude"],"grower_num"=>$row["grower_num"],"username"=>$row["username"]);
    array_push($data1,$temp);
    
   }
 }


}else if ($description=="" && $seasonid!=""){
	// get alll
	$sql = "Select growers.grower_num, growers.name as grower_name , barn_location.latitude ,barn_location.longitude , users.username from barn_location join users on users.id=barn_location.userid join growers on growers.id=barn_location.growerid where barn_location.seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $temp=array("grower_name"=>$row["grower_name"],"lat"=>$row["latitude"] ,"long"=>$row["longitude"],"grower_num"=>$row["grower_num"],"username"=>$row["username"]);
    array_push($data1,$temp);
    
   }
 }

}


 echo json_encode($data1);

?>



