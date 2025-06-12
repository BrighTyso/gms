<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=$data->seasonid;
$description="";
$hectares_1="";

$data1=array();
$response=array();
// get grower locations

if ($userid!="") {
  
$sql11 = "Select distinct description,quantity from  scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id where scheme_hectares.seasonid=$seasonid ";

$result3 = $conn->query($sql11);
 
 if ($result3->num_rows > 0) {
   // output data of each row
   while($row13 = $result3->fetch_assoc()) {


          $description=$row13["description"];
          $hectares_1=$row13["quantity"];
          $data1=array();


          $sql1 = "Select scheme_hectares_products.quantity as products_quantity,description,scheme_hectares.quantity as hectares,products.name,amount from scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id join scheme_hectares_products on scheme_hectares_products.scheme_hectaresid=scheme_hectares.id join products on products.id=scheme_hectares_products.productid join prices on prices.productid=scheme_hectares_products.productid   where scheme_hectares.seasonid=$seasonid and prices.seasonid=$seasonid and scheme.description='$description' order by scheme.description";
        $result1 = $conn->query($sql1);
         
         if ($result1->num_rows > 0) {
           // output data of each row
           while($row = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("products_quantity"=>$row["products_quantity"],"description"=>$row["description"],"hectares"=>$row["hectares"],"name"=>$row["name"],"amount"=>$row["amount"]);
          array_push($data1,$temp);

         
         }
       }


  $interest_amount=0;

   $sql15 = "Select parameters.name,value from charges_amount join parameters on parameters.id=charges_amount.parameterid where charges_amount.seasonid=$seasonid and chargeid=1 limit 1 ";

    $result5 = $conn->query($sql15);
     
     if ($result5->num_rows > 0) {
       // output data of each row
       while($row5 = $result5->fetch_assoc()) {

        $interest_amount=$row5["value"];
       
       }
     }




      $temp=array("description"=>$description,"hectares"=>$hectares_1,"scheme_data"=>$data1,"interest_value"=>$interest_amount);

      array_push($response,$temp);


   }

 }




}

 echo json_encode($response);


?>


