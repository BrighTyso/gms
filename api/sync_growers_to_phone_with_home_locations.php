<?php
require "conn.php";

$data=array();

//http://192.168.1.190/gms/api/get_products.php

$username=$_GET["username"];
$hash=md5($_GET["hash"]);
$userid=0;
$seasonid=0;
$rule=0;



if ($username!="" && $hash!="") {
  

$sql = "Select * from users where hash='$hash' and  username='$username' and  active=1";

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


      $sql = "Select distinct  name,surname,grower_num,visits.userid,area,province,phone,id_num,growers.created_at from growers join visits on visits.growerid=growers.id  where  visits.userid=$userid and visits.seasonid=$seasonid";
      $result = $conn->query($sql);
       
       if ($result->num_rows > 0) {
         // output data of each row
         while($row = $result->fetch_assoc()) {


          $home_latitude="";
          $home_longitude="";


          $barn_latitude="";
          $barn_longitude="";


          $farm_latitude="";
          $farm_longitude="";


          $seedbed_latitude="";
          $seedbed_longitude="";


          $sql1 = "Select * from lat_long  where  lat_long.userid=$userid and lat_long.seasonid=$seasonid limit 1";
          $result1 = $conn->query($sql1);
           
           if ($result1->num_rows > 0) {
             // output data of each row
             while($row1 = $result1->fetch_assoc()) {

              $home_latitude=$row1["latitude"];
                $home_longitude=$row1["longitude"];

             }

           }


           $sql1 = "Select * from barn_location  where  barn_location.userid=$userid and barn_location.seasonid=$seasonid limit 1";
          $result1 = $conn->query($sql1);
           
           if ($result1->num_rows > 0) {
             // output data of each row
             while($row1 = $result1->fetch_assoc()) {
              
                $barn_latitude=$row1["latitude"];
                $barn_longitude=$row1["longitude"];
             }

           }


           $sql1 = "Select * from grower_farm  where  grower_farm.userid=$userid and grower_farm.seasonid=$seasonid limit 1";
          $result1 = $conn->query($sql1);
           
           if ($result1->num_rows > 0) {
             // output data of each row
             while($row1 = $result1->fetch_assoc()) {

             
              $farm_latitude=$row1["latitude"];
                $farm_longitude=$row1["longitude"];

             }

           }




           $sql1 = "Select * from seedbed_location  where  seedbed_location.userid=$userid and seedbed_location.seasonid=$seasonid limit 1";
          $result1 = $conn->query($sql1
          );
           
           if ($result1->num_rows > 0) {
             // output data of each row
             while($row1 = $result1->fetch_assoc()) {

                $seedbed_latitude=$row1["latitude"];
                $seedbed_longitude=$row1["longitude"];
             }

           }




          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
          $temp=array("name"=>$row["name"],"surname"=>$row["surname"],"grower_num"=>$row["grower_num"],"userid"=>$row["userid"]
            ,"area"=>$row["area"],"province"=>$row["province"],"phone"=>$row["phone"],"id_num"=>$row["id_num"],"created_at"=>$row["created_at"],"seasonid"=>$seasonid,"home_latitude"=>$home_latitude,"home_longitude"=>$home_longitude,"barn_latitude"=>$barn_latitude,"barn_longitude"=>$barn_longitude,"farm_latitude"=>$farm_latitude,"farm_longitude"=>$farm_longitude,"seedbed_latitude"=>$seedbed_latitude,"seedbed_longitude"=>$seedbed_longitude);
          array_push($data,$temp);
          
         }
       }




}





}

 echo json_encode($data); 



?>