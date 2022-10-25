<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=$data->userid;
$seasonid=$data->seasonid;
$growerid=0;
$loan_found=0;
$grower_num=$data->grower_num;
$amount=$data->amount;
$created_at=$data->created_at;
$mass=$data->mass;

if (isset($data->seasonid) && isset($data->userid) && isset($data->grower_num) && isset($data->amount) && isset($data->created_at)){


  $sql = "Select * from growers where grower_num='$grower_num'";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

      $growerid=$row["id"];
      
     }

   }


    $sql = "Select * from loan_payments where growerid=$growerid and seasonid=$seasonid and created_at='$created_at' and mass='$mass' limit 1";
    $result = $conn->query($sql);
     
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

        $loan_found=1;
        
       }

     }




if ($growerid>0 && $loan_found==0) {

      $user_sql = "INSERT INTO loan_payments(userid,seasonid,growerid,amount,mass,created_at) VALUES ($userid,$seasonid,$growerid,'$amount','$mass','$created_at')";
         //$sql = "select * from login";
         if ($conn->query($user_sql)===TRUE) {
         
           $last_id = $conn->insert_id;
           echo json_encode("success");

         }else{

          echo $conn->error;

          echo json_encode("failed");

         }




      }else{

        echo json_encode("field cant be empty");
      }


}






?>





