<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$seasonid=$data->seasonid;
$growerid=$data->growerid;
$description=$data->description;


$data1=array();

//http://192.168.1.190/gms/api/get_products.php

if ( isset($seasonid) && isset($growerid) && isset($description)) {


if ($growerid==0  && $description=="") {

$sql = "Select estimates.estimate from estimates join growers on growers.id=estimates.growerid where estimates.seasonid=$seasonid ";
      $result = $conn->query($sql);
       
       if ($result->num_rows > 0) {
         // output data of each row
         while($row = $result->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
          $temp=array("estimate"=>$row["estimate"]);
          array_push($data1,$temp);
          
         }
       }
   
  }else if($description!="" && $growerid==0){

    $sql = "Select estimates.estimate from estimates join growers on growers.id=estimates.growerid join users on users.id=estimates.userid where (estimates.seasonid=$seasonid  and users.username='$description') or (estimates.seasonid=$seasonid  and growers.grower_num='$description') or (estimates.seasonid=$seasonid  and growers.area='$description') or (estimates.seasonid=$seasonid  and growers.province='$description')";
          $result = $conn->query($sql);
           
           if ($result->num_rows > 0) {
             // output data of each row
             while($row = $result->fetch_assoc()) {
              // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
              $temp=array("estimate"=>$row["estimate"]);
              array_push($data1,$temp);
              
             }
        }

  }else if($growerid>0 && $description!=""){

    $sql = "Select estimates.estimate from estimates join growers on growers.id=estimates.growerid where estimates.seasonid=$seasonid and growerid=$growerid ";
          $result = $conn->query($sql);
           
           if ($result->num_rows > 0) {
             // output data of each row
             while($row = $result->fetch_assoc()) {
              // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
              $temp=array("estimate"=>$row["estimate"]);
              array_push($data1,$temp);
              
             }
        }

  }else if($growerid>0){

$sql = "Select estimates.estimate from estimates join growers on growers.id=estimates.growerid where estimates.seasonid=$seasonid and growerid=$growerid ";
          $result = $conn->query($sql);
           
           if ($result->num_rows > 0) {
             // output data of each row
             while($row = $result->fetch_assoc()) {
              // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
              $temp=array("estimate"=>$row["estimate"]);
              array_push($data1,$temp);
              
             }
        }

  }


  

  }
  



 echo json_encode($data1); 


?>