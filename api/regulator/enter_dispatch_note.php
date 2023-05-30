<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";



$data = json_decode(file_get_contents("php://input"));

$userid=0;
$name="";
$surname="";
$horse_num="";
$trailer_num="";
$created_at="";
$receiverid=0;
$seasonid=0;
$note="";


$data1=array();

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($data->userid) &&  isset($data->name) && isset($data->created_at) && isset($data->surname) && isset($data->horse_num) && isset($data->trailer_num) && isset($data->company_userid)&& isset($data->company_to_selling_pointid) && isset($data->receiverid) && isset($data->season)){

$userid=$data->userid;
$created_at=$data->created_at;
$season=$data->season;
$name=$data->name;
$surname=$data->surname;
$horse_num=$data->horse_num;
$trailer_num=$data->trailer_num;
$receiverid=$data->receiverid;
$note=$data->note;
$company_userid=$data->company_userid;
$company_to_selling_pointid=$data->company_to_selling_pointid;
$response=0;
$farm_response=0;



 $sql = "Select * from seasons where name='$season' and active=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $seasonid=$row["id"];
   
    
   }

 }




if ($seasonid>0) {
 
// checks if grower is already in database

	$sql = "INSERT INTO dispatch_note(userid,receiverid,seasonid,note,driver_name,driver_surname,horse_num ,trailer_num,company_userid,company_to_selling_pointid,created_at) VALUES ($userid,$receiverid,$seasonid,'$note','$name','$surname','$horse_num' ,'$trailer_num',$company_userid,$company_to_selling_pointid,'$created_at')";
	   //$sql = "select * from login";
	   if ($conn->query($sql)===TRUE) {
	   
	     $last_id = $conn->insert_id;

	     //$sqlitegrowerid=0;

	     $sql11 = "INSERT INTO dispatch_note_total_dispatched(dispatch_noteid,quantity,created_at) VALUES ($last_id,0,'$created_at')";
     //$sql = "select * from login";
     if ($conn->query($sql11)===TRUE) {
     
       $last_id = $conn->insert_id;

       //$sqlitegrowerid=0;

       $sql1 = "INSERT INTO dispatch_note_total_received(dispatch_noteid,quantity,created_at) VALUES ($last_id,0,'$created_at')";
     //$sql = "select * from login";
     if ($conn->query($sql1)===TRUE) {
     
       $last_id = $conn->insert_id;

       //$sqlitegrowerid=0;

       $temp=array("response"=>"success");
        array_push($data1,$temp);

            }

         }

	   }

}


}


echo json_encode($data1);



?>


