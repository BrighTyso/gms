<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");


$data = json_decode(file_get_contents("php://input"));


$data1=array();
$questions=array();


$seasonid=$data->seasonid;


$start_date=substr($data->start,0,-8);
$end_date=substr($data->end,0,-8);

$userid=0;
$username="";

$grower_visits_total=0;
$daily_visits_total=0;
$daily_percent=0;



  $sql = "Select distinct users.username,users.id,start_date,end_date,system_generated_weekly_visits,weekly_visits,daily_visits,no_of_visits_per_grower,weekly_plan.created_at
 from users join weekly_plan on weekly_plan.userid=users.id where active=1 and weekly_plan.seasonid=$seasonid and  weekly_plan.created_at between '$start_date' and '$end_date'  order by username,weekly_plan.created_at desc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
        $userid=$row['id'];
        $username=$row['username'];

        $start=$row['start_date'];
        $end=$row['end_date'];

        $daily_visits_total=0;
        $grower_visits_total=0;
        $daily_percent=0;
        

      $sql2 = "Select * from  visits where userid=$userid and  visits.created_at between '$start' and '$end'";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {
         // output data of each row
         while($row1 = $result2->fetch_assoc()) {

          
         }
      }


      $sql2 = "Select distinct growerid,created_at from  visits where userid=$userid and  visits.created_at between '$start' and '$end' order by created_at";
      $result2 = $conn->query($sql2);
      $grower_visits_total=$result2->num_rows;


      $sql2 = "Select distinct created_at from  visits where userid=$userid and  visits.created_at between '$start' and '$end'";
      $result2 = $conn->query($sql2);
      $user_daily_total=$result2->num_rows;

      
       
       if ($result2->num_rows > 0) {
         // output data of each row
         while($row1 = $result2->fetch_assoc()) {

            $created_at=$row1["created_at"];


            $sql21 = "Select distinct growerid,created_at from  visits where userid=$userid and  visits.created_at='$created_at' order by created_at";
            $result21 = $conn->query($sql21);
            $daily_visits_total=$result21->num_rows;


            $daily_percent+=($daily_visits_total/$row["daily_visits"])*100;


         }
      }

      if ($user_daily_total>0) {
        $avarage_percent=$daily_percent/7;
      }else{
        $avarage_percent=0;
      }
      

       
      

      $temp=array("username"=>$row["username"],"start_date"=>$row["start_date"],"end_date"=>$row["end_date"],"system_generated_weekly_visits"=>$row["system_generated_weekly_visits"],"weekly_visits"=>$row["weekly_visits"] ,"daily_visits"=>$row["daily_visits"],"no_of_visits_per_grower"=>$row["no_of_visits_per_grower"],"created_at"=>$row["created_at"],"grower_visits_total"=>$grower_visits_total,"daily_visits_percent"=>$avarage_percent);
          array_push($data1,$temp);

   
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