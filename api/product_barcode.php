<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$found=0;

$response=array();

if (isset($data->userid) && isset($data->barcode) && isset($data->productid) && isset($data->created_at)){

$userid=$data->userid;
$description=$data->barcode;
$productid=$data->productid;
$created_at=$data->created_at;


$sql = "Select * from product_barcodes where barcode='$description'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found=$row["id"];
    
   }
 }





if ($found==0 ) {

 $user_sql = "INSERT INTO product_barcodes(userid,productid,barcode,created_at) VALUES ($userid,$productid,'$description','$created_at')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $last_id = $conn->insert_id;

     $temp=array("response"=>"success");
     array_push($response,$temp);

   }else{

    
    $temp=array("response"=>"failed");
     array_push($response,$temp);

   }


}else{

$temp=array("response"=>"Barcode Already Used");
array_push($response,$temp);

}






}else{

  $temp=array("response"=>"field empty");
     array_push($response,$temp);

	//echo json_encode("field cant be empty");
}



echo json_encode($response);

?>





