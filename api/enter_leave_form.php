<?php
require "conn.php";
require "validate.php";

$userid=0;
$growerid=0;
$grower_num="";
$lat="";
$long="";
$kg_per_ha="";
$seasonid=0;
$sqliteid=0;



$data=array();




//http://192.168.1.190/gms/api/enter_hail_strike.php?userid=1&grower_num=V123456&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&percentage_strike=12333&strike_date=12333&seasonid=1&sqliteid=1

if (isset($_GET['userid']) && isset($_GET['seasonid']) && isset($_GET['created_at']) && isset($_GET['sqliteid'])  ){


$userid=validate($_GET['userid']);
$seasonid=validate($_GET['seasonid']);
$created_at=validate($_GET['created_at']);
$sqliteid=validate($_GET['sqliteid']);


$leave_type=$_GET['leave_type'];
$description=$_GET['description'];
$days=$_GET['days'];
// $state=$_GET['state'];
$leave_date=$_GET['leave_date'];



$leave_form_found=0;

$sql = "Select * from leave_form where leave_type='$leave_type' and description='$description' and days=$days and leave_date='$leave_date' and created_at='$created_at' and seasonid=$seasonid and userid=$userid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $leave_form_found=$row["id"];
  
    
   }

 }

// then insert loan


  if ($leave_form_found==0) {

   $insert_sql = "INSERT INTO leave_form(userid,seasonid,leave_type,description,days,leave_date, created_at) VALUES ($userid,$seasonid,'$leave_type','$description','$days','$leave_date','$created_at')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
          $temp=array("id"=>$sqliteid);
          array_push($data,$temp);

   }else{

    $temp=array("id"=>$conn->error);
    array_push($data,$temp);
    
   }


   }



}




echo json_encode($data);


?>





