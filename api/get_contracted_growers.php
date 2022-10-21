<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));


$data1=array();

$description=$data->description;
$seasonid=$data->seasonid;


if ($description!="" && $seasonid!=""){

$sql = "Select growers.id , growers.name as grower_name , growers.surname as grower_surname , growers.grower_num  , growers.created_at,province,area,phone,id_num,lat_long.latitude,lat_long.longitude,barn_location.latitude as barn_latitude,barn_location.longitude as barn_longitude,grower_farm.latitude as farm_latitude,grower_farm.longitude as farm_longitude,username,contracted_hectares.hectares from growers left join lat_long on growers.id=lat_long.growerid left  join users on users.id=lat_long.userid left join contracted_hectares on contracted_hectares.growerid=lat_long.growerid left join grower_farm on growers.id=grower_farm.growerid left join barn_location on growers.id=barn_location.growerid where  growers.grower_num='$description'  or growers.surname='$description' or growers.area='$description' or growers.province='$description' or users.username='$description' and lat_long.seasonid=$seasonid and contracted_hectares.seasonid=$seasonid ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {


   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("id"=>$row["id"],"latitude"=>$row["latitude"],"longitude"=>$row["longitude"],"grower_num"=>$row["grower_num"],"grower_surname"=>$row["grower_surname"],"grower_name"=>$row["grower_name"],"created_at"=>$row["created_at"],"province"=>$row["province"] ,"area"=>$row["area"],"phone"=>$row["phone"],"username"=>$row["username"],"hectares"=>$row["hectares"] ,"barn_latitude"=>$row["barn_latitude"],"barn_longitude"=>$row["barn_longitude"],"farm_latitude"=>$row["farm_latitude"],"farm_longitude"=>$row["farm_longitude"]);
    array_push($data1,$temp);
   
    
   }
 }

}else if ($description=="" && $seasonid!=""){

$sql = "Select growers.id , growers.name as grower_name , growers.surname as grower_surname , growers.grower_num  , growers.created_at,province,area,phone,id_num,lat_long.latitude,lat_long.longitude,barn_location.latitude as barn_latitude,barn_location.longitude as barn_longitude,grower_farm.latitude as farm_latitude,grower_farm.longitude as farm_longitude,username,contracted_hectares.hectares from growers left join lat_long on growers.id=lat_long.growerid left join users on users.id=lat_long.userid left join contracted_hectares on contracted_hectares.growerid=lat_long.growerid left join grower_farm on growers.id=grower_farm.growerid left join barn_location on growers.id=barn_location.growerid where  lat_long.seasonid=$seasonid and contracted_hectares.seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("id"=>$row["id"],"latitude"=>$row["latitude"],"longitude"=>$row["longitude"],"grower_num"=>$row["grower_num"],"grower_surname"=>$row["grower_surname"],"grower_name"=>$row["grower_name"],"created_at"=>$row["created_at"],"province"=>$row["province"] ,"area"=>$row["area"],"phone"=>$row["phone"],"username"=>$row["username"],"hectares"=>$row["hectares"] ,"barn_latitude"=>$row["barn_latitude"],"barn_longitude"=>$row["barn_longitude"],"farm_latitude"=>$row["farm_latitude"],"farm_longitude"=>$row["farm_longitude"]);
    array_push($data1,$temp);
   
    
   }
 }

}


 echo json_encode($data1); 

?>