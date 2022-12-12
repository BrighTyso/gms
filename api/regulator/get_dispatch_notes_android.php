<?php
require_once("conn.php");
require "validate.php";
require "datasource.php";


$userid=$_GET['userid'];
$seasonid=0;




$data1=array();
// get grower locations

 $sql = "Select * from seasons where active=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $seasonid=$row["id"];
   
    
   }

 }



$sql11 = "Select distinct note,dispatch_note.id from dispatch_note join dispatch_note_total_dispatched on dispatch_note.id=dispatch_note_total_dispatched.dispatch_noteid join users on dispatch_note.receiverid=users.id where userid=$userid and seasonid=$seasonid and open_close=0";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);

    $temp=array("note"=>$row["note"],"id"=>$row["id"]);
     array_push($data1,$temp);
    
   }
 }






 echo json_encode($data1);


?>


