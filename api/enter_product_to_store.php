<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$storeid=0;
$productid=0;
$quantity=0;
$created_at="";
$found=0;
$old_quantity=0;
$invoice_found=0;

$response=array();

if (isset($data->userid) && isset($data->storeid) && isset($data->productid) && isset($data->seasonid) && isset($data->quantity) && isset($data->created_at) && isset($data->supplierid) && isset($data->invoice_number) && isset($data->unit_price) && isset($data->currencyid)){



$userid=$data->userid;
$storeid=$data->storeid;
$productid=$data->productid;
$quantity=$data->quantity;
$created_at=$data->created_at;
$seasonid=$data->seasonid;
$invoice_number=$data->invoice_number;
$unit_price=$data->unit_price;
$supplierid=$data->supplierid;
$currencyid=$data->currencyid;

$sql = "Select * from store_items where storeid=$storeid and productid=$productid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found=$row["id"];
    $old_quantity=$row["quantity"];
    
   }
 }


$sql = "Select * from arc_store_items_invoice where invoice_number='$invoice_number'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $invoice_found=$row["id"];
    
    
   }
 }


if ($invoice_found==0) {



$new_quantity=$old_quantity+$quantity;



if ($found==0) {
  
   $user_sql = "INSERT INTO store_items(userid,storeid,productid,quantity,created_at) VALUES ($userid,$storeid,$productid,$quantity,'$created_at')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $last_id = $conn->insert_id;

     

     $user_sql = "INSERT INTO arc_products(userid,storeitemid,old_quantity,new_quantity,created_at) VALUES ($userid,$last_id,$old_quantity,$new_quantity,'$created_at')";
                           //$sql = "select * from login";
           if ($conn->query($user_sql)===TRUE) {

              $arc_products_id = $conn->insert_id;

                $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description,quantity_balance) VALUES ($userid,$storeid,$productid,$quantity,$arc_products_id,'$created_at','PRODUCT PURCHASE',$quantity)";
          
              if ($conn->query($user_sql1)===TRUE) {

                $arc_store_item_id = $conn->insert_id;

                  $user_sql2 = "INSERT INTO arc_store_items_invoice(userid,arc_store_itemsid,seasonid,supplierid,invoice_number,unit_price,currencyid) VALUES ($userid,$arc_store_item_id,$seasonid,$supplierid,'$invoice_number','$unit_price','$currencyid')";
          
                    if ($conn->query($user_sql2)===TRUE) {

                        $temp=array("response"=>"success");
                           array_push($response,$temp);

                       }

                 }

           }



   }else{

     $temp=array("response"=>"failed");
     array_push($response,$temp);

   }

}else{

  $user_sql1 = "update store_items set quantity=quantity+$quantity , userid=$userid ,  created_at='$created_at' where storeid = $storeid and productid=$productid";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {
   
         $user_sql = "INSERT INTO arc_products(userid,storeitemid,old_quantity,new_quantity,created_at) VALUES ($userid,$found,$old_quantity,$new_quantity,'$created_at')";
                                 //$sql = "select * from login";
                 if ($conn->query($user_sql)===TRUE) {

                    $arc_products_id = $conn->insert_id;

                    $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description) VALUES ($userid,$storeid,$productid,$quantity,$arc_products_id,'$created_at','PRODUCT PURCHASE')";
          
                    if ($conn->query($user_sql1)===TRUE) {

                        $arc_store_item_id = $conn->insert_id;

                         $user_sql2 = "INSERT INTO arc_store_items_invoice(userid,arc_store_itemsid,seasonid,supplierid,invoice_number,unit_price,currencyid) VALUES ($userid,$arc_store_item_id,$seasonid,$supplierid,'$invoice_number','$unit_price','$currencyid')";
          
                            if ($conn->query($user_sql2)===TRUE) {

                                $temp=array("response"=>"success");
                                   array_push($response,$temp);

                               }

                       }

                 }

   }else{
    

     $temp=array("response"=>"failed");
     array_push($response,$temp);

   }

}
 
}else{

     $temp=array("response"=>"Invoice Already Captured");
     array_push($response,$temp);
}


}else{

	 $temp=array("response"=>"Field Empty");
     array_push($response,$temp);
  
}


echo json_encode($response);



?>





