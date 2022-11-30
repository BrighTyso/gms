<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));


$data1=array();

$productid=0;
$seasonid=0;
$growerid=0;
$storeid=0;
$contracted_to=0;
$old_quantity=0;

$userid=$data->userid;
$lat=$data->latitude;
$long=$data->longitude;
$quantity=$data->quantity;
$created_at=$data->created_at;
$description=$data->grower;
$product=$data->product;
$season=$data->season;
$username=$data->username;
$sqliteid=0;
$verifyLoan=0;
$verifyHectares=0;
$disbursement_trucksid=0;
$disbursementid=0;
$hectares=$data->hectares;






//http://192.168.1.190/gms/api/enter_loans.php?userid=1&product=sadza&quantity=1&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&description=12333&seasonid=1&sqliteid=1

if (isset($userid) && isset($description)  && isset($lat)  && isset($long)  && isset($product) && isset($quantity) && isset($season) && isset($created_at) && isset($hectares) && isset($username)){





$sql = "Select status from regulator_sync_status where status=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $statusid=$row["status"];

  
   }

 }




 $sql = "Select * from seasons where name='$season'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $seasonid=$row["id"];
   
    
   }

 }







 if ($statusid>0 && $seasonid>0 ) {







$sql = "Select * from growers where grower_num='$description'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) { 
   
   $growerid=$row["id"];

    
    
   }

 }


//  $sql = "Select * from contracted_to where growerid=$growerid and seasonid=$seasonid";
// $result = $conn->query($sql);
 
//  if ($result->num_rows > 0) {
//    // output data of each row
//    while($row = $result->fetch_assoc()) { 
   
//    $contracted_to=$row["id"];
    
    
//    }

//  }

 // get selected  products id


$product_sql = "Select * from products  where name='$product'";
$result = $conn->query($product_sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $productid=$row["id"];

   
    
   }

 }

//check if loan is there


 $sql = "Select * from loans where loans.seasonid=$seasonid and productid=$productid  and  growerid=$growerid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

   $verifyLoan=1;
  
    
   }
 }



//checks if hectares are found
  $sql1 = "Select * from contracted_hectares where contracted_hectares.seasonid=$seasonid and growerid=$growerid";
$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

   $verifyHectares=1;
   
    
   }
 }


$sql2 = "Select store.id,quantity from store join store_items on store.id=store_items.storeid where name='$username' and productid=$productid and quantity>0 and quantity>=$quantity";
$result = $conn->query($sql2);
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) { 
   
   $storeid=$row["id"];
   $quantity_Enough=$row["id"];
    $old_quantity=$row["quantity"];


   }

 }


// then insert loan

  if ($productid>0  && $growerid>0 && $verifyLoan==0 && $productid>0 && $storeid>0) {

    
     $insert_sql = "INSERT INTO loans(userid,growerid,productid,seasonid,quantity,latitude,longitude,hectares,verified,created_at) VALUES ($userid,$growerid,$productid,$seasonid,$quantity,'$lat','$long','$hectares',1,'$created_at')";
         //$gr = "select * from login";
         if ($conn->query($insert_sql)===TRUE) {
         
          // $last_id = $conn->insert_id;

         if ($verifyHectares==0) {

         $insert_sql = "INSERT INTO contracted_hectares(userid,growerid,seasonid,hectares,created) VALUES ($userid,$growerid,$seasonid,'$hectares','$created_at')";
         //$gr = "select * from login";
                if ($conn->query($insert_sql)===TRUE) {

                   $user_sql1 = "update store_items set quantity=quantity-$quantity  where storeid=$storeid and productid=$productid";
                         //$sql = "select * from login";
                         if ($conn->query($user_sql1)===TRUE) {

                            $last_id = $conn->insert_id;
                          
                            $new_quantity=$old_quantity-$quantity;


                            $user_sql2 = "INSERT INTO arc_products(userid,storeitemid,old_quantity,new_quantity,created_at) VALUES ($userid,$quantity_Enough,$old_quantity,$new_quantity,'$created_at')";
                           //$sql = "select * from login";
                               if ($conn->query($user_sql2)===TRUE) {

                                $temp=array("response"=>"success");
                               array_push($data1,$temp);

                             }



                         }
                }

          }else{



                    $user_sql1 = "update store_items set quantity=quantity-$quantity  where storeid=$storeid and productid=$productid";
                         //$sql = "select * from login";
                         if ($conn->query($user_sql1)===TRUE) {

                            $last_id = $conn->insert_id;
                          
                            $new_quantity=$old_quantity-$quantity;


                            $user_sql2 = "INSERT INTO arc_products(userid,storeitemid,old_quantity,new_quantity,created_at) VALUES ($userid,$quantity_Enough,$old_quantity,$new_quantity,'$created_at')";
                           //$sql = "select * from login";
                               if ($conn->query($user_sql2)===TRUE) {

                                $temp=array("response"=>"success");
                               array_push($data1,$temp);

                             }



                         }


          }

         }else{

          $temp=array("response"=>"failed");
            array_push($data1,$temp);

        }

  }

  


   }else{

    if ($productid==0) {
       $temp=array("response"=>"Product Not Found");
      array_push($data1,$temp);

    }elseif ($growerid==0) {
       $temp=array("response"=>"Grower Not Found");
      array_push($data1,$temp);

    }elseif($verifyLoan==1){
 $temp=array("response"=>"Input Already Captured For Grower");
      array_push($data1,$temp);
    }


   }

 }





echo json_encode($data1);


?>





