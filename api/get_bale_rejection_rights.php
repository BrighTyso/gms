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
$id=$data->id;
$seasonid=$data->seasonid;


$data1=array();
	

$sql = "Select users.name,rejected_bales_rights.created_at,rejected_bales_rights.id,rejected_bales_rights.active,seasons.name as season_name,rejected_bales_rights.datetime from rejected_bales_rights join users on users.id=rejected_bales_rights.companyid join seasons on seasons.id=rejected_bales_rights.seasonid where useridrights=$id and seasonid=$seasonid";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("name"=>$row["name"],"active"=>$row["active"] ,"id"=>$row["id"],"created_at"=>$row["created_at"],"datetime"=>$row["datetime"]);
    array_push($data1,$temp);
    
   }
 }



 echo json_encode($data1);

?>



