<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$productid=0;
$quantity=0;
$growerid=0;
$userid=0;
$created_at="";
$seasonid=0;
$last_id=0;
$response=array();
$verified=0;


$loanid=0;
$dbseasonid=0;



if (isset($data->productid) && isset($data->quantity)  && isset($data->growerid) && isset($data->userid) && isset($data->seasonid) && isset($data->created_at)){

$productid=$data->productid;
$quantity=$data->quantity;
$growerid=$data->growerid;
$userid=$data->userid;
$seasonid=$data->seasonid;
$created_at=$data->created_at;




$sql = "SELECT loans.id,loans.seasonid,loans.verified FROM loans where loans.productid=$productid and loans.quantity=$quantity and loans.seasonid=$seasonid and loans.growerid=$growerid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
   
    $loanid=$row['id'];
   
    
    if ($row['verified']==0) {
      $verified=0;
    }else{
      $verified=1;
    }
     
   }



   if ($verified==0) {
     $sql = "UPDATE loans SET verified = 1 , verified_by=$userid , verified_at='$created_at' WHERE id = $loanid";
   //$sql = "select * from login";
   if ($conn->query($sql)===TRUE) {
     
     $temp=array("response"=>"success");
    array_push($response,$temp);

   }else{

    $temp=array("response"=>"failed");
   array_push($response,$temp);
   }

   }else{

   $temp=array("response"=>"success Verified");
   array_push($response,$temp);

   }
   


 }else{

  $temp=array("response"=>"not found");
   array_push($response,$temp);

 }



}else{

  $temp=array("response"=>"empty");
  array_push($response,$temp);

	
}


echo json_encode($response);

?>





