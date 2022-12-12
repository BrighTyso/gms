<?php
require_once("conn.php");
require "validate.php";
require "datasource.php";

$userid=$_GET['userid'];
$seasonid=0;




$data1=array();
// get grower locations

if ($userid!="") {



  $sql = "Select * from seasons  where active=1 ";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);

   $seasonid=$row["id"];
  

   
   }
 }

  


$sql11 = "Select * from users join bale_tracking_rights on users.id=bale_tracking_rights.delivering_userid where rightsid=14 and receiving_userid=$userid and seasonid=$seasonid";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);

   $temp=array("name"=>$row["username"]);
    array_push($data1,$temp);

   
   }
 }





}

 echo json_encode($data1);


?>


