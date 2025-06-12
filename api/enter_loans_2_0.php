<?php
require "conn.php";
require "validate.php";

$userid=0;
$growerid=0;
$lat="";
$long="";
$product="";
$quantity="";
$created_at="";
$description="";
$productid=0;
$seasonid=0;
$sqliteid=0;
$verifyLoan=0;
$hectares=0;
$storeid=0;
$deduction_point=0;
$old_quantity=0;
$quantity_Enough=0;
$previous_growerid=0;
$active_grower=0;
$active_grower_found=0;
$verifyLoan=0;
$verifyHectares=0;
$disbursement_trucksid=0;
$disbursementid=0;
$android_captureid=0;


$data1=array();




//http://192.168.1.190/gms/api/enter_loans.php?userid=1&product=sadza&quantity=1&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&description=12333&seasonid=1&sqliteid=1

if (isset($_GET['description']) && isset($_GET['userid'])  && isset($_GET['latitude'])  && isset($_GET['longitude'])  && isset($_GET['product']) && isset($_GET['seasonid']) && isset($_GET['quantity']) && isset($_GET['created_at']) && isset($_GET['sqliteid']) && isset($_GET['capture_userid'])  ){


$userid=validate($_GET['userid']);
$seasonid=validate($_GET['seasonid']);
$lat=validate($_GET['latitude']);
$long=validate($_GET['longitude']);
$description=validate($_GET['description']);
$product=validate($_GET['product']);
$quantity=validate($_GET['quantity']);
$created_at=validate($_GET['created_at']);
$sqliteid=validate($_GET['sqliteid']);
$receipt_number=validate($_GET['receipt_number']);
$trucknumber=validate($_GET['trucknumber']);
$productid=validate($_GET['productid']);
$android_captureid=validate($_GET['capture_userid']);










$sql = "Select * from truck_destination where (truck_destination.trucknumber='$trucknumber' or truck_destination.id=$trucknumber) and close_open=1";
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





$sql = "Select * from growers where grower_num='$description' or phone='$description'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) { 
   
   $growerid=$row["id"];
      
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



 $sql5 = "Select * from loans where growerid=$growerid and loans.seasonid=$seasonid and productid=$productid and receipt_number='$receipt_number'  limit 1 ";
$result = $conn->query($sql5);
 
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

       $insert_sql = "INSERT INTO loans(userid,growerid,productid,seasonid,quantity,latitude,longitude,hectares,verified,created_at,receipt_number,android_captureid) VALUES ($userid,$growerid,$productid,$seasonid,$quantity,'$lat','$long','$hectares',0,'$created_at','$receipt_number',$android_captureid)";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
     $loanid_android = $conn->insert_id;

   if ($verifyHectares==0) {

   $insert_sql = "INSERT INTO contracted_hectares(userid,growerid,seasonid,hectares,created) VALUES ($userid,$growerid,$seasonid,'$hectares','$created_at')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
    // $last_id = $conn->insert_id;

    $user_sql1 = "update disbursement set quantity=quantity-$quantity  where disbursement_trucksid=$disbursement_trucksid and productid=$productid";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {
   
       $insert_sql = "INSERT INTO truck_to_grower(userid,growerid,disbursement_trucksid,quantity,productid,created_at,loanid) VALUES ($userid,$growerid,$disbursement_trucksid,$quantity,$productid,'$created_at',loanid_android)";
             //$sql = "select * from login";
             if ($conn->query($insert_sql)===TRUE) {
             
               $last_id = $conn->insert_id;
              if ($active_grower_found==0) {
                $user_sql = "INSERT INTO active_growers(userid,growerid,seasonid) VALUES ($userid,$growerid,$seasonid)";
                 //$sql = "select * from login";
                     if ($conn->query($user_sql)===TRUE) {

                      $temp=array("response"=>"success","id"=>$sqliteid);
                      array_push($data1,$temp);

                     }

                  }else{
                     $temp=array("response"=>"success","id"=>$sqliteid);
                      array_push($data1,$temp);
                  }

             }else{
              

              //$last_id = $conn->insert_id;
               $temp=array("response"=>"Truck To Grower Failed","id"=>$sqliteid);
                array_push($data1,$temp);

             }

   }else{
    

    //$last_id = $conn->insert_id;
     $temp=array("response"=>"Failed To Update","id"=>$sqliteid);
      array_push($data1,$temp);

    }

   }


   }else{

      $user_sql1 = "update disbursement set quantity=quantity-$quantity  where disbursement_trucksid=$disbursement_trucksid and productid=$productid";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {
   
           $insert_sql = "INSERT INTO truck_to_grower(userid,growerid,disbursement_trucksid,quantity,productid,created_at,loanid) VALUES ($userid,$growerid,$disbursement_trucksid,$quantity,$productid,'$created_at',$loanid_android)";
         //$sql = "select * from login";
         if ($conn->query($insert_sql)===TRUE) {
         
           $last_id = $conn->insert_id;
           if ($active_grower_found==0) {
            $user_sql = "INSERT INTO active_growers(userid,growerid,seasonid) VALUES ($userid,$growerid,$seasonid)";
             //$sql = "select * from login";
                 if ($conn->query($user_sql)===TRUE) {

                  $temp=array("response"=>"success","id"=>$sqliteid);
                  array_push($data1,$temp);

                 }

              }else{
                 $temp=array("response"=>"success","id"=>$sqliteid);
                  array_push($data1,$temp);
              }

         }else{
         

          //$last_id = $conn->insert_id;
           $temp=array("response"=>"Truck To Grower Failed","id"=>$sqliteid);
            array_push($data1,$temp);

         }

   }else{
    
    //$last_id = $conn->insert_id;
     $temp=array("response"=>"Failed To Update","id"=>$sqliteid);
      array_push($data1,$temp);

   }

   }



   }else{

    $temp=array("response"=>"failed","id"=>$sqliteid);
      array_push($data1,$temp);

  }
      }else{


            if ($disbursement_trucksid==0 && $disbursementid==0) {

              $temp=array("response"=>"Truck Not Found","id"=>$sqliteid);
              array_push($data1,$temp);
              
            }elseif($disbursementid==0){

                  $temp=array("response"=>"Out Of Stock","id"=>$sqliteid);
                  array_push($data1,$temp);

            }elseif($disbursement_trucksid==0){

                $temp=array("response"=>"Truck Not Found","id"=>$sqliteid);
                  array_push($data1,$temp);

            }


      }

  


   }else{


      if ($previous_growerid!=$growerid) {

          $temp=array("response"=>"Receipt Captured for another Grower","id"=>$sqliteid);
          array_push($data1,$temp);

        }elseif ($productid==0) {

         $temp=array("response"=>"Product Not Found","id"=>$sqliteid);
        array_push($data1,$temp);

        }elseif ($growerid==0) {
           $temp=array("response"=>"Grower Not Found","id"=>$sqliteid);
          array_push($data1,$temp);

        }elseif($verifyLoan==1){


          $temp=array("response"=>"Input Already Captured For Grower","id"=>$sqliteid);
          array_push($data1,$temp);
        }


   }

}




echo json_encode($data1);


?>





