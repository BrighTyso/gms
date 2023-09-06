<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";



$data = json_decode(file_get_contents("php://input"));


$data1=array();


if (isset($data->userid) && isset($data->seasonid) && isset($data->productid)) {
 
$userid=$data->userid;
$seasonid=$data->seasonid;
$productid=$data->productid;
$found=0;
$growerid=0;
$fetched_records=0;
$inserted_records=0;
$found_rollover=0;
$found_working_capital=0;


  $sql = "Select distinct loans.id as loanid,products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,quantity,units,loans.created_at,verified,amount from loans join growers on growers.id=loans.growerid join products on loans.productid=products.id join prices on prices.productid=loans.productid where loans.seasonid=$seasonid and  prices.seasonid=$seasonid and loans.productid=$productid and processed=1 and verified=1 order by growers.grower_num limit 5000";

  $result = $conn->query($sql);
 
  if ($result->num_rows > 0) {
   // output data of each row
     $fetched_records=$result->num_rows;
     $count=0;
     
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";


     $amount=0;
     $loanid=0;
     $quantity=0;
     $total_cost=0;
     $growerid=0;

     $found_rollover=0;
     $found_working_capital=0;


    

     $amount=$row["amount"];
     $loanid=$row["loanid"];
     $quantity=$row["quantity"];
     $total_cost=$amount*$quantity;
     $growerid=$row["id"];


    $sql1 = "Select * from inputs_total where seasonid=$seasonid and growerid=$growerid and amount>=$total_cost";
    $result1 = $conn->query($sql1);
     
     if ($result1->num_rows > 0) {
       // output data of each row
       while($row4 = $result1->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

       $found=$row4["id"];
        
       }
    }



if ($found>0) {


        $user_sql1 = "update loans set product_amount=0,product_total_cost=0,processed=0,processed_by=$userid where id=$loanid and processed=1";
           //$sql = "select * from login";
           if ($conn->query($user_sql1)===TRUE) {




            $inserted_records+=1;

            
            $user_sql2 = "update inputs_total set amount=amount-$total_cost where id=$found";
            //$sql = "select * from login";
           if ($conn->query($user_sql2)===TRUE) {

              

            }

              

      }

}
  


     




    // $temp=array("productid"=>$row["productid"],"name"=>$row["name"],"id"=>$row["id"],"surname"=>$row["surname"],"grower_num"=>$row["grower_num"],"product_name"=>$row["product_name"],"quantity"=>$row["quantity"],"units"=>$row["units"],"created_at"=>$row["created_at"],"verified"=>$row["verified"],"username"=>$row["username"],"amount"=>$row["amount"]);
    // array_push($data1,$temp);
    
   }

 }


 



}

  $temp=array("fetched_records"=>$fetched_records,"processed"=>$inserted_records);
   array_push($data1,$temp);



 echo json_encode($data1);

?>
