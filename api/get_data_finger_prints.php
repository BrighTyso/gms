<?php
require "conn.php";
require "validate.php";



$response=array();
$otps=array();
$home_location=array();
$scheme_growers=array();
$loans=array();
$prints=array();

$lat="";
$long="";

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($_GET['userid'])){

$seasonid=$_GET['seasonid'];
$userid=$_GET['userid'];

    $sql1 = "Select distinct feature,grower_num,description from grower_finger_print join growers on grower_finger_print.growerid=growers.id  order by rand() ";
    $result1 = $conn->query($sql1);
   
   if ($result1->num_rows > 0) {
     // output data of each row
     while($row1 = $result1->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

      $grower_num="";

      if ($row1["description"]=="") {
        
        $grower_num=$row1["grower_num"];
      }else{
        $grower_num=$row1["description"];
      }

      $temp=array("grower_num"=>$grower_num,"feature"=>$row1["feature"],"seasonid"=>$seasonid);
      array_push($response,$temp);
      
     }
   }

}

 echo json_encode($response);