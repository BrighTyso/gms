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


    $sql = "Select  
   bale_tags.grower_number_of_balesid,bale_tags.code,name,surname,grower_num,growers.userid,area,province,phone,id_num,growers.created_at,bale_tags.seasonid from growers join grower_number_of_bales on grower_number_of_bales.growerid=growers.id join bale_tags on bale_tags.grower_number_of_balesid=grower_number_of_bales.id  where bale_tags.seasonid=$seasonid and bale_tags.used=0";
          $result = $conn->query($sql);
           
           if ($result->num_rows > 0) {
             // output data of each row
             while($row = $result->fetch_assoc()) {
              // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
              $temp=array("name"=>$row["name"],"surname"=>$row["surname"],"grower_num"=>$row["grower_num"],"userid"=>$row["userid"]
                ,"area"=>$row["area"],"province"=>$row["province"],"phone"=>$row["phone"],"id_num"=>$row["id_num"],"created_at"=>$row["created_at"],"seasonid"=>$row["seasonid"],"grower_number_of_balesid"=>$row["grower_number_of_balesid"],"code"=>$row["code"]);
              array_push($data,$temp);
              
             }
           }
          
         }
       

 echo json_encode($data); 

?>