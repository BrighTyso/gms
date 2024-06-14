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
$to_found=0;
$old_quantity=0;
$invoice_found=0;
$old_to_quantity=0;
$new_to_quantity=0;

$response=array();

if (isset($data->userid) && isset($data->storeid) && isset($data->fromproductid) && isset($data->toproductid) && isset($data->quantity) && isset($data->created_at) && isset($data->divide_by)){



$userid=$data->userid;
$storeid=$data->storeid;
$fromproductid=$data->fromproductid;
$toproductid=$data->toproductid;
$quantity=$data->quantity;
$created_at=$data->created_at;
$divide_by=$data->divide_by;


$sql = "Select * from store_items where storeid=$storeid and productid=$fromproductid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found=$row["id"];
    $old_quantity=$row["quantity"];
    
   }
 }




$sql = "Select * from store_items where storeid=$storeid and productid=$toproductid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $to_found=$row["id"];
    $old_to_quantity=$row["quantity"];
    
   }
 }




//$new_quantity=$old_quantity+$quantity;



if ($to_found==0) {

    $user_sql = "INSERT INTO product_split(userid,storeid,fromproductid,toproductid,quantity,divide_by,created_at) VALUES ($userid,$storeid,$fromproductid,$toproductid,$quantity,$divide_by,'$created_at')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $last_id = $conn->insert_id;


     $user_sql1 = "update store_items set quantity=quantity-$quantity , userid=$userid ,  created_at='$created_at' where storeid = $storeid and productid=$fromproductid";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {

   $new_to_quantity=$quantity*$divide_by;
   $new_to_quantity_next_value=$new_to_quantity+$old_to_quantity;
   $new_quantity=$old_quantity-$quantity;

  
   $user_sql = "INSERT INTO store_items(userid,storeid,productid,quantity,created_at) VALUES ($userid,$storeid,$toproductid,$new_to_quantity_next_value,'$created_at')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $last_id = $conn->insert_id;

    
     $user_sql = "INSERT INTO arc_products(userid,storeitemid,old_quantity,new_quantity,created_at) VALUES ($userid,$last_id,$old_to_quantity,$new_to_quantity_next_value,'$created_at')";
                           //$sql = "select * from login";
           if ($conn->query($user_sql)===TRUE) {

              $arc_products_id = $conn->insert_id;

                $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description,quantity_balance) VALUES ($userid,$storeid,$toproductid,$new_to_quantity,$arc_products_id,'$created_at','PRODUCT SPLIT',$new_to_quantity)";
          
              if ($conn->query($user_sql1)===TRUE) {

                $arc_store_item_id = $conn->insert_id;

                    
                     $user_sql = "INSERT INTO arc_products(userid,storeitemid,old_quantity,new_quantity,created_at) VALUES ($userid,$last_id,$old_quantity,$new_quantity,'$created_at')";
                                           //$sql = "select * from login";
                           if ($conn->query($user_sql)===TRUE) {

                              $arc_products_id = $conn->insert_id;

                                $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description,quantity_balance) VALUES ($userid,$storeid,$fromproductid,$quantity,$arc_products_id,'$created_at','PRODUCT SPLIT',$quantity)";
                          
                              if ($conn->query($user_sql1)===TRUE) {

                                $arc_store_item_id = $conn->insert_id;

                                  
                                    $temp=array("response"=>"success");
                                       array_push($response,$temp);

                                       
                                 }else{

                                    $temp=array("response"=>$conn->error);
                                       array_push($response,$temp);
                                 }

                           } else{

                                    $temp=array("response"=>$conn->error);
                                       array_push($response,$temp);
                                 }             

                 }else{

                        $temp=array("response"=>$conn->error);
                           array_push($response,$temp);
                     }

           }else{

                $temp=array("response"=>$conn->error);
                   array_push($response,$temp);
             }



   }else{

     $temp=array("response"=>"failed");
     array_push($response,$temp);

   }
}else{

        $temp=array("response"=>$conn->error);
           array_push($response,$temp);
     }

}else{

        $temp=array("response"=>$conn->error);
           array_push($response,$temp);
     }

}else{

  $user_sql1 = "update store_items set quantity=quantity-$quantity , userid=$userid ,  created_at='$created_at' where storeid = $storeid and productid=$fromproductid";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {

    $new_to_quantity=$quantity*$divide_by;
    $new_to_quantity_next_value=$new_to_quantity+$old_to_quantity;
    $new_quantity=$old_quantity-$quantity;


    $user_sql1 = "update store_items set quantity=quantity+$new_to_quantity , userid=$userid ,  created_at='$created_at' where storeid = $storeid and productid=$toproductid";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {


   
         $user_sql = "INSERT INTO arc_products(userid,storeitemid,old_quantity,new_quantity,created_at) VALUES ($userid,$to_found,$old_to_quantity,$new_to_quantity_next_value,'$created_at')";
                                 //$sql = "select * from login";
                 if ($conn->query($user_sql)===TRUE) {

                    $arc_products_id = $conn->insert_id;

                    $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description) VALUES ($userid,$storeid,$toproductid,$new_to_quantity,$arc_products_id,'$created_at','PRODUCT SPLIT')";
          
                    if ($conn->query($user_sql1)===TRUE) {

                        $arc_store_item_id = $conn->insert_id;

                         

                                $user_sql = "INSERT INTO arc_products(userid,storeitemid,old_quantity,new_quantity,created_at) VALUES ($userid,$found,$old_quantity,$new_quantity,'$created_at')";
                                 //$sql = "select * from login";
                                 if ($conn->query($user_sql)===TRUE) {

                                    $arc_products_id = $conn->insert_id;

                                    $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description) VALUES ($userid,$storeid,$fromproductid,$quantity,$arc_products_id,'$created_at','PRODUCT SPLIT')";
                          
                                    if ($conn->query($user_sql1)===TRUE) {

                                        $arc_store_item_id = $conn->insert_id;

                                         
                                        $temp=array("response"=>"success");
                                        array_push($response,$temp);


                                   }

                             }

                               

                       }

                 }

   }else{
    

     $temp=array("response"=>"failed");
     array_push($response,$temp);

   }

}
 
}


}else{

	 $temp=array("response"=>"Field Empty");
     array_push($response,$temp);
  
}


echo json_encode($response);



?>





