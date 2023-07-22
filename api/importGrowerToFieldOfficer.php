<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));




$response=array();

if (isset($data->seasonid) && isset($data->userid) && isset($data->officerid) && isset($data->grower_num)  && isset($data->created_at)){


$userid=$data->userid;
$officerid=$data->officerid;
$seasonid=$data->seasonid;
$growerid=0;
$grower_num=validate($data->grower_num);
$created_at=$data->created_at;

$grower_found=0;



  $sql = "Select * from growers where grower_num='$grower_num'";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

      $growerid=$row["id"];
      
     }

   }




  $sql = "Select * from grower_field_officer where growerid=$growerid and seasonid=$seasonid";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $grower_found=$row["id"];
      
     }

   }




   if ($grower_found==0 && $growerid>0) {


     $user_sql = "INSERT INTO grower_field_officer(userid,seasonid,growerid,field_officerid,created_at) VALUES ($userid,$seasonid,$growerid,$officerid,'$created_at')";
                   //$sql = "select * from login";
       if ($conn->query($user_sql)===TRUE) {

            $temp=array("response"=>"success");
            array_push($response,$temp);
        
       }else{
          $temp=array("response"=>$conn->error);
            array_push($response,$temp);
       }
       
   }else{
    if ($grower_found>0) {
      $temp=array("response"=>"Grower Already Assigned");
            array_push($response,$temp);
    }else if($growerid==0){
      $temp=array("response"=>"Grower not found");
      array_push($response,$temp);

    }
   }


}





 echo json_encode($response);






?>





