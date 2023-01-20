<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$id=0;
$userid=0;


$data1=array();


if (isset($data->id) && isset($data->userid)){

$id=$data->id;
$userid=$data->userid;



  $user_sql1 = "DELETE FROM bale_tracking_rights where id = $id ";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {

          $temp=array("response"=>"success");
          array_push($data1,$temp);
          
        }
 
    }







echo json_encode($data1);

?>





