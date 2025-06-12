<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";



$data = json_decode(file_get_contents("php://input"));


$data1=array();


if (isset($data->userid) && isset($data->seasonid) && isset($data->grower_num)) {
 
$userid=$data->userid;
$seasonid=$data->seasonid;
$grower_num=$data->grower_num;
$found=0;
$growerid=0;
$fetched_records=0;
$inserted_records=0;
$found_rollover=0;
$found_working_capital=0;


  $sql = "Select distinct loans.id as loanid,products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,quantity,units,loans.created_at,verified,amount from loans join growers on growers.id=loans.growerid join products on loans.productid=products.id join prices on prices.productid=loans.productid where loans.seasonid=$seasonid and  prices.seasonid=$seasonid and processed=0 and verified=1 and (grower_num='$grower_num') order by growers.grower_num limit 5000";

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


    $sql1 = "Select * from inputs_total where seasonid=$seasonid and growerid=$growerid";
    $result1 = $conn->query($sql1);
     
     if ($result1->num_rows > 0) {
       // output data of each row
       while($row4 = $result1->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

       $found=$row4["id"];
        
       }
    }




    $sql2 = "Select * from working_capital_total where seasonid=$seasonid and growerid=$growerid limit 1";
    $result2 = $conn->query($sql2);
     
     if ($result2->num_rows > 0) {
       // output data of each row
       while($row1 = $result2->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

       $found_working_capital=$row1["id"];
      
        
       }
    }




     $sql3 = "Select * from rollover where seasonid=$seasonid and growerid=$growerid limit 1";
    $result3 = $conn->query($sql3);
     
     if ($result3->num_rows > 0) {
       // output data of each row
       while($row2 = $result3->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

       $found_rollover=$row2["id"];




        
       }
    }





     $user_sql1 = "update loans set product_amount=$amount,product_total_cost=product_total_cost+$total_cost,processed=1,processed_by=$userid where id=$loanid and processed=0";
     //$sql = "select * from login";
     if ($conn->query($user_sql1)===TRUE) {


      $inserted_records+=1;

      if ($found==0) {
        
            $insert_sql = "INSERT INTO inputs_total(userid,seasonid,growerid,amount) VALUES ($userid,$seasonid,$growerid,'$total_cost')";
                                 //$gr = "select * from login";
               if ($conn->query($insert_sql)===TRUE) {

               

               }

      }else{

          $user_sql1 = "update inputs_total set amount=amount+$total_cost where id=$found";
          //$sql = "select * from login";
         if ($conn->query($user_sql1)===TRUE) {

            

            }

        }

       

      }


// create working capital starting balances

         if ($found_working_capital==0) {
        
            $insert_sql2 = "INSERT INTO working_capital_total(userid,seasonid,growerid) VALUES ($userid,$seasonid,$growerid)";
                                 //$gr = "select * from login";
               if ($conn->query($insert_sql2)===TRUE) {

               }

           }

// create rollover starting balances



           if ($found_rollover==0) {
           
        
            $insert_sql3 = "INSERT INTO rollover(userid,seasonid,growerid,rollover_seasonid) VALUES ($userid,$seasonid,$growerid,$seasonid)";
                                 //$gr = "select * from login";
               if ($conn->query($insert_sql3)===TRUE) {

              
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
