<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";

$data = json_decode(file_get_contents("php://input"));

$found_store=0;
$user_found=0;

$response=array();

if (isset($data->userid)){

$seasonid=$data->seasonid;
$userid=$data->userid;
$questionnaires_bales_answers_by_growerid=$data->questionnaires_bales_answers_by_growerid;
$quantity=$data->quantity;
$sell_date=$data->sell_date;
$created_at=$data->created_at;
$username=$data->username;
$productid=0;
$storeid=0;
$found=0;
$old_quantity=0;



$date = new DateTime();
$datetimes=$date->format('H:i:s');

$sql = "Select * from receive_bales_to_warehouse where questionnaires_bales_answers_by_growerid=$questionnaires_bales_answers_by_growerid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $user_found=$row["id"];

  
   }

 }


$sql = "Select productid from bale_receiving_product where active=1 limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // product id
        $productid=$row["productid"];

   }

 }



$sql = "Select storeid from field_officer_warehouse join users on users.id=field_officer_warehouse.field_officerid where users.active=1 and username='$username' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // product id
        $storeid=$row["storeid"];

   }

 }






if ($user_found>0 || $storeid==0 || $productid==0 ) {
  

   // $user_sql1 = "update salary_variables set hectares=$hectares,daily_reports=$daily_reports,grower_visits=$grower_visits,system_based_tasks=$system_based_tasks,bike_maintenance=$bike_maintenance,ctl_related=$ctl_related,training_and_demo=$training_and_demo where id=$user_found";
   //     //$sql = "select * from login";
   //     if ($conn->query($user_sql1)===TRUE) {

   //            $temp=array("response"=>"successfully updated");
   //            array_push($response,$temp);

         
   //      }else{

   //   $temp=array("response"=>$conn->error);
   //   array_push($response,$temp);

   //   }


    if ($user_found==0) {
        $temp=array("response"=>"Already Received","barcode"=>$barcode);
        array_push($response,$temp);
    }



     if ($storeid==0) {
        $temp=array("response"=>"Store Not Found","barcode"=>$barcode);
        array_push($response,$temp);
    }



     if ($productid==0) {
        $temp=array("response"=>"Product Not Found","barcode"=>$barcode);
        array_push($response,$temp);
    }
  

    
}else{


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





  $user_sql = "INSERT INTO receive_bales_to_warehouse(userid,seasonid,storeid,questionnaires_bales_answers_by_growerid,quantity,created_at,sell_date,datetimes) VALUES ($userid,$seasonid,$storeid,$questionnaires_bales_answers_by_growerid,$quantity,'$sell_date','$created_at','$datetimes')";
     //$sql = "select * from login";
     if ($conn->query($user_sql)===TRUE) {
     
       $last_id = $conn->insert_id;

       $user_sql1 = "update questionnaires_bales_answers_by_grower set sync=1 where id=$questionnaires_bales_answers_by_growerid";
       //$sql = "select * from login";
       if ($conn->query($user_sql1)===TRUE) {

              // $temp=array("response"=>"successfully updated");
              // array_push($response,$temp);
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

                            $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description,quantity_balance) VALUES ($userid,$storeid,$productid,$quantity,$arc_products_id,'$created_at','FIELD',$quantity)";
                      
                          if ($conn->query($user_sql1)===TRUE) {

                            $arc_store_item_id = $conn->insert_id;

                              
                            $temp=array("response"=>"success","barcode"=>$barcode);
                               array_push($response,$temp);
                                           

                             }

                       }



               }else{

                 $temp=array("response"=>"failed","barcode"=>$barcode);
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

                    $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description) VALUES ($userid,$storeid,$productid,$quantity,$arc_products_id,'$created_at','FIELD')";
          
                    if ($conn->query($user_sql1)===TRUE) {

                        $arc_store_item_id = $conn->insert_id;

                                           
                        $temp=array("response"=>"success","barcode"=>$barcode);
                        array_push($response,$temp);
                                 

                       }

                 }

   }else{
    

     $temp=array("response"=>"failed","barcode"=>$barcode);
     array_push($response,$temp);

   }


}



         
        }else{

             $temp=array("response"=>$conn->error);
             array_push($response,$temp);

     }
       
     }else{

     $temp=array("response"=>$conn->error);
     array_push($response,$temp);

     }

   }


}else{


$temp=array("response"=>"Field empty","barcode"=>$barcode);
array_push($response,$temp);
	
}


echo json_encode($response);



?>





