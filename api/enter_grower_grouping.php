<?php
require "conn.php";
require "validate.php";

$userid=0;
$taskid=0;
$grower_num="";
$lat="";
$long="";
$percentage_strike="";
$strike_date="";
$seasonid=0;
$sqliteid=0;
$task_found=0;
$growerid=0;


$data=array();




//http://192.168.1.190/gms/api/enter_hail_strike.php?userid=1&grower_num=V123456&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&percentage_strike=12333&strike_date=12333&seasonid=1&sqliteid=1

if (isset($_GET['seasonid']) && isset($_GET['userid']) && isset($_GET['created_at']) && isset($_GET['sqliteid']) ){


$userid=validate($_GET['userid']);
$seasonid=validate($_GET['seasonid']);
$created_at=validate($_GET['created_at']);
$sqliteid=validate($_GET['sqliteid']);
$grower_num=validate($_GET['grower_num']);
$description=validate($_GET['description']);

$field_officerid=validate($_GET['field_officerid']);
$grower_groupid=0;

$growerid=0;




$sql = "Select * from growers where grower_num='$grower_num'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $growerid=$row["id"];
  
    
   }

 }


 $sql = "Select * from grower_groups where description='$description'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $grower_groupid=$row["id"];
  
    
   }

 }

 


  if ($growerid>0 && $grower_groupid>0) {

   $insert_sql = "INSERT INTO grower_field_officer_groups(userid,seasonid,growerid,field_officerid,grower_groupid,created_at) VALUES ($userid,$seasonid,$growerid,$field_officerid,$grower_groupid,'$created_at')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
    // $last_id = $conn->insert_id;
       
        // $last_id = $conn->insert_id;

         $temp=array("id"=>$sqliteid);
          array_push($data,$temp);

      }else{

        // $temp=array("id"=>$conn->error);
        //   array_push($data,$temp);
      }


   }else{

   
   
   }



}




echo json_encode($data);


?>





