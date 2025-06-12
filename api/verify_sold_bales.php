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
$warehousing_sold_balesid=0;

$date = new DateTime();
$datetimes=$date->format('H:i:s');

// $sql = "Select * from warehousing_sold_bales where barcode='$barcode' limit 1";
// $result = $conn->query($sql);
 
//  if ($result->num_rows > 0) {
//    // output data of each row
//    while($row = $result->fetch_assoc()) {
//     // product id
//    $user_found=$row["id"];
//    $warehousing_sold_balesid=$row["id"];

//    }

//  }



  
  $sql = "Select * from verify_sold_bales where barcode='$barcode' limit 1";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {

      // product id
    
     $bale_received=$row["id"];

    
     }

   }







if ($bale_received>0) {



  if ($bale_received>0) {
       $temp=array("response"=>"Product Already Sold","barcode"=>$barcode);
    array_push($response,$temp);
  }
  

    
}else{

  $user_sql = "INSERT INTO verify_sold_bales(userid,seasonid,barcode,created_at,datetimes
) VALUES ($userid,$seasonid,'$barcode','$created_at','$datetimes')";
     //$sql = "select * from login";
     if ($conn->query($user_sql)===TRUE) {
     
       $last_id = $conn->insert_id;


       $temp=array("response"=>"success","barcode"=>$barcode);
            array_push($response,$temp);
        

         //   $user_sql = "INSERT INTO warehousing_sold_bales(userid,seasonid,grower_num,barcode,lot,buyer_grade,timb_grade,buyer_mark,location,mass,price,sell_date,created_at,datetimes) VALUES ($userid,$seasonid,'','$barcode',0,'','','','',0,0,'$created_at','$created_at','$datetimes')";
         // //$sql = "select * from login";
         // if ($conn->query($user_sql)===TRUE) {

         //     $temp=array("response"=>"success","barcode"=>$barcode);
         //    array_push($response,$temp);
         //  }
       
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





