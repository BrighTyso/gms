
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));


$data1=array();

$userid=$data->userid;
$growerid=$data->growerid;
$seasonid=$data->seasonid;
$active_grower_found=0;


$sql = "Select * from active_growers where growerid=$growerid and seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $active_grower_found=$row["id"];
  
    
   }

 }


 if ($active_grower_found==0) {
  $user_sql = "INSERT INTO active_growers(userid,growerid,seasonid) VALUES ($userid,$growerid,$seasonid)";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {

    $temp=array("response"=>"success");
    array_push($data1,$temp);

   }else{
   	$temp=array("response"=>$conn->error);
    array_push($data1,$temp);
   }
}else{
   $temp=array("response"=>"Already active");
    array_push($data1,$temp);
}



  echo json_encode($data1);


?>