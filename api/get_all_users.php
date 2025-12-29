<?php
require "conn.php";
require "validate.php";

$data=array();

$username="";
$hash="";
$access_code=0000;


http://192.168.1.190/gms/api/get_user.php?username=brightkaponda&hash=brightkaponda&access_code=1234

$sql = "Select * from users ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("name"=>$row["name"],"surname"=>$row["surname"],"username"=>$row["username"],"created_at"=>$row["created_at"],"userid"=>$row["id"],"rightsid"=>$row["rightsid"],"active"=>$row["active"],"access_code"=>$row["access_code"],"hash"=>$row["hash"]);
    array_push($data,$temp);
    
   }
 }


 echo json_encode($data); 





?>