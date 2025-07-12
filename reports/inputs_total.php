<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=$data->seasonid;

$data1=array();
// get grower locations

$chairman="";
$fieldOfficer="";
$area_manager="";
$growerid=0;

if ($userid!="") {
  


$sql11 = "Select * from inputs_total join growers on growers.id=inputs_total.growerid where inputs_total.seasonid=$seasonid";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

      $growerid=$row["growerid"];



      $sql = "Select * from grower_managers  where  growerid=$growerid and seasonid=$seasonid limit 1";
      $result1 = $conn->query($sql);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {

          // product id
            $chairman=$row1["chairman"];
            $fieldOfficer=$row1["fieldOfficer"];
            $area_manager=$row1["area_manager"];
         
          
         }

       }

   $temp=array("id_num"=>$row["id_num"],"phone"=>$row["phone"],"area"=>$row["area"],"chairman"=>$chairman,"fieldOfficer"=>$fieldOfficer,"area_manager"=>$area_manager,"name"=>$row["name"],"grower_num"=>$row["grower_num"],"surname"=>$row["surname"],"amount"=>$row["amount"]);
    array_push($data1,$temp);

   
   }
 }





}

 echo json_encode($data1);


?>


