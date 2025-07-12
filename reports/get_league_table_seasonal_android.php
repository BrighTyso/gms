<?php
require "../conn.php";
//require "validate.php";


$data1=array();
$questions=array();
$response=array();

$seasonid=$_GET['seasonid'];

$userid=0;
$username="";
$target_points=0;
$points_earned=0;

$sys_targeted_points=0;

$fetched_records=0;


$company_details_data=array();

 $sql13 = "Select * from company_details_and_contact limit 1";

    $result3 = $conn->query($sql13);
     
     if ($result3->num_rows > 0) {
       // output data of each row
       while($row3 = $result3->fetch_assoc()) {

        $loans=array("name"=>$row3["company_name"],"address"=>$row3["address"],"phone_1"=>$row3["phone_1"],"phone_2"=>$row3["phone_2"],"phone_3"=>$row3["phone_3"],"email"=>$row3["email"]);

          array_push($company_details_data,$loans);
       
       }
     }


  $sql = "Select distinct users.username,users.id,target_points from users join total_points_earned on total_points_earned.userid=users.id where users.active=1 and total_points_earned.seasonid=$seasonid order by points desc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
        $userid=$row['id'];
        $username=$row['username'];
        $target_points=0;
        $points_earned=0;
        $sys_targeted_points=$row['target_points'];
        $questions=array();

      $sql2 = "Select distinct points,target_points,weekly_points_earned.start_date from weekly_points_earned where weekly_points_earned.userid=$userid and weekly_points_earned.seasonid=$seasonid";
      $result2 = $conn->query($sql2);
       $fetched_records=$result2->num_rows;
       if ($result2->num_rows > 0) {
         // output data of each row
         while($row1 = $result2->fetch_assoc()) {

         $target_points+=$row1["target_points"];
         $points_earned+=$row1["points"];

         }
      }


       $temp=array("username"=>$username,"no_of_records"=>$fetched_records,"sys_target_points"=>$sys_targeted_points,"points"=>$points_earned,"target_points"=>$target_points);
       array_push($response,$temp);

      
   
   }
}






 echo json_encode($response); 

?>