<?php


require_once("../api/conn.php");






$data1=array();
// get grower location


$growerid=0;
$input_total=0;
$working_capital=0;
$roll_over=0;
$total_loan_amount=0;
$loan_payment=0;
$loan_interest=0;
$loan_balance=0;
$balance=0;
$grower_num="";
$grower_name="";
$grower_surname="";

$total_lamount=0;
  
$loans_data=array();

//$sql11 = "Select growers.id from  growers join active_growers on growers.id=active_growers.growerid where active_growers.seasonid=$seasonid";

$sql11 = "Select * from  seasons where active=1 ";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $seasonid=$row["id"];

   
   }
 }


    $total_loan_amount=0;
    $loan_payment=0;
    $loan_interest=0;
    $loan_balance=0;

    
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";



    $sql12 = "Select distinct * from inputs_total join growers on growers.id=inputs_total.growerid  where inputs_total.seasonid=$seasonid ";

    $result2 = $conn->query($sql12);
     
     if ($result2->num_rows > 0) {
       // output data of each row
       while($row2 = $result2->fetch_assoc()) {
        $input_total+=$row2["amount"];
       
       }
     }







     $sql14 = "Select distinct * from rollover_total join growers on growers.id=rollover_total.growerid join active_growers on active_growers.growerid=growers.id where rollover_total.seasonid=$seasonid ";

    $result4 = $conn->query($sql14);
     
     if ($result4->num_rows > 0) {
       // output data of each row
       while($row4 = $result4->fetch_assoc()) {

        $roll_over+=$row4["amount"];
       
       }
     }



      $sql13 = "Select distinct * from working_capital_total join growers on growers.id=working_capital_total.growerid  where working_capital_total.seasonid=$seasonid ";

    $result3 = $conn->query($sql13);
     
     if ($result3->num_rows > 0) {
       // output data of each row
       while($row3 = $result3->fetch_assoc()) {

        $working_capital+=$row3["amount"];

       
       
       }
     }



   $total_loan_amount=$input_total + $working_capital + $roll_over;

   $loan_interest=0;

   $loan_balance=0;



   $sql15 = "Select parameters.name,value from charges_amount join parameters on parameters.id=charges_amount.parameterid where charges_amount.seasonid=$seasonid ";

    $result5 = $conn->query($sql15);
     
     if ($result5->num_rows > 0) {
       // output data of each row
       while($row5 = $result5->fetch_assoc()) {

        if ($row5["name"]=="Amount") {

          $loan_interest+=$row5["value"];
          

        }else{


          $loan_interest+=($total_loan_amount*$row5["value"])/100;

        }

       
       }
     }

     $loan_balance+=$total_loan_amount+$loan_interest;




     $sql16 = "Select distinct amount from loan_payment_total  where loan_payment_total.seasonid=$seasonid and loan_payment_total.growerid=$growerid";

    $result6 = $conn->query($sql16);
     
     if ($result6->num_rows > 0) {
       // output data of each row
       while($row6 = $result6->fetch_assoc()) {

        
          $loan_payment+=$row6["amount"];

       
       }
     }


    $balance=$loan_balance-$loan_payment;


  


   









 echo $loan_balance;


?>


