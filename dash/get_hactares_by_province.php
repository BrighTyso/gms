<?php

require_once("../api/conn.php");



$description=$_GET['province'];

$seasonid=0;
$ha=0;

$sql11 = "Select * from  seasons where active=1 ";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $seasonid=$row["id"];

   
   }
 }

$sql2 = "Select  id from  growers  where (province='$description') ";

$result1 = $conn->query($sql2);

 if ($result1->num_rows > 0) {
   // output data of each row
   while($row = $result1->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $ha+=$row["id"];

   
   }
 }


 echo $ha;


?>