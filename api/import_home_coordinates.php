<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
//require "validate.php";



$data = json_decode(file_get_contents("php://input"));

$userid=0;
$grower_num="";
$created_at="";
$growerid=0;
$seasonid=0;

$data1=array();

$lat="";
$long="";

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($data->userid) &&  isset($data->grower_num)  && isset($data->latitude) && isset($data->longitude)){

try {
  






$userid=$data->userid;
$grower_num=$data->grower_num;
$created_at=$data->created_at;
$seasonid=$data->seasonid;
$lat=$data->latitude;
$long=$data->longitude;

$response=0;
$farm_response=0;



if ($seasonid>0 && $userid>0) {
 
// checks if grower is already in database

$sql = "Select growers.id from growers  where  grower_num='$grower_num'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $response=1;
   $growerid=$row["id"];
   
    
   }

 }





//check farm
$sql1 = "Select id from lat_long  where  growerid=$growerid and seasonid=$seasonid";
$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $farm_response=1;
  // $growerid=$row["id"];
   
    
   }

 }



 if ($growerid>0 && $farm_response==0){

	$grower_farm_sql = "INSERT INTO lat_long(userid,growerid,latitude,longitude,seasonid,created_at) VALUES ($userid,$growerid,'$lat','$long',$seasonid,'$created_at')";
	   //$sql = "select * from login";
	   if ($conn->query($grower_farm_sql)===TRUE) {
	   
	     $last_id = $conn->insert_id;

	     //$sqlitegrowerid=0;

	     $temp=array("response"=>"success");
        array_push($data1,$temp);

	   }else{

	   $temp=array("response"=>$conn->error);
     array_push($data1,$temp);

	   }

}else{
  $temp=array("response"=>"Not Found Or Created Already");
     array_push($data1,$temp);
}

}


} catch (Exception $e) {
  
 $temp=array("response"=>"error");
 array_push($data1,$temp);

}


}else{

	$temp=array("response"=>"Field Empty");
     array_push($data1,$temp);
}




echo json_encode($data1);



?>


