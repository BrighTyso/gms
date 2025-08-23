<?php
require "conn.php";
require "validate.php";



$response=array();

$lat="";
$long="";

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($_POST['userid'])){

$seasonid=$_POST['seasonid'];
$userid=$_POST['userid'];
$feature=$_POST['feature'];
$grower_num=$_POST['grower_num'];
$growerid=0;
$print_found=0;


$sql = "Select distinct * from growers  where grower_num='$grower_num' ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $growerid=$row["id"];
   }
 }



$sql = "Select distinct * from grower_finger_print   where growerid=$growerid ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $print_found=$row["id"];
   }
 }


 if ($print_found==0) {


   $user_sql = "INSERT INTO grower_finger_print(userid,seasonid,growerid,feature) VALUES ($userid,$seasonid,$growerid,'$feature')";
           //$sql = "select * from login";
     if ($conn->query($user_sql)===TRUE) {
     
       $last_id = $conn->insert_id;

       $insert_sql = "insert into visits(userid,growerid,seasonid,latitude,longitude,created_at,description) value($userid,$growerid,$seasonid,'','','$created_at','Grower Finger Print');";
       //$gr = "select * from login";
       if ($conn->query($insert_sql)===TRUE) {

        $temp=array("pin"=>$grower_num);
        array_push($response,$temp);
        
      }

     }else{


     }
 }


}

 echo json_encode($response);