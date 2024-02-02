<?php
require_once("../api/conn.php");

$seasonid=0;

$total_growers=0;
$contracted_ha=0;
$visited_ha=0;
$non_visited_ha=0;
$grower_visits=0;

$input_total=0;
$working_capital=0;
$roll_over=0;
$total_loan_amount=0;
$loan_payment=0;
$loan_interest=0;
$loan_balance=0;
$percantage=0;
$visited_growers=0;
$risk=0;


$startDate="";
$endDate="";


// $id=$_GET['id'];
// $startDate=date_format(date_create($_GET['start']),"Y-m-d");
// $endDate=date_format(date_create($_GET['end']),"Y-m-d");



$sql11 = "Select * from  seasons where active=1 ";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $seasonid=$row["id"];

   
   }
 }


 $sql11 = "Select distinct users.id,surname,name from  users where active=1 and (rightsid=7 or rightsid=8 or rightsid=9)";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
				$total_growers=0;
		    $contracted_ha=0;
		    $visited_ha=0;
		    $non_visited_ha=0;
		    $grower_visits=0;
		    $name=$row["name"];
		    $surname=$row["surname"];
		    $userid=$row['id'];

		    

		    
		    $percantage=0;
		    $risk=0;
		    $total_visits=0;




		     $sql132 = "Select distinct growerid from  visits where userid=$userid and seasonid=$seasonid";

			    $result32 = $conn->query($sql132);
			     
			     if ($result32->num_rows > 0) {
			       // output data of each row
			       while($row32 = $result32->fetch_assoc()) {

			       	      $input_total=0;
								    $working_capital=0;
								    $roll_over=0;
								    $total_loan_amount=0;
								    $loan_payment=0;
								    $loan_interest=0;
								    $loan_balance=0;
								    $total_visits=0;

			             	$growerid=$row32["growerid"];
			             


								    $sql12 = "Select * from inputs_total join growers on growers.id=inputs_total.growerid join active_growers on active_growers.growerid=growers.id where inputs_total.seasonid=$seasonid and inputs_total.growerid=$growerid";

								    $result2 = $conn->query($sql12);
								     
								     if ($result2->num_rows > 0) {
								       // output data of each row
								       while($row2 = $result2->fetch_assoc()) {

								        $input_total+=$row2["amount"];
								       
								       }
								     }

								   

								     $sql14 = "Select * from rollover_total join growers on growers.id=rollover_total.growerid join active_growers on active_growers.growerid=growers.id where rollover_total.seasonid=$seasonid and rollover_total.growerid=$growerid";

								    $result4 = $conn->query($sql14);
								     
								     if ($result4->num_rows > 0) {
								       // output data of each row
								       while($row4 = $result4->fetch_assoc()) {

								        $roll_over+=$row4["amount"];
								       
								       }
								     }



								      $sql13 = "Select * from working_capital_total join growers on growers.id=working_capital_total.growerid join active_growers on active_growers.growerid=growers.id where working_capital_total.seasonid=$seasonid and working_capital_total.growerid=$growerid";

								    $result3 = $conn->query($sql13);
								     
								     if ($result3->num_rows > 0) {
								       // output data of each row
								       while($row3 = $result3->fetch_assoc()) {

								        $working_capital+=$row3["amount"];
								       
								       }
								     }



								   $total_loan_amount=$input_total + $working_capital + $roll_over;

								   $loan_interest=0;

								   //$loan_balance=0;



								     $sql1 = "Select distinct growerid,created_at from visits where  userid=$userid and seasonid=$seasonid";
										$result1 = $conn->query($sql1);
										 
										$visited_growers=$result1->num_rows;



								   $sql15 = "Select parameters.name,value from charges_amount join parameters on parameters.id=charges_amount.parameterid where charges_amount.seasonid=$seasonid ";

								    $result5 = $conn->query($sql15);
								     
								     if ($result5->num_rows > 0) {
								       // output data of each row
								       while($row5 = $result5->fetch_assoc()) {

								        if ($row5["name"]=="Amount") {

								          $loan_interest+=$row5["value"];

								        }else{

								          $loan_interest+=$total_loan_amount*$row5["value"]/100;

								        }

								       
								       }
								     }

								     $loan_balance+=$total_loan_amount+$loan_interest;




								     $sql16 = "Select amount from loan_payment_total join active_growers on active_growers.growerid=loan_payment_total.growerid  where loan_payment_total.seasonid=$seasonid  and loan_payment_total.growerid=$growerid";

								    $result6 = $conn->query($sql16);
								     
								     if ($result6->num_rows > 0) {
								       // output data of each row
								       while($row6 = $result6->fetch_assoc()) {

								        
								          $loan_payment+=$row6["amount"];

								       
								       }
								     }


								    $balance=$loan_balance-$loan_payment;


										$sql4 = "Select distinct growerid,description from  visits where  userid=".$row['id']." and seasonid=$seasonid and growerid=$growerid";

										$result4 = $conn->query($sql4);

										$visits=$result4->num_rows;



										$total_visits+=$visits;


								    

			       }

			       $risk=100-(($total_visits/(20*$result32->num_rows))*100);

			       $percantage+=($loan_payment/$loan_balance)*100;
			     }else{


			     	$risk=100;

			     }
					    
		    

    
			$sql2 = "Select distinct growerid,created_at from  visits where  userid=".$row['id']." and seasonid=$seasonid";

			$result1 = $conn->query($sql2);

			$grower_visits=$result1->num_rows;




			$sql2 = "Select distinct growerid from  visits where userid=$userid and seasonid=$seasonid ";

			$result2 = $conn->query($sql2);

			$total_growers=$result2->num_rows;



			$sql2 = "Select * from  lat_long join contracted_hectares on contracted_hectares.growerid=lat_long.growerid  where  lat_long.seasonid=$seasonid and lat_long.userid=$userid";

			$result2 = $conn->query($sql2);

			

			if ($result2->num_rows > 0) {
       // output data of each row
       while($row2 = $result2->fetch_assoc()) {

        $visited_ha+=$row2["hectares"];

       
       }
     }


     $sql2 = "Select * from  contracted_hectares  where  seasonid=$seasonid ";

			$result2 = $conn->query($sql2);

			

			if ($result2->num_rows > 0) {
       // output data of each row
       while($row2 = $result2->fetch_assoc()) {

        $contracted_ha+=$row2["hectares"];
        
       }
     }






					echo "<tr>";
            echo    "<td class='text-truncate'><i class='la la-dot-circle-o success font-medium-1 mr-1'></i> ".$name." ".$surname."</td>";
             echo   "<td class='text-truncate'><a href='#'>".$contracted_ha."</a></td>";
             echo "<td class='text-truncate p-1'>".$total_growers."</td>";
            echo    "<td class='text-truncate'>".$visited_ha."</td>";
             echo   "<td class='text-truncate'>".$grower_visits."</td>";
             echo  " <td class='text-truncate'>";
             echo       "<a href='#' class='mb-0 btn-sm btn btn-outline-danger round'>".$risk."%</a>";
              echo  "</td>";
             echo   "<td class='text-truncate'>";
              echo      "<a href='#' class='mb-0 btn-sm btn btn-outline-primary round'>".$percantage."%</a>";
             echo  " </td>";
             echo   "<td class='text-truncate'>";
                echo    "<a href='#' class='mb-0 btn-sm btn btn-outline-primary round'>Download</a>";
              echo  "</td>";
            echo "</tr>";

		
   }
 }

?>