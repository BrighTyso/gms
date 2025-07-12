<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");


$data = json_decode(file_get_contents("php://input"));


$data1=array();
$weekly_plan=array();


$seasonid=$data->seasonid;
$start_date=substr($data->start,0,-8);
$end_date=substr($data->end,0,-8);



$number_of_weeks=0;
$userid=0;
$username="";


  $sql = "Select distinct users.username,id from users where active=1 order by id desc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
        $weekly_plan=array();
        $userid=$row['id'];
        $username=$row['username'];

        $system_generated_weekly_visits=0;
        $weekly_visits=0;
        $daily_visits=0;
        $no_of_visits_per_grower=0;
      

      $sql2 = "Select distinct * from weekly_plan  where  weekly_plan.userid=$userid and weekly_plan.seasonid=$seasonid and weekly_plan.created_at between '$start_date' and '$end_date'";
      $result2 = $conn->query($sql2);
       $number_of_weeks=$result2->num_rows;


       if ($result2->num_rows > 0) {
         // output data of each row
         while($row1 = $result2->fetch_assoc()) {


          $system_generated_weekly_visits+=$row1["system_generated_weekly_visits"];
          $weekly_visits+=$row1["weekly_visits"];
          $daily_visits+=$row1["daily_visits"];
          $no_of_visits_per_grower+=$row1["no_of_visits_per_grower"];

         }
      }

       $temp=array("system_generated_weekly_visits"=>$system_generated_weekly_visits,"weekly_visits"=>$weekly_visits ,"daily_visits"=>$daily_visits,"no_of_visits_per_grower"=>$no_of_visits_per_grower);
        array_push($weekly_plan,$temp);



      $sql2 = "Select distinct growerid,created_at from  visits where  userid=$userid and seasonid=$seasonid and (created_at between '$start_date' and '$end_date')";
      $result1 = $conn->query($sql2);
      $grower_visits=$result1->num_rows;



      $sql2 = "Select distinct growerid from  visits where  userid=$userid and seasonid=$seasonid and (created_at between '$start_date' and '$end_date')";
      $result1 = $conn->query($sql2);
      $no_of_growers=$result1->num_rows;



    $temp=array("username"=>$row["username"],"plan"=>$weekly_plan,"no_of_growers"=>$no_of_growers,"grower_visits"=>$grower_visits,"no_of_weeks"=>$number_of_weeks);
    array_push($data1,$temp);
   
   }
}



 echo json_encode($data1); 

?>