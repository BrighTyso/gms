<?php
require_once("conn.php");
require "validate.php";
require "datasource.php";

$username=$_POST['username'];
$hash=md5($_POST['password']);

$data1=array();
// get grower locations

if ($username!="" && $hash!="") {
  

$sql = "Select * from users where hash='$hash' and  username='$username' and  active=1 ";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    array_push($data1,$temp);
    
   }
 }


}

 echo json_encode($data1);


?>


