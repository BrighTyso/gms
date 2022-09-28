<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$storeid=0;
$productid=0;
$quantity=0;
$created_at="";
$found=0;

$response=array();

if (isset($data->userid) && isset($data->storeid) && isset($data->productid) && isset($data->quantity) && isset($data->created_at)){



$userid=$data->userid;
$storeid=$data->storeid;
$productid=$data->productid;
$quantity=$data->quantity;
$created_at=$data->created_at;


$sql = "Select * from store_items where storeid=$storeid and productid=$productid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found=$row["id"];
    
   }
 }


if ($found==0) {
   $user_sql = "INSERT INTO store_items(userid,storeid,productid,quantity,created_at) VALUES ($userid,$storeid,$productid,$quantity,'$created_at')";
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

  $user_sql1 = "update store_items set quantity=quantity+$quantity , userid=$userid ,  created_at='$created_at' where storeid = $storeid and productid=$productid";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {
   
   $temp=array("response"=>"success");
     array_push($response,$temp);

   }else{
    echo $conn->error;

     $temp=array("response"=>"failed");
     array_push($response,$temp);

   }

}


}else{

	 $temp=array("response"=>"Field Empty");
     array_push($response,$temp);
  
}


echo json_encode($response);



?>





