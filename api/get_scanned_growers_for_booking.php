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


if ($description=="") {
  
  $sql = "Select ready_for_booking.id,grower_num,ready_for_booking.bales,users.name,ready_for_booking.created_at,sell_date from ready_for_booking join grower_number_of_bales on grower_number_of_bales.id=ready_for_booking.grower_number_of_balesid join growers on growers.id=grower_number_of_bales.growerid join users on ready_for_booking.userid=users.id where grower_number_of_bales.seasonid=$seasonid ";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          $temp=array("grower_num"=>$row["grower_num"],"bales"=>$row["bales"],"name"=>$row["name"],"sell_date"=>$row["sell_date"],"created_at"=>$row["created_at"],"id"=>$row["id"]);
          array_push($data1,$temp);
           
      
     }

   }


}else{




  $sql = "Select ready_for_booking.id,grower_num,ready_for_booking.bales,users.name,ready_for_booking.created_at,sell_date from ready_for_booking join grower_number_of_bales on grower_number_of_bales.id=ready_for_booking.grower_number_of_balesid join growers on growers.id=grower_number_of_bales.growerid join users on ready_for_booking.userid=users.id where grower_number_of_bales.seasonid=$seasonid and (users.name='$description' or users.surname='$description' or users.username='$description' or growers.name='$description' or growers.grower_num='$description')";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          $temp=array("grower_num"=>$row["grower_num"],"bales"=>$row["bales"],"name"=>$row["name"],"sell_date"=>$row["sell_date"],"created_at"=>$row["created_at"],"id"=>$row["id"]);
          array_push($data1,$temp);
           
      
     }

   }





}




 }






echo json_encode($data1);

?>





