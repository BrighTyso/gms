<?php
require "conn.php";
require "validate.php";

$userid=0;
$growerid=0;
$grower_num="";
$lat="";
$long="";
$no_of_irr_beds="";
$no_of_dry_beds="";
$buying_seedlings_for="";
$varieties_irr="";
$varieties_dry="";
$seasonid=0;
$sqliteid=0;



$data=array();




//http://192.168.1.190/gms/api/enter_seedbeds.php?userid=1&grower_num=777&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&no_of_irr_beds=0&no_of_dry_beds=1&buying_seedlings_for=0&varieties_irr=0&varieties_dry=0&seasonid=1&sqliteid=1

if (isset($_GET['no_of_irr_beds']) && isset($_GET['userid'])  && isset($_GET['latitude'])  && isset($_GET['longitude'])  && isset($_GET['no_of_dry_beds']) && isset($_GET['seasonid']) && isset($_GET['buying_seedlings_for']) && isset($_GET['created_at']) && isset($_GET['sqliteid']) && isset($_GET['varieties_irr']) && isset($_GET['varieties_dry']) && isset($_GET['grower_num'])){


$userid=validate($_GET['userid']);
$seasonid=validate($_GET['seasonid']);
$lat=validate($_GET['latitude']);
$long=validate($_GET['longitude']);
$grower_num=validate($_GET['grower_num']);
$no_of_irr_beds=validate($_GET['no_of_irr_beds']);
$no_of_dry_beds=validate($_GET['no_of_dry_beds']);
$buying_seedlings_for=validate($_GET['buying_seedlings_for']);
$varieties_irr=validate($_GET['varieties_irr']);
$varieties_dry=validate($_GET['varieties_dry']);
$created_at=validate($_GET['created_at']);
$sqliteid=validate($_GET['sqliteid']);



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

   $insert_sql = "INSERT INTO seed_beds(userid,growerid,seasonid,latitude,longitude,no_of_irr_beds,no_of_dry_beds,buying_seedlings_for,varieties_irr,varieties_dry,created_at) VALUES ($userid,$growerid,$seasonid,'$lat','$long',$no_of_irr_beds,$no_of_dry_beds,$buying_seedlings_for,'$varieties_irr','$varieties_dry','$created_at')";
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





