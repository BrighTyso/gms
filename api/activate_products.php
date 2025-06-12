<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$id="";

$data1=array();


if (isset($data->userid)){

$productid=$data->productid;
$seasonid=$data->seasonid;

 $user_sql1 = "update scheme_hectares_products set active=1 where productid=$productid ";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {

    $temp=array("response"=>"success");
    array_push($data1,$temp);

     
    }

  }




echo json_encode($data1);

?>





