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

if (isset($data->seasonid)){

$seasonid=$data->seasonid;
$userid=$data->userid;
$created_at=$data->created_at;
$barcode=$data->barcode;
$classification=$data->classification;
$datetimes=$data->datetimes;
$batch=$data->batch;
$bale_classified=0;





$sql = "Select * from warehousing_sold_bales where barcode='$barcode' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $user_found=$row["id"];

  
   }

 }






if ($user_found==0) {
  

   $temp=array("response"=>"Barcode not found","barcode"=>$barcode);
    array_push($response,$temp);
        
    
}else{


  $sql = "Select * from warehousing_sold_bales_reclassification where warehousing_sold_balesid=$user_found  limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
  // $user_sql = "update warehousing_sold_bales_reclassification set buyer_grade='$classification',bale_batch=$batch where warehousing_sold_balesid=$user_found";
  //  //$sql = "select * from login";
  //  if ($conn->query($user_sql)===TRUE) {
   
  //    $last_id = $conn->insert_id;
  //    $temp=array("response"=>"successfully Updated","barcode"=>$barcode);
  //      array_push($response,$temp);

  //  }

 }else{

      $user_sql = "INSERT INTO warehousing_sold_bales_reclassification(userid,seasonid,warehousing_sold_balesid,buyer_grade,created_at,datetimes,bale_batch) VALUES ($userid,$seasonid,$user_found,'$classification','$created_at','$datetimes',$batch)";
           //$sql = "select * from login";
           if ($conn->query($user_sql)===TRUE) {
           
             $last_id = $conn->insert_id;

            
               $temp=array("response"=>"success","barcode"=>$barcode);
                array_push($response,$temp);
              
           }else{

           $temp=array("response"=>$conn->error);
           array_push($response,$temp);

           }

 }



  

   }


}else{


$temp=array("response"=>"Field empty","barcode"=>$barcode);
array_push($response,$temp);
	
}


echo json_encode($response);



?>





