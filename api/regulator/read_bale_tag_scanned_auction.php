<?php


require_once("conn.php");
require "validate.php";
require "dataSource.php";

$datasource=new DataSource();



$userid=0;
$seasonid=0;
$barcode="";
$data1=array();

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($_POST['barcode'])){

$barcode=$_POST['barcode'];



if ($barcode!="") {


  $try="grower_number_of_bales.userid as companyid ,grower_number_of_bales.id,growers.id as growerid,grower_num,bales,growers.name as grower_name,growers.surname,id_num, users.name,seasons.name as season_name from grower_number_of_bales join mapped_hectares on mapped_hectares.growerid=grower_number_of_bales.growerid join users on mapped_hectares.userid=users.id join growers on growers.id=grower_number_of_bales.growerid join seasons on seasons.id=grower_number_of_bales.seasonid where seasons.active=1 and code.id=$barcode limit 1";




$sql = "Select distinct bale_tags.id,bale_tags.code,bale_tags.created_at,grower_number_of_bales.userid,used,grower_number_of_balesid from bale_tags join grower_number_of_bales on  bale_tags.grower_number_of_balesid=grower_number_of_bales.id join seasons on seasons.id=grower_number_of_bales.seasonid join auction_growers on auction_growers.userid=grower_number_of_bales.userid  where code='$barcode' and active=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {


     $value=$datasource->encryptor("encrypt",$row["grower_number_of_balesid"]);

     
      $temp=array("id"=>$row["id"],"code"=>$row["code"],"created_at"=>$row["created_at"],"used"=>$row["used"],"qrcode"=>$value,"companyid"=>$row["userid"],"name"=>"Auction");
      array_push($data1,$temp);
 

   }

 }


}


}



echo json_encode($data1);



?>


