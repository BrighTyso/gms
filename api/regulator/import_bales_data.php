<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";



$data = json_decode(file_get_contents("php://input"));


$barcode="";
$mass=0;
$price=0;
$userid=0;
$grower_num="";
$created_at="";
$latitude="";
$longitude="";


$barcode_found=0;
$total_found=0;
$grower_found=0;
$seasonid=0;

$data1=array();

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($data->userid) &&  isset($data->mass) && isset($data->created_at) && isset($data->price) && isset($data->grower_num) && isset($data->barcode) && $data->barcode!=""){




$barcode=$data->barcode;
$mass=$data->mass;
$price=$data->price;
$userid=$data->userid;
$grower_num=$data->grower_num;
$created_at=$data->created_at;
$latitude=$data->latitude;
$longitude=$data->longitude;


$response=0;
$farm_response=0;


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





$sql11 = "Select * from growers where grower_num='$grower_num'";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);

    $grower_found=$row["id"];

 
    
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

    $barcode_found=1;
   
    
   }
 }






$sql1 = "Select * from total_sold_bales where userid='$userid' and  seasonid=$seasonid";

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




 if ($barcode_found==0 && $grower_found>0 && $seasonid>0) {


   $insert_sql = "INSERT INTO sold_bales(userid,seasonid,growerid,barcode,mass,price,latitude,longitude,created_at) VALUES ($userid,$seasonid,$grower_found,'$barcode',$mass,$price,'$latitude','$longitude','$created_at')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
    // $last_id = $conn->insert_id;

     if($total_found==0){

     $insert_sql = "INSERT INTO total_sold_bales(userid,seasonid,quantity,created_at) VALUES ($userid,$seasonid,1,'$created_at')";
       //$gr = "select * from login";
       if ($conn->query($insert_sql)===TRUE) {
       
        // $last_id = $conn->insert_id;

         $temp=array("response"=>"success");
          array_push($data1,$temp);

          }


      }else{

       $user_sql1 = "update total_sold_bales set quantity=quantity+1 where id=$total_found";
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


