<?php
require_once("conn.php");
require "validate.php";
require "datasource.php";

$barcode=$_GET['barcode'];
$userid=$_GET['userid'];
$created_at=$_GET['created_at'];
$latitude=$_GET['latitude'];
$longitude=$_GET['longitude'];


$barcode_found=0;
$total_found=0;
$seasonid=0;
$sold_baleid=0;

$data1=array();
// get grower locations

if ($barcode!="") {



  $sql11 = "Select * from seasons where active=1 limit 1";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);

    $seasonid=$row["id"];
    
   }
 }
  



$sql = "Select * from sold_bales  where barcode='$barcode' and  userid=$userid and  seasonid=$seasonid";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);

    $sold_baleid=$row["id"];
    
   }
 }





$sql = "Select * from dispatch  where sold_balesid=$sold_baleid and userid=$userid";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);

    $barcode_found=$row["id"];
    
   }
 }



$sql1 = "Select * from total_dispatch where userid='$userid' and  seasonid=$seasonid";

$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);

    $total_found=$row["id"];
    
   }
 }




 if ($barcode_found==0 && $sold_baleid>0) {

   $insert_sql = "INSERT INTO dispatch(userid,sold_balesid,latitude,longitude,created_at) VALUES ($userid,$sold_baleid,'$latitude','$longitude','$created_at')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
    // $last_id = $conn->insert_id;

     if($total_found==0){

     $insert_sql = "INSERT INTO total_dispatch(userid,seasonid,quantity,created_at) VALUES ($userid,$seasonid,1,'$created_at')";
       //$gr = "select * from login";
       if ($conn->query($insert_sql)===TRUE) {
       
        // $last_id = $conn->insert_id;

         $temp=array("response"=>"success");
          array_push($data1,$temp);

          }


      }else{

       $user_sql1 = "update total_dispatch set quantity=quantity+1 where id=$total_found";
         //$sql = "select * from login";
         if ($conn->query($user_sql1)===TRUE) {

          $temp=array("response"=>"success");
          array_push($data1,$temp);

     
          }

      }


   }


}
}

 echo json_encode($data1);


?>

