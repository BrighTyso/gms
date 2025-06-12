<?php
require "conn.php";
require "validate.php";



$response=array();
$otps=array();
$home_location=array();
$scheme_growers=array();
$loans=array();
$prints=array();

$lat="";
$long="";

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($_GET['userid'])){

$seasonid=$_GET['seasonid'];
$userid=$_GET['userid'];
$grower_num=$_GET['grower_num'];



$sql = "Select growers_otp.seasonid,grower_num,used,sent,otp from growers_otp join growers on growers.id=growers_otp.growerid where growers_otp.seasonid=$seasonid and grower_num='$grower_num' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) { 
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("seasonid"=>$row["seasonid"],"grower_num"=>$row["grower_num"],"otp"=>$row["otp"],"used"=>$row["used"],"sent"=>$row["sent"]);
    array_push($otps,$temp);
    
   }
 }



 $sql = "Select growers.grower_num, growers.name as grower_name , lat_long.latitude ,lat_long.longitude , users.username from lat_long join users on users.id=lat_long.userid join growers on growers.id=lat_long.growerid where lat_long.seasonid=$seasonid and grower_num='$grower_num' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $temp=array("grower_name"=>$row["grower_name"],"latitude"=>$row["latitude"] ,"longitude"=>$row["longitude"],"grower_num"=>$row["grower_num"]);
    array_push($home_location,$temp);
    
   }
 }


  $sql1 = "Select distinct grower_num,quantity,scheme_hectares.seasonid,scheme.description from scheme_hectares_growers join growers on scheme_hectares_growers.growerid=growers.id join scheme_hectares on scheme_hectares.id=scheme_hectares_growers.scheme_hectaresid join scheme on scheme.id=scheme_hectares.schemeid  where  grower_num='$grower_num' and scheme_hectares.seasonid=$seasonid limit 1";
      $result1 = $conn->query($sql1);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          $temp=array("grower_num"=>$row1["grower_num"],"quantity"=>$row1["quantity"],"seasonid"=>$row1["seasonid"],"description"=>$row1["description"]);
          array_push($scheme_growers,$temp);
          
         }
       }





        $sql1 = "Select distinct feature,grower_num from grower_finger_print join growers on grower_finger_print.growerid=growers.id  where  grower_num='$grower_num' limit 1";
        $result1 = $conn->query($sql1);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          $temp=array("grower_num"=>$row1["grower_num"],"feature"=>$row1["feature"],"seasonid"=>$row1["seasonid"]);
          array_push($prints,$temp);
          
         }
       }






       $sql = "Select distinct products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,quantity,units,loans.created_at,verified, users.username,receipt_number,product_amount,product_total_cost,loans.seasonid  from loans join growers on growers.id=loans.growerid join products on loans.productid=products.id join users on users.id=loans.userid   where loans.seasonid=$seasonid and grower_num='$grower_num'";
      $result = $conn->query($sql);
       
       if ($result->num_rows > 0) {
         // output data of each row
         while($row = $result->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

           $temp=array("productid"=>$row["productid"],"name"=>$row["name"],"id"=>$row["id"],"surname"=>$row["surname"],"grower_num"=>$row["grower_num"],"product_name"=>$row["product_name"],"quantity"=>$row["quantity"],"units"=>$row["units"],"created_at"=>$row["created_at"],"verified"=>$row["verified"],"username"=>$row["username"],"receipt_number"=>$row["receipt_number"],"product_amount"=>$row["product_amount"],"product_total_cost"=>$row["product_total_cost"],"seasonid"=>$row["seasonid"]);
          array_push($loans,$temp);
          
         }
       }



$temp=array("otps"=>$otps,"home_location"=>$home_location,"scheme"=>$scheme_growers,"loans"=>$loans,"prints"=>$prints);
array_push($response,$temp);


}

 echo json_encode($response);