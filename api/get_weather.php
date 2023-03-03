<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$description=$data->description;
$seasonid=$data->seasonid;

$data1=array();
// get grower locations

if ($description=="") {
  


$sql11 = "Select weather.id,grower_num,temp,temp_min,temp_max,pressure,humidity,rain,clouds,wind_speed ,weather.created_at,city,weather.datetime from weather join growers on weather.growerid=growers.id where weather.seasonid=$seasonid order by weather.id desc limit 5000  ";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);

   $temp=array("grower_num"=>$row["grower_num"],"temp"=>$row["temp"],"temp_min"=>$row["temp_min"],"temp_max"=>$row["temp_max"],"pressure"=>$row["pressure"],"humidity"=>$row["humidity"],"rain"=>$row["rain"],"clouds"=>$row["clouds"],"wind_speed"=>$row["wind_speed"],"clouds"=>$row["clouds"],"created_at"=>$row["created_at"],"city"=>$row["city"],"datetime"=>$row["datetime"],"id"=>$row["id"]);
    array_push($data1,$temp);

   
   }
 }





}

 echo json_encode($data1);


?>


