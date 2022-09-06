<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";

$data = json_decode(file_get_contents("php://input"));


$data1=array();


$userid=$data->userid;
$name=$data->name;
$surname=$data->surname;
$grower_num=$data->grower_num;
$area=$data->area;
$province=$data->province;
$phone=$data->phone;
$id_num=$data->id_num;
$created_at=$data->created_at;
//$hectares=$data->hectares;
$seasonid=$data->seasonid;



//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($userid) && isset($name)  && isset($surname)  && isset($grower_num)  && isset($area)  &&  isset($province)  && isset($phone)  && isset($id_num)   && isset($created_at)){

$response=0;
$growerid=0;


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



if ($response==0) {


	$grower_sql = "INSERT INTO growers(userid,name,surname,grower_num,area,province,phone,id_num,seasonid,created_at) VALUES ($userid,'$name','$surname','$grower_num','$area','$province','$phone','$id_num',$seasonid,'$created_at')";
   //$sql = "select * from login";
   if ($conn->query($grower_sql)===TRUE) {
   
     $last_id = $conn->insert_id;
     

	   	$temp=array("response"=>"success");
      array_push($data1,$temp);


   }

 }

}



echo json_encode($data1);



?>


