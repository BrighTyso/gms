<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$id=0;
$productid=0;
$growerid=0;
$grower="";
$seasonid=0;
$receipt_number=0;
$to_growerid=0;
$loanid=0;
$processed=0;
$amount=0.0;
$userid=0;
$created_at="";
$data1=array();




if (isset($data->productid) && isset($data->growerid) && isset($data->seasonid)  && isset($data->grower_num) && isset($data->receiptnumber)){




    $productid=$data->productid;
    $growerid=$data->growerid;
    $grower=$data->grower_num;
    $seasonid=$data->seasonid;
    $userid=$data->userid;
    $created_at=$data->created_at;
    $receipt_number=validate($data->receiptnumber);


    $sql2 = "Select * from growers where grower_num='$grower' limit 1";
        $result2 = $conn->query($sql2);
         
         if ($result2->num_rows > 0) {
           // output data of each row
           while($row1 = $result2->fetch_assoc()) {
            // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

           $to_growerid=$row1["id"];

     
            
           }
        }


     $sql = "Select distinct loans.id as loanid,products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,quantity,units,loans.created_at,verified,processed,product_total_cost,product_amount from loans join growers on growers.id=loans.growerid join products on loans.productid=products.id  where loans.seasonid=$seasonid and productid=$productid  and growerid=$growerid and loans.receipt_number='$receipt_number' limit 1";

      $result = $conn->query($sql);
     
      if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {

     
        $processed=$row["processed"];
        $loanid=$row["loanid"];
        $amount=$row["product_total_cost"];



       }

     }



     if ($loanid>0) {
     

     if ($processed==0) {
     
             $user_sql1 = "update loans set growerid=$to_growerid where id=$loanid and seasonid=$seasonid";
           //$sql = "select * from login";
              if ($conn->query($user_sql1)===TRUE) {

                $insert_sql = "INSERT INTO transfer_grower_loan(userid,from_growerid,to_growerid,loanid,seasonid,created_at) VALUES ($userid,$growerid,$to_growerid,$loanid,$seasonid,'$created_at')";
   //$gr = "select * from login";
                    if ($conn->query($insert_sql)===TRUE) {

                        $temp=array("response"=>"success");
                        array_push($data1,$temp);

                  }else{

                    $temp=array("response"=>$conn->error);
                        array_push($data1,$temp);

                  }

              }

        }else{


           $user_sql1 = "update inputs_total set amount=amount-$amount where growerid=$growerid and seasonid=$seasonid";
                     //$sql = "select * from login";
             if ($conn->query($user_sql1)===TRUE) {

                   $user_sql2 = "update loans set growerid=$to_growerid,processed=0,product_total_cost=0,product_amount=0 where id=$loanid and seasonid=$seasonid";
                   //$sql = "select * from login";
                   if ($conn->query($user_sql2)===TRUE) {

                    $insert_sql = "INSERT INTO transfer_grower_loan(userid,from_growerid,to_growerid,loanid,seasonid,created_at) VALUES ($userid,$growerid,$to_growerid,$loanid,$seasonid,'$created_at')";
   //$gr = "select * from login";
                    if ($conn->query($insert_sql)===TRUE) {

                        $temp=array("response"=>"success");
                        array_push($data1,$temp);

                         }else{

                    $temp=array("response"=>$conn->error);
                        array_push($data1,$temp);

                  }


                      }

                }

        }

      }else{

            $temp=array("response"=>"loan Id Not Found");
             array_push($data1,$temp);

      }

     }


 




echo json_encode($data1);

?>





