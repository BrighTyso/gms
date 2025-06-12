<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));


$data1=array();


if (isset($data->productid) && isset($data->userid)){

$productid=$data->productid;
$created_at=$data->created_at;
$userid=$data->userid;



  $user_sql1 = "DELETE FROM custom_disbursement_product_quantity where productid = $productid and created_at='$created_at'";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {

          $temp=array("response"=>"success");
          array_push($data1,$temp);
          
        }
 
    }



echo json_encode($data1);

?>





