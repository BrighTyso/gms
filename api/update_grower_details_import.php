<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";

$data = json_decode(file_get_contents("php://input"));

$response=array();


$growerid=0;




//http://192.168.1.190/gms/api/enter_hail_strike.php?userid=1&grower_num=V123456&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&percentage_strike=12333&strike_date=12333&seasonid=1&sqliteid=1

if (isset($data->userid)  && isset($data->area)  && isset($data->province) && isset($data->phone)  && isset($data->grower_num)){


$userid=validate($data->userid);
$area=validate($data->area);
$province=validate($data->province);
$phone=validate($data->phone);
$grower_num=validate($data->grower_num);

$name=validate($data->name);
$surname=validate($data->surname);
$id_num=validate($data->id_num);





$sql = "Select * from growers where grower_num='$grower_num'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $growerid=$row["id"];
  
    
   }

 }
 


// then insert loan


  if ($growerid>0) {

        $user_sql2 = "update growers set area='$area' , province='$province',phone='$phone',name='$name',surname='$surname',id_num='$id_num'  where id = $growerid ";
             //$sql = "select * from login";
             if ($conn->query($user_sql2)===TRUE) {
             
            $temp=array("response"=>"success");
               array_push($response,$temp);

             }else{

              //$last_id = $conn->insert_id;
               $temp=array("response"=>"Failed To Update");
               array_push($response,$temp);

             }

   }else{

   
   }



}




echo json_encode($response);


?>





