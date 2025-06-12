<?php

require "conn.php";
require "validate.php";

$found_store=0;
$user_found=0;
$bale_received=0;

$response=array();

if (isset($_GET['userid'])){

$userid=$_GET['userid'];
$seasonid=$_GET['seasonid'];
$barcode=$_GET['barcode'];
$grower_num=$_GET['grower_num'];
$lot=$_GET['lot'];
$mass=$_GET['mass'];
$price=$_GET['price'];
$buyer_grade=$_GET['buyer_grade'];
$created_at=$_GET['created_at'];
$warehousing_sold_balesid=0;

$date = new DateTime();
$datetimes=$date->format('H:i:s');

  
  $sql = "Select * from warehousing_sold_bales where barcode='$barcode' and price=0 and mass=0 limit 1";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {

      // product id
    
     $bale_received=$row["id"];

    
     }

   }







if ($bale_received==0) {

//Product Already Sold

  if ($bale_received==0) {
       $temp=array("response"=>"Barcode Already Captured ","barcode"=>$barcode);
    array_push($response,$temp);
  }
  
    
}else{


      $user_sql1 = "update warehousing_sold_bales set grower_num='$grower_num',mass=$mass,price=$price,lot=$lot,buyer_grade='$buyer_grade' where seasonid=$seasonid and id=$bale_received";
 //$sql = "select * from login";
     if ($conn->query($user_sql1)===TRUE) {

       $temp=array("response"=>"success","barcode"=>$barcode);
        array_push($response,$temp);
       
      }


       
   }


}else{


$temp=array("response"=>"Field empty","barcode"=>$barcode);
array_push($response,$temp);
	
}


echo json_encode($response);  



?>





