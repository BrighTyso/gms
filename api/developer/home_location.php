<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");
//require "validate.php";

require "../datasource.php";


$datasource=new CompanyCode();


$data = json_decode(file_get_contents("php://input"));

$userid="";
$grower_num="";
$sqlitegrowerid=0;
$lat_longid=0;
$hectares="";
$statusid=0;
$seasonid=0;
$season="";
$created_at="";

$data1=array();

$lat="";
$long="";

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($data->userid)  && isset($data->grower_num) && isset($data->latitude) && isset($data->longitude) && isset($data->sqliteid)){


$userid=$datasource->encryptor("decrypt",$data->userid);

echo $userid;
//$userid=$data->userid;
$grower_num=$data->grower_num;
$season=$data->season;
//$sqlitegrowerid=$data->sqlitegrowerid;
$lat_longid=$data->sqliteid;
//$hectares=$data->hectares;
$created_at=date("Y-m-d");


$lat=$data->latitude;
$long=$data->longitude;

$response=0;
$growerid=0;



$sql = "Select status from regulator_sync_status where status=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $statusid=$row["status"];
   
    
   }

 }




 $sql = "Select * from seasons where active=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $seasonid=$row["id"];
   
    
   }

 }


 if ($statusid>0 && $seasonid>0) {
// code...


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



 if ($response==1){

    $response=0;

		$sql = "Select id from lat_long where growerid=$growerid and seasonid=$seasonid";
		$result = $conn->query($sql);
		 
		 if ($result->num_rows > 0) {
		   // output data of each row
		   while($row = $result->fetch_assoc()) {

		    // product id
		   $response=1;
		   
		   }

		 }



  if ($response==0) {
  

	$lat_long_sql = "INSERT INTO lat_long(userid,growerid,latitude,longitude,seasonid,hectares,created_at) VALUES ($userid,$growerid,'$lat','$long',$seasonid,'$hectares','$created_at')";
	   //$sql = "select * from login";
	   if ($conn->query($lat_long_sql)===TRUE) {
	   
	     $last_id = $conn->insert_id;

	     //$sqlitegrowerid=0;

	     $temp=array("sqliteid"=>$lat_longid);
        array_push($data1,$temp);

	   }

    }

    //else{

   //  $user_sql = "update lat_long set latitude='$lat' and longitude='$long' where growerid=$growerid and seasonid=$seasonid";
   // //$sql = "select * from login";
   // if ($conn->query($user_sql)===TRUE) {
   
   //   $temp=array("growerid"=>$sqlitegrowerid,"lat_longid"=>$lat_longid);
   //   array_push($data,$temp);

   // }


   }


}


}else{

	
}



echo json_encode($data1);



?>


