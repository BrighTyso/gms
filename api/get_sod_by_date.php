<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));


$data1=array();

$description=$data->description;
$seasonid=$data->seasonid;
$startDate=substr($data->startDate,0,-8);
$endDate=substr($data->endDate,0,-8);
$hours_worked=0;
$total_growers=0;
$grower_visits=0;


if ($description=="All" || $description=="all") {
  $sql = "Select userid,latitude,longitude,sod.seasonid,time,eod,sod.created_at,eod_created_at,username from sod join users on users.id=sod.userid where sod.seasonid=$seasonid and  (sod.created_at between '$startDate' and '$endDate') order by created_at desc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {


   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

        $userid=$row['userid'];
        $created_at=$row['created_at'];
        $distance=0;
        $hours_worked=0;
        $total_growers=0;
        $grower_visits=0;




        $sql1 = "Select distinct growerid from visits where created_at='$created_at' and userid=$userid and seasonid=$seasonid";
      $result1 = $conn->query($sql1);
       
      $visited_growers=$result1->num_rows;


      $sql2 = "Select distinct * from distance where created_at='$created_at' and userid=$userid and seasonid=$seasonid";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {


         // output data of each row
         while($row2 = $result2->fetch_assoc()) {

          $distance+=$row2['distance'];

          

         }
      }




      $sql2 = "Select distinct growerid from  visits where userid=$userid and seasonid=$seasonid ";

      $result2 = $conn->query($sql2);

      $total_growers=$result2->num_rows;


      $sql2 = "Select distinct growerid,created_at from  visits where  userid=$userid and seasonid=$seasonid ";

      $result1 = $conn->query($sql2);

      $grower_visits=$result1->num_rows;




      $sql2 = "Select distinct * from hours_worked where created_at='$created_at' and userid=$userid and seasonid=$seasonid";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {


         // output data of each row
         while($row2 = $result2->fetch_assoc()) {

          $hours_worked+=$row2['hours'];

          

         }
      }



      $kms=$distance/1000;




     $temp=array("longitude"=>$row["longitude"],"latitude"=>$row["latitude"],"userid"=>$row["userid"],"seasonid"=>$row["seasonid"],"time"=>$row["time"],"eod"=>$row["eod"],"created_at"=>$row["created_at"],"eod_created_at"=>$row["eod_created_at"],"username"=>$row["username"],"time"=>$row["time"]
,"distance"=>$kms,"hours"=>$hours_worked,"visits"=>$visited_growers,"total_growers"=>$total_growers,"total_visits"=>$grower_visits);
    array_push($data1,$temp);
   
   
    
   }


}

}else{

$sql = "Select userid,latitude,longitude,sod.seasonid,time,eod,sod.created_at,eod_created_at,username from sod join users on users.id=sod.userid where sod.seasonid=$seasonid and username='$description' and (sod.created_at between '$startDate' and '$endDate')  order by created_at desc" ;
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {

   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

        $userid=$row['userid'];
        $created_at=$row['created_at'];
        $distance=0;
        $hours_worked=0;
        $total_growers=0;
        $grower_visits=0;




        $sql1 = "Select distinct growerid from visits where created_at='$created_at' and userid=$userid and seasonid=$seasonid";
      $result1 = $conn->query($sql1);
       
      $visited_growers=$result1->num_rows;


      $sql2 = "Select distinct * from distance where created_at='$created_at' and userid=$userid and seasonid=$seasonid";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {


         // output data of each row
         while($row2 = $result2->fetch_assoc()) {

          $distance+=$row2['distance'];

          

         }
      }




      $sql2 = "Select distinct growerid from  visits where userid=$userid and seasonid=$seasonid ";

      $result2 = $conn->query($sql2);

      $total_growers=$result2->num_rows;


      $sql2 = "Select distinct growerid,created_at from  visits where  userid=$userid and seasonid=$seasonid ";

      $result1 = $conn->query($sql2);

      $grower_visits=$result1->num_rows;




      $sql2 = "Select distinct * from hours_worked where created_at='$created_at' and userid=$userid and seasonid=$seasonid";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {


         // output data of each row
         while($row2 = $result2->fetch_assoc()) {

          $hours_worked+=$row2['hours'];

          

         }
      }



      $kms=$distance/1000;




     $temp=array("longitude"=>$row["longitude"],"latitude"=>$row["latitude"],"userid"=>$row["userid"],"seasonid"=>$row["seasonid"],"time"=>$row["time"],"eod"=>$row["eod"],"created_at"=>$row["created_at"],"eod_created_at"=>$row["eod_created_at"],"username"=>$row["username"],"time"=>$row["time"]
,"distance"=>$kms,"hours"=>$hours_worked,"visits"=>$visited_growers,"total_growers"=>$total_growers,"total_visits"=>$grower_visits);
    array_push($data1,$temp);
   
   
   
    
   }


}


}



// else if ($description=="" && $seasonid!=""){

// $sql = "Select grower_visits.id,grower_visits.latitude,grower_visits.longitude,grower_visits.description,grower_visits.conditions,grower_visits.other, users.username , growers.name as grower_name , growers.surname as grower_surname , growers.grower_num  , grower_visits.created_at from grower_visits join users on users.id=grower_visits.userid  join growers on growers.id=grower_visits.growerid where  grower_visits.seasonid='$seasonid'";
// $result = $conn->query($sql);
 
//  if ($result->num_rows > 0) {
//    // output data of each row
//    while($row = $result->fetch_assoc()) {
//     // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

//     $temp=array("id"=>$row["id"],"latitude"=>$row["latitude"],"longitude"=>$row["longitude"],"grower_num"=>$row["grower_num"],"grower_surname"=>$row["grower_surname"],"grower_name"=>$row["grower_name"],"created_at"=>$row["created_at"],"description"=>$row["description"] ,"conditions"=>$row["conditions"],"other"=>$row["other"],"username"=>$row["username"]);
//     array_push($data1,$temp);
    
//    }
//  }

// }


 echo json_encode($data1); 

?>