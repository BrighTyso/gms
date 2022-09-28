<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$productid=0;
$quantity=0;
$growerid=0;
$userid=0;
$created_at="";
$seasonid=0;
$last_id=0;
$description="";


$reQuantity=0;
$reProductid=0;
$reGrowerid=0;

$verified=0;

$disbursement_trucksid=0;
$disbursementid=0;

$loanid=0;
$dbseasonid=0;

$verifyHectares=0;


$response=array();

if (isset($data->productid) && isset($data->quantity)  && isset($data->growerid) && isset($data->userid) && isset($data->seasonid) && isset($data->created_at)){

$productid=$data->productid;
$quantity=$data->quantity;
$growerid=$data->growerid;
$userid=$data->userid;
$seasonid=$data->seasonid;
$created_at=$data->created_at;
$hectares=$data->hectares;
$trucknumber=$data->trucknumber;
$description=$data->description;



$sql = "Select * from truck_destination where truck_destination.trucknumber='$trucknumber' and close_open=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //$found=$row["id"];
   
    $disbursement_trucksid=$row["id"];
   

    
   }
 }





 $sql = "Select * from disbursement where disbursement_trucksid=$disbursement_trucksid and productid=$productid and quantity>=$quantity and  quantity>0 ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //$found=$row["id"];
  
    $disbursementid=$row["id"];
    
   }
 }




$sql = "Select * from growers where grower_num='$description' or phone='$description'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) { 


   
   $growerid=$row["id"];
   
      
   }

 }



   $sql1 = "Select * from contracted_hectares where contracted_hectares.seasonid=$seasonid and growerid=$growerid";
$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

   $verifyHectares=1;
    
   }
 }




$sql = "SELECT loans.id,loans.seasonid,loans.verified,loans.quantity,productid,growerid FROM loans where loans.productid=$productid and loans.quantity=$quantity and loans.seasonid=$seasonid and loans.growerid=$growerid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
   
    $loanid=$row['id'];
    $reQuantity=$row['quantity'];
    $reProductid=$row['productid'];
    $reGrowerid=$row['growerid'];

  

  
   
    if ($row['verified']==0) {
      $verified=0;
    }else{
      $verified=1;
    }
     
   }

 }



   if ($verified==0) {

    if ($reProductid==$productid && $growerid==$reGrowerid && $reQuantity==$quantity){

      

      if ($disbursementid>0 && $disbursement_trucksid>0 ) {

      

            $sql = "UPDATE loans SET verified = 1 , verified_by=$userid , verified_at='$created_at' WHERE id = $loanid";
                           //$sql = "select * from login";
             if ($conn->query($sql)===TRUE) {

                  if ($verifyHectares==0) {


                         $insert_sql = "INSERT INTO contracted_hectares(userid,growerid,seasonid,hectares,created) VALUES ($userid,$growerid,$seasonid,'$hectares','$created_at')";
                       //$gr = "select * from login";
                       if ($conn->query($insert_sql)===TRUE) {
                       
                        // $last_id = $conn->insert_id;

                        $user_sql1 = "update disbursement set quantity=quantity-$quantity  where disbursement_trucksid=$disbursement_trucksid and productid=$productid";
                       //$sql = "select * from login";
                             if ($conn->query($user_sql1)===TRUE) {
                             
                                 $insert_sql = "INSERT INTO truck_to_grower(userid,growerid,disbursement_trucksid,quantity,productid,created_at) VALUES ($userid,$growerid,$disbursement_trucksid,$quantity,$productid,'$created_at')";
                                 //$sql = "select * from login";
                                 if ($conn->query($insert_sql)===TRUE) {
                                 
                                   $last_id = $conn->insert_id;
                                   $temp=array("response"=>"success");
                                   array_push($response,$temp);

                                 }else{
                                  

                                  //$last_id = $conn->insert_id;
                                   $temp=array("response"=>"Truck To Grower Failed");
                                    array_push($response,$temp);

                                 }

                       }else{
                        

                        //$last_id = $conn->insert_id;
                         $temp=array("response"=>"Failed To Update");
                          array_push($response,$temp);

                       }



                  }else{


                   

                      $temp=array("response"=>"failed");
                     array_push($response,$temp);
                 

                 }


        }else{

                $user_sql1 = "update disbursement set quantity=quantity-$quantity  where disbursement_trucksid=$disbursement_trucksid and productid=$productid";
             //$sql = "select * from login";
            if ($conn->query($user_sql1)===TRUE) {
             
                             $insert_sql = "INSERT INTO truck_to_grower(userid,growerid,disbursement_trucksid,quantity,productid,created_at) VALUES ($userid,$growerid,$disbursement_trucksid,$quantity,$productid,'$created_at')";
                           //$sql = "select * from login";
                           if ($conn->query($insert_sql)===TRUE) {
                           
                               $last_id = $conn->insert_id;
                               $temp=array("response"=>"success");
                               array_push($response,$temp);

                             }else{
                             

                              //$last_id = $conn->insert_id;
                               $temp=array("response"=>"Truck To Grower Failed");
                                array_push($response,$temp);

                             }

                         }else{
                          
                          //$last_id = $conn->insert_id;
                           $temp=array("response"=>"Failed To Update");
                            array_push($response,$temp);

                         }



                    }

                  }else{
                             $temp=array("response"=>"Failed To Verify");
                              array_push($response,$temp);

                  }

            }else{

              $temp=array("response"=>"Truck Failed");
                          array_push($response,$temp);
            }



         }else{


          if ($reQuantity!=$quantity) {

         $temp=array("response"=>"Quantity Not Matching");
         array_push($response,$temp);


          }

        

         }
         


       }else{

        $temp=array("response"=>"Already Verified");
         array_push($response,$temp);

       }


    



}else{

  $temp=array("response"=>"empty");
  array_push($response,$temp);

	
 }

    


echo json_encode($response);

?>





