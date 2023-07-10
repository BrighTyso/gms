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
$storeitemid=0;
$store_hold_areaid=0;
$hold_area_quantity=0;


$response=array();

if (isset($data->userid) && isset($data->storeid) && isset($data->productid) && isset($data->quantity) && isset($data->created_at)){



$userid=$data->userid;
$storeid=$data->storeid;
$productid=$data->productid;
$quantity=$data->quantity;
$created_at=$data->created_at;



$sql = "Select * from store_items where storeid=$storeid and productid=$productid and quantity>0 and quantity>=$quantity limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found=$row["id"];
    $storeitemid=$row["id"];
    $old_quantity=$row["quantity"];
    
   }
 }



$sql = "Select * from store_hold_area where store_itemid=$storeitemid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $store_hold_areaid=$row["id"];
    $hold_area_quantity=$row["quantity"];
   
    
   }
 }



if ($found>0) {



   $new_quantity=$old_quantity-$quantity;


   $user_sql1 = "update store_items set quantity=quantity-$quantity  where storeid=$storeid and productid=$productid";
             //$sql = "select * from login";
     if ($conn->query($user_sql1)===TRUE) {

        //$last_id = $conn->insert_id;
        

        $user_sql = "INSERT INTO arc_products(userid,storeitemid,old_quantity,new_quantity,created_at) VALUES ($userid,$storeitemid,$old_quantity,$new_quantity,'$created_at')";
                           //$sql = "select * from login";
           if ($conn->query($user_sql)===TRUE) {

              $arc_products_id = $conn->insert_id;
              

                $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description,quantity_balance) VALUES ($userid,$storeid,$productid,$quantity,$arc_products_id,'$created_at','TRANSFER TO HOLD AREA',$quantity)";
          
                  if ($conn->query($user_sql1)===TRUE) {
                    

                    $arc_store_item_id = $conn->insert_id;

                    $new_hold_area_quantity=$hold_area_quantity+$quantity;



                    if ($store_hold_areaid==0) {


                     $user_sql1 = "INSERT INTO store_hold_area(userid,store_itemid,quantity,created_at) VALUES ($userid,$storeitemid,$quantity,'$created_at')";
          
                          if ($conn->query($user_sql1)===TRUE) {

                            $store_hold_area_id = $conn->insert_id;

                                $user_sql1 = "INSERT INTO arc_store_hold_area(userid,store_hold_areaid,old_quantity,new_quantity,quantity,created_at) VALUES ($userid,$store_hold_area_id,$hold_area_quantity,$new_hold_area_quantity,$quantity,'$created_at')";
                  
                                  if ($conn->query($user_sql1)===TRUE) {

                                    //$store_hold_area_id = $conn->insert_id;

                                    $temp=array("response"=>"success");
                                    array_push($response,$temp);


                                     }else{


                                    $temp=array("response"=>$conn->error);
                                    array_push($response,$temp);

                                    }

                             }else{


                                    $temp=array("response"=>$conn->error);
                                    array_push($response,$temp);

                             }


                  }else{

                    $user_sql1 = "update store_hold_area set quantity=quantity+$quantity  where store_itemid=$storeitemid";
                    //$sql = "select * from login";
                     if ($conn->query($user_sql1)===TRUE) {

                        $user_sql1 = "INSERT INTO arc_store_hold_area(userid,store_hold_areaid,old_quantity,new_quantity,quantity,created_at) VALUES ($userid,$store_hold_areaid,$hold_area_quantity,$new_hold_area_quantity,$quantity,'$created_at')";
                  
                                  if ($conn->query($user_sql1)===TRUE) {

                                    //$store_hold_area_id = $conn->insert_id;

                                    $temp=array("response"=>"success");
                                    array_push($response,$temp);

                                     }
                     }

                  }
              }
       
    }


    }else{



    }
}else{

     $temp=array("response"=>"Product Not Found");
     array_push($response,$temp);

}
 



}else{

	 $temp=array("response"=>"Field Empty");
     array_push($response,$temp);
  
}


echo json_encode($response);



?>





