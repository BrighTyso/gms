<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$growerid=0;
$receiptnumber="";
$productid=0;
$loanid=0;
$quantity=0;
$newquantity=0;

$loan_found=1;
$truck_to_growerid=0;
$disbursement_trucksid=0;
$disbusment_quantity=0;
$created_at="";

$data1=array();


if (isset($data->userid)  && isset($data->productid) && isset($data->growerid) && isset($data->quantity)  && isset($data->seasonid) && isset($data->loanid)){

$userid=$data->userid;

$growerid=$data->growerid;
$productid=$data->productid;
$quantity=$data->quantity;
$seasonid=$data->seasonid;
$loanid=$data->loanid;
$created_at=$data->created_at;
$security_otp_found=0;


    // code...


  if ($growerid>0  && $loanid>0) {
    // update loan

    $user_sql1 = "update grower_field_loans set adjustment_quantity=$quantity,adjust=1  where id=$loanid and seasonid=$seasonid and productid=$productid";
       //$sql = "select * from login";
       if ($conn->query($user_sql1)===TRUE) {

            $temp=array("response"=>"success");
           array_push($data1,$temp);


        }


}else{
   $temp=array("response"=>"Grower/loan not found");
        array_push($data1,$temp); 
}
}else{
        $temp=array("response"=>"Field Empty");
        array_push($data1,$temp);
}






echo json_encode($data1);

?>
