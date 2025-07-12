<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=0;
$description=$data->description;


$growerid=0;
$input_total=0;
$working_capital=0;
$roll_over=0;
$total_loan_amount=0;


$data1=array();


$loans_data=array();
$company_details_data=array();
$product_items_data=array();

$seasonData=array();
$rollOverData=array();

// get grower locations

if ($userid!="") {


if ($description!="") {
  
$sql112 = "Select * from seasons order by id";

$result133 = $conn->query($sql112);
 
 if ($result133->num_rows > 0) {
   // output data of each row
   while($row133 = $result133->fetch_assoc()) {

    $seasonid=$row133["id"];
    $season_name=$row133["name"];


    $growerid=0;
    $input_total=0;
    $working_capital=0;
    $roll_over=0;
    $total_loan_amount=0;


   $sql11 = "Select distinct growers.id,growers.name,growers.surname,growers.grower_num,growers.area,growers.id_num from  growers join active_growers on active_growers.growerid=growers.id where active_growers.seasonid=$seasonid and (name ='$description' or grower_num='$description' or province='$description' or area='$description')";

  $result1 = $conn->query($sql11);
 
 if ($result1->num_rows > 0) {
   // output data of each row
   while($row1 = $result1->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $growerid=$row1["id"];

    $grower_name=$row1["name"];
    $grower_surname=$row1["surname"];
    $grower_num=$row1["grower_num"];
    $grower_area=$row1["area"];
    $grower_id_num=$row1["id_num"];




    $min_code_found=0;
    $min_code="";

    $field_officer_username="";


    $sql2 = "Select * from grower_field_officer join users on users.id=grower_field_officer.field_officerid where growerid=$growerid and seasonid=$seasonid limit 1";
  $result2 = $conn->query($sql2);
   
   if ($result2->num_rows > 0) {
     // output data of each row
     while($row2 = $result2->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $field_officer_username=$row2["username"];
      
     }

   }


  $sql12 = "Select  * from grower_to_ministry_of_agricalture_numbers join ministry_of_agricalture_numbers on ministry_of_agricalture_numbers.id=grower_to_ministry_of_agricalture_numbers.ministry_of_agricalture_numbersid  where seasonid=$seasonid and growerid=$growerid and used=1 limit 1";

  $result2 = $conn->query($sql12);
   
   if ($result2->num_rows > 0) {
     // output data of each row
     while($row2 = $result2->fetch_assoc()) {

        $min_code=$row2["description"];

     }
   }



   if ($min_code=="") {
     
       $sql12 = "Select  * from  ministry_of_agricalture_numbers  where seasonid=$seasonid and used=0 limit 1";
        $result2 = $conn->query($sql12);
         
         if ($result2->num_rows > 0) {
           // output data of each row
           while($row2 = $result2->fetch_assoc()) {

          $minid=$row2['id'];
          $user_sql = "INSERT INTO grower_to_ministry_of_agricalture_numbers(userid,growerid,ministry_of_agricalture_numbersid) VALUES ($userid,$growerid,$minid)";
             //$sql = "select * from login";
             if ($conn->query($user_sql)===TRUE) {
             
             $user_sql131 = "update ministry_of_agricalture_numbers set used=1 where id=$minid";
               //$sql = "select * from login";
               if ($conn->query($user_sql131)===TRUE) {

                  $min_code=$row2["description"];

                 
                }
               
             }

           }

         }
   }




    $sql = "Select distinct products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,quantity,units,package_units,loans.created_at,verified, users.username,amount,receipt_number,product_amount,product_total_cost  from loans join growers on growers.id=loans.growerid join products on loans.productid=products.id join users on users.id=loans.userid join prices on prices.productid=loans.productid where loans.seasonid=$seasonid and prices.seasonid=$seasonid and processed=1 and loans.growerid=$growerid order by product_amount ";
    $result = $conn->query($sql);

 
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";



        $loans=array("id"=>$row["id"],"product_name"=>$row["product_name"],"quantity"=>$row["quantity"],"units"=>$row["units"],"package_units"=>$row["package_units"],"created_at"=>$row["created_at"],"amount"=>$row["amount"],"receipt_number"=>$row["receipt_number"],"product_amount"=>$row["product_amount"],"product_total_cost"=>$row["product_total_cost"]);

        array_push($loans_data,$loans);
        
       }
     }



    $sql12 = "Select * from inputs_total join growers on growers.id=inputs_total.growerid where inputs_total.seasonid=$seasonid and inputs_total.growerid=$growerid";

    $result2 = $conn->query($sql12);
     
     if ($result2->num_rows > 0) {
       // output data of each row
       while($row2 = $result2->fetch_assoc()) {

        $input_total+=$row2["amount"];
       
       }
     }





     $sql14 = "Select * from rollover_total join growers on growers.id=rollover_total.growerid where rollover_total.seasonid=$seasonid and rollover_total.growerid=$growerid";

    $result4 = $conn->query($sql14);
     
     if ($result4->num_rows > 0) {
       // output data of each row
       while($row4 = $result4->fetch_assoc()) {

        $roll_over=$row4["amount"];
       
       }
     }



    $sql13 = "Select * from working_capital_total join growers on growers.id=working_capital_total.growerid where working_capital_total.seasonid=$seasonid and working_capital_total.growerid=$growerid";

    $result3 = $conn->query($sql13);
     
     if ($result3->num_rows > 0) {
       // output data of each row
       while($row3 = $result3->fetch_assoc()) {

        $working_capital+=$row3["amount"];
       
       }
     }




  $sql14 = "Select * from roll_over_interest  where roll_over_interest.seasonid=$seasonid limit 1";

  $result4 = $conn->query($sql14);
   
   if ($result4->num_rows > 0) {
     // output data of each row
     while($row4 = $result4->fetch_assoc()) {

      $roll_over_percentage=$row4["value"];
     
     }
   }


    $sql14 = "Select * from rollover_total join growers on growers.id=rollover_total.growerid where rollover_total.seasonid=$seasonid and rollover_total.growerid=$growerid";

    $result4 = $conn->query($sql14);
     
     if ($result4->num_rows > 0) {
       // output data of each row
       while($row4 = $result4->fetch_assoc()) {

       
       
       }
     }



   $total_loan_amount=$input_total + $working_capital + $roll_over;

   $loan_interest=0;

   $loan_balance=0;

   $interest_amount=0;




   $sql15 = "Select parameters.name,value from charges_amount join parameters on parameters.id=charges_amount.parameterid where charges_amount.seasonid=$seasonid and chargeid=1 limit 1 ";

    $result5 = $conn->query($sql15);
     
     if ($result5->num_rows > 0) {
       // output data of each row
       while($row5 = $result5->fetch_assoc()) {

        $interest_amount=$row5["value"];
       
       }
     }


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



     $loan_balance=$total_loan_amount+$loan_interest;


    $temp=array("grower_area"=>$grower_area,"grower_id_num"=>$grower_id_num,"grower_name"=>$grower_name,"grower_surname"=>$grower_surname,"grower_num"=>$grower_num,"loan_total_amount"=>$loan_balance,"working_capital"=>$working_capital,"roll_over"=>$roll_over,"input_total"=>$input_total,"interest"=>$loan_interest,"inputs"=>$loans_data,"company_data"=>$company_details_data,"min_code"=>$min_code,"interest_value"=>$interest_amount,"field_officer"=>$field_officer_username);
    array_push($data1,$temp);

   
   }
 }


    $temp=array("season_loan_data"=>$data1,"season_name"=>$season_name,"roll_over_interest"=>$roll_over_percentage);
    array_push($seasonData,$temp);

  
  }
 }


}

}

 echo json_encode($seasonData);


?>


