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
$classification=strtoupper($_POST['classification']);
$datetimes=$_POST['datetimes'];
//=$_POST['batch']
$batch=0;
$bale_classified=0;

$bale_batch=0;
$batch=0;

$mass="";
$grade="";
$price="";




$sql = "Select * from warehousing_grades_batches where buyer_grade='$classification' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
  
   
 }else{


    $system_batch_number=0;

    $sql1 = "Select * from warehousing_grades_batches order by bale_batch desc limit 1";
    $result1 = $conn->query($sql1);
     
     if ($result1->num_rows > 0) {
      while($row1 = $result1->fetch_assoc()) {
  // product id
  
         $system_batch_number=$row1["bale_batch"]+1;

        }
       
     }

     

    $user_sql = "INSERT INTO warehousing_grades_batches(userid,seasonid,buyer_grade,bale_batch,created_at,datetimes) VALUES ($userid,$seasonid,'$classification',$system_batch_number,'$created_at','$datetimes')";
           //$sql = "select * from login";
           if ($conn->query($user_sql)===TRUE) {
           
             $last_id = $conn->insert_id;
              
           }else{


          }

 }



$sql = "Select * from warehousing_grades_batches where buyer_grade='$classification' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
  // product id
  
   $bale_batch=$row["id"];
   $batch=$row["bale_batch"];

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

   }

 }



if ($user_found==0 || $bale_batch==0) {
  
  if ($bale_batch==0) {
    $temp=array("response"=>"Grade Not Found","barcode"=>$barcode);
    array_push($response,$temp);
  }else{
   $temp=array("response"=>"Barcode not found","barcode"=>$barcode);
    array_push($response,$temp);
  }
        
    
}else{


  $sql = "Select * from warehousing_sold_bales_reclassification where warehousing_sold_balesid=$user_found  limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {

      while($row = $result->fetch_assoc()) {
   
       $id=$row["id"];
       $grade=$row["buyer_grade"];

       $temp=array("response"=>"Already Classified","barcode"=>$barcode,"mass"=>$mass,"price"=>$price,"grade"=>$grade,"id"=>$id);
       array_push($response,$temp);
       
     }

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





