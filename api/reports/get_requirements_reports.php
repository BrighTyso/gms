<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$data1=array();
$loans=array();
$totals=array();


if (isset($data->userid) && isset($data->description)) {
 
$userid=$data->userid;
$seasonid=$data->seasonid;
$description=$data->description;

$no_of_grower=0;
$total_hectares=0;

if ($description!="") {

  $sql1 = "Select distinct growers.name,growers.id,growers.surname,growers.grower_num,grower_field_loans.hectares from grower_field_loans join growers on growers.id=grower_field_loans.growerid join products on grower_field_loans.productid=products.id join users on users.id=grower_field_loans.userid  where grower_field_loans.seasonid=$seasonid and users.username='$description' order by grower_num ";
  $result1 = $conn->query($sql1);
  $no_of_grower=$result1->num_rows;
   if ($result1->num_rows > 0) {
     // output data of each row
     while($row1 = $result1->fetch_assoc()) {

      $growerid=$row1["id"];
      $total_hectares+=$row1["hectares"];
      $loans=array();
      $totals=array();

       $sql = "Select distinct grower_field_loans.id as loanid, products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,quantity,units,grower_field_loans.created_at, users.username,farmer_comment,adjustment_quantity,grower_field_loans.hectares,adjust from grower_field_loans join growers on growers.id=grower_field_loans.growerid join products on grower_field_loans.productid=products.id join users on users.id=grower_field_loans.userid  where grower_field_loans.seasonid=$seasonid and growers.id=$growerid order by grower_num ";
      $result = $conn->query($sql);
       
       if ($result->num_rows > 0) {
         // output data of each row
         while($row = $result->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          $temp=array("productid"=>$row["productid"],"name"=>$row["name"],"id"=>$row["id"],"surname"=>$row["surname"],"grower_num"=>$row["grower_num"],"product_name"=>$row["product_name"],"quantity"=>$row["quantity"],"units"=>$row["units"],"created_at"=>$row["created_at"],"username"=>$row["username"],"loanid"=>$row["loanid"],"farmer_comment"=>$row["farmer_comment"],"adjustment_quantity"=>$row["adjustment_quantity"],"hectares"=>$row["hectares"],"adjust"=>$row["adjust"]);
          array_push($loans,$temp);
          
         }
       }

       $temp1=array("total_hectares"=>$total_hectares,"no_of_grower"=>$no_of_grower);
       array_push($totals,$temp1);
     
       
       $temp=array("grower_num"=>$row1["grower_num"],"surname"=>$row1["surname"],"name"=>$row1["name"],"inputs"=>$loans,"totals"=>$totals);
       array_push($data1,$temp);

     }
   }


}else{


$sql1 = "Select distinct growers.name,growers.id,growers.surname,growers.grower_num,grower_field_loans.hectares from grower_field_loans join growers on growers.id=grower_field_loans.growerid join products on grower_field_loans.productid=products.id join users on users.id=grower_field_loans.userid  where grower_field_loans.seasonid=$seasonid  order by grower_num ";
  $result1 = $conn->query($sql1);
  $no_of_grower=$result1->num_rows;

   if ($result1->num_rows > 0) {
     // output data of each row
     while($row1 = $result1->fetch_assoc()) {

       $growerid=$row1["id"];
       $total_hectares+=$row1["hectares"];
       $loans=array();
       $totals=array();

       $sql = "Select distinct grower_field_loans.id as loanid, products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,quantity,units,grower_field_loans.created_at, users.username,farmer_comment,adjustment_quantity,grower_field_loans.hectares,adjust from grower_field_loans join growers on growers.id=grower_field_loans.growerid join products on grower_field_loans.productid=products.id join users on users.id=grower_field_loans.userid  where grower_field_loans.seasonid=$seasonid and growers.id=$growerid order by grower_num ";
      $result = $conn->query($sql);
       
       if ($result->num_rows > 0) {
         // output data of each row
         while($row = $result->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          $temp=array("productid"=>$row["productid"],"name"=>$row["name"],"id"=>$row["id"],"surname"=>$row["surname"],"grower_num"=>$row["grower_num"],"product_name"=>$row["product_name"],"quantity"=>$row["quantity"],"units"=>$row["units"],"created_at"=>$row["created_at"],"username"=>$row["username"],"loanid"=>$row["loanid"],"farmer_comment"=>$row["farmer_comment"],"adjustment_quantity"=>$row["adjustment_quantity"],"hectares"=>$row["hectares"],"adjust"=>$row["adjust"]);
          array_push($loans,$temp);
          
         }
       }

       
       $temp1=array("total_hectares"=>$total_hectares,"no_of_grower"=>$no_of_grower);
       array_push($totals,$temp1);
     
       
       $temp=array("grower_num"=>$row1["grower_num"],"surname"=>$row1["surname"],"name"=>$row1["name"],"inputs"=>$loans,"totals"=>$totals);
       array_push($data1,$temp);

     }
   }

}




}



 echo json_encode($data1);


?>
