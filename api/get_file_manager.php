<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";



$data = json_decode(file_get_contents("php://input"));


$data1=array();
$found=0;


if (isset($data->userid)  && isset($data->seasonid)) {
 
$userid=$data->userid;
$seasonid=$data->seasonid;
$description=$data->description;


if($description==""){
  $sql = "select distinct file_manager.id,growerid,location_url,description,file_type,storages,file_manager.created_at,datetimes,grower_num,growers.name,surname,seasons.name as season_name from file_manager join growers on growers.id=file_manager.growerid join seasons on seasons.id=file_manager.seasonid order by file_manager.id desc limit 100";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $temp=array("season"=>$row["season_name"],"id"=>$row["id"],"location_url"=>$row["location_url"],"description"=>$row["description"],"file_type"=>$row["file_type"],"storages"=>$row["storages"],"created_at"=>$row["created_at"],"datetimes"=>$row["datetimes"],"name"=>$row["name"],"surname"=>$row["surname"],"grower_num"=>$row["grower_num"]);
    array_push($data1,$temp);
    
   }
 }
}else{
  $sql = "select distinct file_manager.id,growerid,location_url,description,file_type,storages,file_manager.created_at,datetimes,grower_num,growers.name,surname,seasons.name as season_name from file_manager join growers on growers.id=file_manager.growerid join seasons on seasons.id=file_manager.seasonid where grower_num='$description' or seasons.name='$description' or surname='$description' or description='$description' or growers.name='$description' order by seasons.id desc limit 500";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $temp=array("season"=>$row["season_name"],"id"=>$row["id"],"location_url"=>$row["location_url"],"description"=>$row["description"],"file_type"=>$row["file_type"],"storages"=>$row["storages"],"created_at"=>$row["created_at"],"datetimes"=>$row["datetimes"],"name"=>$row["name"],"surname"=>$row["surname"],"grower_num"=>$row["grower_num"]);
    array_push($data1,$temp);
    
   }
 }
}

   




}






 echo json_encode($data1);





?>





