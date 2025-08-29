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
$purchase_order_product_found=0;
$purchase_order_product_received=0;
$purchase_order_product_quantity=0;
$purchase_order_otp_found=0;

$store_name="";
$location="";
$product_name="";
$username="";

$response=array();

if (isset($data->userid) && isset($data->storeid) && isset($data->productid) && isset($data->seasonid) && isset($data->quantity) && isset($data->created_at) && isset($data->supplierid) && isset($data->invoice_number) && isset($data->unit_price) && isset($data->currencyid) && isset($data->otp)){



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
$purchase_orderid=$data->purchasing_orderid;
$otp=$data->otp;

$payment_typeid=0;
$branch_id_count=0;
$account_branchid=0;

$original_amount=0;
$sub_accountid=0;


$sql = "Select id from accounts_branch";
$result = $conn->query($sql);
 
 $branch_id_count=$result->num_rows;

 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    // $temp=array("main_account"=>$row["main_account"],"sub_acc"=>$row["sub_acc"],"balance_side"=>$row["balance_side"],"main_account_id"=>$row["id"],"sub_account_id"=>$row["sub_account_id"]);
    // array_push($response,$temp);

    $account_branchid=$row['id'];

   }
 }




$sql = "Select main_accounts.description as main_account,main_accounts.id,balancing_side.description as balance_side,sub_accounts.description as sub_acc , sub_accounts.id as sub_account_id from main_accounts join sub_accounts on main_accounts.id=sub_accounts.main_accountid join main_account_balancing_side on main_account_balancing_side.main_accountid=main_accounts.id join balancing_side on main_account_balancing_side.balancing_sideid=balancing_side.id where sub_accounts.description='Cash In Bank' order by main_accounts.id limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    // $temp=array("main_account"=>$row["main_account"],"sub_acc"=>$row["sub_acc"],"balance_side"=>$row["balance_side"],"main_account_id"=>$row["id"],"sub_account_id"=>$row["sub_account_id"]);
    // array_push($response,$temp);

    $payment_typeid=$row['sub_account_id'];

   }
 }



 $sql = "Select main_accounts.description as main_account,main_accounts.id,balancing_side.description as balance_side,sub_accounts.description as sub_acc , sub_accounts.id as sub_account_id from main_accounts join sub_accounts on main_accounts.id=sub_accounts.main_accountid join main_account_balancing_side on main_account_balancing_side.main_accountid=main_accounts.id join balancing_side on main_account_balancing_side.balancing_sideid=balancing_side.id where sub_accounts.description='Purchases' order by main_accounts.id limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    // $temp=array("main_account"=>$row["main_account"],"sub_acc"=>$row["sub_acc"],"balance_side"=>$row["balance_side"],"main_account_id"=>$row["id"],"sub_account_id"=>$row["sub_account_id"]);
    // array_push($response,$temp);

    $sub_accountid=$row['sub_account_id'];

   }
 }



   //  $account_receivableid=0;

   //  $sql1 = "Select id from accounts_receivable_notes  where  customer_id=$customerid and seasonid=$seasonid";
   //   $result = $conn->query($sql1);
   
   //  if ($result->num_rows > 0) {
   //   // output data of each row
   //   while($row = $result->fetch_assoc()) {

   //   $account_receivableid=$row['id'];
     
   //   }

   // }



$sql = "Select * from purchase_order_otp where  otp='$otp' and purchasing_orderid=$purchase_orderid and storeid=$storeid AND created_at > NOW() - INTERVAL 30 MINUTE limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // product id
   $purchase_order_otp_found=$row["id"];
   
   }

 }




 if ($purchase_order_otp_found>0) {
     // code...
 


$sql = "Select * from users where id=$userid ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $username=$row["username"];
    
   }
 }







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




$sql = "Select * from products where id=$productid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $product_name=$row["name"];
    
   }
 }


$sql = "Select * from store where id=$productid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $store_name=$row["name"];
    $location=$row["location"];
    
   }
 }



$sql = "Select * from arc_store_items_invoice where invoice_number='$invoice_number'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //$invoice_found=$row["id"];
    
    
   }
 }



$sql = "Select * from purchasing_order_products where productid=$productid and purchasing_orderid=$purchase_orderid limit 1";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

      $purchase_order_product_quantity=$row["quantity"];
      $original_amount=$row["quantity"]*$row["unit_price"];
      
     }
   }



 $sql = "Select * from purchasing_order_received_products where productid=$productid and purchasing_orderid=$purchase_orderid";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

      $purchase_order_product_received+=$row["quantity"];
      
     }
   }





   $remaining_purchase_order_quantity=$purchase_order_product_quantity-$purchase_order_product_received;



  $sql = "Select purchasing_order_products.id,order_number from purchasing_order_products join purchasing_order on purchasing_order.id=purchasing_order_products.purchasing_orderid where productid=$productid and purchasing_orderid=$purchase_orderid  and quantity>quantity_received and quantity>$purchase_order_product_received limit 1";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $purchase_order_product_found=$row["id"];
      $invoice_number=$row["order_number"];
      
     }
   }


if ($purchase_order_product_found>0 && $remaining_purchase_order_quantity>=$quantity) {



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

                        $user_sql2 = "INSERT INTO purchasing_order_received_products(userid,purchasing_orderid,storeid,productid,quantity,created_date) VALUES ($userid,$purchase_orderid,$storeid,$productid,$quantity,'$created_at')";
          
                            if ($conn->query($user_sql2)===TRUE) {


                                $purchase_insert_orderid = $conn->insert_id;

                                $user_sql1 = "update purchasing_order_products set quantity_received=quantity_received+$quantity  where purchasing_orderid = $purchase_orderid and productid=$productid";
                                   //$sql = "select * from login";
                                   if ($conn->query($user_sql1)===TRUE) {



                            $credit_sql = "INSERT INTO transactions(userid,account_branchid,seasonid,currencyid,description,receipt_num,amount,debit_sub_accountsid,credit_sub_accountsid,purchasing_order_received_productsid,created_at) VALUES ($userid,$account_branchid,$seasonid,$currencyid,'Product Purchase','$invoice_number',$original_amount,$sub_accountid,$payment_typeid,$purchase_insert_orderid,'$created_at')";
                                 //$sql = "select * from login";
                                 if ($conn->query($credit_sql)===TRUE) {


                                        $sql = "Select * from operations_contacts where  active=1";
                                        $result = $conn->query($sql);
                                         
                                         if ($result->num_rows > 0) {
                                           // output data of each row
                                           while($row = $result->fetch_assoc()) {
                                            $phone=$row["phone"];
                                            $contact_email=$row["email"];

                                              $to = $contact_email;
                                              $subject = "Warehouse Stock Movement";
                                              $txt = "User ".$username." Added ".$quantity." ".$product_name." into ".$store_name."\n\n Invoice number ".$invoice_number;
                                              $headers = "From: warehouse@coreafricagrp.com";

                                              mail($to,$subject,$txt,$headers);
                                           }

                                         }
                                     }


                                        $temp=array("response"=>"success");
                                           array_push($response,$temp);
                               }

                            }

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

                                 $user_sql2 = "INSERT INTO purchasing_order_received_products(userid,purchasing_orderid,storeid,productid,quantity,created_date) VALUES ($userid,$purchase_orderid,$storeid,$productid,$quantity,'$created_at')";
          
                                        if ($conn->query($user_sql2)===TRUE) {

                                            $purchase_insert_orderid=$conn->insert_id;
                                            
                                            $user_sql1 = "update purchasing_order_products set quantity_received=quantity_received+$quantity  where purchasing_orderid = $purchase_orderid and productid=$productid";
                                               //$sql = "select * from login";
                                               if ($conn->query($user_sql1)===TRUE) {


                                                $credit_sql = "INSERT INTO transactions(userid,account_branchid,seasonid,currencyid,description,receipt_num,amount,debit_sub_accountsid,credit_sub_accountsid,purchasing_order_received_productsid,created_at) VALUES ($userid,$account_branchid,$seasonid,$currencyid,'Product Purchase','$invoice_number',$original_amount,$sub_accountid,$payment_typeid,$purchase_insert_orderid,'$created_at')";
                                                     //$sql = "select * from login";
                                                     if ($conn->query($credit_sql)===TRUE) {

                                                $sql = "Select * from operations_contacts where  active=1";
                                                    $result = $conn->query($sql);
                                                     
                                                     if ($result->num_rows > 0) {
                                                       // output data of each row
                                                       while($row = $result->fetch_assoc()) {
                                                        $phone=$row["phone"];
                                                        $contact_email=$row["email"];
                                                        $to = $contact_email;
                                                        $subject = "Warehouse Stock Movement";
                                                        $txt = "User ".$username." Added ".$quantity." ".$product_name." in ".$store_name."\n\n Invoice number ".$invoice_number;
                                                        $headers = "From: warehouse@coreafricagrp.com";
                                                        mail($to,$subject,$txt,$headers);
                                                       }

                                                     }
                                                 }

                                                    $temp=array("response"=>"success");
                                                       array_push($response,$temp);
                                             }

                                     }

                               }

                       }

                 }

   }else{
    

     $temp=array("response"=>"failed");
     array_push($response,$temp);

   }

}
 
}else{
  
    if($remaining_purchase_order_quantity<$quantity){
    $temp=array("response"=>"Exceeding Purchuse Order Quantity");
         array_push($response,$temp);
    }else if($invoice_found>0){
    $temp=array("response"=>"Invoice Already Captured");
     array_push($response,$temp);
    }else if($purchase_order_product_found==0){
     $temp=array("response"=>"Purchase Order Not Found");
     array_push($response,$temp);
    }

     
}
}else{
    $temp=array("response"=>"OTP Expired(not found)");
     array_push($response,$temp);
}


}else{

	 $temp=array("response"=>"Field Empty");
     array_push($response,$temp);
  
}


echo json_encode($response);



?>





