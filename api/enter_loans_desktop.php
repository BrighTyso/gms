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
$growerid=$data->growerid;
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






//http://192.168.1.190/gms/api/enter_loans.php?userid=1&product=sadza&quantity=1&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&description=12333&seasonid=1&sqliteid=1

if (isset($userid) && isset($description)  && isset($lat)  && isset($long)  && isset($productid) && isset($quantity) && isset($seasonid) && isset($created_at) && isset($hectares) && isset($trucknumber)){



$sql = "Select * from truck_destination where truck_destination.trucknumber='$trucknumber' and close_open=1";
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


  if ($productid>0  && $growerid>0 && $verifyLoan==0 ) {

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
               $temp=array("response"=>"success");
               array_push($data1,$temp);

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
           $temp=array("response"=>"success");
           array_push($data1,$temp);

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

    if ($productid==0) {
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




echo json_encode($data1);


?>





