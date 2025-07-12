<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));

$userid=$data->userid;
$seasonid=$data->seasonid;
$description=$data->description;
$created_at=$data->created_at;

$data1=array();
// get grower locations

if ($userid!="") {




  $growerid=0;
$input_total=0;
$working_capital=0;
$roll_over=0;
$total_loan_amount=0;


  
$loans_data=array();
$company_details_data=array();
$product_items_data=array();
$season_data=array();

//$sql11 = "Select growers.id from  growers join active_growers on growers.id=active_growers.growerid where active_growers.seasonid=$seasonid";
$sql13 = "Select * from seasons where id=$seasonid limit 1";

    $result3 = $conn->query($sql13);
     
     if ($result3->num_rows > 0) {
       // output data of each row
       while($row3 = $result3->fetch_assoc()) {

        $seasond=array("name"=>$row3["name"]);
        array_push($season_data,$seasond);
       
   }
 }




 $sql13 = "Select * from company_details_and_contact limit 1";

    $result3 = $conn->query($sql13);
     
     if ($result3->num_rows > 0) {
       // output data of each row
       while($row3 = $result3->fetch_assoc()) {

        $loans=array("name"=>$row3["company_name"],"address"=>$row3["address"],"phone_1"=>$row3["phone_1"],"phone_2"=>$row3["phone_2"],"phone_3"=>$row3["phone_3"],"email"=>$row3["email"]);

          array_push($company_details_data,$loans);
       
   }
 }


if ($description==""){


  $sql11 = "Select distinct growers.id,growers.name,growers.surname,growers.grower_num,growers.area,growers.id_num from  growers join active_growers on active_growers.growerid=growers.id  where active_growers.seasonid=$seasonid   order by active_growers.id";

  $result1 = $conn->query($sql11);
   
   if ($result1->num_rows > 0) {
     // output data of each row
     while($row1 = $result1->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $loans_data=array();
      $growerid=0;




$sql = "Select distinct products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,quantity,units,package_units,grower_field_loans.created_at, users.username from grower_field_loans join growers on growers.id=grower_field_loans.growerid join products on grower_field_loans.productid=products.id join users on users.id=grower_field_loans.userid  where grower_field_loans.seasonid=$seasonid  and grower_field_loans.growerid=$growerid  order by product_amount ";
    $result = $conn->query($sql);

 
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

        $product_id=$row["productid"];
        $product_items_data=array();


          $to_be_disbursed_quantity=$product_scheme_quantity-$disbursed_quantity;

           if ($product_scheme_quantity>$disbursed_quantity && $to_be_disbursed_quantity>0) {

            $insert_sql = "INSERT INTO grower_disbursed_products(userid,seasonid,growerid,productid,quantity,created_at) VALUES ($userid,$seasonid,$growerid,$product_id,$to_be_disbursed_quantity,'$created_at')";
             //$gr = "select * from login";
             if ($conn->query($insert_sql)===TRUE) {

                $loans=array("id"=>$row["id"],"product_name"=>$row["product_name"],"quantity"=>$to_be_disbursed_quantity,"units"=>$row["units"],"package_units"=>$row["package_units"],"created_at"=>$row["created_at"]);

               array_push($loans_data,$loans);
             
              }

           }
        
       }
     }

 }
}
}else{


  $sql11 = "Select distinct growers.id,growers.name,growers.surname,growers.grower_num,growers.area,growers.id_num from  growers join active_growers on active_growers.growerid=growers.id  where active_growers.seasonid=$seasonid   order by active_growers.id";

  $result1 = $conn->query($sql11);
   
   if ($result1->num_rows > 0) {
     // output data of each row
     while($row1 = $result1->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $loans_data=array();
      $growerid=0;




$sql = "Select distinct products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,quantity,units,package_units,grower_field_loans.created_at, users.username from grower_field_loans join growers on growers.id=grower_field_loans.growerid join products on grower_field_loans.productid=products.id join users on users.id=grower_field_loans.userid  where grower_field_loans.seasonid=$seasonid  and grower_field_loans.growerid=$growerid  order by product_amount ";
    $result = $conn->query($sql);

 
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

        $product_id=$row["productid"];
        $product_items_data=array();


          $to_be_disbursed_quantity=$product_scheme_quantity-$disbursed_quantity;

           if ($product_scheme_quantity>$disbursed_quantity && $to_be_disbursed_quantity>0) {

            $insert_sql = "INSERT INTO grower_disbursed_products(userid,seasonid,growerid,productid,quantity,created_at) VALUES ($userid,$seasonid,$growerid,$product_id,$to_be_disbursed_quantity,'$created_at')";
             //$gr = "select * from login";
             if ($conn->query($insert_sql)===TRUE) {

                $loans=array("id"=>$row["id"],"product_name"=>$row["product_name"],"quantity"=>$to_be_disbursed_quantity,"units"=>$row["units"],"package_units"=>$row["package_units"],"created_at"=>$row["created_at"]);

               array_push($loans_data,$loans);
             
              }

           }
        
       }
     }

 }
}


}



    
  


}

 echo json_encode($data1);


?>


