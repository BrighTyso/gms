<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");
//require "validate.php";

require "../datasource.php";


$datasource=new CompanyCode();


$dispatchNote=new DispatchNote();


$data = json_decode(file_get_contents("php://input"));

$barcode=validate($data->barcode);
$userid=$datasource->encryptor("decrypt",$data->userid);
$created_at=$data->created_at;
$latitude=$data->latitude;
$longitude=$data->longitude;
$dispatch_noteid=$data->dispatch_noteid;


$barcode_found=0;
$total_found=0;
$seasonid=0;
$sold_baleid=0;
$dispatch_note_open=0;
$mass=0;
$auction_rights=0;

$buyer="";
$buyer_company_id=0;

$data1=array();
// get grower locations




if ($barcode!="") {


$sql11 = "Select * from seasons where active=1 limit 1";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);

    $seasonid=$row["id"];

    
   }
 }
  





$sql111 = "Select * from dispatch_note where id=$dispatch_noteid and open_close=0";

$result = $conn->query($sql111);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);

    $dispatch_note_open=$row["id"];
    $receiverid=$row["receiverid"];
    
   }

 }




if ($dispatch_note_open>0) {




 $sql1 = "Select companyid,id from auction_rights  where  companyid=$userid and seasonid=$seasonid";
  $result = $conn->query($sql1);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // product id
      $auction_rights=$row["companyid"];

     }

   }




if ($auction_rights==0) {






$sql = "Select sold_bales.id,mass,buyer from sold_bales join bale_tag_to_sold_bale on bale_tag_to_sold_bale.sold_balesid=sold_bales.id join bale_tags on bale_tags.id=bale_tag_to_sold_bale.bale_tagid where (barcode='$barcode' or temp_barcode='$barcode') and  bale_tag_to_sold_bale.userid=$userid and  sold_bales.seasonid=$seasonid";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);

    $sold_baleid=$row["id"];
    $mass=$row["mass"];
    $buyer=$row["buyer"];
    
   }
 }







}else{


$sql = "Select sold_bales.id,mass,buyer from sold_bales join bale_tag_to_sold_bale on bale_tag_to_sold_bale.sold_balesid=sold_bales.id join bale_tags on bale_tags.id=bale_tag_to_sold_bale.bale_tagid join grower_number_of_bales on bale_tags.grower_number_of_balesid=grower_number_of_bales.id join auction_growers on auction_growers.growerid=grower_number_of_bales.growerid where (barcode='$barcode' or temp_barcode='$barcode' or code='$barcode') and  sold_bales.seasonid=$seasonid and bale_tag_to_sold_bale.userid=$userid";


$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);


    $sold_baleid=$row["id"];
    $mass=$row["mass"];
    $buyer=$row["buyer"];


    
   }
 }

}

// check auction buyer code

$sql123 = "Select * from buyer where description='$buyer'";

$result = $conn->query($sql123);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);

    $buyer_company_id=$row["companyid"];

    
   }
 }





$sql = "Select * from dispatch  where sold_balesid=$sold_baleid and userid=$userid";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);
    $barcode_found=$row["id"];
    
   }
 }



$sql1 = "Select * from dispatch_note_total_dispatched where dispatch_noteid=$dispatch_noteid ";

$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);

    $total_found=$row["id"];
    
   }
 }




 if ($barcode_found==0 && $sold_baleid>0) {


  if ($auction_rights>0) {
    // auction 
    if ($buyer_company_id==$receiverid) {
      // auction past here

      
//=========================================================cut here =========================================

   $insert_sql = "INSERT INTO dispatch(userid,sold_balesid,dispatch_noteid,latitude,longitude,created_at) VALUES ($userid,$sold_baleid,$dispatch_noteid,'$latitude','$longitude','$created_at')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
    // $last_id = $conn->insert_id;

     if($total_found==0){

     $insert_sql = "INSERT INTO dispatch_note_total_dispatched(dispatch_noteid,quantity,mass,created_at) VALUES ($dispatch_noteid,1,$mass,'$created_at')";
       //$gr = "select * from login";
       if ($conn->query($insert_sql)===TRUE) {
       
        // $last_id = $conn->insert_id;

          $temp=array("response"=>"success");
          array_push($data1,$temp);

          }


      }else{

       $user_sql1 = "update dispatch_note_total_dispatched set quantity=quantity+1,mass=mass+$mass where dispatch_noteid=$total_found";
         //$sql = "select * from login";
         if ($conn->query($user_sql1)===TRUE) {

          $temp=array("response"=>"success");
          array_push($data1,$temp);

     
          }

      }


   }

// =======================================cut here =============================================



    }else{

       $temp=array("response"=>"Wrong Dispatch Destination");
       array_push($data1,$temp);

    }



  }else{
// contract paste here


//=========================================================cut here =========================================

   $insert_sql = "INSERT INTO dispatch(userid,sold_balesid,dispatch_noteid,latitude,longitude,created_at) VALUES ($userid,$sold_baleid,$dispatch_noteid,'$latitude','$longitude','$created_at')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
    // $last_id = $conn->insert_id;

     if($total_found==0){

     $insert_sql = "INSERT INTO dispatch_note_total_dispatched(dispatch_noteid,quantity,mass,created_at) VALUES ($dispatch_noteid,1,$mass,'$created_at')";
       //$gr = "select * from login";
       if ($conn->query($insert_sql)===TRUE) {
       
        // $last_id = $conn->insert_id;

          $temp=array("response"=>"success");
          array_push($data1,$temp);

          }


      }else{

       $user_sql1 = "update dispatch_note_total_dispatched set quantity=quantity+1,mass=mass+$mass where dispatch_noteid=$total_found";
         //$sql = "select * from login";
         if ($conn->query($user_sql1)===TRUE) {

          $temp=array("response"=>"success");
          array_push($data1,$temp);

     
          }

      }


   }

// =======================================cut here =============================================


  }


  }else{

      if ($sold_baleid==0) {
            $temp=array("response"=>"Bale Not Sold");
            array_push($data1,$temp);
        }else if ($barcode_found>0) {
           $temp=array("response"=>"Bale Already Dispatched");
            array_push($data1,$temp);
        }

  }
  
}else{

$temp=array("response"=>"Sorry You Can Not dispatch bales on a closed Dispatch Note .");
array_push($data1,$temp);

}

}else{

$temp=array("response"=>"Barcode Empty");
array_push($data1,$temp);

}

 echo json_encode($data1);


?>



