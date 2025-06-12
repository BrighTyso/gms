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


  $sql = "Select distinct users.username,id from users where active=1 order by id desc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
        $userid=$row['id'];
        $username=$row['username'];
        


        


      $sql2 = "Select distinct grower_num,question,bales,questionnaires_bales_answers_by_grower.created_at,questionnaires_bales_answers_by_grower.datetimes,question_created_at from questionnaires_bales_answers_by_grower join growers on growers.id=questionnaires_bales_answers_by_grower.growerid where  questionnaires_bales_answers_by_grower.userid=$userid and questionnaires_bales_answers_by_grower.seasonid=$seasonid and questionnaires_bales_answers_by_grower.created_at between '$start_date' and '$end_date' order by growers.id";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {
         // output data of each row
         while($row1 = $result2->fetch_assoc()) {


         $grower_num=$row1["grower_num"];
         $question=$row1["question"];
         $question_created_at=$row1["question_created_at"];

         $comment="";
         $next_delivery_date="";


          $sql5 = "Select distinct grower_num,question,comment,next_delivery_date,zero_bale_comment.created_at,zero_bale_comment.datetimes from zero_bale_comment join growers on growers.id=zero_bale_comment.growerid where  zero_bale_comment.userid=$userid and zero_bale_comment.seasonid=$seasonid and question_date='$question_created_at' and question='$question' and grower_num='$grower_num' limit 1";
        $result5 = $conn->query($sql5);
         
         if ($result5->num_rows > 0) {
           // output data of each row
           while($row15 = $result5->fetch_assoc()) {

            $comment=$row15["comment"];
            $next_delivery_date=$row15["next_delivery_date"];

           }
        }



          $temp=array("grower_num"=>$row1["grower_num"],"question"=>$row1["question"],"answer"=>$row1["bales"],"created_at"=>$row1["created_at"] ,"datetimes"=>$row1["datetimes"],"comment"=>$comment,"next_delivery_date"=>$next_delivery_date);
          array_push($questions,$temp);

         }
      }



    $temp=array("username"=>$row["username"],"data"=>$questions);
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