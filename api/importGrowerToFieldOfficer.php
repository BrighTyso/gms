<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));




$response=array();

if (isset($data->seasonid) && isset($data->userid) && isset($data->username) && isset($data->grower_num)  && isset($data->created_at)){


$userid=$data->userid;
$officerid=0;
$username=$data->username;
$seasonid=$data->seasonid;
$growerid=0;
$grower_num=validate($data->grower_num);
$created_at=$data->created_at;
$active_grower_found=0;
$grower_found=0;



$sql = "Select * from users where username='$username' or surname='$username' or name='$username'";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

      $officerid=$row["id"];
      
     }

   }



  $sql = "Select * from growers where grower_num='$grower_num'";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

      $growerid=$row["id"];
      
     }

   }



$sql = "Select * from active_growers where growerid=$growerid and seasonid=$seasonid";
        $result = $conn->query($sql);
         
         if ($result->num_rows > 0) {
           // output data of each row
           while($row = $result->fetch_assoc()) {
           
           $active_grower_found=$row["id"];
          
            
           }

         }



  $sql = "Select * from grower_field_officer where growerid=$growerid and seasonid=$seasonid";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $grower_found=$row["id"];
      
     }

   }




   if ($grower_found==0 && $growerid>0 && $officerid>0) {


     $user_sql = "INSERT INTO grower_field_officer(userid,seasonid,growerid,field_officerid,created_at) VALUES ($userid,$seasonid,$growerid,$officerid,'$created_at')";
                   //$sql = "select * from login";
       if ($conn->query($user_sql)===TRUE) {

            if ($active_grower_found==0) {
            $user_sql = "INSERT INTO active_growers(userid,growerid,seasonid) VALUES ($userid,$growerid,$seasonid)";
             //$sql = "select * from login";
                 if ($conn->query($user_sql)===TRUE) {

                  $temp=array("response"=>"success");
                   array_push($response,$temp);

                 }
              }else{
                 $temp=array("response"=>"success");
                 array_push($response,$temp);
              }
        
       }else{
          $temp=array("response"=>$conn->error);
            array_push($response,$temp);
       }
       
   }else{
    if ($grower_found>0) {

          $user_sql = "update grower_field_officer set field_officerid=$officerid  where growerid = $growerid and seasonid=$seasonid ";
       //$sql = "select * from login";
       if ($conn->query($user_sql)===TRUE) {


        if ($active_grower_found==0) {
            $user_sql = "INSERT INTO active_growers(userid,growerid,seasonid) VALUES ($userid,$growerid,$seasonid)";
             //$sql = "select * from login";
                 if ($conn->query($user_sql)===TRUE) {

                  $temp=array("response"=>"success update");
                   array_push($response,$temp);

                 }
              }else{
                 $temp=array("response"=>"success update");
                 array_push($response,$temp);
              }

       
         
         
       }
    }else if($growerid==0){
      $temp=array("response"=>"Grower not found");
      array_push($response,$temp);

    }
   }


}





 echo json_encode($response);






?>





