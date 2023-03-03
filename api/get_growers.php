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

$sql = "Select  growers.id,users.username , growers.name as grower_name , growers.surname as grower_surname, growers.grower_num, growers.id_num,area,province,latitude,longitude from growers join lat_long on growers.id=lat_long.growerid join users on users.id=lat_long.userid where  growers.grower_num='$description'  or growers.phone='$description'  or growers.surname='$description' or growers.area='$description' or users.username='$description' and lat_long.seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {


   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("id"=>$row["id"],"username"=>$row["username"],"grower_name"=>$row["grower_name"],"grower_num"=>$row["grower_num"],"grower_surname"=>$row["grower_surname"],"id_num"=>$row["id_num"],"area"=>$row["area"],"province"=>$row["province"],"latitude"=>$row["latitude"],"longitude"=>$row["longitude"]);
    array_push($data1,$temp);
   
    
   }
 }

}else if ($description=="" && $seasonid!=""){

$sql = "Select growers.id,users.username , growers.name as grower_name , growers.surname as grower_surname, growers.grower_num, growers.id_num, area, province,latitude,longitude from growers join lat_long on growers.id=lat_long.growerid join users on users.id=lat_long.userid where  lat_long.seasonid='$seasonid'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("id"=>$row["id"],"username"=>$row["username"],"grower_name"=>$row["grower_name"],"grower_num"=>$row["grower_num"],"grower_surname"=>$row["grower_surname"],"id_num"=>$row["id_num"],"area"=>$row["area"],"province"=>$row["province"],"latitude"=>$row["latitude"],"longitude"=>$row["longitude"]);
    array_push($data1,$temp);
    
   }
 }

}


 echo json_encode($data1); 

?>