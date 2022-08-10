<?php
require "conn.php";
require "validate.php";

$userid=0;
$growerid="";
$lat="";
$long="";
$description="";
$condition="";
$other="";
$created_at="";
$phone="";
$grower="";
$sqliteid=0;
$verifyGrowerVisits=0;


$data=array();


if (isset($_GET['grower_num']) && isset($_GET['userid']) && isset($_GET['lat'])  && isset($_GET['long'])  && isset($_GET['description']) && isset($_GET['condition']) && isset($_GET['other'])  && isset($_GET['created_at']) && isset($_GET['sqliteid'])  && isset($_GET['seasonid'])){



$userid=validate($_GET['userid']);
//$growerid=validate($_POST['growerid']);
$lat=validate($_GET['lat']);
$long=validate($_GET['long']);
$description=validate($_GET['description']);
$condition=validate($_GET['condition']);
$other=validate($_GET['other']);
$seasonid=validate($_GET['seasonid']);
$created_at=validate($_GET['created_at']);
$grower=validate($_GET['grower_num']);
$sqliteid=validate($_GET['sqliteid']);





$sql = "Select * grower_visits join growers on grower_visits.growerid=growers.id where  growers.grower_num='$grower'  and grower_visits.description='$description'  and grower_visits.condition='$condition' and grower_visits.created_at='$created_at'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {


   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $verifyGrowerVisits=1;
   
    
   }
 }




if ($verifyGrowerVisits==0) {

$sql = "Select * from growers where grower_num='$grower' or phone='$grower'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //$temp=array("name"=>$row["name"],"surname"=>$row["surname"],"username"=>$row["username"],"created_at"=>$row["created_at"],"receiptid"=>$row["receiptid"]);
    //array_push($data,$temp);

   $growerid=$row["id"];

   $visits_sql = "INSERT INTO grower_visits(userid,growerid,latitude,longitude,description,conditions,other,seasonid,created_at) VALUES ($userid,$growerid,'$lat','$long','$description','$condition','$other',$seasonid,'$created_at')";
   //$gr = "select * from login";
   if ($conn->query($visits_sql)===TRUE) {
   
     $last_id = $conn->insert_id;
    // echo "success";

       $temp=array("id"=>$sqliteid);
        array_push($data,$temp);

   }else{


   }


    
   }

 }else{




 }

}
  // code...



}else{


}


echo json_encode($data);

?>





