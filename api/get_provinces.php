<?php
require_once("conn.php");
require "validate.php";


$data1=array();

//http://192.168.1.190/gms/api/get_province.php

$sql = "Select * from province";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $temp=array("name"=>$row["names"],"id"=>$row["id"]);
    array_push($data1,$temp);
    
   }
 }



 echo json_encode($data1);

?>





