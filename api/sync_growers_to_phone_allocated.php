<?php
require "conn.php";

$data=array();

//http://192.168.1.190/gms/api/get_products.php

$username=$_GET["username"];
$userid=0;
$seasonid=0;
$rule=0;



if ($username!="") {
  

$sql = "Select * from users where  username='$username' and  active=1";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $userid=$row["id"];

   }
 }




$sql = "Select * from seasons where active=1 limit 1";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $seasonid=$row["id"];

   }
 }



 $sql = "Select rule from download_growers_rule  limit 1";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $rule=$row["rule"];

   }
 }


if ($userid>0) {



      $sql = "Select name,surname,grower_num,growers.userid,area,province,phone,id_num,growers.created_at,grower_field_officer.seasonid from growers join grower_field_officer on grower_field_officer.growerid=growers.id  where  grower_field_officer.field_officerid=$userid and grower_field_officer.seasonid=$seasonid";
      $result = $conn->query($sql);
       
       if ($result->num_rows > 0) {
         // output data of each row
         while($row = $result->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
          $temp=array("name"=>$row["name"],"surname"=>$row["surname"],"grower_num"=>$row["grower_num"],"userid"=>$row["userid"]
            ,"area"=>$row["area"],"province"=>$row["province"],"phone"=>$row["phone"],"id_num"=>$row["id_num"],"created_at"=>$row["created_at"],"seasonid"=>$row["seasonid"]);
          array_push($data,$temp);
          
         }
       }



}



}

 echo json_encode($data); 



?>