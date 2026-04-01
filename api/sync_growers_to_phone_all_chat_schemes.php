<?php
require "conn.php";

$data=array();

$data1=array();

$scheme_growers=array();
$scheme_data=array();
$scheme_products=array();
$scheme_all_data=array();

$combine_data=array();

//http://192.168.1.190/gms/api/get_products.php

$username=$_GET["username"];

$start_date=$_GET['start_date'];
$end_date=$_GET['end_date'];

$userid=0;
$seasonid=0;
$rule=0;
$file_name="";



if ($username!="") {
  

$sql = "Select * from users where  username='$username' and  active=1";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $userid=$row["id"];

   }
 }




$sql = "Select * from seasons where active=1 limit 1";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $seasonid=$row["id"];

   }
 }



 $sql = "Select rule from download_growers_rule  limit 1";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $rule=$row["rule"];

   }
 }


if ($userid>0) {


     $sql = "Select products.name,units,package_units,products.id,product_type.name as product_type_name from products join product_type on product_type.id=products.product_typeid";
    $result = $conn->query($sql);
     
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
        $temp=array("name"=>$row["name"],"units"=>$row["units"],"package_units"=>$row["package_units"],"package_units"=>$row["package_units"],"productid"=>$row["id"],"product_type_name"=>$row["product_type_name"]);
        array_push($data,$temp);
        
       }
     }




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
    array_push($scheme_all_data,$temp);
    
   }
 }




$sql = "Select * from disburse_products_by_date join products on products.id=disburse_products_by_date.productid where end_date between '$start_date' and '$end_date'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $temp=array("name"=>$row["name"],"id"=>$row["id"],"seasonid"=>$row["seasonid"],"start_date"=>$row["start_date"],"end_date"=>$row["end_date"],"created_at"=>$row["start_date"]);
    array_push($data1,$temp);
    
   }
 }



$temp=array("products"=>$data,"schemes"=>$scheme_all_data,"disbursement"=>$data1);
    array_push($combine_data,$temp);


}



$file_name=$userid."-schemes-".time().".txt";
$path="../images/".$file_name;

file_put_contents($path, json_encode($combine_data, JSON_PRETTY_PRINT), FILE_APPEND);


}


$url_d=array();

$temp=array("file_url"=>$file_name,"data"=>$combine_data,"time"=>time(),"username"=>$username,"userid"=>$userid,"seasonid"=>$seasonid,"file_url1"=>$file_name,"file_url2"=>$file_name,"file_url3"=>$file_name,"file_url4"=>$file_name,"file_url5"=>$file_name,"file_url6"=>$file_name,);
array_push($url_d,$temp);


echo json_encode($url_d); 



?>