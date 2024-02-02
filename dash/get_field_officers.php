<?php

require_once("../api/conn.php");



echo "<option value=0>Select Field-Officer</option>";


$sql2 = "Select  * from  users  where active=1 and (rightsid=7 or rightsid=8 or rightsid=9)";

$result1 = $conn->query($sql2);

 if ($result1->num_rows > 0) {
   // output data of each row
   while($row = $result1->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $name=$row["name"];
    $surname=$row["surname"];
    $id=$row["id"];

    echo "<option value=$id>".$name." ".$surname."</option>";

   
   }
 }


 

?>