<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$seasonid=0;
$found=0;
$security_otp_found=0;
$fetched_records=0;
$processed_records=0;
$found_production=0;
$otp_production="";

$data1=array();

$otp_data=array();

$contact_data=array();

if (isset($data->userid)){
$otp="";
$userid=$data->userid;
//$security=$data->security_otp;
$seasonid=$data->seasonid;


// $sql = "Select * from grower_sms_otp WHERE otp ='$security'
//   AND created_at > NOW() - INTERVAL 10 MINUTE; ";
// $result = $conn->query($sql);
 
//  if ($result->num_rows > 0) {

//    // output data of each row
//    while($row = $result->fetch_assoc()) {

//     $security_otp_found=$row["id"];   
    
//    }
//  }





// $user_sql1 = "DELETE FROM growers_otp where sent=0 and seasonid=$seasonid";
//  //$sql = "select * from login";
//  if ($conn->query($user_sql1)===TRUE) {

//  }



$sql = "SELECT FLOOR(RAND() * 1000000) AS otp_code;";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $otp_production=$row["otp_code"];
   
   }

 }



 $sql = "Select * from grower_inputs_distribution_otp where  otp='$otp_production' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $found_production=$row["id"];
   
   }

 }


 if ($found_production==0) {
  
   $user_sql = "INSERT INTO grower_inputs_distribution_otp(userid,otp) VALUES ($userid,'$otp_production')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $temp=array("otp"=>$otp_production);
     array_push($otp_data,$temp);

   }else{

  

   }

}else{


}


if ($found_production==0) {



$sql = "Select * from operations_contacts where  active=1 limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {


    $phone=$row["phone"];

    // $numbers="";
    // if (((int) substr($row["phone"],0,1)=="0" || (int) substr($row["phone"],0,1)==0) && $phone) {

    //   $numbers= "263".(int) substr($my_numbers,1,1).(int) substr($my_numbers,2,1).(int) substr($my_numbers,3,1)
    //                 .(int) substr($my_numbers,4,1).(int) substr($my_numbers,5,1).(int) substr($my_numbers,6,1).(int) substr($my_numbers,7,1).
    //                 (int) substr($my_numbers,8,1).(int) substr($my_numbers,9,1);
    // }else if (condition) {
    //   // code...
    // }
  

    $temp=array("to"=>$phone);
     array_push($contact_data,$temp);
   
   }

 }


  // code...

 $sql1 = "Select distinct growerid from scheme_hectares_growers join growers on growers.id=scheme_hectares_growers.growerid join scheme_hectares on scheme_hectares.id=scheme_hectares_growers.scheme_hectaresid  where  scheme_hectares.seasonid=$seasonid";
$result1 = $conn->query($sql1);

 $fetched_records=$result1->num_rows;

 if ($result1->num_rows > 0) {
   // output data of each row
   while($row1 = $result1->fetch_assoc()) {


                     $growerid=$row1["growerid"];

                      $sql = "SELECT FLOOR(RAND() * 1000000) AS otp_code;";
                      $result = $conn->query($sql);
                       
                       if ($result->num_rows > 0) {
                         // output data of each row
                         while($row = $result->fetch_assoc()) {

                          // product id
                         $otp=$row["otp_code"];
                         
                         }

                       }



                       $sql = "Select * from growers_otp where  otp='$otp' and growerid=$growerid  limit 1";
                      $result = $conn->query($sql);
                       
                       if ($result->num_rows > 0) {
                         // output data of each row
                         while($row = $result->fetch_assoc()) {

                          // product id
                         $found=$row["id"];
                         
                         }

                       }


                       if ($found==0) {
                        
                         $user_sql = "INSERT INTO growers_otp(userid,seasonid,growerid,otp) VALUES ($userid,$seasonid,$growerid,'$otp')";
                         //$sql = "select * from login";
                         if ($conn->query($user_sql)===TRUE) {
                         
                           $processed_records+=1;

                         }else{

                         // $temp=array("response"=>$conn->error);
                         // array_push($data1,$temp);

                         }

                      }else{

                      // $temp=array("response"=>"Could not Generate OTP");
                      // array_push($data1,$temp);

                      }





     
     }

   }

   }



    $temp=array("response"=>"Fetched ".$fetched_records.", Proccessed ".$processed_records,"otp"=>$otp_data,"contacts"=>$contact_data);
    array_push($data1,$temp);

}

echo json_encode($data1);

?>
