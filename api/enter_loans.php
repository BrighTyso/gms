<?php
require "conn.php";
require "validate.php";

$userid=0;
$growerid=0;
$lat="";
$long="";
$product="";
$quantity="";
$created_at="";
$description="";
$productid=0;
$seasonid=0;
$sqliteid=0;
$verifyLoan=0;
$hectares=0;

$data=array();




//http://192.168.1.190/gms/api/enter_loans.php?userid=1&product=sadza&quantity=1&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&description=12333&seasonid=1&sqliteid=1

if (isset($_GET['description']) && isset($_GET['userid'])  && isset($_GET['latitude'])  && isset($_GET['longitude'])  && isset($_GET['product']) && isset($_GET['seasonid']) && isset($_GET['quantity']) && isset($_GET['created_at']) && isset($_GET['sqliteid'])){


$userid=validate($_GET['userid']);
$seasonid=validate($_GET['seasonid']);
$lat=validate($_GET['latitude']);
$long=validate($_GET['longitude']);
$description=validate($_GET['description']);
$product=validate($_GET['product']);
$quantity=validate($_GET['quantity']);
$created_at=validate($_GET['created_at']);
$sqliteid=validate($_GET['sqliteid']);



$sql = "Select * from growers where grower_num='$description' or phone='$description'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $growerid=$row["id"];
  
    
   }

 }

 // get selected  products id


$product_sql = "Select * from products where name='$product'";
$result = $conn->query($product_sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $productid=$row["id"];
   
    
   }

 }

//check if loan is there


 $sql = "Select * from loans where loans.seasonid=$seasonid and productid=$productid  and  growerid=$growerid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

   $verifyLoan=1;
    
   }
 }




// then insert loan


  if ($productid>0  && $growerid>0 && $verifyLoan==0) {

   $insert_sql = "INSERT INTO loans(userid,growerid,productid,seasonid,quantity,latitude,longitude,created_at) VALUES ($userid,$growerid,$productid,$seasonid,$quantity,'$lat','$long','$created_at')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
    // $last_id = $conn->insert_id;

     $temp=array("id"=>$sqliteid);
      array_push($data,$temp);

   }


   }else{

   
   }





}




echo json_encode($data);


?>





