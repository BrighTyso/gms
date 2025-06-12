<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";

$data = json_decode(file_get_contents("php://input"));

$found_store=0;
$user_found=0;

$response=array();

if (isset($data->userid)){

$seasonid=$data->seasonid;
$userid=$data->userid;
$created_at=$data->created_at;
$barcode=$data->barcode;
$processing_dispatch_truckid=$data->processing_dispatch_truckid;
$already_dispatched=0;
$found=0;

$date = new DateTime();
$datetimes=$date->format('H:i:s');


$sql = "Select warehousing_sold_bales_reclassification.id from warehousing_sold_bales_reclassification join warehousing_sold_bales on warehousing_sold_bales.id=warehousing_sold_bales_reclassification.warehousing_sold_balesid where barcode='$barcode' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows==0) {
     $warehousing_sold_bales_reclassificationid=0;
 }else{

  while($row = $result->fetch_assoc()) {
     
     $warehousing_sold_bales_reclassificationid=$row["id"];
    
    }
  
 }



$sql11 = "Select * from truck_to_processing_bales where warehousing_sold_bales_reclassificationid=$warehousing_sold_bales_reclassificationid  limit 1";
$result11 = $conn->query($sql11);
 if ($result11->num_rows==0) {

  $already_dispatched=$result11['id'];       
 
 }




if ($already_dispatched==0) {
  
         $sql = "Select * from truck_to_processing_bales where warehousing_sold_bales_reclassificationid=$warehousing_sold_bales_reclassificationid and processing_dispatch_truckid=$processing_dispatch_truckid limit 1";
         $result = $conn->query($sql);
          
          if ($result->num_rows==0) {
           $found=0;
          }else{

           while($row = $result->fetch_assoc()) {
              
              $found=$row["id"];
             
             }
           
         }
}else{

   $found=1;

}


if ($found>0 || $warehousing_sold_bales_reclassificationid==0 || $already_dispatched>0) {
  

      if ($found>0) {
         
       $temp=array("response"=>"Barcode Already Dispatched");
        array_push($response,$temp);
      }else if($already_dispatched>0){

       $temp=array("response"=>"Already Dispatched");
        array_push($response,$temp);
      }else{

         $temp=array("response"=>"Barcode Not Classified");
         array_push($response,$temp);
      }

        
}else{
  $user_sql = "INSERT INTO truck_to_processing_bales(userid,seasonid,processing_dispatch_truckid,warehousing_sold_bales_reclassificationid,created_at) VALUES ($userid,$seasonid,$processing_dispatch_truckid,$warehousing_sold_bales_reclassificationid,'$created_at')";
     //$sql = "select * from login";
     if ($conn->query($user_sql)===TRUE) {
    
         $temp=array("response"=>"success");
          array_push($response,$temp);
         
     }else{

     $temp=array("response"=>$conn->error);
     array_push($response,$temp);

     }

   }

}else{

$temp=array("response"=>"Field empty");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





