<?php
// header("Access-Control-Allow-Origin: *");
// header("Content-Type:application/json");
// header("Access-Control-Allow-Origin-Methods:POST");
// header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";
require "datasource.php";

$datasource=new DataSource();


//$data = json_decode(file_get_contents("php://input"));


$userid=0;
$seasonid=0;
$description="";
$growerid=0;
$grower_bales=0;
$data1=array();

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($_GET['qrcode']) &&  isset($_GET['userid'])  &&  isset($_GET['growerid']) &&  isset($_GET['bales']) &&  isset($_GET['season']) &&  isset($_GET['created_at']) &&  isset($_GET['latitude']) &&  isset($_GET['longitude'])){

 

$qrcode=$_GET['qrcode'];
$bales=$_GET['bales'];
$growerid=$_GET['growerid'];
$season=$_GET['season'];
$created_at=$_GET['created_at'];
$userid=$_GET['userid'];
$latitude=$_GET['latitude'];
$longitude=$_GET['longitude'];
$value=$datasource->encryptor("decrypt",$qrcode);


$response=0;
$farm_response=0;



$sql = "Select seasons.id from seasons  where  name='$season'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $response=1;
   $seasonid=$row["id"];

 
 
   }

 }



if ($seasonid>0) {

// checks if grower is already in database


$sql1 = "Select id from grower_number_of_bales  where  growerid=$growerid and seasonid=$seasonid and id=$value and bales>0 and bales>=$bales";
$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // product id
    $grower_bales=1;
  

   }

 }



 if ($response==1 && $grower_bales==1){



   $grower_bales = "INSERT INTO ready_for_booking(userid,grower_number_of_balesid,bales,latitude,longitude,created_at) VALUES ($userid,$value,$bales,'$latitude','$longitude','$created_at')";
     
     if ($conn->query($grower_bales)===TRUE) {
     
        $user_sql1 = "update grower_number_of_bales set bales=bales-$bales where id=$value";
           //$sql = "select * from login";
           if ($conn->query($user_sql1)===TRUE) {

            $temp=array("response"=>"success");
            array_push($data1,$temp);

            }

       }

   }

 }

}


echo json_encode($data1);



?>


