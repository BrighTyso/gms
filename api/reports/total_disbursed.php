<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=$data->seasonid;
$productid=$data->productid;

$data1=array();
// get grower locations

if ($userid!="") {



    
      $sql11 = "Select * from total_disbursement join products on products.id=total_disbursement.productid where productid=$productid and total_disbursement.seasonid=$seasonid";

      $result = $conn->query($sql11);
       
       if ($result->num_rows > 0) {
         // output data of each row
         while($row = $result->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("name"=>$row["name"],"quantity"=>$row["quantity"]);
          array_push($data1,$temp);

         
         }
       }



}

 echo json_encode($data1);


?>


