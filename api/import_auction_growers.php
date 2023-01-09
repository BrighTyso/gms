<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));




$response=array();

if (isset($data->seasonid) && isset($data->userid) && isset($data->grower_num)  && isset($data->created_at)){


$userid=$data->userid;
$seasonid=$data->seasonid;
$growerid=0;
$grower_num=$data->grower_num;
$created_at=$data->created_at;

$contracted_grower=0;
$auction_grower=0;


  $sql = "Select * from growers where grower_num='$grower_num'";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

      $growerid=$row["id"];
      
     }

   }




  $sql = "Select * from contracted_hectares where growerid=$growerid and seasonid=$seasonid";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $contracted_grower=$row["id"];
      
     }

   }




  $sql = "Select * from auction_growers where growerid=$growerid and seasonid=$seasonid";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $auction_grower=$row["id"];
      
     }

   }




   if ($auction_grower==0 && $contracted_grower==0) {


     $user_sql = "INSERT INTO auction_growers(userid,seasonid,growerid,created_at) VALUES ($userid,$seasonid,$growerid,'$created_at')";
                   //$sql = "select * from login";
       if ($conn->query($user_sql)===TRUE) {

            $temp=array("response"=>"success");
            array_push($response,$temp);
        
       }
       
   }else{


    if ($auction_grower>0) {

     $temp=array("response"=>"Already Created");
      array_push($response,$temp);

    }else if ($contracted_grower>0) 
    {

      $temp=array("response"=>"Already Created");
      array_push($response,$temp);
    }  

   }


}





 echo json_encode($response);






?>





