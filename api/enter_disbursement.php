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
$old_quantity=0;

$otp_quantity=0;
$otp_productid=0;
$otp_storeid=0;
$otp_id=0;

$response=array();

if (isset($data->productid) && isset($data->userid) && isset($data->storeid) && isset($data->quantity) && isset($data->created_at)){


$trucknumber=$data->trucknumber;
$productid=$data->productid;
$storeid=$data->storeid;
$quantity=$data->quantity;
$created_at=$data->created_at;
$userid=$data->userid;
$otp=$data->otp;




$sql = "Select * from disbursement_otp WHERE otp ='$otp'
  AND created_at > NOW() - INTERVAL 30 MINUTE; ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {

   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $otp_quantity=$row["quantity"];
    $otp_productid=$row["productid"];
    $otp_storeid=$row["storeid"];
    $otp_id=$row["id"];
   
    
   }
 }





 $sql1 = "Select * from store_items where storeid=$storeid and productid=$productid and quantity>=$quantity";
$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //$found=$row["id"];
    $quantity_Enough=$row["id"];
    $old_quantity=$row["quantity"];
    
   }
 }




$sql = "Select * from truck_destination where trucknumber='$trucknumber' and close_open=0";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found=$row["id"];
    $disbursement_trucksid=$row["id"];


   }
 }







$sql1 = "Select * from disbursement where disbursement_trucksid=$disbursement_trucksid and  productid=$productid ";
$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //$found=$row["id"];
    $product_disbursed=$row["id"];
    
   }
 }




if ($product_disbursed==0  && $otp_storeid==$storeid && $otp_productid==$productid && $otp_quantity<=$quantity) {


      if ($found>0) {


          if ($quantity_Enough>0) {

            $user_sql = "INSERT INTO disbursement(disbursement_trucksid,userid,productid,storeid,quantity,created_at) VALUES ($disbursement_trucksid,$userid,$productid,$storeid,$quantity,'$created_at')";
           //$sql = "select * from login";
               if ($conn->query($user_sql)===TRUE) {

                       $disbursemnt_last_id=$conn->insert_id;


                       $user_sql1 = "update store_items set quantity=quantity-$quantity  where storeid = $storeid and productid=$productid";
                         //$sql = "select * from login";
                         if ($conn->query($user_sql1)===TRUE) {
                         
                          $last_id = $conn->insert_id;
                         
                           $new_quantity=$old_quantity-$quantity;

                            $user_sql = "INSERT INTO arc_products(userid,storeitemid,old_quantity,new_quantity,created_at) VALUES ($userid,$quantity_Enough,$old_quantity,$new_quantity,'$created_at')";
                           //$sql = "select * from login";
                               if ($conn->query($user_sql)===TRUE) {


                                $last_id_arc_product = $conn->insert_id;

                               
                               $user_sql = "INSERT INTO total_disbursement(disbursement_trucksid,disbursementid,userid,productid,storeid,quantity,created_at) VALUES ($disbursement_trucksid,$disbursemnt_last_id,$userid,$productid,$storeid,$quantity,'$created_at')";
                                   //$sql = "select * from login";
                                       if ($conn->query($user_sql)===TRUE) {

                                          $user_sql2 = "INSERT INTO arc_product_truck(arc_productid,disbursementid,quantity) VALUES ($last_id_arc_product,$disbursemnt_last_id,$quantity)";
                                            //$sql = "select * from login";
                                           if ($conn->query($user_sql2)===TRUE) {

                                              // $last_id = $conn->insert_id;

                                              $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description,quantity_balance) VALUES ($userid,$storeid,$productid,$quantity,$last_id_arc_product,'$created_at','PRODUCT DISBURSEMENT',$quantity)";
          
                                            if ($conn->query($user_sql1)===TRUE) {

                                                $temp=array("response"=>"success");
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
                                $temp=array("response"=>$conn->error);
                                                   array_push($response,$temp);
                             }
                          

                         }else{
                         
                          //$last_id = $conn->insert_id;
                           $temp=array("response"=>"Failed To Update");
                           array_push($response,$temp);

                         }


                 
                 
               }else{

               $temp=array("response"=>$conn->error);
               array_push($response,$temp);

               }

          }else{

            $temp=array("response"=>"Out Of Stock");
             array_push($response,$temp);
          }
      
    }else{

      $temp=array("response"=>"already Inserted");
      array_push($response,$temp);

    }

}else{


//&&  && $otp_productid==$productid && $otp_quantity<=$quantity

    if ($otp_storeid!=$storeid) {
        $temp=array("response"=>"Confirmed store is not matching");
        array_push($response,$temp);
    }else if ($otp_productid!=$productid) {
        $temp=array("response"=>"Confirmed product is not matching");
        array_push($response,$temp);
    }else if ($quantity>$otp_quantity) { 

     $temp=array("response"=>"Confirmed quantity is not matching");
    array_push($response,$temp);

    }else if ($product_disbursed>0) {
        
        $temp=array("response"=>"Product Already Disbursed");
        array_push($response,$temp);
    }



}




}else{


$temp=array("response"=>"Field Cant Be Empty");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





