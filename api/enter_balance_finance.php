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
$working_capital_found=0;
$active_grower_found=0;
$rollover_seasonid=0;
$active_balanced_finances=0;

$data1=array();




//http://192.168.1.190/gms/api/enter_hail_strike.php?userid=1&grower_num=V123456&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&percentage_strike=12333&strike_date=12333&seasonid=1&sqliteid=1

if (isset($data->userid)  && isset($data->seasonid) && isset($data->created_at) && isset($data->grower_num)){


$userid=validate($data->userid);
$seasonid=validate($data->seasonid);
$grower_num=validate($data->grower_num);
$created_at=validate($data->created_at);
$year=validate($data->year);
$balance_b_f=validate($data->balance_b_f);
$stop_order=validate($data->stop_order);
$payments=validate($data->payments);
$outstanding=validate($data->outstanding);
$interest=validate($data->interest);
$balance=validate($data->balance);




 if ($seasonid>0) {


    $sql = "Select * from growers where grower_num='$grower_num'";
    $result = $conn->query($sql);
     
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
       
       $growerid=$row["id"];
      
        
       }

     }



    $sql = "Select * from active_balanced_finances where growerid=$growerid";
    $result = $conn->query($sql);
     
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
       
       $active_balanced_finances=$row["id"];
      
        
       }

     }



    $sql = "Select * from balanced_finances where growerid=$growerid and year='$year'";
    $result = $conn->query($sql);
     
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
       
       $receipt_found=$row["id"];
      
        
       }

     }

// then insert loan


  if ($growerid>0 && $receipt_found==0) {

   $insert_sql = "INSERT INTO balanced_finances(growerid,seasonid,userid,year,balance_b_f,stop_order,payments,outstanding,interest,balance,created_at) VALUES ($growerid,$seasonid,$userid,'$year',$balance_b_f,$stop_order,$payments,$outstanding,$interest,$balance,'$created_at')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
    // $last_id = $conn->insert_id;

    if ($active_balanced_finances==0) {

        $user_sql1 = "INSERT INTO active_balanced_finances(growerid,seasonid,userid,created_at) VALUES ($growerid,$seasonid,$userid,'$created_at')";
   //$sql = "select * from login";
             if ($conn->query($user_sql1)===TRUE) {
             
               $last_id = $conn->insert_id;
                $temp=array("response"=>"success");
                array_push($data1,$temp);

             }else{

             $temp=array("response"=>$conn->error);
             array_push($data1,$temp);

             }

    }else{

      $temp=array("response"=>"success");
      array_push($data1,$temp);

    }

      


    }else{

    $temp=array("response"=>$conn->error);
   array_push($data1,$temp);

  }


   }else{

    if ($receipt_found>0) {

     $temp=array("response"=>"already captured");
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





