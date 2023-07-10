<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));


$data1=array();

$userid=$data->userid;
$growerid=0;
$lat=$data->latitude;
$long=$data->longitude;
$quantity=$data->quantity;
$created_at=$data->created_at;
$description=$data->grower;
$productid=$data->productid;
$seasonid=$data->seasonid;
$receipt_number=$data->receiptnumber;
$sqliteid=0;
$verifyLoan=0;
$verifyHectares=0;
$disbursement_trucksid=0;
$disbursementid=0;
$hectares=$data->hectares;
$trucknumber=$data->trucknumber;
$storeid=0;
$deduction_point=0;
$old_quantity=0;
$quantity_Enough=0;
$previous_growerid=0;
$active_grower=0;
$active_grower_found=0;






//http://192.168.1.190/gms/api/enter_loans.php?userid=1&product=sadza&quantity=1&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&description=12333&seasonid=1&sqliteid=1

if (isset($userid) && isset($description)  && isset($lat)  && isset($long)  && isset($productid) && isset($quantity) && isset($seasonid) && isset($created_at) && isset($hectares) && isset($trucknumber)){



      $sql = "Select * from loan_deduction_point limit 1";
      $result = $conn->query($sql);
       
       if ($result->num_rows > 0) {
         // output data of each row
         while($row = $result->fetch_assoc()) { 
         
         $deduction_point=$row["point"];
            
         }

       }




        $sql = "Select * from active_growers where growerid=$growerid and seasonid=$seasonid";
        $result = $conn->query($sql);
         
         if ($result->num_rows > 0) {
           // output data of each row
           while($row = $result->fetch_assoc()) {
           
           $active_grower_found=$row["id"];
          
            
           }

         }

// deduction_point 0 means we are deducting all the loans from the warehouse alse from the truck

if ($deduction_point==0) {





                      $sql = "Select * from user_to_store where loan_userid=$userid and active=1 ";
                          $result = $conn->query($sql);
                           
                           if ($result->num_rows > 0) {
                             // output data of each row
                             while($row = $result->fetch_assoc()) { 
                             
                             $storeid=$row["storeid"];

                                
                             }

                           }



                          $sql = "Select * from growers where grower_num='$description' or grower_num like '%$description%' limit 1";
                          $result = $conn->query($sql);
                           
                           if ($result->num_rows > 0) {
                             // output data of each row
                             while($row = $result->fetch_assoc()) { 
                             
                             $growerid=$row["id"];
                           
                               
                             }

                           }

                           // get selected  products id


                          // $product_sql = "Select * from products where name='$product'";
                          // $result = $conn->query($product_sql);
                           
                          //  if ($result->num_rows > 0) {
                          //    // output data of each row
                          //    while($row = $result->fetch_assoc()) {

                          //     // product id
                          //    $productid=$row["id"];
                             
                              
                          //    }

                          //  }

                          //check if loan is there


                           $sql = "Select * from loans where  (loans.seasonid=$seasonid  and receipt_number='$receipt_number') limit 1";
                          $result = $conn->query($sql);
                           
                           if ($result->num_rows > 0) {
                             // output data of each row
                             while($row = $result->fetch_assoc()) {
                              // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

                             #$verifyLoan=1;
                              $previous_growerid=$row["growerid"];



                              
                             }
                           }



                           $sql = "Select * from loans where (growerid=$growerid) and (loans.seasonid=$seasonid and productid=$productid and receipt_number='$receipt_number') ";
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




                           



                           $sql2 = "Select store.id,quantity,store_items.id as storeid from store join store_items on store.id=store_items.storeid where store.id=$storeid and productid=$productid and quantity>0 and quantity>=$quantity";
                            $result = $conn->query($sql2);
                             if ($result->num_rows > 0) {
                               // output data of each row
                               while($row = $result->fetch_assoc()) { 
                               

                               $storeid=$row["id"];
                               $quantity_Enough=$row["storeid"];
                               $old_quantity=$row["quantity"];

                                
                               }

                          }




  if (($productid>0  && $growerid>0 && $verifyLoan==0 && $productid>0 && $storeid>0 && $quantity_Enough>0) && ($previous_growerid==$growerid || $previous_growerid==0)) {


    
     $insert_sql = "INSERT INTO loans(userid,growerid,productid,seasonid,quantity,latitude,longitude,hectares,verified,created_at,receipt_number) VALUES ($userid,$growerid,$productid,$seasonid,$quantity,'$lat','$long','$hectares',1,'$created_at','$receipt_number')";
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

                                $arc_products_id = $conn->insert_id;


                                $user_sql2 = "INSERT INTO arc_product_grower(arc_productid,growerid,quantity) VALUES ($arc_products_id,$growerid,$quantity)";
                                        //$sql = "select * from login";
                                       if ($conn->query($user_sql2)===TRUE) {

                                    $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description,quantity_balance) VALUES ($userid,$storeid,$productid,$quantity,$arc_products_id,'$created_at','GROWER LOAN',$quantity)";
                              
                                    if ($conn->query($user_sql1)===TRUE) {


                                     if ($active_grower_found==0) {
                                      $user_sql = "INSERT INTO active_growers(userid,growerid,seasonid) VALUES ($userid,$growerid,$seasonid)";
                                       //$sql = "select * from login";
                                           if ($conn->query($user_sql)===TRUE) {

                                            $temp=array("response"=>"success");
                                            array_push($data1,$temp);

                                           }
                                        }else{
                                           $temp=array("response"=>"success");
                                            array_push($data1,$temp);
                                        }



                                     }else{

                                      $temp=array("response"=>$conn->error);
                                      array_push($data1,$temp);

                                     }
                                   }
                                   else{

                                      $temp=array("response"=>$conn->error);
                                      array_push($data1,$temp);

                                     }

                             }
                             else{

                                      $temp=array("response"=>$conn->error);
                                      array_push($data1,$temp);

                                     }

                        
                         }

                }

          }else{



                    $user_sql1 = "update store_items set quantity=quantity-$quantity  where storeid=$storeid and productid=$productid";
                         //$sql = "select * from login";
                         if ($conn->query($user_sql1)===TRUE) {

                            //$last_id = $conn->insert_id;


                          
                            $new_quantity=$old_quantity-$quantity;


                            $user_sql2 = "INSERT INTO arc_products(userid,storeitemid,old_quantity,new_quantity,created_at) VALUES ($userid,$quantity_Enough,$old_quantity,$new_quantity,'$created_at')";
                           //$sql = "select * from login";
                               if ($conn->query($user_sql2)===TRUE) {

                                     //$last_id = $conn->insert_id;
                                     $arc_products_id = $conn->insert_id;

                                      $user_sql2 = "INSERT INTO arc_product_grower(arc_productid,growerid,quantity) VALUES ($arc_products_id,$growerid,$quantity)";
                                        //$sql = "select * from login";
                                       if ($conn->query($user_sql2)===TRUE) {

                                       // $last_id = $conn->insert_id;
                                        
                                          $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description,quantity_balance) VALUES ($userid,$storeid,$productid,$quantity,$arc_products_id,'$created_at','GROWER LOAN',$quantity)";
                                    
                                          if ($conn->query($user_sql1)===TRUE) {



                                            if ($active_grower_found==0) {
                                            $user_sql = "INSERT INTO active_growers(userid,growerid,seasonid) VALUES ($userid,$growerid,$seasonid)";
                                             //$sql = "select * from login";
                                                 if ($conn->query($user_sql)===TRUE) {

                                                  $temp=array("response"=>"success");
                                                  array_push($data1,$temp);

                                                 }

                                              }else{
                                                 $temp=array("response"=>"success");
                                                  array_push($data1,$temp);
                                              }

                                            

                                           }else{

                                      $temp=array("response"=>$conn->error);
                                      array_push($data1,$temp);

                                     }

                                    }else{

                                      $temp=array("response"=>$conn->error);
                                      array_push($data1,$temp);

                                     }

                             }else{

                                      $temp=array("response"=>$conn->error);
                                      array_push($data1,$temp);

                                     }

                    }

          }


         }else{

          $temp=array("response"=>"failed");
            array_push($data1,$temp);

        }

  }else{


        if ($previous_growerid!=$growerid && $previous_growerid!=0) {

          $temp=array("response"=>"Receipt Captured for another Grower");
          array_push($data1,$temp);

        }elseif ($productid==0) {
          $temp=array("response"=>"Product Not Found");
            array_push($data1,$temp);
        }elseif($growerid==0){

          $temp=array("response"=>"Grower Not Found");
            array_push($data1,$temp);

        }elseif($storeid==0){

          $temp=array("response"=>"User Store Not Found");
            array_push($data1,$temp);
        }else{

          $temp=array("response"=>"Receipt already Captured");
            array_push($data1,$temp);

        }

  }




// end of loan here =================
  

}else{


$sql = "Select * from truck_destination where (truck_destination.trucknumber='$trucknumber' or id=$trucknumber) and close_open=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //$found=$row["id"];
    $disbursement_trucksid=$row["id"];
   

    
   }
 }




$sql = "Select * from disbursement where disbursement_trucksid=$disbursement_trucksid and productid=$productid and quantity>=$quantity and  quantity>0 ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //$found=$row["id"];
  
    $disbursementid=$row["id"];
    
   }
 }





$sql = "Select * from growers where grower_num='$description' or grower_num like '%$description' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) { 
   
   $growerid=$row["id"];
      
   }

 }

 // get selected  products id


// $product_sql = "Select * from products where name='$product'";
// $result = $conn->query($product_sql);
 
//  if ($result->num_rows > 0) {
//    // output data of each row
//    while($row = $result->fetch_assoc()) {

//     // product id
//    $productid=$row["id"];
   
    
//    }

//  }

//check if loan is there




 $sql = "Select * from loans where  (loans.seasonid=$seasonid  and receipt_number='$receipt_number') ";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     #$verifyLoan=1;
      $previous_growerid=$row["growerid"];

      
     }
   }



 $sql = "Select * from loans where (growerid=$growerid) and (loans.seasonid=$seasonid and productid=$productid and receipt_number='$receipt_number') ";
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




// then insert loan


  if (($productid>0  && $growerid>0 && $verifyLoan==0) && ($previous_growerid==$growerid || $previous_growerid==0)) {

    if ($disbursementid>0 && $disbursement_trucksid>0 ) {

       $insert_sql = "INSERT INTO loans(userid,growerid,productid,seasonid,quantity,latitude,longitude,hectares,verified,created_at,receipt_number) VALUES ($userid,$growerid,$productid,$seasonid,$quantity,'$lat','$long','$hectares',1,'$created_at','$receipt_number')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
    // $last_id = $conn->insert_id;

   if ($verifyHectares==0) {

   $insert_sql = "INSERT INTO contracted_hectares(userid,growerid,seasonid,hectares,created) VALUES ($userid,$growerid,$seasonid,'$hectares','$created_at')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
    // $last_id = $conn->insert_id;

    $user_sql1 = "update disbursement set quantity=quantity-$quantity  where disbursement_trucksid=$disbursement_trucksid and productid=$productid";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {
   
       $insert_sql = "INSERT INTO truck_to_grower(userid,growerid,disbursement_trucksid,quantity,productid,created_at) VALUES ($userid,$growerid,$disbursement_trucksid,$quantity,$productid,'$created_at')";
             //$sql = "select * from login";
             if ($conn->query($insert_sql)===TRUE) {
             
               $last_id = $conn->insert_id;
              if ($active_grower_found==0) {
                $user_sql = "INSERT INTO active_growers(userid,growerid,seasonid) VALUES ($userid,$growerid,$seasonid)";
                 //$sql = "select * from login";
                     if ($conn->query($user_sql)===TRUE) {

                      $temp=array("response"=>"success");
                      array_push($data1,$temp);

                     }

                  }else{
                     $temp=array("response"=>"success");
                      array_push($data1,$temp);
                  }

             }else{
              

              //$last_id = $conn->insert_id;
               $temp=array("response"=>"Truck To Grower Failed");
                array_push($data1,$temp);

             }

   }else{
    

    //$last_id = $conn->insert_id;
     $temp=array("response"=>"Failed To Update");
      array_push($data1,$temp);

    }

   }


   }else{

      $user_sql1 = "update disbursement set quantity=quantity-$quantity  where disbursement_trucksid=$disbursement_trucksid and productid=$productid";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {
   
           $insert_sql = "INSERT INTO truck_to_grower(userid,growerid,disbursement_trucksid,quantity,productid,created_at) VALUES ($userid,$growerid,$disbursement_trucksid,$quantity,$productid,'$created_at')";
         //$sql = "select * from login";
         if ($conn->query($insert_sql)===TRUE) {
         
           $last_id = $conn->insert_id;
           if ($active_grower_found==0) {
            $user_sql = "INSERT INTO active_growers(userid,growerid,seasonid) VALUES ($userid,$growerid,$seasonid)";
             //$sql = "select * from login";
                 if ($conn->query($user_sql)===TRUE) {

                  $temp=array("response"=>"success");
                  array_push($data1,$temp);

                 }

              }else{
                 $temp=array("response"=>"success");
                  array_push($data1,$temp);
              }

         }else{
         

          //$last_id = $conn->insert_id;
           $temp=array("response"=>"Truck To Grower Failed");
            array_push($data1,$temp);

         }

   }else{
    
    //$last_id = $conn->insert_id;
     $temp=array("response"=>"Failed To Update");
      array_push($data1,$temp);

   }

   }



   }else{

    $temp=array("response"=>"failed");
      array_push($data1,$temp);

  }
}else{


if ($disbursement_trucksid==0 && $disbursementid==0) {

  $temp=array("response"=>"Truck Not Found");
  array_push($data1,$temp);
  
}elseif($disbursementid==0){

      $temp=array("response"=>"Out Of Stock");
      array_push($data1,$temp);

}elseif($disbursement_trucksid==0){

    $temp=array("response"=>"Truck Not Found");
      array_push($data1,$temp);

}


}

  


   }else{


      if ($previous_growerid!=$growerid && $previous_growerid!=0) {

          $temp=array("response"=>"Receipt Captured for another Grower");
          array_push($data1,$temp);

        }elseif ($productid==0) {

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

}




echo json_encode($data1);


?>





