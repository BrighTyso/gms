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

$sql = "Select mapped_hectares.ha from mapped_hectares join growers on growers.id=mapped_hectares.growerid where mapped_hectares.seasonid=$seasonid ";
      $result = $conn->query($sql);
       
       if ($result->num_rows > 0) {
         // output data of each row
         while($row = $result->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
          $temp=array("ha"=>$row["ha"]);
          array_push($data1,$temp);
          
         }
       }
   
  }else if($description!="" && $growerid==0){

 $sql = "Select mapped_hectares.ha from mapped_hectares join growers on growers.id=mapped_hectares.growerid join users on users.id=mapped_hectares.userid where (mapped_hectares.seasonid=$seasonid  and users.username='$description') or (mapped_hectares.seasonid=$seasonid  and growers.grower_num='$description') or (mapped_hectares.seasonid=$seasonid  and growers.area='$description') or (mapped_hectares.seasonid=$seasonid  and growers.province='$description') ";
          $result = $conn->query($sql);
           
           if ($result->num_rows > 0) {
             // output data of each row
             while($row = $result->fetch_assoc()) {
              // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
              $temp=array("ha"=>$row["ha"]);
              array_push($data1,$temp);
              
             }
        }


  }else if($growerid>0 && $description!=""){


 $sql = "Select mapped_hectares.ha from mapped_hectares join growers on growers.id=mapped_hectares.growerid where mapped_hectares.seasonid=$seasonid and growerid=$growerid ";
          $result = $conn->query($sql);
           
           if ($result->num_rows > 0) {
             // output data of each row
             while($row = $result->fetch_assoc()) {
              // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
              $temp=array("ha"=>$row["ha"]);
              array_push($data1,$temp);
              
             }
        }

    

  }
  else if($growerid>0){


    $sql = "Select mapped_hectares.ha from mapped_hectares join growers on growers.id=mapped_hectares.growerid where mapped_hectares.seasonid=$seasonid and growerid=$growerid ";
          $result = $conn->query($sql);
           
           if ($result->num_rows > 0) {
             // output data of each row
             while($row = $result->fetch_assoc()) {
              // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
              $temp=array("ha"=>$row["ha"]);
              array_push($data1,$temp);
              
             }
        }

  }


  

  }
  



 echo json_encode($data1); 


?>