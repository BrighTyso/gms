<?php
require_once("conn.php");
require "validate.php";
require "dataSource.php";

$datasource=new DispatchNote();


$response=array();

if(isset($_POST["qrcode"]) ) {


$qrcode=$_POST["qrcode"];
$userid=$_POST["userid"];
$latitude=$_POST["latitude"];
$longitude=$_POST["longitude"];


$value=$datasource->encryptor("decrypt",$qrcode);


   $insert_sql = "INSERT INTO dispatch_note_check_point(userid,dispatch_noteid,latitude,longitude) VALUES ($userid,$value,'$latitude','$longitude')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
    // $last_id = $conn->insert_id;

     $temp=array("response"=>"success");
      array_push($response,$temp);

   }

	
}





echo json_encode($response);


?>