<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$trucknumber="";
$productid=0;
$storeid=0;
$quantity=0;
$created_at="";
$disbursement_trucksid=0;
$userid=0;
$found=0;
$quantity_Enough=0;
$product_disbursed=0;
$storeitemid=0;
$old_quantity=0;

$response=array();

if (isset($data->productid) && isset($data->quantity) && isset($data->trucknumber)){


$trucknumber=$data->trucknumber;
$productid=$data->productid;
 $quantity=$data->quantity;
 // $storeid=$data->storeid;
 $created_at=$data->created_at;
 $userid=$data->userid;





//  $sql1 = "Select * from store_items where storeid=$storeid and productid=$productid and quantity>=$quantity";
// $result = $conn->query($sql1);
 
//  if ($result->num_rows > 0) {
//    // output data of each row
//    while($row = $result->fetch_assoc()) {
//     // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

//     //$found=$row["id"];
//     $quantity_Enough=$row["id"];
    
//    }
//  }




$sql = "Select truck_destination.id,storeid  from truck_destination join disbursement on disbursement.disbursement_trucksid=truck_destination.id where trucknumber='$trucknumber' and productid=$productid and quantity>=$quantity and disbursement.quantity>0 and close_open=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found=$row["id"];
    $disbursement_trucksid=$row["id"];
    $storeid=$row["storeid"];
    
   }
 }





$sql1 = "Select * from store_items where storeid=$storeid  and  productid=$productid ";
$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //$found=$row["id"];
    $storeitemid=$row["id"];
    $old_quantity=$row["quantity"];
    
   }
 }



if ($disbursement_trucksid>0 && $storeitemid>0) {

    $user_sql1 = "update store_items set quantity=quantity+$quantity  where storeid = $storeid and productid=$productid";
         //$sql = "select * from login";
         if ($conn->query($user_sql1)===TRUE) {

            $new_quantity=$old_quantity+$quantity;
         
             $user_sql2 = "update disbursement set quantity=quantity-$quantity  where storeid = $storeid and productid=$productid and disbursement_trucksid=$disbursement_trucksid";
             //$sql = "select * from login";
             if ($conn->query($user_sql2)===TRUE) {
             
            $last_id = $conn->insert_id;
            
               $user_sql = "INSERT INTO returned_stock(disbursement_trucksid,userid,productid,storeid,quantity,created_at) VALUES ($disbursement_trucksid,$userid,$productid,$storeid,$quantity,'$created_at')";
           //$sql = "select * from login";
               if ($conn->query($user_sql)===TRUE) {


                 $user_sql2 = "INSERT INTO arc_products(userid,storeitemid,old_quantity,new_quantity,created_at) VALUES ($userid,$storeid,$old_quantity,$new_quantity,'$created_at')";
                                  //$sql = "select * from login";
                               if ($conn->query($user_sql2)===TRUE) {

                                $arc_products_id = $conn->insert_id;

                                $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description,quantity_balance) VALUES ($userid,$storeid,$productid,$quantity,$arc_products_id,'$created_at','RETURNED PRODUCTS',$quantity)";
                          
                                      if ($conn->query($user_sql1)===TRUE) {

                                        $temp=array("response"=>"success");
                                        array_push($response,$temp);

                                         }
                         }
                            
               }

             }else{

              //$last_id = $conn->insert_id;
               $temp=array("response"=>"Failed To Update");
               array_push($response,$temp);

             }

            }else{

          //$last_id = $conn->insert_id;
           $temp=array("response"=>"Failed To Update");
           array_push($response,$temp);

            }

    }else{

    $temp=array("response"=>"Failed To Return Product");
    array_push($response,$temp);

    }

}else{


$temp=array("response"=>"Field  Empty");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





