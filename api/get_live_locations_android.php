<?php
require "conn.php";
require "validate.php";

$data=array();

$userid=0;


//http://192.168.1.190/gms/api/get_user.php?username=brightkaponda&hash=brightkaponda&access_code=1234
if (isset($_GET['userid'])) {

$userid=$_GET['userid'];
$description=$_GET['description'];


if ($description=="") {
  // code...


$sql11 = "select distinct users.id,username from live_locations join users on users.id=live_locations.userid order by datetimes desc";
$result1 = $conn->query($sql11);
 
 if ($result1->num_rows > 0) {
   // output data of each row
   while($row1 = $result1->fetch_assoc()) {

        $location_userid=$row1["id"];
        $sql = "select users.id,live_locations.userid,username,live_locations.seasonid,latitude,longitude,live_locations.created_at,datetimes from live_locations join users on users.id=live_locations.userid where users.id=$location_userid order by datetimes desc limit 1";
        $result = $conn->query($sql);
         
         if ($result->num_rows > 0) {
           // output data of each row
           while($row = $result->fetch_assoc()) {
            // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
            $temp=array("userid"=>$row["userid"],"latitude"=>$row["latitude"],"longitude"=>$row["longitude"],"datetimes"=>$row["datetimes"],"id"=>$row["id"],"username"=>$row["username"],"created_at"=>$row["created_at"]);
            array_push($data,$temp);
            
           }
         }
   }
 }



}else{


  $sql11 = "select distinct users.id,username from live_locations join users on users.id=live_locations.userid where username='$description' and name='$description' and surname='$description' order by datetimes desc limit 1";
$result1 = $conn->query($sql11);
 
 if ($result1->num_rows > 0) {
   // output data of each row
   while($row1 = $result1->fetch_assoc()) {

    $location_userid=$row1["id"];
      $sql = "select users.id,live_locations.userid,username,live_locations.seasonid,latitude,longitude,live_locations.created_at,datetimes from live_locations join users on users.id=live_locations.userid where users.id=$location_userid order by datetimes desc limit 1";
      $result = $conn->query($sql);
       
       if ($result->num_rows > 0) {
         // output data of each row
         while($row = $result->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
          $temp=array("userid"=>$row["userid"],"latitude"=>$row["latitude"],"longitude"=>$row["longitude"],"datetimes"=>$row["datetimes"],"id"=>$row["id"],"username"=>$row["username"],"created_at"=>$row["created_at"]);
          array_push($data,$temp);
          
         }
       }
   }
 }





}
}


 echo json_encode($data); 


?>