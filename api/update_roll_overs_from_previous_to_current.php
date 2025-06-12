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

$userid=validate($data->userid);
$season=validate($data->season);
$grower_num=validate($data->grower_num);
$rollover_seasonid=validate($data->rollover_seasonid);

$data1=array();



$sql11 = "Select distinct * from  growers join  where  (grower_num='$grower_num')";
  $result1 = $conn->query($sql11);
 
 if ($result1->num_rows > 0) {
   // output data of each row
   while($row1 = $result1->fetch_assoc()) {

      $growerid=$row1["id"];

   }
 }




$sql1 = "Select distinct * from balanced_finances where growerid=$growerid order by year desc limit 1";
  $result1 = $conn->query($sql1);
   
   if ($result1->num_rows > 0) {
     // output data of each row
     while($row = $result1->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);

   $temp=array("stop_order"=>$row["stop_order"],"balance_b_f"=>$row["balance_b_f"],"payments"=>$row["payments"],"outstanding"=>$row["outstanding"],"interest"=>$row["interest"],"balance"=>$row["balance"],"year"=>$row["year"]);
    array_push($data1,$temp);


    $amount=$row["balance"];
   

  if ($amount>0){


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


 $sql = "Select * from active_growers where growerid=$growerid and seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $active_grower_found=$row["id"];
  
    
   }

 }


  $sql = "Select * from rollover_total where growerid=$growerid and seasonid=$seasonid limit 1";
    $result = $conn->query($sql);
     
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

        $working_capital_found=1;
        
       }

     }




    $sql = "Select * from rollover where growerid=$growerid and seasonid=$seasonid and rollover_seasonid=$rollover_seasonid";
    $result = $conn->query($sql);
     
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
       
       $receipt_found=$row["id"];
      
        
       }

     }

    // then insert loan


  if ($growerid>0 && $receipt_found==0 && $seasonid!=$rollover_seasonid) {

   $insert_sql = "INSERT INTO rollover(userid,seasonid,growerid,rollover_seasonid,amount) VALUES ($userid,$seasonid,$growerid,$rollover_seasonid,'$amount')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
    // $last_id = $conn->insert_id;

       if ($working_capital_found==0) {
            
                 $user_sql = "INSERT INTO rollover_total(userid,seasonid,growerid,amount) VALUES ($userid,$seasonid,$growerid,'$amount')";
                   //$sql = "select * from login";
                       if ($conn->query($user_sql)===TRUE) {

                            if ($active_grower_found==0) {
                              $user_sql = "INSERT INTO active_growers(userid,growerid,seasonid) VALUES ($userid,$growerid,$seasonid)";
                       //$sql = "select * from login";
                           if ($conn->query($user_sql)===TRUE) {

                            $temp=array("response"=>"success");
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

                      $user_sql2 = "update rollover_total set amount=amount+$amount where growerid = $growerid and seasonid=$seasonid";
                     //$sql = "select * from login";
                     if ($conn->query($user_sql2)===TRUE) {
                     
                                 if ($active_grower_found==0) {
                                      $user_sql = "INSERT INTO active_growers(userid,growerid,seasonid) VALUES ($userid,$growerid,$seasonid)";
                               //$sql = "select * from login";
                                   if ($conn->query($user_sql)===TRUE) {

                                    $temp=array("response"=>"success");
                                    array_push($data1,$temp);

                                   }
                                }else{
                                   $temp=array("response"=>"success");
                                    array_push($data1,$temp);
                                }

                     }else{

                      //$last_id = $conn->insert_id;
                       $temp=array("response"=>"Failed To Update");
                       array_push($data1,$temp);

                     }

                  }


        }else{

          $temp=array("response"=>$conn->error);
         array_push($data1,$temp);

        }


   }else{

    if ($receipt_found>0) {

     $temp=array("response"=>"RollOver already captured");
      array_push($data1,$temp);

    }



    if ($growerid==0) {

     $temp=array("response"=>"Grower not found");
      array_push($data1,$temp);

    }

   
   }

 }



}

}
}




echo json_encode($data1);


?>





