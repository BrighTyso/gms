<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$seasonid=0;
$found=0;

$scheme_hectares_to_verify="";

$data1=array();

if (isset($data->userid)){

//$userid=$data->userid;
//$description=$data->description;

$userid=$data->userid;
$hectares=$data->hectares;
$grower_num=$data->grower_num;
$growerid=0;
$scheme_hectaresid=0;
$seasonid=0;
$already_in=0;
$already_processed=0;




$sql = "Select * from seasons where  active=1 limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $seasonid=$row["id"];
   
   }

 }



$sql = "Select * from growers where  grower_num='$grower_num' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $growerid=$row["id"];
   
   }

 }



 $sql = "Select distinct products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,quantity,units,package_units,loans.created_at,verified, users.username,amount,receipt_number,product_amount,product_total_cost  from loans join growers on growers.id=loans.growerid join products on loans.productid=products.id join users on users.id=loans.userid join prices on prices.productid=loans.productid where loans.seasonid=$seasonid and prices.seasonid=$seasonid and processed=1 and loans.growerid=$growerid order by product_amount limit 1";
    $result = $conn->query($sql);
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {

        $already_processed=$row["id"];

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
   
     $temp=array("response"=>"success");
     array_push($data1,$temp);

   }else{

   $temp=array("response"=>$conn->error);
   array_push($data1,$temp);

   }

}else{

  if ($already_in>0 && $scheme_hectares_to_verify!=$hectares && $scheme_hectares_to_verify!="" && $already_processed==0) {


  $scheme_hectaresid=0;

  $sql = "Select * from scheme_hectares where  quantity='$hectares' and seasonid=$seasonid";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {

      // product id
     $scheme_hectaresid=$row["id"];
     
     }

   }


    if ($scheme_hectaresid>0) {
            $user_sql1 = "update scheme_hectares_growers set scheme_hectaresid=$scheme_hectaresid where growerid=$growerid";
       //$sql = "select * from login";
       if ($conn->query($user_sql1)===TRUE) {

        $temp=array("response"=>"updated scheme");
        array_push($data1,$temp);

         
        }
    }else{
      if ($scheme_hectaresid==0){
        $temp=array("response"=>"Scheme not Found");
     array_push($data1,$temp);
   }
   }

   

  }else if ($already_in>0 && $scheme_hectares_to_verify==$hectares) {

    $temp=array("response"=>"Grower Already in scheme");
     array_push($data1,$temp);
  
  }else if ($scheme_hectaresid==0) {
     $temp=array("response"=>"Scheme not Found");
     array_push($data1,$temp);
  }else if($already_processed>0){

      $temp=array("response"=>"Reverse Products First");
     array_push($data1,$temp);
    
  }else{

     $temp=array("response"=>"Grower not Found");
     array_push($data1,$temp);

  }
 
}


}else{

   $temp=array("response"=>"field cant be empty");
   array_push($data1,$temp);

}




echo json_encode($data1)

?>



























