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
$bale_received=0;

$response=array();

if (isset($data->seasonid)){

$userid=$data->userid;
$seasonid=$data->seasonid;
$barcode=$data->barcode;
$created_at=$data->created_at;
$shipment=$data->shipment;
$verify_mass=$data->mass;
$warehousing_sold_balesid=0;
$shipment_fileid=0;
$shipment_detailsid=0;
$productid=0;
$storeid=0;
$found=0;
$old_quantity=0;
$quantity=1;

$mass=0;
$price=0;

$date = new DateTime();
$datetimes=$date->format('H:i:s');


$sql = "Select * from shipment_details where shipment='$shipment' order by id desc limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // product id  
   $shipment_detailsid=$row["id"];
  
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



$sql = "Select storeid from field_officer_warehouse join users on users.id=field_officer_warehouse.field_officerid where users.active=1 and users.id=$userid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // product id
        $storeid=$row["storeid"];

   }

 }



if ($storeid==0 || $productid==0 || $shipment_detailsid==0) {
  

     if ($storeid==0) {
        $temp=array("response"=>"Store Not Found","barcode"=>$barcode);
        array_push($response,$temp);
    }



     if ($productid==0) {
        $temp=array("response"=>"Product Not Found","barcode"=>$barcode);
        array_push($response,$temp);
    }
  

    
    if ($shipment_detailsid==0) {
        $temp=array("response"=>"Shipment not found","barcode"=>$barcode);
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





    $sql = "Select * from warehousing_sold_bales where barcode='$barcode' limit 1";
    $result = $conn->query($sql);
     
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        // product id
       $user_found=$row["id"];
       $warehousing_sold_balesid=$row["id"];
       $mass=$row["mass"];
       $price=$row["price"];

       }

     }



     //$sql = "Select * from shipment_file where barcode='$barcode' and shipment_detailsid=$shipment_detailsid limit 1";


     $sql = "Select * from shipment_file where  shipment_detailsid=$shipment_detailsid order by id desc limit 1";
    $result = $conn->query($sql);
     
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        // product id
       $shipment_fileid=$row["id"];
    
       }

     }




if ($user_found>0) {
  
    $sql = "Select * from warehousing_storage_received_bales where warehousing_sold_balesid=$user_found limit 1";
    $result = $conn->query($sql);
     
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {

        // product id
      
       $bale_received=$row["id"];

      
       }

     }


}




if ($bale_received>0 || $warehousing_sold_balesid==0 || $user_found==0 || $shipment_fileid==0 || $shipment_detailsid==0) {
  

if ($shipment_fileid==0) {
  $temp=array("response"=>"Barcode Not In Shipment","barcode"=>$barcode);
array_push($response,$temp);
}else if ($shipment_detailsid==0) {
  $temp=array("response"=>"Shipment Not found","barcode"=>$barcode);
array_push($response,$temp);
}else{
  $temp=array("response"=>"Product Already Received","barcode"=>$barcode);
array_push($response,$temp);
}

    
}else{

  $user_sql = "INSERT INTO warehousing_storage_received_bales(userid,seasonid,storeid,warehousing_sold_balesid,created_at,datetimes,shipment_detailsid,shipment_fileid
) VALUES ($userid,$seasonid,$storeid,$warehousing_sold_balesid,'$created_at','$datetimes',$shipment_detailsid,$shipment_fileid)";
     //$sql = "select * from login";
     if ($conn->query($user_sql)===TRUE) {
     
       $last_id = $conn->insert_id;

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

                            $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description,quantity_balance) VALUES ($userid,$storeid,$productid,$quantity,$arc_products_id,'$created_at','WAREHOUSE STORAGE',$quantity)";
                      
                          if ($conn->query($user_sql1)===TRUE) {

                            $arc_store_item_id = $conn->insert_id;

                              
                            $bale_value=$mass*$price;


                             $user_sql1 = "update total_sold_received set received_mass=received_mass+'$mass',received_bales=received_bales+1,received_total_value=received_total_value+'$bale_value' where seasonid=$seasonid";
                             //$sql = "select * from login";
                             if ($conn->query($user_sql1)===TRUE) {


                                  $user_sql = "INSERT INTO warehousing_storage_received_bale_mass(userid,seasonid,warehousing_sold_balesid,mass,created_at,datetimes
                                  ) VALUES ($userid,$seasonid,$warehousing_sold_balesid,$verify_mass,'$created_at','$datetimes')";
                                       //$sql = "select * from login";
                                       if ($conn->query($user_sql)===TRUE) {
                                       
                                         $last_id = $conn->insert_id;

                                           $temp=array("response"=>"success","barcode"=>$barcode,"warehousing_sold_balesid"=>$warehousing_sold_balesid);
                                           array_push($response,$temp);
                                         
                                       }else{

                                       $temp=array("response"=>$conn->error);
                                       array_push($response,$temp);

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

                    $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description) VALUES ($userid,$storeid,$productid,$quantity,$arc_products_id,'$created_at','WAREHOUSE STORAGE')";
          
                    if ($conn->query($user_sql1)===TRUE) {

                        $arc_store_item_id = $conn->insert_id;

                                           
                        $bale_value=$mass*$price;


                       $user_sql1 = "update total_sold_received set received_mass=received_mass+'$mass',received_bales=received_bales+1,received_total_value=received_total_value+'$bale_value' where seasonid=$seasonid";
                       //$sql = "select * from login";
                       if ($conn->query($user_sql1)===TRUE) {

                          $user_sql = "INSERT INTO warehousing_storage_received_bale_mass(userid,seasonid,warehousing_sold_balesid,mass,created_at,datetimes
                                  ) VALUES ($userid,$seasonid,$warehousing_sold_balesid,$verify_mass,'$created_at','$datetimes')";
                                       //$sql = "select * from login";
                                       if ($conn->query($user_sql)===TRUE) {
                                       
                                         $last_id = $conn->insert_id;

                                           $temp=array("response"=>"success","barcode"=>$barcode,"warehousing_sold_balesid"=>$warehousing_sold_balesid);
                                           array_push($response,$temp);
                                         
                                       }else{

                                       $temp=array("response"=>$conn->error);
                                       array_push($response,$temp);

                                  }
                         
                        }
                           

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

   }


}

}else{


$temp=array("response"=>"Field empty");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





