<?php
require "conn.php";
require "validate.php";

$data=array();


$userid=0;
$seasonid=0;
$description="";



//http://192.168.1.190/gms/api/get_user.php?username=brightkaponda&hash=brightkaponda&access_code=1234


if (isset($_GET['userid']) && isset($_GET["description"]) && isset($_GET["seasonid"])) {


$userid=$_GET['userid'];
//$seasonid=$_GET['seasonid'];
$description=$_GET["description"];



$sql = "Select * from seasons where active=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $seasonid=$row["id"];
   
    
   }

 }



if ($description=="") {
 $sql = "select grower_num,name,surname,latitude,longitude,growers.id from growers left join lat_long on growers.id=lat_long.growerid where lat_long.userid=$userid and lat_long.seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("name"=>$row["name"],"surname"=>$row["surname"],"id"=>$row["id"],"grower_num"=>$row["grower_num"],"latitude"=>$row["latitude"],"longitude"=>$row["longitude"]);
    array_push($data,$temp);
    
   }
 }

}else{

$sql = "select grower_num,name,surname,latitude,longitude,growers.id from growers left join lat_long on growers.id=lat_long.growerid where lat_long.userid=$userid and lat_long.seasonid=$seasonid and grower_num like '%$description%'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("name"=>$row["name"],"surname"=>$row["surname"],"id"=>$row["id"],"grower_num"=>$row["grower_num"],"latitude"=>$row["latitude"],"longitude"=>$row["longitude"]);
    array_push($data,$temp);
    
   }
 }



}






}





 echo json_encode($data); 



?>