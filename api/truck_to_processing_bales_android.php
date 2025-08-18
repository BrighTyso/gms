<?php


require_once("conn.php");
require "validate.php";


$found_store=0;
$user_found=0;

$response=array();

if (isset($_POST['userid'])){

$seasonid=$_POST['seasonid'];
$userid=$_POST['userid'];
$created_at=$_POST['created_at'];
$barcode=$_POST['barcode'];
$processing_dispatch_truckid=$_POST['processing_dispatch_truckid'];
$already_dispatched=0;
$found=0;
$grades_match=0;
$bale_grade="";

$date = new DateTime();
$datetimes=$date->format('H:i:s');

//processing_dispatch_truck_grades

$bale_price=0;
$bale_mass=0;
$total_truck_bales=0;

$sql = "Select warehousing_sold_bales_reclassification.id,warehousing_sold_bales_reclassification.buyer_grade,warehousing_sold_bales.price,warehousing_sold_bales.mass from warehousing_sold_bales_reclassification join warehousing_sold_bales on warehousing_sold_bales.id=warehousing_sold_bales_reclassification.warehousing_sold_balesid where barcode='$barcode' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows==0) {
     $warehousing_sold_bales_reclassificationid=0;
 }else{

  while($row = $result->fetch_assoc()) {
     
     $warehousing_sold_bales_reclassificationid=$row["id"];
     $bale_grade=$row["buyer_grade"];
      $bale_price=$row["price"];
      $bale_mass=$row["mass"];
    
    }
  
 }



$sql11 = "Select * from processing_dispatch_truck_grades where grade='$bale_grade' and processing_dispatch_truckid=$processing_dispatch_truckid";
$result11 = $conn->query($sql11);
 if ($result11->num_rows>0) {
   while($row = $result11->fetch_assoc()) {

    $grades_match=$row['id'];

    

  }      
 
 }


$warehousing_reclassificationid=0;


$sql11 = "Select * from truck_to_processing_bales where warehousing_sold_bales_reclassificationid=$warehousing_sold_bales_reclassificationid  limit 1";
$result11 = $conn->query($sql11);
 if ($result11->num_rows==0) {
   while($row = $result11->fetch_assoc()) {

    $already_dispatched=$row['id']; 
    $warehousing_reclassificationid=$row['warehousing_sold_bales_reclassificationid'];
  }      
 
 }



$sql11 = "Select * from truck_to_processing_bales where processing_dispatch_truckid=$processing_dispatch_truckid";
$result11 = $conn->query($sql11);
$total_truck_bales=$result11->num_rows;

 




if ($already_dispatched==0 ) {
  
         $sql = "Select * from truck_to_processing_bales where warehousing_sold_bales_reclassificationid=$warehousing_sold_bales_reclassificationid and processing_dispatch_truckid=$processing_dispatch_truckid limit 1";
         $result = $conn->query($sql);
          
          if ($result->num_rows==0) {
           $found=0;
          }else{

           while($row = $result->fetch_assoc()) {
              
              $found=$row["id"];
             
             }
           
         }





}else{

   $found=1;

}


if ($found>0 || $warehousing_sold_bales_reclassificationid==0 || $already_dispatched>0 || $grades_match==0) {
  

      if ($found>0) {
         
       $temp=array("response"=>"Already Dispatched","warehousing_sold_bales_reclassificationid"=>$warehousing_sold_bales_reclassificationid,"bale_grade"=>$bale_grade,"bale_price"=>$bale_price,"bale_mass"=>$bale_mass,"total_truck_bales"=>$total_truck_bales);
        array_push($response,$temp);
      }else if($already_dispatched>0){

       $temp=array("response"=>"Already Dispatched","warehousing_sold_bales_reclassificationid"=>$warehousing_sold_bales_reclassificationid,"bale_grade"=>$bale_grade,"bale_price"=>$bale_price,"bale_mass"=>$bale_mass,"total_truck_bales"=>$total_truck_bales);
        array_push($response,$temp);

      }else if($grades_match==0){
         $temp=array("response"=>"Wrong Grade","warehousing_sold_bales_reclassificationid"=>$warehousing_sold_bales_reclassificationid);
         array_push($response,$temp);
      }else{

         $temp=array("response"=>"Barcode Not Classified","warehousing_sold_bales_reclassificationid"=>$warehousing_sold_bales_reclassificationid);
         array_push($response,$temp);
      }

        
}else{
  $user_sql = "INSERT INTO truck_to_processing_bales(userid,seasonid,processing_dispatch_truckid,warehousing_sold_bales_reclassificationid,created_at) VALUES ($userid,$seasonid,$processing_dispatch_truckid,$warehousing_sold_bales_reclassificationid,'$created_at')";
     //$sql = "select * from login";
     if ($conn->query($user_sql)===TRUE) {
    
         $temp=array("response"=>"success","warehousing_sold_bales_reclassificationid"=>$warehousing_sold_bales_reclassificationid,"bale_grade"=>$bale_grade,"bale_price"=>$bale_price,"bale_mass"=>$bale_mass,"total_truck_bales"=>$total_truck_bales);
          array_push($response,$temp);
         
     }else{

     $temp=array("response"=>$conn->error,"warehousing_sold_bales_reclassificationid"=>$warehousing_sold_bales_reclassificationid,"bale_grade"=>$bale_grade,"bale_price"=>$bale_price,"bale_mass"=>$bale_mass,"total_truck_bales"=>$total_truck_bales);
     array_push($response,$temp);

     }

   }

}else{

$temp=array("response"=>"Field empty");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





