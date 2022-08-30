<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$productid=0;
$amount="";
$seasonid=0;
$created_at="";
$found=0;
$response=array();

if (isset($data->userid) && isset($data->productid)  && isset($data->amount)  && isset($data->seasonid) && isset($data->created_at)){

$userid=$data->userid;
$productid=$data->productid;
$amount=$data->amount;
$seasonid=$data->seasonid;
$created_at=$data->created_at;



$sql = "Select * from prices where productid=$productid and seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found=1;
    
   }
 }



if ($found==0) {

 $user_sql = "INSERT INTO prices(userid,productid,amount,seasonid,created_at) VALUES ($userid,$productid,'$amount',$seasonid,'$created_at')";
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

$sql = "UPDATE prices SET amount = $amount  WHERE productid=$productid and seasonid=$seasonid";

   //$sql = "select * from login";
   if ($conn->query($sql)===TRUE) {
     
     $temp=array("response"=>"successfully Updated Price");
    array_push($response,$temp);

   }else{

    $temp=array("response"=>"failed");
   array_push($response,$temp);
   }


}





}else{

  $temp=array("response"=>"field cant be empty");
     array_push($response,$temp);

	//echo json_encode("field cant be empty");
}



echo json_encode($response);

?>





