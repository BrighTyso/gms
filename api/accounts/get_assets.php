<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");
//require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;

$response=array();

if (isset($data->userid) && isset($data->description) ){

$userid=$data->userid;
$description=$data->description;

if($description==""){

$sql = "Select distinct main_accounts.description as main_account,main_accounts.id,sub_accounts.description as sub_acc , sub_accounts.id as sub_account_id, assets.id as assetid, assets.location , assets.description as asset_description,assets.name,assets.serial_number from assets join sub_accounts on assets.sub_accountid=sub_accounts.id join main_accounts on main_accounts.id=sub_accounts.main_accountid ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("main_account"=>$row["main_account"],"sub_acc"=>$row["sub_acc"],"asset_description"=>$row["asset_description"],"main_account_id"=>$row["id"],"assetid"=>$row["assetid"],"location"=>$row["location"],"asset_description"=>$row["asset_description"],"serial_number"=>$row["serial_number"],"name"=>$row["name"],"sub_account_id"=>$row["sub_account_id"]);
    array_push($response,$temp);
    
   }
 }


}else{


$sql = "Select distinct main_accounts.description as main_account,main_accounts.id,sub_accounts.description as sub_acc , sub_accounts.id as sub_account_id, assets.id as assetid, assets.location , assets.description as asset_description,assets.name,assets.serial_number from assets join sub_accounts on assets.sub_accountid=sub_accounts.id join main_accounts on main_accounts.id=sub_accounts.main_accountid where serial_number='$description' or name='$description' or assets.description='$description'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("main_account"=>$row["main_account"],"sub_acc"=>$row["sub_acc"],"asset_description"=>$row["asset_description"],"main_account_id"=>$row["id"],"assetid"=>$row["assetid"],"location"=>$row["location"],"asset_description"=>$row["asset_description"],"serial_number"=>$row["serial_number"],"name"=>$row["name"],"sub_account_id"=>$row["sub_account_id"]);
    array_push($response,$temp);
    
   }
 }

}




}



echo json_encode($response);

?>





