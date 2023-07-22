<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";



$data = json_decode(file_get_contents("php://input"));


$data1=array();
$found=0;


if (isset($data->userid)  && isset($data->seasonid) && isset($data->description)) {
 
$userid=$data->userid;
$seasonid=$data->seasonid;
$description=$data->description;


if ($description=="") {

  $sql = "Select distinct loans.id as loanid ,products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,quantity,units,loans.created_at,verified, users.username,amount,receipt_number,product_amount,product_total_cost  from loans join growers on growers.id=loans.growerid join products on loans.productid=products.id join users on users.id=loans.userid join prices on prices.productid=loans.productid where loans.seasonid=$seasonid and  prices.seasonid=$seasonid and loans.userid=$userid order by loans.id desc limit 30";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

   $temp=array("productid"=>$row["productid"],"name"=>$row["name"],"id"=>$row["loanid"],"surname"=>$row["surname"],"grower_num"=>$row["grower_num"],"product_name"=>$row["product_name"],"quantity"=>$row["quantity"],"units"=>$row["units"],"created_at"=>$row["created_at"],"verified"=>$row["verified"],"username"=>$row["username"],"amount"=>$row["amount"],"receipt_number"=>$row["receipt_number"],"product_amount"=>$row["product_amount"],"product_total_cost"=>$row["product_total_cost"]);
    array_push($data1,$temp);
    
   }
 }

}else{


  // $sql = "Select distinct products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,quantity,units,loans.created_at,verified, users.username, amount,receipt_number,product_amount,product_total_cost  from loans join growers on growers.id=loans.growerid join products on loans.productid=products.id join contracted_hectares on contracted_hectares.growerid=growers.id join users on users.id=contracted_hectares.userid join prices on prices.productid=loans.productid  where loans.seasonid=$seasonid and prices.seasonid=$seasonid and contracted_hectares.seasonid=$seasonid  and (grower_num='$description' or  users.username='$description' or province='$description')  ";


$sql = "Select distinct loans.id as loanid ,products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,quantity,units,loans.created_at,verified, users.username, amount,receipt_number,product_amount,product_total_cost  from loans join growers on growers.id=loans.growerid join products on loans.productid=products.id join users on users.id=loans.userid  join prices on prices.productid=loans.productid  where loans.seasonid=$seasonid and prices.seasonid=$seasonid and loans.userid=$userid  and (grower_num='$description' or  users.username='$description' or province='$description' or receipt_number='$description' or grower_num like '%$description')  order by loans.id desc limit 30";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("productid"=>$row["productid"],"name"=>$row["name"],"id"=>$row["loanid"],"surname"=>$row["surname"],"grower_num"=>$row["grower_num"],"product_name"=>$row["product_name"],"quantity"=>$row["quantity"],"units"=>$row["units"],"created_at"=>$row["created_at"],"verified"=>$row["verified"],"username"=>$row["username"],"amount"=>$row["amount"],"receipt_number"=>$row["receipt_number"],"product_amount"=>$row["product_amount"],"product_total_cost"=>$row["product_total_cost"]);
    array_push($data1,$temp);
    
   }
 }

}

}







 echo json_encode($data1);





?>





