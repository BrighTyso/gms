<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=$data->seasonid;
$purchasing_orderid=$data->purchasing_orderid;

$data1=array();

$shipment=array();

$products=array();
// get grower locations

if ($userid!="") {
  


$sql1 = "Select * from purchasing_order where id=$purchasing_orderid";
  $result1 = $conn->query($sql1);
   
   if ($result1->num_rows > 0) {
     // output data of each row
     while($row = $result1->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);


    $sql11 = "Select address,phone,email from purchasing_order_shipment where purchasing_orderid=$purchasing_orderid";
      $result11 = $conn->query($sql11);
       
       if ($result11->num_rows > 0) {
         // output data of each row
         while($row1 = $result11->fetch_assoc()) {

           $temp=array("address"=>$row1["address"],"phone"=>$row1["phone"] ,"email"=>$row1["email"]);
            array_push($shipment,$temp);

         }
       }



        $sql11 = "Select * from purchasing_order_products join products on products.id=purchasing_order_products.productid where purchasing_orderid=$purchasing_orderid";
      $result11 = $conn->query($sql11);
       
       if ($result11->num_rows > 0) {
         // output data of each row
         while($row1 = $result11->fetch_assoc()) {


          $temp=array("unit_price"=>$row1["unit_price"],"quantity"=>$row1["quantity"] ,"name"=>$row1["name"]);
          array_push($products,$temp);

         }
       }




    $temp=array("shipment"=>$shipment,"products"=>$products);
    array_push($data1,$temp);

   
   }
 }


}

 echo json_encode($data1);


?>


