<?php
require "conn.php";
require "validate.php";

$userid=0;
$growerid=0;
$grower_num="";
$lat="";
$long="";
$percentage_strike="";
$strike_date="";
$seasonid=0;
$sqliteid=0;

$data=array();
//http://192.168.1.190/gms/api/enter_hail_strike.php?userid=1&grower_num=V123456&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&percentage_strike=12333&strike_date=12333&seasonid=1&sqliteid=1

$userid=validate($_GET['userid']);
$seasonid=validate($_GET['seasonid']);
$latitude=validate($_GET['latitude']);
$longitude=validate($_GET['longitude']);
$description=validate($_GET['description']);
$product=validate($_GET['product']);
$quantity=validate($_GET['quantity']);
$trucknumber=validate($_GET['trucknumber']);
$created_at=validate($_GET['created_at']);
$sqliteid=validate($_GET['sqliteid']);
$comment=validate($_GET['comment']);
$adjust=validate($_GET['adjust']);
$adjustment_quantity=validate($_GET['adjustment_quantity']);
$datetime=validate($_GET['datetime']);


$disbused_total_quantity=0;

$product_captured_quantity=0;
$hectares=0;
$scheme_captured_quantity=0;
$active_grower_found=0;
$disbursement_trucksid=0;

if ($adjust==0) {
  $disbused_total_quantity=$quantity;
}else{
  $disbused_total_quantity=$adjustment_quantity;
}

$productid=0;

$storeid=0;
$deduction_point=0;
$old_quantity=0;
$quantity_Enough=0;
$previous_growerid=0;
$grower_field_loansid=0;
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



   $sql = "Select * from products where name='$product' limit 1";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) { 
     
     $productid=$row["id"];
   
      
     }

   }

   $sql = "Select * from truck_destination where trucknumber='$trucknumber' limit 1";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) { 
     
     $disbursement_trucksid=$row["id"];
   
      
     }

   }


     $receipt_number=0;

     $sql2 = "Select distinct * from system_receipt_number where growerid=$growerid and seasonid=$seasonid";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {
        
         // output data of each row
         while($row2 = $result2->fetch_assoc()) {

          $receipt_number=$row2['receipt_number'];

         }
      }


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




 $sql = "Select scheme_hectares_products.quantity from scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id join scheme_hectares_products on scheme_hectares_products.scheme_hectaresid=scheme_hectares.id join scheme_hectares_growers on scheme_hectares_growers.scheme_hectaresid=scheme_hectares.id where scheme_hectares.seasonid=$seasonid and scheme_hectares_products.productid=$productid and scheme_hectares_growers.growerid=$growerid";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

      $scheme_captured_quantity+=$row["quantity"];
      
     }
   }


   $quantity_to_be_captured=$scheme_captured_quantity-$product_captured_quantity;







   $sql2 = "Select distinct * from disbursed_products_grower_truck where growerid=$growerid and seasonid=$seasonid and productid=$productid limit 1";
    $result2 = $conn->query($sql2);
     
     if ($result2->num_rows > 0) {
       // output data of each row
       while($row2 = $result2->fetch_assoc()) {

        $grower_field_loansid=$row2['id'];

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


//&& $quantity_to_be_captured>=$disbused_total_quantity
if ($growerid>0 && $grower_field_loansid==0 && $productid>0 && $disbursement_trucksid>0) {

   $insert_sql = "INSERT INTO disbursed_products_grower_truck(userid,seasonid,growerid,productid,quantity,latitude,longitude,disbursement_trucksid,farmer_comment,adjustment_quantity,adjust,created_at,datetimes) VALUES ($userid,$seasonid,$growerid,$productid,$quantity,'$latitude','$longitude',$disbursement_trucksid,'$comment',$adjustment_quantity,$adjust,'$created_at','$datetime')";
 //$gr = "select * from login";
 if ($conn->query($insert_sql)===TRUE) {


  $insert_sql = "INSERT INTO loans(userid,growerid,productid,seasonid,quantity,latitude,longitude,hectares,verified,created_at,receipt_number) VALUES ($userid,$growerid,$productid,$seasonid,$disbused_total_quantity,'$latitude','$longitude','$hectares',1,'$created_at','$receipt_number')";
         //$gr = "select * from login";
         if ($conn->query($insert_sql)===TRUE) {

        
            if ($active_grower_found==0) {
            $user_sql = "INSERT INTO active_growers(userid,growerid,seasonid) VALUES ($userid,$growerid,$seasonid)";
             //$sql = "select * from login";
                 if ($conn->query($user_sql)===TRUE) {
                  $temp=array("response"=>"success","id"=>$sqliteid);
                  array_push($data,$temp);
                 }

              }else{
                 $temp=array("response"=>"success","id"=>$sqliteid);
                  array_push($data,$temp);
              }


        }
           
 }else{

  $temp=array("response"=>$conn->error);
    array_push($data,$temp);
 }


  
}




  

echo json_encode($data);


?>





