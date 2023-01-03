<?php
require_once("conn.php");
require "validate.php";
require "datasource.php";

$barcode=validate($_GET['barcode']);
$userid=$_GET['userid'];
$created_at=$_GET['created_at'];
$latitude=$_GET['latitude'];
$longitude=$_GET['longitude'];
$company=$_GET['company'];
$dispatch_noteid=$_GET['dispatch_noteid'];

$barcode_found=0;
$dispatchid=0;
$total_found=0;
$dispatchid_found=0;
$companyid=0;
$mass=0;

$data1=array();
// get grower locations

if ($barcode!="") {



$sql11 = "Select * from users where active=1 and name='$company' limit 1";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);

    $companyid=$row["id"];
    
   }
 }







$sql11 = "Select * from seasons where active=1 limit 1";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);

    $seasonid=$row["id"];
    
   }
 }
  

$sql = "Select dispatch.id,mass from dispatch join sold_bales on dispatch.sold_balesid=sold_bales.id  where sold_bales.barcode='$barcode' and  dispatch.userid=$companyid and dispatch_noteid=$dispatch_noteid and seasonid=$seasonid";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);

    $barcode_found=1;
    $dispatchid=$row["id"];
    $mass=$row["mass"];

    
   }
 }





$sql = "Select dispatchid from received_bales_principal  where dispatchid=$dispatchid ";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);
    $dispatchid_found=$row["dispatchid"];

 
    
   }
 }



$sql1 = "Select * from dispatch_note_total_received where dispatch_noteid=$dispatch_noteid ";

$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);

    $barcode_found=1;
   // $dispatchid=$row["id"];
    $total_found=$row["id"];
    
   }
 }




//  $sql11 = "Select * from total_dispatch where userid='$userid' and  seasonid=$seasonid";

// $result = $conn->query($sql11);
 
//  if ($result->num_rows > 0) {
//    // output data of each row
//    while($row = $result->fetch_assoc()) {
//     // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

//     //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
//     // array_push($data1,$temp);

//     $total_found=$row["id"];
    
//    }
//  }



 if ($barcode_found>0 && $dispatchid>0  && $dispatchid_found==0) {

   $insert_sql = "INSERT INTO received_bales_principal(dispatchid,userid,latitude,longitude,created_at) VALUES ($dispatchid,$userid,'$latitude','$longitude','$created_at')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
    // $last_id = $conn->insert_id;

    if($total_found==0){

     $insert_sql = "INSERT INTO dispatch_note_total_received(dispatch_noteid,quantity,mass,created_at) VALUES ($dispatch_noteid,1,$mass,'$created_at')";
       //$gr = "select * from login";
       if ($conn->query($insert_sql)===TRUE) {
       
        // $last_id = $conn->insert_id;

         $temp=array("response"=>"success");
          array_push($data1,$temp);

          }


      }else{

       $user_sql1 = "update dispatch_note_total_received set quantity=quantity+1,mass=mass+$mass where dispatch_noteid=$total_found";
         //$sql = "select * from login";
         if ($conn->query($user_sql1)===TRUE) {

          $temp=array("response"=>"success");
          array_push($data1,$temp);

     
          }

        }

      }else{

         $temp=array("response"=>"".$conn->error);
          array_push($data1,$temp);

      }


   }else{

      if ($dispatchid_found>0) {
          $temp=array("response"=>"Already Received");
          array_push($data1,$temp);
      }else if ($barcode_found==0) {
         $temp=array("response"=>"Barcode Not Found");
          array_push($data1,$temp);
      }else if ($dispatchid==0) {
          $temp=array("response"=>"Barcode Not Found");
          array_push($data1,$temp);
      }

   }


}else{

$temp=array("response"=>"Barcode Empty");
array_push($data1,$temp);

}


 echo json_encode($data1);


?>


