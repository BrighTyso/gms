<?php

require "conn.php";
require "validate.php";


$found_store=0;
$user_found=0;

$response=array();

if (isset($_POST['seasonid'])){

$seasonid=$_POST['seasonid'];
$userid=$_POST['userid'];
$created_at=$_POST['created_at'];
$barcode=$_POST['barcode'];
$datetimes=$_POST['datetimes'];
//=$_POST['batch']
$batch=0;
$bale_classified=0;

$bale_batch=0;
$batch=0;

$mass="";
$grade="";
$price="";




$sql = "Select * from warehouse_bale_recount where barcode='$barcode' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
  
   $temp=array("response"=>"Already Counted","barcode"=>$barcode);
    array_push($response,$temp);

 }else{

  $user_sql = "INSERT INTO warehouse_bale_recount(userid,seasonid,barcode,created_at) VALUES ($userid,$seasonid,'$barcode','$created_at')";
         //$sql = "select * from login";
         if ($conn->query($user_sql)===TRUE) {
         
           $last_id = $conn->insert_id;

           $temp=array("response"=>"Bale Counted","barcode"=>$barcode);
           array_push($response,$temp);
            
         }else{


      }

}

      
    
    

}else{


$temp=array("response"=>"Field empty","barcode"=>$barcode);
array_push($response,$temp);
	
}


echo json_encode($response);



?>





