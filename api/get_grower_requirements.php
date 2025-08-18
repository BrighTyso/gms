<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";



$data = json_decode(file_get_contents("php://input"));


$data1=array();


if (isset($data->userid) && isset($data->description)) {
 
$userid=$data->userid;
$description=$data->description;
$seasonid=$data->seasonid;

if ($description=="") {

  $sql = "Select distinct grower_field_loans.id as loanid, products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,quantity,units,grower_field_loans.created_at, users.username,farmer_comment,adjustment_quantity,grower_field_loans.hectares from grower_field_loans join growers on growers.id=grower_field_loans.growerid join products on grower_field_loans.productid=products.id join users on users.id=grower_field_loans.userid  where grower_field_loans.seasonid=$seasonid and (grower_field_loans.productid) not in (Select loans.productid from loans where loans.productid=grower_field_loans.productid and loans.growerid=growers.id and loans.seasonid=$seasonid) order by grower_num limit 200";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $temp=array("productid"=>$row["productid"],"name"=>$row["name"],"id"=>$row["id"],"surname"=>$row["surname"],"grower_num"=>$row["grower_num"],"product_name"=>$row["product_name"],"quantity"=>$row["quantity"],"units"=>$row["units"],"created_at"=>$row["created_at"],"username"=>$row["username"],"loanid"=>$row["loanid"],"farmer_comment"=>$row["farmer_comment"],"adjustment_quantity"=>$row["adjustment_quantity"],"hectares"=>$row["hectares"]);
    array_push($data1,$temp);
    
   }
 }

}else{


$sql = "Select distinct grower_field_loans.id as loanid,products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,quantity,units,grower_field_loans.created_at, users.username,farmer_comment,adjustment_quantity,grower_field_loans.hectares  from grower_field_loans join growers on growers.id=grower_field_loans.growerid join products on grower_field_loans.productid=products.id  join users on users.id=grower_field_loans.userid  where grower_field_loans.seasonid=$seasonid and (grower_num='$description' or  users.username='$description' or province='$description' or  grower_num  like '%$description') and (grower_field_loans.productid) not in (Select loans.productid from loans where loans.productid=grower_field_loans.productid and loans.growerid=growers.id and loans.seasonid=$seasonid) order by grower_num limit 150";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("productid"=>$row["productid"],"name"=>$row["name"],"id"=>$row["id"],"surname"=>$row["surname"],"grower_num"=>$row["grower_num"],"product_name"=>$row["product_name"],"quantity"=>$row["quantity"],"units"=>$row["units"],"created_at"=>$row["created_at"],"username"=>$row["username"],"loanid"=>$row["loanid"],"farmer_comment"=>$row["farmer_comment"],"adjustment_quantity"=>$row["adjustment_quantity"],"hectares"=>$row["hectares"]);
    array_push($data1,$temp);
    
   }
 }

}




}





 echo json_encode($data1);





?>





