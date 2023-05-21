<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";



$data = json_decode(file_get_contents("php://input"));

$userid=0;
$growerid=0;
$grower_num="";
$lat="";
$long="";
$kg_per_ha="";
$seasonid=0;
$sqliteid=0;
$statusid=0;
$receipt_found=0;
$loan_payment_found=0;

$data1=array();




//http://192.168.1.190/gms/api/enter_hail_strike.php?userid=1&grower_num=V123456&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&percentage_strike=12333&strike_date=12333&seasonid=1&sqliteid=1

if (isset($data->receipt) && isset($data->userid)  && isset($data->season) && isset($data->created_at) && isset($data->amount)  && isset($data->grower_num)){


$userid=validate($data->userid);
$season=validate($data->season);
$grower_num=validate($data->grower_num);
$receipt=validate($data->receipt);
$created_at=validate($data->created_at);
$amount=validate($data->amount);





 $sql = "Select * from seasons where name='$season'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $seasonid=$row["id"];
   
    
   }

 }


 if ($seasonid>0) {


$sql = "Select * from growers where grower_num='$grower_num'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $growerid=$row["id"];
  
    
   }

 }



   $sql = "Select * from loan_payment_total where growerid=$growerid and seasonid=$seasonid limit 1";
    $result = $conn->query($sql);
     
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

        $loan_payment_found=1;
        
       }

     }





$sql = "Select * from loan_payments where receipt_number='$receipt' and seasonid=$seasonid and description='Cash Payment'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $receipt_found=$row["id"];
  
    
   }

 }

// then insert loan


  if ($growerid>0 && $receipt_found==0) {

   $insert_sql = "INSERT INTO loan_payments(userid,seasonid,growerid,receipt_number,amount,description,created_at) VALUES ($userid,$seasonid,$growerid,'$receipt','$amount','Cash Payment','$created_at')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
    // $last_id = $conn->insert_id;

              if ($loan_payment_found==0) {
            
                 $user_sql = "INSERT INTO loan_payment_total(userid,seasonid,growerid,amount,created_at) VALUES ($userid,$seasonid,$growerid,'$amount','$created_at')";
                   //$sql = "select * from login";
                       if ($conn->query($user_sql)===TRUE) {

                            $temp=array("response"=>"success");
                            array_push($data1,$temp);
                        
                       }

                  }else{

                      $user_sql2 = "update loan_payment_total set amount=amount+$amount where growerid = $growerid and seasonid=$seasonid";
                     //$sql = "select * from login";
                     if ($conn->query($user_sql2)===TRUE) {
                     
                        $temp=array("response"=>"success");
                       array_push($data1,$temp);

                     }else{

                      //$last_id = $conn->insert_id;
                       $temp=array("response"=>"Failed To Update");
                       array_push($data1,$temp);

                     }

                  }


        }else{

          $temp=array("response"=>"failed");
          array_push($data1,$temp);

        }


   }else{

    if ($receipt_found>0) {

     $temp=array("response"=>"receipt already captured");
      array_push($data1,$temp);

    }



    if ($growerid==0) {

     $temp=array("response"=>"Grower not found");
      array_push($data1,$temp);

    }

   
   }

 }



}




echo json_encode($data1);


?>





