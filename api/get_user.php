<?php
require "conn.php";
require "validate.php";

$data=array();

$username="";
$hash="";
$access_code=0000;


http://192.168.1.190/gms/api/get_user.php?username=brightkaponda&hash=brightkaponda&access_code=1234

if (isset($_GET['username']) && isset($_GET['hash'])){


$username=validate($_GET['username']);
$hash=validate($_GET['hash']);
$access_code=validate($_GET['access_code']);


$sql = "Select * from users where username='$username' and hash='$hash' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("name"=>$row["name"],"surname"=>$row["surname"],"username"=>$row["username"],"created_at"=>$row["created_at"],"userid"=>$row["id"],"rightsid"=>$row["rightsid"],"active"=>$row["active"],"access_code"=>$row["access_code"]);
    array_push($data,$temp);
    
   }
 }


 echo json_encode($data); 

}



?>