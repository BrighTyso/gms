<?php
require "conn.php";

$data=array();

//http://192.168.1.190/gms/api/get_products.php

$username=$_GET["username"];
$hash=md5($_GET["hash"]);
$trucknumber=$_GET["trucknumber"];
$userid=0;
$seasonid=0;
$rule=0;



if ($username!="" && $hash!="") {
  

$sql = "Select * from users where hash='$hash' and  username='$username' and  active=1 limit 1";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $userid=$row["id"];



   }
 }




if ($userid>0) {

        $sql = "Select truck_destination.id,truck_destination.userid,truck_destination.trucknumber,truck_destination.driver_name,truck_destination.driver_surname,truck_destination.destination,truck_destination.close_open,truck_destination.created_at ,disbursement.id as disbursementid,disbursement.disbursement_trucksid,disbursement.productid,disbursement.storeid,disbursement.quantity from truck_destination join disbursement on disbursement.disbursement_trucksid=truck_destination.id join truck_disbursment_sync_active on truck_disbursment_sync_active.disbursement_trucksid=truck_destination.id join user_to_truck_disbursment on user_to_truck_disbursment.disbursement_trucksid=truck_destination.id where (trucknumber='$trucknumber' or truck_destination.id=$trucknumber) and truck_disbursment_sync_active.active=1 and  user_to_truck_disbursment.truck_userid=$userid and truck_destination.close_open=1";
        $result = $conn->query($sql);
         
         if ($result->num_rows > 0) {
           // output data of each row
           while($row = $result->fetch_assoc()) {
            // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
            $temp=array("id"=>$row["id"],"userid"=>$row["userid"],"trucknumber"=>$row["trucknumber"],"destination"=>$row["destination"]
              ,"driver_name"=>$row["driver_name"],"driver_surname"=>$row["driver_surname"],"close_open"=>$row["close_open"],"storeid"=>$row["storeid"],"created_at"=>$row["created_at"],"disbursement_trucksid"=>$row["disbursement_trucksid"],"productid"=>$row["productid"],"quantity"=>$row["quantity"]);
            array_push($data,$temp);
            
           }
         }
}





}

 echo json_encode($data); 



?>