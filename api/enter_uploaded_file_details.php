<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$name="";
$found=0;
$disbusment_quantity=0;

$response=array();

if (isset($data->seasonid) && isset($data->userid) && isset($data->grower_num)){


$seasonid=$data->seasonid;
$userid=$data->userid;
$grower_num=$data->grower_num;
$growerid=0;
$created_at=$data->created_at;
$description=$data->description;
$file_type=$data->file_type;
$location_url=$data->location_url;
$datetimes="";

$otp_found=0;

$grower_id=0;


    $sql = "Select * from growers where grower_num='$grower_num'";
    $result = $conn->query($sql);
     
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) { 
       
       $growerid=$row["id"];
          
       }

     }


     $user_sql = "INSERT INTO file_manager(userid,seasonid,growerid,location_url,description,file_type,storages,created_at,datetimes) VALUES ($userid,$seasonid,$growerid,'$location_url','$description','$file_type','Drive','$created_at','$datetimes')";
     //$sql = "select * from login";
     if ($conn->query($user_sql)===TRUE) {
     
       $last_id = $conn->insert_id;
       $temp=array("response"=>"success");
       array_push($response,$temp);
       
     }else{

     $temp=array("response"=>$conn->error);
     array_push($response,$temp);

     }

}else{


$temp=array("response"=>"Field Empty");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





