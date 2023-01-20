<?php  

require "conn.php";

$userid=0;
$password="";
$change="";



$sql = "Select * from users";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

      $userid=$row["id"];
      $password=md5($row["hash"]);


       $user_sql = "update users set hash='$password' where id=$userid";
         //$sql = "select * from login";
         if ($conn->query($user_sql)===TRUE) {
         
           echo json_encode("success");

         }else{

          echo json_encode($conn->error);

         }


   }


 }





?>