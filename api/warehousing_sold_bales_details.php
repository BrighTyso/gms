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
$grower_num=$_POST['grower_num'];
$lot=$_POST['lot'];
$buyer_grade=$_POST['buyer_grade'];
$mass=$_POST['mass'];
$price=$_POST['price'];
$sell_date=$_POST['sell_date'];



$batch=0;
$bale_found=0;

$bale_batch=0;
$batch=0;




$sql = "Select * from warehousing_sold_bales where barcode='$barcode' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
  
   $bale_found=1;
 }else{

  $bale_found=0;
}



if ($bale_found==1) {
   
   $temp=array("response"=>"Barcode Already Captured","barcode"=>$barcode);
    array_push($response,$temp);
 
}else{

      $user_sql = "INSERT INTO warehousing_sold_bales(userid,seasonid,grower_num,barcode,lot,buyer_grade,mass,price,sell_date,created_at,datetimes) VALUES ($userid,$seasonid,'$grower_num','$barcode',$lot,'$buyer_grade',$mass,$price,'$sell_date','$created_at','$datetimes')";
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




}else{


$temp=array("response"=>"Field empty","barcode"=>$barcode);
array_push($response,$temp);
	
}


echo json_encode($response);



?>





