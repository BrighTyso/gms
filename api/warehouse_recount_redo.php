<?php

require "conn.php";
require "validate.php";


$found_store=0;
$user_found=0;

$response=array();

if (isset($_POST['userid'])){

$seasonid=$_POST['seasonid'];
$userid=$_POST['userid'];
$created_at=$_POST['created_at'];
$barcode=$_POST['barcode'];
$classification=strtoupper($_POST['classification']);
$price=$_POST['price'];
$mass=$_POST['mass'];

//=$_POST['batch']
$batch=0;
$bale_classified=0;

$bale_batch=0;
$batch=0;

$total_bales=0;




$sql1 = "Select * from bale_counting_redo where grade='$classification'";
    $result1 = $conn->query($sql1);
$total_bales=$result1->num_rows;



$sql1 = "Select * from bale_counting_redo where barcode='$barcode' limit 1";
    $result1 = $conn->query($sql1);
     
   if ($result1->num_rows > 0) {
    while($row1 = $result1->fetch_assoc()) {
      // product id
       $bale_batch=$row1["id"];

      }  
}



  if ($bale_batch>0) {
  

    $temp=array("response"=>"Already counted","barcode"=>$barcode);
    array_push($response,$temp);

  }else{
     $user_sql = "INSERT INTO bale_counting_redo(userid,grade,barcode,mass,price,created_at) VALUES ($userid,'$classification','$barcode',$mass,$price,'$created_at')";
               //$sql = "select * from login";
       if ($conn->query($user_sql)===TRUE) {
       
         $last_id = $conn->insert_id;

         $total_bales+=1;

          $temp=array("response"=>"success","barcode"=>$barcode,"counter"=>$total_bales);
          array_push($response,$temp);

          
       }else{

          $temp=array("response"=>$conn->error,"barcode"=>$barcode);
          array_push($response,$temp);
      }
}
              
         
}else{


$temp=array("response"=>"Field empty","barcode"=>$barcode);
array_push($response,$temp);
	
}


echo json_encode($response);



?>





