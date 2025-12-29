<?php
require "conn.php";
require "validate.php";

$userid=0;
$growerid=0;
$grower_num="";
$lat="";
$long="";
$excellent=0;
$standard=0;
$average=0;
$poor=0;
$seasonid=0;
$sqliteid=0;

$data=array();
//http://192.168.1.190/gms/api/enter_seedling_quality.php?userid=1&grower_num=777&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&excellent=0&standard=1&average=0&poor=0&seasonid=1&sqliteid=1

if (isset($_GET['userid'])  && isset($_GET['latitude'])  && isset($_GET['longitude']) && isset($_GET['seasonid']) && isset($_GET['created_at']) && isset($_GET['sqliteid']) && isset($_GET['grower_num'])){


$userid=validate($_GET['userid']);
$seasonid=validate($_GET['seasonid']);
$lat=validate($_GET['latitude']);
$long=validate($_GET['longitude']);
$grower_num=validate($_GET['grower_num']);
$question=validate($_GET['question']);
$answer=validate($_GET['answer']);
$question_created_at=validate($_GET['question_created_at']);
$datetimes=validate($_GET['datetimes']);
$created_at=validate($_GET['created_at']);
$sqliteid=validate($_GET['sqliteid']);




$name=$_GET['name'];
$surname=$_GET['surname'];
$phone=$_GET['phone'];
$id_num=$_GET['id_num'];
$area=$_GET['area'];
$province=$_GET['province'];




$sql = "Select * from growers where grower_num='$grower_num' limit 1";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) { 
     
     $growerid=$row["id"];
   
       
     }

   }else{

        $grower_farm_sql = "INSERT INTO growers(userid,seasonid,grower_num,name,surname,phone,id_num,area,province,created_at) VALUES ($userid,$seasonid,'$grower_num','$name','$surname','$phone','$id_num','$area','$province','$created_at')";
         //$sql = "select * from login";
         if ($conn->query($grower_farm_sql)===TRUE) {

         }else{
          $temp=array("response"=>$conn->error,"hh"=>"kkk");
          array_push($data,$temp);
         }

     }




$sql = "Select * from growers where grower_num='$grower_num';";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $growerid=$row["id"];
  
    
   }

 }


// then insert loan
// insert into visits(userid,growerid,seasonid,latitude,longitude,created_at,description) value(NEW.userid,NEW.growerid,NEW.seasonid,NEW.latitude,NEW.longitude,NEW.created_at,"fertilization potassium");


  $question_created_at_found=0;

  $sql = "Select * from questionnaires_answers_by_grower where growerid=$growerid and question='$question' and  question_created_at='$question_created_at' and created_at='$created_at' and userid=$userid;";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
     
     $question_created_at_found=$row["id"];
    
      
     }

   }



  if ($growerid>0 && $question_created_at_found==0) {

   $insert_sql = "INSERT INTO questionnaires_answers_by_grower(userid,growerid,seasonid,latitude,longitude,question,answer,question_created_at,created_at,datetimes) VALUES ($userid,$growerid,$seasonid,'$lat','$long','$question','$answer','$question_created_at','$created_at','$datetimes')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
     $insert_sql = "insert into visits(userid,growerid,seasonid,latitude,longitude,created_at,description) value($userid,$growerid,$seasonid,'$lat','$long','$created_at','Questionnaire by grower');";
       //$gr = "select * from login";
       if ($conn->query($insert_sql)===TRUE) {
       
        // $last_id = $conn->insert_id;

         $temp=array("id"=>$sqliteid);
          array_push($data,$temp);

       }



   }


   }else{

   
   }





}




echo json_encode($data);


?>





