<?php

require_once("../api/conn.php");



$description=$_GET['province'];

$seasonid=0;

$sql11 = "Select * from  seasons where active=1 ";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $seasonid=$row["id"];

   
   }
 }

$sql2 = "Select distinct growers.id from  growers join active_growers on growers.id=active_growers.growerid where (province='$description') ";

$result1 = $conn->query($sql2);

$growers=$result1->num_rows;

echo $growers;


?>