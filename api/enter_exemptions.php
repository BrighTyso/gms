<?php
require "conn.php";
require "validate.php";

$userid=0;
$exemption_date="";
$description="";
$seasonid="";
$created_at="";
$sqliteid=0;


$data=array();


//http://192.168.1.190/gms/api/roadblocks.php?userid=1&latitude=12.3444&longitude1.89000=&seasonid=1&time=12:30&created_at=2022-06-12

if (isset($_GET['userid'])  && isset($_GET['exemption_date'])  && isset($_GET['description'])  && isset($_GET['seasonid']) && isset($_GET['sqliteid'])){


$userid=validate($_GET['userid']);
//$growerid=validate($_POST['growerid']);
$exemption_date=validate($_GET['exemption_date']);
$description=validate($_GET['description']);
$seasonid=validate($_GET['seasonid']);
$created_at=validate($_GET['created_at']);
$sqliteid=validate($_GET["sqliteid"]);


$sql = "INSERT INTO exemptions(userid,seasonid,exemption_date,description,created_at) VALUES ($userid,$seasonid,'$exemption_date','$description','$created_at')";


   //$gr = "select * from login";
   if ($conn->query($sql)===TRUE) {
   
     //$last_id = $conn->insert_id;

    $temp=array("id"=>$sqliteid);
    array_push($data,$temp);


   }



}


echo json_encode($data);


?>





