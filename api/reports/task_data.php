<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$seasonid=$data->seasonid;
$start=$data->start;
$end=$data->end;


$userid=0;
$user_name="";
$surname="";
$visited_growers=0;
$visits=0;
$duration=0;

$data1=array();

$visits_data=array();
// get grower locations

  


$sql11 = "Select distinct userid,name,surname,task_url,description,duration_days,field_officer_task.created_at from  field_officer_task join users on users.id=field_officer_task.userid where seasonid=$seasonid and field_officer_task.created_at between '$start' and '$end' order by created_at desc";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

            $task_url=$row["task_url"];
            $created_at_by_id=$row["userid"];
            $userid=0;

            $super_v_name=$row["name"];
            $super_v_surname=$row["surname"];

            $description=$row["description"];
            $duration_days=$row["duration_days"];
            $created_at=$row["created_at"];

                                

            $sql112 = "Select distinct userid,name,surname,task_viewer.created_at from  task_viewer join users on users.id=task_viewer.userid where  task_url='$task_url' and created_by_id=$created_at_by_id limit 1";

            $result1 = $conn->query($sql112);
             
             if ($result1->num_rows > 0) {
               // output data of each row
               while($row1 = $result1->fetch_assoc()) {


                        $userid=$row1["userid"];
                        $user_name=$row1["name"];
                        $surname=$row1["surname"];
                        $visited_growers=0;
                        $visits=0;
                        $duration=0;

                        $sql113 = "Select distinct growerid from  task_grower_data where  task_url='$task_url' and userid=$userid ";

                        $result2 = $conn->query($sql113);


                        $visited_growers+=$result2->num_rows;



                        $sql113 = "Select distinct growerid,task_url,created_at from  task_grower_data where  task_url='$task_url' and userid=$userid ";

                        $result2 = $conn->query($sql113);


                        $visits+=$result2->num_rows;



                         $sql113 = "Select distinct task_url,created_at from  task_grower_data where  task_url='$task_url' and userid=$userid ";

                        $result2 = $conn->query($sql113);


                        $duration+=$result2->num_rows;

                        
                     $temp=array("name"=>$user_name,"surname"=>$surname,"visited_growers"=>$visited_growers,"duration_days"=>$duration,"visits"=>$visits);
                     array_push($visits_data,$temp);

           
               }
             }



            
              $temp=array("name"=>$super_v_name,"surname"=>$super_v_surname,"description"=>$description,"created_at"=>$created_at,"duration_days"=>$duration_days,"f_name"=>$user_name,"f_surname"=>$surname,"visited_growers"=>$visited_growers,"days"=>$duration,"visits"=>$visits_data);
                     array_push($data1,$temp);

   
   }
 }



 // $temp=array("name"=>$row["name"],"grower_num"=>$row["grower_num"],"surname"=>$row["surname"],"id_num"=>$row["id_num"],"phone"=>$row["phone"],"area"=>$row["area"],"province"=>$row["province"]);
 //                array_push($data1,$temp);



 echo json_encode($data1);


?>


