<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";



$data = json_decode(file_get_contents("php://input"));

$userid="";
$grower_num="";
$created_at="";
$sqliteid=0;
$growerid=0;
$seasonid=0;

$grower_age=0;
$grower_sex=0;
$number_of_works=0;
$income_per_month=0;
$number_of_kids=0;
$statusid=0;

$data1=array();



//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($data->userid) &&  isset($data->grower_num) && isset($data->created_at) && isset($data->grower_sex) && isset($data->grower_age) && isset($data->number_of_works) && isset($data->income_per_month) && isset($data->number_of_kids) && isset($data->sqliteid)){

$userid=$data->userid;
$grower_num=$data->grower_num;
$created_at=$data->created_at;
$season=$data->season;
$sqliteid=$data->sqliteid;

$grower_age=$data->grower_age;
$grower_sex=$data->grower_sex;
$number_of_works=$data->number_of_works;
$income_per_month=$data->income_per_month;
$number_of_kids=$data->number_of_kids;

$response=0;
$farm_response=0;



$sql = "Select status from regulator_sync_status where status=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $statusid=$row["status"];
   
    
   }

 }





 $sql = "Select * from seasons where name='$season' and active=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $seasonid=$row["id"];
   
    
   }

 }



if ($statusid>0 && $seasonid>0) {

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
$sql1 = "Select id from data_collection  where  growerid=$growerid and seasonid=$seasonid";
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

	$grower_farm_sql = "INSERT INTO data_collection(userid,seasonid,growerid,grower_age,grower_sex,number_of_works,income_per_month,number_of_kids,created_at) VALUES ($userid,$seasonid,$growerid,$grower_age,$grower_sex,$number_of_works,$income_per_month,$number_of_kids,'$created_at')";
	   //$sql = "select * from login";
	   if ($conn->query($grower_farm_sql)===TRUE) {
	   
	     $last_id = $conn->insert_id;

	     //$sqlitegrowerid=0;

	     $temp=array("sqliteid"=>$sqliteid);
        array_push($data1,$temp);

	   }else{

	    

	   }

}

}


}else{

	
}


echo json_encode($data1);



?>


