<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";



$data = json_decode(file_get_contents("php://input"));


$userid=0;
$grower_num="";
$created_at="";
$sqliteid=0;
$growerid=0;
$seasonid=0;
$estimate=0;
$varieties="";
$statusid=0;

$data1=array();



//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($data->userid)  && isset($data->grower_num) && isset($data->created_at) && isset($data->sqliteid)  && isset($data->bales) && isset($data->seasonid)){

$userid=$data->userid;
$grower_num=$data->grower_num;
$created_at=$data->created_at;
$seasonid=$data->seasonid;
$sqliteid=$data->sqliteid;
$bales=$data->bales;


$response=0;
$farm_response=0;




  if ($seasonid>0) {

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
$sql1 = "Select id from grower_number_of_bales  where  growerid=$growerid and seasonid=$seasonid";
$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $farm_response=1;
  // $growerid=$row["id"];

   }

 }



 if ($response==1 && $farm_response==0){

	$grower_farm_sql = "INSERT INTO grower_number_of_bales(userid,seasonid,growerid,bales,created_at) VALUES ($userid,$seasonid,$growerid,$bales,'$created_at')";
	   //$sql = "select * from login";
	   if ($conn->query($grower_farm_sql)===TRUE) {
	   
	     //$last_id = $conn->insert_id;

	     //$sqlitegrowerid=0;

	     $temp=array("sqliteid"=>$sqliteid);
        array_push($data1,$temp);

	   }else{

      echo $conn->error;
     
     }

}

}else{



}


}else{

	  

}


echo json_encode($data1);



?>


