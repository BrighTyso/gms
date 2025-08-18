<?php
require "conn.php";
require "validate.php";



$data=array();
$scheme_data=array();
$scheme_products=array();
$scheme_growers=array();
$scheme_otp=array();


//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($_GET['userid']) && isset($_GET['seasonid'])){

$seasonid=$_GET['seasonid'];
$userid=$_GET['userid'];
$schemeid=0;
$scheme_hectareid=0;

$sql = "Select distinct scheme_hectares.quantity,description,scheme_hectares.id,scheme.id as scheme_id,seasonid from scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id where scheme_hectares.seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $schemeid=$row["scheme_id"];
    $scheme_hectareid=$row["id"];

    $scheme_data=array();
    $scheme_products=array();
    $scheme_growers=array();
    $scheme_otp=array();

     $temp=array("quantity"=>$row["quantity"],"description"=>$row["description"],"scheme_hectares_id"=>$row["id"],"seasonid"=>$seasonid,"scheme_id"=>$row["scheme_id"]);
    array_push($scheme_data,$temp);




      $sql1 = "Select distinct quantity,name,scheme_hectares_products.id,products.id as productid from scheme_hectares_products join products on scheme_hectares_products.productid=products.id   where scheme_hectares_products.scheme_hectaresid=$scheme_hectareid ";
      $result1 = $conn->query($sql1);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";


          $temp=array("quantity"=>$row1["quantity"],"product_name"=>$row1["name"],"id"=>$row1["id"],"productid"=>$row1["productid"]);
          array_push($scheme_products,$temp);
          
         }
       }




      $sql1 = "Select distinct grower_num,used,otp from growers_otp join growers on growers_otp.growerid=growers.id  where growers_otp.used=0 and growers_otp.seasonid=$seasonid ";
      $result1 = $conn->query($sql1);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          $temp=array("grower_num"=>$row1["grower_num"],"used"=>$row1["used"],"otp"=>$row1["otp"]);
          array_push($scheme_otp,$temp);
          
         }
       }


      $sql1 = "Select distinct grower_num from scheme_hectares_growers join growers on scheme_hectares_growers.growerid=growers.id  where  scheme_hectares_growers.scheme_hectaresid=$scheme_hectareid";
      $result1 = $conn->query($sql1);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          $temp=array("grower_num"=>$row1["grower_num"]);
          array_push($scheme_growers,$temp);
          
         }
       }


    $temp=array("scheme"=>$scheme_data,"scheme_growers"=>$scheme_growers,"scheme_products"=>$scheme_products);
    array_push($data,$temp);
    
   }
 }






}


echo json_encode($data);