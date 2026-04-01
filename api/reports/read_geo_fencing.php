
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$end=date("Y-m-d");

$date = new DateTime(); // Defaults to "now"
$date->modify('-30 days');

$start=$date->format('Y-m-d');


$userid1=$data->userid;
$seasonid=$data->seasonid;

$username="";

$data1=array();
// get grower locations

if ($userid1!="") { 
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      
      $sql11 = "SELECT distinct grower_geofence_entry_point.seasonid,grower_geofence_entry_point.created_at,latitude,longitude,entry_time,grower_num,growers.name, growers.surname, id_num,area, province, phone,grower_geofence_entry_point.userid,username,grower_geofence_entry_point.growerid FROM grower_geofence_entry_point join growers on growers.id=grower_geofence_entry_point.growerid join users on users.id=grower_geofence_entry_point.userid where grower_geofence_entry_point.seasonid=$seasonid and (grower_geofence_entry_point.created_at between '$start' and '$end')";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

            $exit_date=$row1["created_at"];
            $growerid=$row1["growerid"];
            $exit_time="";


            $sql = "SELECT distinct grower_geofence_exit_point.seasonid,grower_geofence_exit_point.created_at,latitude,longitude,exit_time,grower_num,growers.name, growers.surname, id_num,area, province, phone,grower_geofence_exit_point.userid,username FROM grower_geofence_exit_point join growers on growers.id=grower_geofence_exit_point.growerid join users on users.id=grower_geofence_exit_point.userid where grower_geofence_exit_point.seasonid=$seasonid and grower_geofence_exit_point.created_at='$exit_date' and grower_geofence_exit_point.growerid=$growerid limit 1";
            $result = $conn->query($sql);
             
             if ($result->num_rows > 0) {
               // output data of each row
               while($row = $result->fetch_assoc()) {
               
                $exit_time=$row['exit_time'];
                
               }

             }



         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"exit_time"=>$exit_time,"latitude"=>$row1["latitude"],"longitude"=>$row1["longitude"],"entry_time"=>$row1["entry_time"],"created_at"=>$row1["created_at"]
       );
              array_push($data1,$temp);

         
         }
       
   }



}

 echo json_encode($data1);


?>


