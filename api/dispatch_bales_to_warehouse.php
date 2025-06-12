<?php

require "conn.php";
require "validate.php";

$found_store=0;
$user_found=0;
$bale_received=0;

$response=array();

if (isset($_POST['userid'])){

$userid=$_POST['userid'];
$seasonid=$_POST['seasonid'];
$barcode=$_POST['barcode'];
$created_at=$_POST['created_at'];


$trailer=$_POST['trailer'];
$horse=$_POST['horse'];
$location=$_POST['location'];


if ($trailer!="" || $horse!="" || $location!="") {
  // code...



$warehousing_sold_balesid=0;

$date = new DateTime();
$datetimes=$_POST['datetimes'];

$sql ="Select * from warehousing_sold_bales where barcode='$barcode' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // product id
   $user_found=$row["id"];
   $warehousing_sold_balesid=$row["id"];

   }

 }


if ($user_found>0) {
  
    $sql = "Select * from floor_dispatched_bales where warehousing_sold_balesid=$user_found limit 1";
    $result = $conn->query($sql);
     
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {

        // product id
      
       $bale_received=$row["id"];

      
       }

     }


}




if ($bale_received>0 || $warehousing_sold_balesid==0 || $user_found==0) {



  if ($bale_received>0) {
       $temp=array("response"=>"Product Already Dispatched","barcode"=>$barcode);
    array_push($response,$temp);
  }else if ($warehousing_sold_balesid==0) {
    $temp=array("response"=>"Product Not Sold","barcode"=>$barcode);
    array_push($response,$temp);
  }else{
    $temp=array("response"=>"Product Not Sold","barcode"=>$barcode);
    array_push($response,$temp);
  }
  

    
}else{

  $user_sql = "INSERT INTO floor_dispatched_bales(userid,seasonid,warehousing_sold_balesid,created_at,datetimes,trailer,horse,location
) VALUES ($userid,$seasonid,$warehousing_sold_balesid,'$created_at','$datetimes','$trailer','$horse','$location')";
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

}else{
  $temp=array("response"=>"Enter Truck Details","barcode"=>$barcode);
  array_push($response,$temp);
}


echo json_encode($response);



?>





