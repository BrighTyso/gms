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
$data1=array();

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($data->userid)  &&  isset($data->seasonid) &&  isset($data->description) ){

$userid=$data->userid;
$description=$data->description;
$seasonid=$data->seasonid;
$auction_rights=0;


  $sql1 = "Select id from auction_rights  where  companyid=$userid and seasonid=$seasonid";
  $result = $conn->query($sql1);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // product id
      $auction_rights=$row["id"];

     }

   }







if ($auction_rights==0) {
  



if ($description=="") {

$sql = "Select distinct growers.id,grower_number_of_bales.id as balesid,grower_num,bales,users.name,estimate,seasons.name as season_name from grower_number_of_bales join growers on grower_number_of_bales.growerid=growers.id join users on grower_number_of_bales.userid=users.id  join seasons on grower_number_of_bales.seasonid=seasons.id join system_estimate_prediction on growers.id=system_estimate_prediction.growerid where grower_number_of_bales.seasonid=$seasonid and grower_number_of_bales.seasonid=$seasonid and system_estimate_prediction.seasonid=$seasonid  and users.id=$userid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {


      $value=$datasource->encryptor("encrypt",$row["balesid"]);
      $temp=array("balesid"=>$value,"growerid"=>$row["id"],"grower_num"=>$row["grower_num"],"bales"=>$row["bales"],"name"=>$row["name"],"estimate"=>$row["estimate"],"season_name"=>$row["season_name"]);
      array_push($data1,$temp);
 
 
   }

 }


}else{

$sql = "Select distinct growers.id,grower_number_of_bales.id as balesid,grower_num,bales,users.name,estimate,seasons.name as season_name from grower_number_of_bales join growers on grower_number_of_bales.growerid=growers.id join users on grower_number_of_bales.userid=users.id  join seasons on grower_number_of_bales.seasonid=seasons.id join system_estimate_prediction on growers.id=system_estimate_prediction.growerid where grower_number_of_bales.seasonid=$seasonid and grower_number_of_bales.seasonid=$seasonid and system_estimate_prediction.seasonid=$seasonid  and users.id=$userid and ( grower_num='$description' )";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    $value=$datasource->encryptor("encrypt",$row["balesid"]);
     $temp=array("balesid"=>$value,"growerid"=>$row["id"],"grower_num"=>$row["grower_num"],"bales"=>$row["bales"],"name"=>$row["name"],"estimate"=>$row["estimate"],"season_name"=>$row["season_name"]);
      array_push($data1,$temp);
 
 
   }

 }
}


}else{


if ($description=="") {

$sql = "Select distinct growers.id,grower_number_of_bales.id as balesid,grower_num,bales,users.name,estimate,seasons.name as season_name from grower_number_of_bales join growers on grower_number_of_bales.growerid=growers.id join users on grower_number_of_bales.userid=users.id  join seasons on grower_number_of_bales.seasonid=seasons.id join system_estimate_prediction on growers.id=system_estimate_prediction.growerid  join auction_growers on auction_growers.growerid=growers.id where grower_number_of_bales.seasonid=$seasonid and grower_number_of_bales.seasonid=$seasonid and system_estimate_prediction.seasonid=$seasonid  and auction_growers.seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {


      $value=$datasource->encryptor("encrypt",$row["balesid"]);
      $temp=array("balesid"=>$value,"growerid"=>$row["id"],"grower_num"=>$row["grower_num"],"bales"=>$row["bales"],"name"=>"Auction","estimate"=>$row["estimate"],"season_name"=>$row["season_name"]);
      array_push($data1,$temp);
 
 
   }

 }


}else{

$sql = "Select distinct growers.id,grower_number_of_bales.id as balesid,grower_num,bales,users.name,estimate,seasons.name as season_name from grower_number_of_bales join growers on grower_number_of_bales.growerid=growers.id join users on grower_number_of_bales.userid=users.id  join seasons on grower_number_of_bales.seasonid=seasons.id join system_estimate_prediction on growers.id=system_estimate_prediction.growerid  join auction_growers on auction_growers.growerid=growers.id where grower_number_of_bales.seasonid=$seasonid and grower_number_of_bales.seasonid=$seasonid and system_estimate_prediction.seasonid=$seasonid  and auction_growers.seasonid=$seasonid and ( grower_num='$description' )";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    $value=$datasource->encryptor("encrypt",$row["balesid"]);
     $temp=array("balesid"=>$value,"growerid"=>$row["id"],"grower_num"=>$row["grower_num"],"bales"=>$row["bales"],"name"=>"Auction","estimate"=>$row["estimate"],"season_name"=>$row["season_name"]);
      array_push($data1,$temp);
 
 
   }

 }
}



}


}else{

	  

}


echo json_encode($data1);



?>


