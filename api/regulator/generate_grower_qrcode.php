<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";
require "dataSource.php";

$datasource=new DataSource();

$data = json_decode(file_get_contents("php://input"));


$userid=0;
$seasonid=0;
$description="";
$found=0;
$data1=array();

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($data->userid)  &&  isset($data->seasonid) &&  isset($data->description) ){

$userid=$data->userid;
$description=$data->description;
$seasonid=$data->seasonid;
$balesid=$data->balesid;



if ($description=="") {

$sql = "Select distinct growers.id,grower_number_of_bales.id as balesid,grower_num,bales,users.name,estimate,seasons.name as season_name from lat_long join growers on lat_long.growerid=growers.id join users on lat_long.userid=users.id join grower_number_of_bales on lat_long.growerid=grower_number_of_bales.growerid join seasons on lat_long.seasonid=seasons.id join system_estimate_prediction on growers.id=system_estimate_prediction.growerid where lat_long.seasonid=$seasonid and grower_number_of_bales.seasonid=$seasonid and system_estimate_prediction.seasonid=$seasonid  and users.id=$userid and grower_number_of_bales.id=$balesid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id

    $found=$row["balesid"];


     $temp=array("balesid"=>$row["balesid"],"growerid"=>$row["id"],"grower_num"=>$row["grower_num"],"bales"=>$row["bales"],"name"=>$row["name"],"estimate"=>$row["estimate"],"season_name"=>$row["season_name"]);
      array_push($data1,$temp);
 
 
   }

 }


}else{

$sql = "Select distinct growers.id,grower_number_of_bales.id as balesid,grower_num,bales,users.name,estimate,seasons.name as season_name from lat_long join growers on lat_long.growerid=growers.id join users on lat_long.userid=users.id join grower_number_of_bales on lat_long.growerid=grower_number_of_bales.growerid join seasons on lat_long.seasonid=seasons.id join system_estimate_prediction on growers.id=system_estimate_prediction.growerid where lat_long.seasonid=$seasonid and grower_number_of_bales.seasonid=$seasonid and system_estimate_prediction.seasonid=$seasonid  and users.id=$userid and grower_number_of_bales.id=$balesid and ( grower_num='$description' )";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

     $found=$row["balesid"];

    // product id
     $temp=array("balesid"=>$row["balesid"],"growerid"=>$row["id"],"grower_num"=>$row["grower_num"],"bales"=>$row["bales"],"name"=>$row["name"],"estimate"=>$row["estimate"],"season_name"=>$row["season_name"]);
      array_push($data1,$temp);
 
 
   }

 }

}

$value;
if ($found==0) {


$value=$datasource->encryptor("encrypt",$balesid);

  $temp=array("code"=>$value);
  array_push($data1,$temp);
}


if ($found==0) {


$value=$datasource->encryptor("decrypt",$value);

  $temp=array("back"=>$value);
  array_push($data1,$temp);
}


}else{

	  

}


echo json_encode($data1);



?>


