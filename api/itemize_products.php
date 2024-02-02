<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$created_at="";
$found=0;
$found_price=0;
$processed_found=0;
$found_product_has_items=0;

$response=array();

if (isset($data->userid)  && isset($data->quantity)&& isset($data->productid)  && isset($data->created_at) && isset($data->seasonid) && isset($data->price) && isset($data->product_itemid)){


$userid=$data->userid;
$productid=$data->productid;
$created_at=$data->created_at;
$seasonid=$data->seasonid;
$quantity=$data->quantity;
$product_itemid=$data->product_itemid;
$price=$data->price;


$sql = "Select * from itemized_product where product_itemid=$product_itemid and productid=$productid and seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found=$row["id"];
    
    
   }
 }




$sql = "Select * from itemized_product where  productid=$productid and seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found_product_has_items=$row["id"];
    
    
   }
 }



$sql = "Select * from prices where productid=$productid and seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found_price=1;
    
   }
 }




 $sql = "Select * from loans where processed=1 and productid=$productid and seasonid=$seasonid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $processed_found=1;
    
   }
 }


if($found_price>0 && $found_product_has_items==0 && $processed_found==0){


    $sql = "UPDATE prices SET amount = 0  WHERE productid=$productid and seasonid=$seasonid";

   //$sql = "select * from login";
   if ($conn->query($sql)===TRUE) {
     
    //  $temp=array("response"=>"success");
    // array_push($response,$temp);

   }else{

   //  $temp=array("response"=>"failed");
   // array_push($response,$temp);
   }



}


if ($found==0 && $processed_found==0) {
   $user_sql = "INSERT INTO itemized_product(userid,seasonid ,productid ,quantity,product_itemid,price ,created_at) VALUES ($userid,$seasonid,$productid,$quantity,$product_itemid,'$price','$created_at')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
        $last_id = $conn->insert_id;

        $sql = "UPDATE prices SET amount = amount + $price  WHERE productid=$productid and seasonid=$seasonid";

           //$sql = "select * from login";
           if ($conn->query($sql)===TRUE) {
             
             $temp=array("response"=>"success");
            array_push($response,$temp);

           }else{

            $temp=array("response"=>"failed");
           array_push($response,$temp);
           }


   }else{

     $temp=array("response"=>$conn->error);
     array_push($response,$temp);

   }

}else{

    if ($processed_found>0) {
       $temp=array("response"=>"Product Already Processed"); 
        array_push($response,$temp);
    }else{
        $temp=array("response"=>"Item Already Created");
        array_push($response,$temp);
    }

    

}
 



}else{

	 $temp=array("response"=>"Field Empty");
     array_push($response,$temp);
  
}


echo json_encode($response);



?>





