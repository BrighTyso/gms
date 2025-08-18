<?php

require "conn.php";
require "validate.php";


$found_store=0;
$user_found=0;

$response=array();

if (isset($_POST['barcode'])){

$barcode=$_POST['barcode'];

//=$_POST['batch']
$batch=0;
$bale_classified=0;

$bale_batch=0;
$batch=0;

$mass="";
$grade="";
$price="";

$grades="";



$sql1 = "Select * from bale_counting_redo where barcode='$barcode' limit 1";
    $result1 = $conn->query($sql1);
     
   if ($result1->num_rows > 0) {
    while($row1 = $result1->fetch_assoc()) {
      // product id
       $bale_batch=$row1["id"];

      }  
}





$sql = "Select * from warehousing_sold_bales where barcode='$barcode' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $user_found=$row["id"];

   $mass=$row["mass"];
   $price=$row["price"];



   $sql1 = "Select * from warehousing_sold_bales_reclassification where warehousing_sold_balesid=$user_found limit 1";
    $result1 = $conn->query($sql1);
     
   if ($result1->num_rows > 0) {
    while($row1 = $result1->fetch_assoc()) {
      // product id
       $grades=$row1["id"];

      }  
    }


   

   }

 }



 $temp=array("mass"=>$mass,"price"=>$price,"grade"=>$grades,"status"=>$bale_batch,"");
  array_push($response,$temp);

      
    
    

}else{


$temp=array("response"=>"Field empty","mass"=>$mass,"price"=>$price,"grade"=>"","status"=>$bale_batch);
array_push($response,$temp);
	
}


echo json_encode($response);



?>





