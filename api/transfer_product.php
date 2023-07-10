<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

//require "validate.php";


require_once("conn.php");
//require "validate.php";



$data = json_decode(file_get_contents("php://input"));


$data1=array();

$productid=0;
$storeFromid=0;
$storeToid=0;
$userid=0;
$quantity=0;
$created_at="";
$to_old_quantity=0;
$old_quantity=0;
$toStoreIdFound=0;
$fromStoreIdFound=0;



//http://192.168.1.190/gms/api/enter_loans.php?userid=1&product=sadza&quantity=1&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&description=12333&seasonid=1&sqliteid=1

if (isset($data->storeFromid)  && isset($data->storeToid) && isset($data->quantity) && isset($data->userid) && isset($data->productid)){




$storeFromid=$data->storeFromid;
$storeToid=$data->storeToid;
$quantity=$data->quantity;
$userid=$data->userid;
$productid=$data->productid;
$created_at=$data->created_at;




$product_sql = "Select * from products  where id=$productid";
$result = $conn->query($product_sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $productid=$row["id"];

    
   }

 }

//check if loan is there




$sql2 = "Select store.id,quantity,store_items.id as storeid from store join store_items on store.id=store_items.storeid where store.id=$storeFromid and productid=$productid and quantity>0 and quantity>=$quantity";
$result = $conn->query($sql2);
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) { 
   

   $storeFromid=$row["id"];
   $quantity_Enough=$row["storeid"];
   $old_quantity=$row["quantity"];
   $fromStoreIdFound=$row["id"];

    
   }

 }


// then insert loan





$sql2 = "Select store.id,quantity,store_items.id as storeid from store join store_items on store.id=store_items.storeid where store.id=$storeToid and productid=$productid ";
$result = $conn->query($sql2);
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) { 
   

   $storeToid=$row["id"];
   $storeItemIdTo=$row["storeid"];
   $to_old_quantity=$row["quantity"];
   $toStoreIdFound=$row["id"];

    
   }

 }




  if ($productid>0 && $fromStoreIdFound>0) {


  
       $user_sql1 = "update store_items set quantity=quantity-$quantity  where storeid=$storeFromid and productid=$productid";
             //$sql = "select * from login";
             if ($conn->query($user_sql1)===TRUE) {

                $last_id = $conn->insert_id;
              
                $new_quantity=$old_quantity-$quantity;


                $user_sql2 = "INSERT INTO arc_products(userid,storeitemid,old_quantity,new_quantity,created_at) VALUES ($userid,$quantity_Enough,$old_quantity,$new_quantity,'$created_at')";
                      //$sql = "select * from login";
                   if ($conn->query($user_sql2)===TRUE) {

                    $arc_products_id = $conn->insert_id;

                    $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description,quantity_balance) VALUES ($userid,$storeFromid,$productid,$quantity,$arc_products_id,'$created_at','PRODUCT TRANSFER',$quantity)";
          
                    if ($conn->query($user_sql1)===TRUE) {

                     $arc_store_item_id = $conn->insert_id;

                      $user_sql2 = "INSERT INTO transfer_products(userid,fromStoreid,toStoreid,quantity,arc_store_itemid,created_at) VALUES ($userid,$storeFromid,$storeToid,$quantity,$arc_store_item_id,'$created_at')";
          
                        if ($conn->query($user_sql2)===TRUE) {


                          if ($toStoreIdFound==0) {

                            $new_quantity=$to_old_quantity+$quantity;

                            $user_sql = "INSERT INTO store_items(userid,storeid,productid,quantity,created_at) VALUES ($userid,$storeToid,$productid,$quantity,'$created_at')";
                           //$sql = "select * from login";
                           if ($conn->query($user_sql)===TRUE) {
                           
                             $last_id = $conn->insert_id;


                             $user_sql = "INSERT INTO arc_products(userid,storeitemid,old_quantity,new_quantity,created_at) VALUES ($userid,$last_id,$to_old_quantity,$new_quantity,'$created_at')";
                                                   //$sql = "select * from login";
                                   if ($conn->query($user_sql)===TRUE) {

                                      $arc_products_id = $conn->insert_id;

                                       $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description,quantity_balance) VALUES ($userid,$storeToid,$productid,$quantity,$arc_products_id,'$created_at','PRODUCT TRANSFER',$quantity)";
                            
                                      if ($conn->query($user_sql1)===TRUE) {

                                       $arc_store_item_id = $conn->insert_id;

                                        $user_sql2 = "INSERT INTO transfer_products(userid,fromStoreid,toStoreid,quantity,arc_store_itemid,created_at) VALUES ($userid,$storeFromid,$storeToid,$quantity,$arc_store_item_id,'$created_at')";
                            
                                          if ($conn->query($user_sql2)===TRUE) {

                                              $temp=array("response"=>"success");
                                                 array_push($data1,$temp);

                                             }else{
                                              $temp=array("response"=>$conn->error);
                                                 array_push($data1,$temp);
                                             }

                                        }


                                    }

                                  }
                           
                          }else{

                             $user_sql1 = "update store_items set quantity=quantity+$quantity  where storeid=$storeToid and productid=$productid";
             

                               if ($conn->query($user_sql1)===TRUE) {

                                  $last_id = $conn->insert_id;
                                
                                  $new_quantity=$to_old_quantity+$quantity;


                                  $user_sql2 = "INSERT INTO arc_products(userid,storeitemid,old_quantity,new_quantity,created_at) VALUES ($userid,$storeItemIdTo,$to_old_quantity,$new_quantity,'$created_at')";
                                        //$sql = "select * from login";
                                     if ($conn->query($user_sql2)===TRUE) {

                                      $arc_products_id = $conn->insert_id;

                                      $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description,quantity_balance) VALUES ($userid,$storeToid,$productid,$quantity,$arc_products_id,'$created_at','PRODUCT TRANSFER',$quantity)";
                            
                                      if ($conn->query($user_sql1)===TRUE) {

                                       $arc_store_item_id = $conn->insert_id;

                                        $user_sql2 = "INSERT INTO transfer_products(userid,fromStoreid,toStoreid,quantity,arc_store_itemid,created_at) VALUES ($userid,$storeFromid,$storeToid,$quantity,$arc_store_item_id,'$created_at')";
                            
                                          if ($conn->query($user_sql2)===TRUE) {

                                              $temp=array("response"=>"success");
                                                 array_push($data1,$temp);

                                             }else{
                                              $temp=array("response"=>$conn->error);
                                                 array_push($data1,$temp);
                                             }

                                      }

                                   }

                              
                               }
// else below
                           }

                         }

                    }

                 }

            
             }

                

          }else{


              $temp=array("response"=>"Out Of Stock");
              array_push($data1,$temp);

        

          }

         }else{

          $temp=array("response"=>"Field Empty");
            array_push($data1,$temp);

        }

  

  


 //   }else{

 //    if ($productid==0) {

 //       $temp=array("response"=>"Product Not Found");
 //      array_push($data1,$temp);

 //    }elseif($userid==0 && $storeid==0){

 //      $temp=array("response"=>"warehouse/Userid Not Found or Not Matching");
 //      array_push($data1,$temp);

 //    }
 //    elseif ($growerid==0) {

 //       $temp=array("response"=>"Grower Not Found");
 //      array_push($data1,$temp);

 //    }elseif($verifyLoan==1){

 //      $temp=array("response"=>"Input Already Captured For Grower");
 //      array_push($data1,$temp);

 //    }elseif($storeid==0){

 //      $temp=array("response"=>"Store Not Found");
 //      array_push($data1,$temp);

 //    }



 //   }

 // }





echo json_encode($data1);


?>





