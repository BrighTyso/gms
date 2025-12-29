
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$data1=array();


if (isset($data->userid) && isset($data->description)) {
 
$userid=$data->userid;
$description=$data->description;
$seasonid=$data->seasonid;
$created_at=$data->created_at;
$disbursement_truckid=$data->disbursement_truckid;

$loans_data=array();
$company_details_data=array();
$product_items_data=array();
$location_data=array();
$season_data=array();
$truck_grower_data=array();

$grower_hectares="";
$receipt_number="";
$field_officer_username="";
$truck_number="";
$username="";
$hectares="";



$sql = "Select * from users where id=$userid limit 1";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $username=$row['username'];
      
     }
   }


$sql = "Select * from truck_destination join total_disbursement on total_disbursement.disbursement_trucksid=truck_destination.id join products on products.id=total_disbursement.productid where truck_destination.id=$disbursement_truckid limit 1";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $truck_number=$row['trucknumber'];
      
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


if ($description=="") {



}else{

  $sql11 = "Select distinct growers.id,growers.name,growers.surname,growers.grower_num,growers.area,growers.id_num from  growers  where  (grower_num='$description' or province='$description' or  grower_num  like '%$description') order by grower_num limit 1";

  $result1 = $conn->query($sql11);
   
   if ($result1->num_rows > 0) {
     // output data of each row
  while($row1 = $result1->fetch_assoc()) {


  $growerid=$row1["id"];
  $grower_num=$row1["grower_num"];
  $name=$row1["name"];
  $surname=$row1["surname"];
  $id_num=$row1["id_num"];
  $area=$row1["area"];

  $grower_hectares="";

  $loans_data=array();
  $product_items_data=array();
  $location_data=array();
  $season_data=array();
  $truck_grower_data=array();


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



$receipt_number=0;

$sql2 = "Select distinct * from system_receipt_number where growerid=$growerid and seasonid=$seasonid";
    $result2 = $conn->query($sql2);
     
     if ($result2->num_rows > 0) {
      
       // output data of each row
       while($row2 = $result2->fetch_assoc()) {

        $receipt_number=$row2['receipt_number'];

       }
    }

  $grower_truck_found=0;
  $sql = "Select distinct * from truck_grower_qrcode_disbursed_mobile where growerid=$growerid and disbursement_trucksid=$disbursement_truckid and seasonid=$seasonid limit 1";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {

      $grower_truck_found=$row['id']; 
      
     }
   }



   $sql = "Select distinct scheme_hectares.quantity as hectares from scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id join scheme_hectares_growers on scheme_hectares_growers.scheme_hectaresid=scheme_hectares.id 
  join growers on growers.id=scheme_hectares_growers.growerid where scheme_hectares.seasonid=$seasonid and scheme_hectares_growers.growerid=$growerid order by scheme_hectares_growers.growerid desc";
  $result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $grower_hectares=$row["hectares"];

   }
 }




if ($grower_truck_found==0) {
  $grower_sql = "INSERT INTO truck_grower_qrcode_disbursed_mobile(userid,seasonid,disbursement_trucksid,growerid,created_at,receipt_num,hectares) VALUES ($userid,$seasonid,$disbursement_truckid,$growerid,'$created_at','$receipt_number','$grower_hectares')";
   //$sql = "select * from login";
 if ($conn->query($grower_sql)===TRUE) {

  $temp=array("response"=>"success");
  array_push($data1,$temp);

 }
}else{

  $temp=array("response"=>"already created");
  array_push($data1,$temp);

}



 
}


}

}



}


 echo json_encode($data1);


?>





