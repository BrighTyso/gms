<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));


$data1=array();

$seasonid=$data->seasonid;




if ($seasonid!=""){

$sql = "Select growers.id , growers.name as grower_name , growers.surname as grower_surname , growers.grower_num  , growers.created_at,province,area,phone,id_num,latitude,longitude,username,contracted_hectares.hectares from growers join grower_farm on growers.id=grower_farm.growerid join users on users.id=grower_farm.userid join contracted_hectares on contracted_hectares.growerid=grower_farm.growerid where  grower_farm.seasonid=$seasonid and contracted_hectares.seasonid=$seasonid ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {

   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("id"=>$row["id"],"latitude"=>$row["latitude"],"longitude"=>$row["longitude"],"grower_num"=>$row["grower_num"],"grower_surname"=>$row["grower_surname"],"grower_name"=>$row["grower_name"],"created_at"=>$row["created_at"],"province"=>$row["province"] ,"area"=>$row["area"],"phone"=>$row["phone"],"username"=>$row["username"],"hectares"=>$row["hectares"]);
    array_push($data1,$temp);
   
    
   }
 }

}

 echo json_encode($data1); 

?>