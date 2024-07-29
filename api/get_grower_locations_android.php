<?php
require "conn.php";
require "validate.php";

$data=array();


//http://192.168.1.190/gms/api/get_user.php?username=brightkaponda&hash=brightkaponda&access_code=1234
$seasonid=0;

$sql = "Select * from seasons where active=1 limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $seasonid=$row["id"];
    
   }
 }



$sql = "select distinct * from growers join lat_long on lat_long.growerid=growers.id where lat_long.seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("latitude"=>$row["latitude"],"longitude"=>$row["longitude"],"surname"=>$row["surname"],"grower_num"=>$row["grower_num"]);
    array_push($data,$temp);
    
   }
 }







 echo json_encode($data); 

?>