<?php
require "conn.php";
require "validate.php";



$data=array();

$lat="";
$long="";

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($_GET['userid'])){

$seasonid=$_GET['seasonid'];
$userid=$_GET['userid'];

$sql = "Select distinct products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,quantity,units,loans.created_at,verified, users.username,receipt_number,product_amount,product_total_cost,loans.seasonid  from loans join growers on growers.id=loans.growerid join products on loans.productid=products.id join users on users.id=loans.userid   where loans.seasonid=$seasonid ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("productid"=>$row["productid"],"name"=>$row["name"],"id"=>$row["id"],"surname"=>$row["surname"],"grower_num"=>$row["grower_num"],"product_name"=>$row["product_name"],"quantity"=>$row["quantity"],"units"=>$row["units"],"created_at"=>$row["created_at"],"verified"=>$row["verified"],"username"=>$row["username"],"receipt_number"=>$row["receipt_number"],"product_amount"=>$row["product_amount"],"product_total_cost"=>$row["product_total_cost"],"seasonid"=>$row["seasonid"]);
    array_push($data,$temp);
    
   }
 }


}

 echo json_encode($data);