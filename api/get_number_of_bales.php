<?php
require "conn.php";

$data=array();

//http://192.168.1.190/gms/api/get_products.php

$userid=$_GET["userid"];
$seasonid=0;
$rule=0;


$sql = "Select * from seasons where active=1 limit 1";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $seasonid=$row["id"];

   }
 }


if ($userid>0) {


      $sql1 = "Select grower_number_of_bales.id,bales,name,surname,grower_num,growers.userid,area,province,phone,id_num from grower_number_of_bales join growers on growers.id=grower_number_of_bales.growerid  where grower_number_of_bales.seasonid=$seasonid";
      $result1 = $conn->query($sql1);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

              // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
              $temp=array("name"=>$row1["name"],"surname"=>$row1["surname"],"grower_num"=>$row1["grower_num"],"userid"=>$row1["userid"]
                ,"area"=>$row1["area"],"province"=>$row1["province"],"phone"=>$row1["phone"],"id_num"=>$row1["id_num"],"id"=>$row1["id"],"bales"=>$row1["bales"]);
              array_push($data,$temp);
                 
          
         }
       }


}





 echo json_encode($data); 



?>