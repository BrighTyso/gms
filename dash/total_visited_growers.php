<?php

require_once("../api/conn.php");

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



$sql2 = "Select distinct growerid from  visits where seasonid=$seasonid";

$result2 = $conn->query($sql2);

$total_growers=$result2->num_rows;

echo $total_growers;


?>