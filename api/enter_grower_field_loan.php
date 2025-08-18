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

$found=0;
$already_in=0 ;
$scheme_hectaresid=0;
$grower_found=0;
$product="";

$data=array();
//http://192.168.1.190/gms/api/enter_hail_strike.php?userid=1&grower_num=V123456&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&percentage_strike=12333&strike_date=12333&seasonid=1&sqliteid=1



$userid=validate($_GET['userid']);
$seasonid=validate($_GET['seasonid']);
$latitude=validate($_GET['latitude']);
$longitude=validate($_GET['longitude']);
$description=validate($_GET['description']);

if ($_GET['product']=="Seedpack" || $_GET['product']=="seedpack") {
  $product="seed";
 //$product= validate($_GET['product']);
}else{
  $product=validate($_GET['product']);
}

$quantity=validate($_GET['quantity']);
$hectares=validate($_GET['hectares']);
$created_at=validate($_GET['created_at']);
$sqliteid=validate($_GET['sqliteid']);
$android_captureid=validate($_GET['android_captureid']);
$comment=validate($_GET['comment']);
$adjust=validate($_GET['adjust']);
$adjustment_quantity=validate($_GET['adjustment_quantity']);
$datetime=validate($_GET['datetime']);


$name=$_GET['name'];
$surname=$_GET['surname'];
$phone=$_GET['phone'];
$id_num=$_GET['id_num'];
$area=$_GET['area'];
$province=$_GET['province'];
$created_at=$_GET['created_at'];



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

   }else{

        $grower_farm_sql = "INSERT INTO growers(userid,seasonid,grower_num,name,surname,phone,id_num,area,province,created_at) VALUES ($userid,$seasonid,'$description','$name','$surname','$phone','$id_num','$area','$province','$created_at')";
         //$sql = "select * from login";
         if ($conn->query($grower_farm_sql)===TRUE) {

         }else{
          $temp=array("response"=>$conn->error,"hh"=>"kkk");
          array_push($data,$temp);
         }

     }



  $sql = "Select growers.id from growers  where  grower_num='$description' limit 1";
    $result = $conn->query($sql);
        
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        // product id
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





$sql = "Select * from scheme_hectares where  quantity='$hectares' and seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $scheme_hectaresid=$row["id"];
   
   }

 }



$sql = "Select scheme_hectares.id,scheme_hectares.quantity from scheme_hectares_growers  join scheme_hectares  on scheme_hectares.id=scheme_hectares_growers.scheme_hectaresid where scheme_hectares.seasonid=$seasonid and growerid=$growerid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $already_in=$row["id"];
   $scheme_hectares_to_verify=$row["quantity"];
   
   }

 }




$sql = "Select * from scheme_hectares_growers where  scheme_hectaresid=$scheme_hectaresid and growerid=$growerid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $found=$row["id"];
   
   }

 }




if ($found==0 && $growerid>0 && $already_in==0 && $scheme_hectaresid>0 ) {
  
$user_sql = "INSERT INTO scheme_hectares_growers(userid,scheme_hectaresid,growerid) VALUES ($userid,$scheme_hectaresid,$growerid)";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
  
   }else{

 
   }

}







$sql = "Select * from grower_field_officer where growerid=$growerid and seasonid=$seasonid";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $grower_found=$row["id"];
      
     }

  }


   if ($grower_found==0 && $growerid>0) {

     $user_sql = "INSERT INTO grower_field_officer(userid,seasonid,growerid,field_officerid,created_at) VALUES ($userid,$seasonid,$growerid,$userid,'$created_at')";
                   //$sql = "select * from login";
       if ($conn->query($user_sql)===TRUE) {

       }else{
          
       }
       
   }







   $sql2 = "Select distinct * from grower_field_loans where growerid=$growerid and seasonid=$seasonid and productid=$productid limit 1";
    $result2 = $conn->query($sql2);
     
     if ($result2->num_rows > 0) {
       // output data of each row
       while($row2 = $result2->fetch_assoc()) {

        $grower_field_loansid=$row2['id'];

       }
    }



if ($growerid>0 && $grower_field_loansid==0 && $productid>0) {

   $insert_sql = "INSERT INTO grower_field_loans(userid,seasonid,growerid,productid,quantity,latitude,longitude,hectares,android_captureid,farmer_comment,adjustment_quantity,adjust,created_at,datetimes) VALUES ($userid,$seasonid,$growerid,$productid,$quantity,'$latitude','$longitude',$hectares,$android_captureid,'$comment',$adjustment_quantity,$adjust,'$created_at','$datetime')";
 //$gr = "select * from login";
 if ($conn->query($insert_sql)===TRUE) {
 
  $insert_sql = "insert into visits(userid,growerid,seasonid,latitude,longitude,created_at,description) value($userid,$growerid,$seasonid,'$latitude','$longitude','$created_at','Grower Requirements Loan');";
       //$gr = "select * from login";
       if ($conn->query($insert_sql)===TRUE) {
    $temp=array("response"=>"success","id"=>$sqliteid);
      array_push($data,$temp);
    }
           
 }else{

  $temp=array("response"=>$conn->error);
    array_push($data,$temp);
 }


  
}




  

echo json_encode($data);


?>





