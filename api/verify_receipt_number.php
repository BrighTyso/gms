<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$seasonid=0;
$receiptnumber="";
$created_at="";
$growerid=0;

$data1=array();


if (isset($data->userid) && isset($data->receiptnumber) && isset($data->seasonid) && isset($data->created_at) && isset($data->growerid)){

 $userid=$data->userid;
 $seasonid=$data->seasonid;
 $receiptnumber=validate($data->receiptnumber);
 $created_at=$data->created_at;
 $growerid=$data->growerid;
 $loanid=0;



$sql = "Select * from loans where receipt_number='$receiptnumber' and seasonid=$seasonid and growerid=$growerid and verified=0";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $loanid=$row["id"];

   }
 }




 $user_sql1 = "update loans set verified=1,verified_by=$userid,verified_at='$created_at' where receipt_number='$receiptnumber' and seasonid=$seasonid and growerid=$growerid and verified=0";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {

    $temp=array("response"=>"success","loanid"=>$loanid,"growerid"=>$growerid);
    array_push($data1,$temp);

     
    }else{

      $temp=array("response"=>$conn->error);
       array_push($data1,$temp);

    }

  }




echo json_encode($data1);

?>





