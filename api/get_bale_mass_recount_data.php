<?php

require "conn.php";
require "validate.php";


$found_store=0;
$user_found=0;
$bale_batch=0;

$barcode="";

$response=array();

if (isset($_POST['id'])){

$id=$_POST['id'];

//=$_POST['batch']
$batch=0;
$bale_classified=0;

$bale_batch=0;
$batch=0;

$mass="";
$grade="";
$price="";

$grades="";
$barcode="";



$sql1 = "Select * from bale_counting_redo where id=$id limit 1";
    $result1 = $conn->query($sql1);
     
   if ($result1->num_rows > 0) {
    while($row1 = $result1->fetch_assoc()) {
      // product id
       $bale_batch=$row1["id"];

       $mass=$row1["mass"];
       $price=$row1["price"];
       $grades=$row1["grade"];
       $barcode=$row1["barcode"];

       $temp=array("response"=>"Field empty","mass"=>$mass,"price"=>$price,"grade"=>$grades,"barcode"=>$barcode,"status"=>$bale_batch,"id"=>$bale_batch);
       array_push($response,$temp);

      }  
}
    

}else{


$temp=array("response"=>"Field empty","status"=>$bale_batch);
array_push($response,$temp);
	
}


echo json_encode($response);



?>





