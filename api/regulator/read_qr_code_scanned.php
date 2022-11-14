<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";
require "datasource.php";

$datasource=new DataSource();



$data = json_decode(file_get_contents("php://input"));


$userid=0;
$seasonid=0;
$description="";
$qrcode="";
$data1=array();

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($data->qrcode)){

$qrcode=$data->qrcode;

 $value=$datasource->encryptor("decrypt",$qrcode);


if ($qrcode!="") {

$sql = "Select distinct grower_number_of_bales.id,growers.id as growerid,grower_num,bales,growers.name as grower_name,growers.surname,id_num, users.name,seasons.name as season_name from grower_number_of_bales join mapped_hectares on mapped_hectares.growerid=grower_number_of_bales.growerid join users on mapped_hectares.userid=users.id join growers on growers.id=grower_number_of_bales.growerid join seasons on seasons.id=grower_number_of_bales.seasonid where seasons.active=1 and grower_number_of_bales.id=$value";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

     
      $temp=array("id"=>$row["id"],"growerid"=>$row["growerid"],"grower_num"=>$row["grower_num"],"bales"=>$row["bales"],"company_name"=>$row["name"],"season_name"=>$row["season_name"] ,"grower_name"=>$row["grower_name"] ,"surname"=>$row["surname"] ,"id_num"=>$row["id_num"]);
      array_push($data1,$temp);
 
 
   }

 }


}


}


echo json_encode($data1);



?>


