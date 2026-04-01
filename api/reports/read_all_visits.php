<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$userid1=$data->userid;
$seasonid=$data->seasonid;

$username="";


$end=date("Y-m-d");

$date = new DateTime(); // Defaults to "now"
$date->modify('-30 days');

$start=$date->format('Y-m-d');


$data1=array();
$visits=array();
// get grower locations

if ($userid1!="") {


//and (sod.created_at between '$start' and '$end')

 $sql = "Select distinct sod.userid,users.username,sod.created_at from sod join users on users.id=sod.userid where sod.seasonid=$seasonid  order by sod.created_at desc ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
        $userid=$row['userid'];
        $username=$row['username'];
        $created_at=$row['created_at'];
        $visits=array();
    
      $sql11 = "Select distinct grower_num,growers.surname,growers.name,description,visits.created_at,latitude,longitude,times from visits join growers on growers.id=visits.growerid  where visits.seasonid=$seasonid and visits.userid=$userid and visits.created_at='$created_at'";

      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"]
          ,"latitude"=>$row1["latitude"],"longitude"=>$row1["longitude"],"created_at"=>$row1["created_at"],"description"=>$row1["description"],"times"=>$row1["times"]);
          array_push($visits,$temp);

         
         }
       }



        $temp=array("username"=>$username,"visits"=>$visits,"created_at"=>$created_at);
          array_push($data1,$temp);



     }
   }



}

 echo json_encode($data1);


?>


