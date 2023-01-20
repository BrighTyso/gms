<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$seasonid=0;
$description=0;

$data1=array();


if (isset($data->seasonid) && isset($data->description)){

$description=$data->description;
$seasonid=$data->seasonid;
$created_at=$data->created_at;


if ($description=="") {
  
  $sql = "Select bale_booked.id,grower_num,bale_tags.code,users.name,bale_booked.created_at from bale_booked join bale_tags on bale_tags.id=bale_booked.bale_tagid join grower_number_of_bales on grower_number_of_bales.id=bale_tags.grower_number_of_balesid join growers on growers.id=grower_number_of_bales.growerid join users on bale_booked.userid=users.id where grower_number_of_bales.seasonid=$seasonid and bale_booked.created_at='$created_at'";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          $temp=array("grower_num"=>$row["grower_num"],"code"=>$row["code"],"name"=>$row["name"],"created_at"=>$row["created_at"],"id"=>$row["id"]);
          array_push($data1,$temp);
           
      
     }

   }


}else{




  $sql = "Select bale_booked.id,grower_num,bale_tags.code,users.name,bale_booked.created_at from bale_booked join bale_tags on bale_tags.id=bale_booked.bale_tagid join grower_number_of_bales on grower_number_of_bales.id=bale_tags.grower_number_of_balesid join growers on growers.id=grower_number_of_bales.growerid join users on bale_booked.userid=users.id where grower_number_of_bales.seasonid=$seasonid and bale_booked.created_at='$created_at' and (users.name='$description' or users.surname='$description' or users.username='$description' or growers.name='$description' or growers.grower_num='$description' or code='$description' )";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          $temp=array("grower_num"=>$row["grower_num"],"code"=>$row["code"],"name"=>$row["name"],"created_at"=>$row["created_at"],"id"=>$row["id"]);
          array_push($data1,$temp);
           
      
     }

   }





}




 }






echo json_encode($data1);

?>





