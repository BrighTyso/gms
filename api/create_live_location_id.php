<?php
require "conn.php";
require "validate.php";

$response=array();


//http://192.168.1.190/gms/api/get_user.php?username=brightkaponda&hash=brightkaponda&access_code=1234




if (isset($_GET["firebaseid"])) {

  $userid=$_GET["userid"];
  $firebaseid=$_GET["firebaseid"];
  $created_at=$_GET["created_at"];
  $active=0;
 

$sql = "select distinct * from live_location_status where active=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $active=$row["id"];
    
   }
 }




 if ($active>0) {

          $key_found=0;


           $sql = "select distinct * from live_location_userid where userid=$userid";
          $result = $conn->query($sql);
         
         if ($result->num_rows > 0) {
           // output data of each row
           while($row = $result->fetch_assoc()) {
            // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
            $key_found=$row["id"];
            $firebaseid=$row["firebaseid"];
            
           }
         }

         if ($key_found==0) {
           

           $user_sql = "INSERT INTO live_location_userid(userid,firebaseid,created_at) VALUES ($userid,'$firebaseid','$created_at')";
           //$sql = "select * from login";
           if ($conn->query($user_sql)===TRUE) {
           
             $last_id = $conn->insert_id;
              $temp=array("response"=>"success","key"=>$firebaseid);
              array_push($response,$temp);

           }else{

           $temp=array("response"=>$conn->error);
           array_push($response,$temp);

           }

        }else{

          $temp=array("response"=>"success","key"=>$firebaseid);
          array_push($response,$temp);
        }

 }else{

  $temp=array("response"=>"Live Location Not Set");
  array_push($response,$temp);

 }


}






 echo json_encode($response); 

?>