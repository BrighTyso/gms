<?php
require "conn.php";
require "validate.php";

$data=array();


$seasonid=0;
$userid=0;
$growerid=0;


//http://192.168.1.190/gms/api/get_user.php?username=brightkaponda&hash=brightkaponda&access_code=1234


if (isset($_GET['userid']) && isset($_GET["growerid"]) && isset($_GET["seasonid"])) {


$userid=$_GET['userid'];
$seasonid=$_GET['seasonid'];
$growerid=$_GET['growerid'];

 $sql = "select first_lat,first_long,second_lat,second_long,third_lat,third_long ,forth_lat,forth_long
 from farm_mapping where seasonid=$seasonid and growerid=$growerid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("first_lat"=>$row["first_lat"],"first_long"=>$row["first_long"],"second_long"=>$row["second_long"],"second_lat"=>$row["second_lat"],"third_lat"=>$row["third_lat"],"third_long"=>$row["third_long"],"forth_lat"=>$row["forth_long"]);
    array_push($data,$temp);
    
   }
 }


}



 echo json_encode($data); 


?>