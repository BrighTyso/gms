<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=$data->seasonid;
//$scheme=$data->description;
$created_at=$data->created_at;
$trucknumber=validate($data->trucknumber);
$grower_num=$data->description;

$splitid=$data->splitid;

$fetched_records=0;
$processed_records=0;

$data1=array();
// get grower locations

if ($userid!="") {

// $sql123 = "Select distinct scheme_hectares_products.quantity as products_quantity,description,scheme_hectares.quantity as hectares,products.name,grower_num,scheme_hectares_products.productid from scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id join scheme_hectares_products on scheme_hectares_products.scheme_hectaresid=scheme_hectares.id join products on products.id=scheme_hectares_products.productid join scheme_hectares_growers on scheme_hectares_growers.scheme_hectaresid=scheme_hectares.id join growers on growers.id=scheme_hectares_growers.growerid where (scheme_hectares_products.productid,scheme_hectares_products.quantity) not in (Select loans.productid,loans.quantity from loans where loans.productid=scheme_hectares_products.productid and loans.growerid=growers.id and loans.seasonid=$seasonid) and scheme_hectares.seasonid=$seasonid and scheme_hectares_products.active=1 and grower_num='$grower_num' order by scheme.description limit 100";



  
$sql123 = "Select distinct scheme_hectares_products.quantity as products_quantity,description,scheme_hectares.quantity as hectares,products.name,grower_num,scheme_hectares_products.productid from scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id join scheme_hectares_products on scheme_hectares_products.scheme_hectaresid=scheme_hectares.id join products on products.id=scheme_hectares_products.productid join scheme_hectares_growers on scheme_hectares_growers.scheme_hectaresid=scheme_hectares.id join growers on growers.id=scheme_hectares_growers.growerid where  scheme_hectares.seasonid=$seasonid and scheme_hectares_products.active=1 and grower_num='$grower_num' order by scheme.description limit 100";
  $result123 = $conn->query($sql123);
  $fetched_records=$result123->num_rows;

   if ($result123->num_rows > 0) {
     // output data of each row
     while($row123 = $result123->fetch_assoc()) {




    

    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);



  // $temp=array("products_quantity"=>$row["products_quantity"],"description"=>$row["description"],"hectares"=>$row["hectares"],"name"=>$row["name"],"grower_num"=>$row["grower_num"],"productid"=>$row["productid"]);
  //   array_push($data1,$temp);

$growerid=0;
$lat="Desktop Scheme";
$long="Desktop Scheme";
$quantity=$row123["products_quantity"];
$description=$row123["grower_num"];
$productid=$row123["productid"];
$receipt_number=0;
$sqliteid=0;
$verifyLoan=0;
$verifyHectares=0;
$disbursement_trucksid=0;
$disbursementid=0;
$hectares=$row123["hectares"];
$storeid=0;
$deduction_point=0;
$old_quantity=0;
$quantity_Enough=0;
$previous_growerid=0;
$active_grower=0;
$active_grower_found=0;
$scheme_captured_quantity=0;
$product_captured_quantity=0;
$quantity_to_be_captured=0;


 $sql = "Select * from growers where grower_num='$description' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) { 
   
   $growerid=$row["id"];
 
     
   }

 }




 $sql2 = "Select distinct * from system_receipt_number where growerid=$growerid and seasonid=$seasonid and created_at='$created_at'";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {


         // output data of each row
         while($row2 = $result2->fetch_assoc()) {

          $receipt_number=$row2['receipt_number'];

         }
      }


//$receipt_number=0;
  if ($receipt_number==0) {
    $sql2 = "Select distinct * from system_receipt_number order by id desc limit 1";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {
         // output data of each row
         while($row2 = $result2->fetch_assoc()) {

          $receipt_number=$row2['receipt_number']+1;

          $insert_sql = "INSERT INTO system_receipt_number(userid,growerid,seasonid,receipt_number,created_at) VALUES ($userid,$growerid,$seasonid,$receipt_number,'$created_at')";
         //$gr = "select * from login";
         if ($conn->query($insert_sql)===TRUE) {


          }else{
            
          }
 


         }
      }else{

        $receipt_number=1702;
        $insert_sql = "INSERT INTO system_receipt_number(userid,growerid,seasonid,receipt_number,created_at) VALUES ($userid,$growerid,$seasonid,$receipt_number,'$created_at')";
         //$gr = "select * from login";
         if ($conn->query($insert_sql)===TRUE) {


         }else{
            
         }

      }
  }



$already_processed=0;

// $sql2 = "Select distinct * from loans where growerid=$growerid and seasonid=$seasonid and processed=1  limit 1";
// $result2 = $conn->query($sql2);
 
//  if ($result2->num_rows > 0) {

//            $already_processed=$result2->num_rows;

//        }  






//http://192.168.1.190/gms/api/enter_loans.php?userid=1&product=sadza&quantity=1&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&description=12333&seasonid=1&sqliteid=1

if (isset($userid) && isset($description)  && isset($lat)  && isset($long)  && isset($productid) && isset($quantity) && isset($seasonid) && isset($created_at) && isset($hectares) && isset($trucknumber) && ($already_processed>0 || $already_processed==0)){



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



                          $sql = "Select * from growers where grower_num='$description'  limit 1";
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



                           $sql = "Select * from loans where  (loans.seasonid=$seasonid  and loans.productid=$productid and loans.growerid=$growerid) ";
                            $result = $conn->query($sql);
                             
                             if ($result->num_rows > 0) {
                               // output data of each row
                               while($row = $result->fetch_assoc()) {
                                // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

                               #$verifyLoan=1;
                                $product_captured_quantity+=$row["quantity"];


                               }
                             }



                          $sql = "Select scheme_hectares.quantity from scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id  join scheme_hectares_growers on scheme_hectares_growers.scheme_hectaresid=scheme_hectares.id where scheme_hectares.seasonid=$seasonid  and scheme_hectares_growers.growerid=$growerid";
                          $result = $conn->query($sql);
                           
                           if ($result->num_rows > 0) {
                             // output data of each row
                             while($row = $result->fetch_assoc()) {
                              // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

                              $hectares=$row["quantity"];
                              
                             }
                           }




                             $sql = "Select scheme_hectares_products.quantity from scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id join scheme_hectares_products on scheme_hectares_products.scheme_hectaresid=scheme_hectares.id join scheme_hectares_growers on scheme_hectares_growers.scheme_hectaresid=scheme_hectares.id where scheme_hectares.seasonid=$seasonid and scheme_hectares_products.productid=$productid and scheme_hectares_products.active=1 and scheme_hectares_growers.growerid=$growerid";
                              $result = $conn->query($sql);
                               
                               if ($result->num_rows > 0) {
                                 // output data of each row
                                 while($row = $result->fetch_assoc()) {
                                  // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

                                  $scheme_captured_quantity+=$row["quantity"];
                                  
                                 }
                               }


                               $quantity_to_be_captured=$scheme_captured_quantity-$product_captured_quantity;



// && ($previous_growerid==$growerid || $previous_growerid==0)
//&& $quantity_to_be_captured>=$quantity
// echo "here 1";
// echo $quantity;

                            //   echo "hello";

  if (($productid>0  && $growerid>0 && $verifyLoan==0) && ($quantity_Enough>0)) {

 //echo "here";
    
     $insert_sql = "INSERT INTO loans(userid,growerid,productid,seasonid,quantity,latitude,longitude,hectares,verified,created_at,receipt_number,splitid) VALUES ($userid,$growerid,$productid,$seasonid,$quantity,'$lat','$long','$hectares',1,'$created_at','$receipt_number',$splitid)";
         //$gr = "select * from login";
         if ($conn->query($insert_sql)===TRUE) {

         
           $loan_id = $conn->insert_id;

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


                                $user_sql2 = "INSERT INTO arc_product_grower(arc_productid,loanid) VALUES ($arc_products_id,$loan_id)";
                                        //$sql = "select * from login";
                                       if ($conn->query($user_sql2)===TRUE) {

                                    $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description,quantity_balance) VALUES ($userid,$storeid,$productid,$quantity,$arc_products_id,'$created_at','GROWER LOAN',$quantity)";
                              
                                    if ($conn->query($user_sql1)===TRUE) {


                                     if ($active_grower_found==0) {
                                      $user_sql = "INSERT INTO active_growers(userid,growerid,seasonid) VALUES ($userid,$growerid,$seasonid)";
                                       //$sql = "select * from login";
                                           if ($conn->query($user_sql)===TRUE) {

                                            

                                            $processed_records+=1;

                                           }
                                        }else{
                                           

                                            $processed_records+=1;
                                        }



                                     }else{

                                      

                                     }
                                   }
                                   else{

                                      

                                     }

                             }
                             else{

                                      

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

                                      $user_sql2 = "INSERT INTO arc_product_grower(arc_productid,loanid) VALUES ($arc_products_id,$loan_id)";
                                        //$sql = "select * from login";
                                       if ($conn->query($user_sql2)===TRUE) {

                                       // $last_id = $conn->insert_id;
                                        
                                          $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description,quantity_balance) VALUES ($userid,$storeid,$productid,$quantity,$arc_products_id,'$created_at','GROWER LOAN',$quantity)";
                                    
                                          if ($conn->query($user_sql1)===TRUE) {



                                            if ($active_grower_found==0) {
                                            $user_sql = "INSERT INTO active_growers(userid,growerid,seasonid) VALUES ($userid,$growerid,$seasonid)";
                                             //$sql = "select * from login";
                                                 if ($conn->query($user_sql)===TRUE) {

                                                  
                                                  $processed_records+=1;

                                                 }

                                              }else{
                                                 

                                                  $processed_records+=1;
                                              }

                                            

                                           }else{

                                      

                                     }

                                    }else{

                                      
                                     }

                             }else{

                                    

                                     }

                    }

          }


         }else{

         

        }

  }else{


        if ($previous_growerid!=$growerid && $previous_growerid!=0) {


        }elseif ($productid==0) {
          
        }elseif($growerid==0){

         

        }elseif($storeid==0){

          
        }elseif($verifyLoan==1){

          

        }elseif($quantity_Enough==0){

         

        }else if($quantity_to_be_captured<$quantity){

           
        }

  }




// end of loan here =================
  

}else{


$sql = "Select * from truck_destination where (truck_destination.trucknumber='$trucknumber' or id='$trucknumber') and close_open=1";
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





$sql = "Select * from growers where grower_num='$description'  limit 1";
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






$sql = "Select * from loans where  (loans.seasonid=$seasonid  and loans.productid=$productid and loans.growerid=$growerid) ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $product_captured_quantity+=$row["quantity"];


   }
 }



$sql = "Select scheme_hectares.quantity from scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id  join scheme_hectares_growers on scheme_hectares_growers.scheme_hectaresid=scheme_hectares.id where scheme_hectares.seasonid=$seasonid  and scheme_hectares_growers.growerid=$growerid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $hectares=$row["quantity"];
    
   }
 }




$sql = "Select scheme_hectares_products.quantity from scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id join scheme_hectares_products on scheme_hectares_products.scheme_hectaresid=scheme_hectares.id join scheme_hectares_growers on scheme_hectares_growers.scheme_hectaresid=scheme_hectares.id where scheme_hectares.seasonid=$seasonid and scheme_hectares_products.productid=$productid and scheme_hectares_products.active=1 and scheme_hectares_growers.growerid=$growerid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $scheme_captured_quantity+=$row["quantity"];
    
   }
 }


 $quantity_to_be_captured=$scheme_captured_quantity-$product_captured_quantity;





// then insert loan

//&& ($previous_growerid==$growerid || $previous_growerid==0)

  if (($productid>0  && $growerid>0 && $verifyLoan==0)  && $quantity_to_be_captured>=$quantity) {

    if ($disbursementid>0 && $disbursement_trucksid>0 ) {

       $insert_sql = "INSERT INTO loans(userid,growerid,productid,seasonid,quantity,latitude,longitude,hectares,verified,created_at,receipt_number,splitid) VALUES ($userid,$growerid,$productid,$seasonid,$quantity,'$lat','$long','$hectares',1,'$created_at','$receipt_number',$splitid)";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
     $loan_id = $conn->insert_id;

   if ($verifyHectares==0) {

   $insert_sql = "INSERT INTO contracted_hectares(userid,growerid,seasonid,hectares,created) VALUES ($userid,$growerid,$seasonid,'$hectares','$created_at')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
    // $last_id = $conn->insert_id;

    $user_sql1 = "update disbursement set quantity=quantity-$quantity  where disbursement_trucksid=$disbursement_trucksid and productid=$productid";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {
   
       $insert_sql = "INSERT INTO truck_to_grower(userid,growerid,disbursement_trucksid,quantity,productid,loanid,created_at) VALUES ($userid,$growerid,$disbursement_trucksid,$quantity,$productid,$loan_id,'$created_at')";
             //$sql = "select * from login";
             if ($conn->query($insert_sql)===TRUE) {
             
               $last_id = $conn->insert_id;
              if ($active_grower_found==0) {
                $user_sql = "INSERT INTO active_growers(userid,growerid,seasonid) VALUES ($userid,$growerid,$seasonid)";
                 //$sql = "select * from login";
                     if ($conn->query($user_sql)===TRUE) {

                      

                      $processed_records+=1;

                     }

                  }else{
                    


                      $processed_records+=1;
                  }

             }else{
              

              //$last_id = $conn->insert_id;
               

             }

   }else{
    

    //$last_id = $conn->insert_id;
    

    }

   }


   }else{

      $user_sql1 = "update disbursement set quantity=quantity-$quantity  where disbursement_trucksid=$disbursement_trucksid and productid=$productid";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {
   
           $insert_sql = "INSERT INTO truck_to_grower(userid,growerid,disbursement_trucksid,quantity,productid,loanid,created_at) VALUES ($userid,$growerid,$disbursement_trucksid,$quantity,$productid,$loan_id,'$created_at')";
         //$sql = "select * from login";
         if ($conn->query($insert_sql)===TRUE) {
         
           $last_id = $conn->insert_id;
           if ($active_grower_found==0) {
            $user_sql = "INSERT INTO active_growers(userid,growerid,seasonid) VALUES ($userid,$growerid,$seasonid)";
             //$sql = "select * from login";
                 if ($conn->query($user_sql)===TRUE) {

                 


                  $processed_records+=1;

                 }

              }else{

                 


                  $processed_records+=1;
                  
              }

         }else{
         

          //$last_id = $conn->insert_id;
         

         }

   }else{
    
    //$last_id = $conn->insert_id;
     

   }

   }



   }else{

    

  }
}else{


if ($disbursement_trucksid==0 && $disbursementid==0) {

  
  
}elseif($disbursementid==0){

      

}elseif($disbursement_trucksid==0){

   

}


}

  


   }else{


      if ($previous_growerid!=$growerid && $previous_growerid!=0) {

        

        }elseif ($productid==0) {

        
      }elseif ($growerid==0) {
         
      }elseif($verifyLoan==1){
        
      }else if($quantity_to_be_captured<$quantity){

      
      }


     }


   }

}





   
   }
 }


}


$temp=array("fetched_records"=>$fetched_records,"processed_records"=>$processed_records);
        array_push($data1,$temp);
 echo json_encode($data1);


?>


