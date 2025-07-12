<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=$data->seasonid;
$rollover_seasonid=$data->rollover_seasonid;
$grower_num=$data->grower_num;
$growerid=0;

$data1=array();
$response=array();
$company_details_data=array();

$name="";
$surname="";
$id_num="";
$area="";

// get grower locations



$sql13 = "Select * from company_details_and_contact limit 1";

    $result3 = $conn->query($sql13);
     
     if ($result3->num_rows > 0) {
       // output data of each row
       while($row3 = $result3->fetch_assoc()) {

        $loans=array("name"=>$row3["company_name"],"address"=>$row3["address"],"phone_1"=>$row3["phone_1"],"phone_2"=>$row3["phone_2"],"phone_3"=>$row3["phone_3"],"email"=>$row3["email"]);

          array_push($company_details_data,$loans);
       
       }
     }



if ($grower_num!="") {

  
 $sql11 = "Select distinct * from  growers where  (grower_num='$grower_num')";
  $result1 = $conn->query($sql11);
 
 if ($result1->num_rows > 0) {
   // output data of each row
   while($row1 = $result1->fetch_assoc()) {

      $growerid=$row1["id"];
      $name=$row1["name"];
      $surname=$row1["surname"];
      $id_num=$row1["id_num"];
      $area=$row1["area"];

   }
 }


$sql1 = "Select distinct * from balanced_finances where growerid=$growerid  order by year desc limit 1";
  $result1 = $conn->query($sql1);
   
   if ($result1->num_rows > 0) {
     // output data of each row
     while($row = $result1->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);

   $temp=array("stop_order"=>$row["stop_order"],"balance_b_f"=>$row["balance_b_f"],"payments"=>$row["payments"],"outstanding"=>$row["outstanding"],"interest"=>$row["interest"],"balance"=>$row["balance"],"year"=>$row["year"]);
    array_push($data1,$temp);

   
   }
 }



$temp=array("data"=>$data1,"grower_num"=>$grower_num,"name"=>$name,"surname"=>$surname,"id_num"=>$id_num,"area"=>$area,"company_data"=>$company_details_data);
array_push($response,$temp);

}else{



$sql1133 = "Select distinct * from  growers join active_growers on active_growers.growerid=growers.id where active_growers.seasonid=$rollover_seasonid";
  $result133 = $conn->query($sql1133);
 
 if ($result133->num_rows > 0) {
   // output data of each row
   while($row133 = $result133->fetch_assoc()) {

      $growerid=$row133["id"];
      $name=$row133["name"];
      $surname=$row133["surname"];
      $id_num=$row133["id_num"];
      $area=$row133["area"];

  

$sql11 = "Select distinct growers.id,growers.name,growers.surname,growers.grower_num,area,id_num from growers join active_balanced_finances on active_balanced_finances.growerid=growers.id limit 1";

$result1 = $conn->query($sql11);
 
 if ($result1->num_rows > 0) {
   // output data of each row
   while($row1 = $result1->fetch_assoc()) {
      $data1=array();

      $growerid=$row1["id"];
      $grower_num=$row1["grower_num"];
       $name=$row1["name"];
      $surname=$row1["surname"];
      $id_num=$row1["id_num"];
      $area=$row1["area"];


        $sql13 = "Select distinct * from balanced_finances where growerid=$growerid order by year desc limit 1";
      $result13 = $conn->query($sql13);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row = $result13->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

        //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
        // array_push($data1,$temp);

       $temp=array("stop_order"=>$row["stop_order"],"balance_b_f"=>$row["balance_b_f"],"payments"=>$row["payments"],"outstanding"=>$row["outstanding"],"interest"=>$row["interest"],"balance"=>$row["balance"],"year"=>$row["year"]);
        array_push($data1,$temp);

           
           }
         }



        $temp=array("data"=>$data1,"rollover_seasonid"=>$rollover_seasonid,"grower_num"=>$grower_num,"name"=>$name,"surname"=>$surname,"id_num"=>$id_num,"area"=>$area,"company_data"=>$company_details_data);
        array_push($response,$temp);



   }
 }

 }
 }
}

 echo json_encode($response);


?>


