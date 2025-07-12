<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");


$data = json_decode(file_get_contents("php://input"));


$data1=array();


$seasonid=$data->seasonid;
$start=substr($data->start,0,-8);
$end=substr($data->end,0,-8);


$hours_worked=0;
$total_growers=0;
$grower_visits=0;
$userid=0;
$working_days=0;
$fuel_allocation=0;
$total_start_of_days=0;

  $sql = "Select distinct sod.userid,users.username from sod join users on users.id=sod.userid where sod.seasonid=$seasonid order by sod.created_at desc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
        $userid=$row['userid'];
        //$created_at=$row['created_at'];
        $distance=0;
        $hours_worked=0;
        $total_growers=0;
        $grower_visits=0;
        $working_days=0;
        $fuel_allocation=0;
        $total_start_of_days=0;



        $sql1 = "Select distinct growerid from visits where  userid=$userid and seasonid=$seasonid and (created_at between '$start' and '$end') ";
      $result1 = $conn->query($sql1);
       
      $visited_growers=$result1->num_rows;


      $sql2 = "Select distinct * from distance where  userid=$userid and seasonid=$seasonid and (created_at between '$start' and '$end')";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {


         // output data of each row
         while($row2 = $result2->fetch_assoc()) {

          $distance+=$row2['distance'];

          

         }
      }



      $sql50 = "Select distinct sod.userid,users.username,sod.created_at from sod join users on users.id=sod.userid join visits on visits.created_at=sod.created_at where sod.seasonid=$seasonid and sod.userid=$userid and (visits.created_at between '$start' and '$end') order by sod.created_at desc";
			$result50 = $conn->query($sql50);
			 
			 $working_days=$result50->num_rows;



        $sql501 = "Select distinct sod.userid,users.username,sod.created_at from sod join users on users.id=sod.userid  where sod.seasonid=$seasonid and sod.userid=$userid and (sod.created_at between '$start' and '$end') order by sod.created_at desc";
      $result501 = $conn->query($sql501);
       
       $total_start_of_days=$result501->num_rows;




       $sql2 = "Select distinct growerid from  visits where userid=$userid and seasonid=$seasonid and (created_at between '$start' and '$end')";

      $result2 = $conn->query($sql2);

      $total_growers=$result2->num_rows;



      $sql2 = "Select distinct created_at from  visits where userid=$userid and seasonid=$seasonid and (created_at between '$start' and '$end')";

      $result2 = $conn->query($sql2);

      $working_days=$result2->num_rows;




      $sql2 = "Select distinct growerid,created_at from  visits where  userid=$userid and seasonid=$seasonid and (created_at between '$start' and '$end')";

      $result1 = $conn->query($sql2);

      $grower_visits=$result1->num_rows;




      $sql2 = "Select distinct * from hours_worked where  userid=$userid and seasonid=$seasonid and (created_at between '$start' and '$end')";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {


         // output data of each row
         while($row2 = $result2->fetch_assoc()) {

          $hours_worked+=$row2['hours'];

          

         }
      }



      $sql2 = "Select distinct * from fuel_allocation where  fuel_userid=$userid  and seasonid=$seasonid and (allocation_date between '$start' and '$end')";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {


         // output data of each row
         while($row2 = $result2->fetch_assoc()) {

          $fuel_allocation+=$row2['quantity'];

          

         }
      }






      $kms=$distance/1000;




     $temp=array("userid"=>$row["userid"],"username"=>$row["username"]
,"distance"=>$kms,"hours"=>$hours_worked,"visits"=>$visited_growers,"total_growers"=>$total_growers,"total_visits"=>$grower_visits,"working_days"=>$working_days,"fuel_allocation"=>$fuel_allocation,"start_of_days"=>$total_start_of_days);
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