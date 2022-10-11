<?php
require "conn.php";
require "validate.php";

$userid=0;
$growerid=0;
$grower_num="";
$lat="";
$long="";
$percentage_strike="";
$strike_date="";
$seasonid=0;
$sqliteid=0;
$image="";



$data=array();




//http://192.168.1.190/gms/api/enter_hail_strike.php?userid=1&grower_num=V123456&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&percentage_strike=12333&strike_date=12333&seasonid=1&sqliteid=1

if (isset($_POST['userid']) && isset($_POST['seasonid']) && isset($_POST['created_at']) && isset($_POST['sqliteid'])  && isset($_POST['grower_num']) && isset($_POST['image'])){


$userid=validate($_POST['userid']);
$seasonid=validate($_POST['seasonid']);
$grower_num=validate($_POST['grower_num']);
$created_at=validate($_POST['created_at']);
$sqliteid=validate($_POST['sqliteid']);
$image=validate($_POST['image']);



$sql = "Select * from growers where grower_num='$grower_num'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $growerid=$row["id"];
  
    
   }

 }


// then insert loan


  if ($growerid>0) {

   $insert_sql = "INSERT INTO grower_image(userid,growerid,seasonid,image,created_at
) VALUES ($userid,$growerid,$seasonid,'$image','$created_at')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
    // $last_id = $conn->insert_id;

     $temp=array("id"=>$sqliteid);
      array_push($data,$temp);

   }


   }else{

   
   }





}




echo json_encode($data);


?>





