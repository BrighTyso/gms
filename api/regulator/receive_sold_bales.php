<?php
require_once("conn.php");
require "validate.php";
require "dataSource.php";

$barcode=validate($_GET['barcode']);
$mass=$_GET['mass'];
$price=$_GET['price'];
$userid=$_GET['userid'];
$grower_num=$_GET['grower_num'];
$created_at=$_GET['created_at'];
$latitude=$_GET['latitude'];
$longitude=$_GET['longitude'];
$temp_barcode=validate($_GET['temp_barcode']);


$barcode_found=0;
$total_found=0;
$grower_found=0;
$seasonid=0;
$bale_tagid=0;
$bale_tag_booked=0;
$auction_rights=0;
$mapped=0;

$data1=array();
// get grower locations

if ($barcode!="" && $mass!="" && $price!=""  && $grower_num!="" && $created_at!="") {
  

$response=0;
$farm_response=0;


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







   $sql1 = "Select companyid,id from auction_rights  where  companyid=$userid and seasonid=$seasonid";
  $result = $conn->query($sql1);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // product id
      $auction_rights=$row["companyid"];

     }

   }


 
     



$sql11 = "Select * from growers where grower_num='$grower_num'";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);

    $grower_found=$row["id"];

 
    
   }
 }






$sql = "Select * from sold_bales  where (barcode='$barcode') and  userid=$userid and  seasonid=$seasonid";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);

    $barcode_found=$row['id'];
   
    
   }
 }



     if ($auction_rights==0) {

     $sql = "Select bale_tags.id from bale_tags join grower_number_of_bales on bale_tags.grower_number_of_balesid=grower_number_of_bales.id join contracted_hectares on contracted_hectares.growerid=grower_number_of_bales.growerid where (code='$temp_barcode') and  grower_number_of_bales.seasonid=$seasonid and grower_number_of_bales.userid=$userid and contracted_hectares.growerid=$grower_found and bale_tags.seasonid=$seasonid";

    $result = $conn->query($sql);
     
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

        //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
        // array_push($data1,$temp);

        $bale_tagid=$row["id"];
       
        
       }
     }

    }else{

          // $sql11 = "Select distinct bale_tags.id,bale_tags.code,bale_tags.created_at,grower_number_of_bales.userid,used,grower_number_of_balesid from bale_tags join grower_number_of_bales on  bale_tags.grower_number_of_balesid=grower_number_of_bales.id join seasons on seasons.id=grower_number_of_bales.seasonid join auction_growers on auction_growers.userid=grower_number_of_bales.userid  where code='$barcode' and active=1";

           $sql = "Select bale_tags.id from bale_tags join grower_number_of_bales on bale_tags.grower_number_of_balesid=grower_number_of_bales.id join auction_growers on auction_growers.growerid=grower_number_of_bales.growerid  where (code='$temp_barcode') and  grower_number_of_bales.seasonid=$seasonid and auction_growers.growerid=$grower_found and bale_tags.seasonid=$seasonid";

          $result = $conn->query($sql);
           
           if ($result->num_rows > 0) {
             // output data of each row
             while($row = $result->fetch_assoc()) {
              // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

              //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
              // array_push($data1,$temp);

              $bale_tagid=$row["id"];
             
              
             }
           }

    }






$sql = "Select * from bale_booked where (bale_tagid=$bale_tagid)";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);
    $bale_tag_booked=$row["id"];
   
    
   }
 }





$sql = "Select * from bale_tag_to_sold_bale where  bale_tagid=$bale_tagid and sold_balesid=$barcode_found";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);
    $mapped=$row["id"];
   
    
   }
 }





$sql1 = "Select * from total_sold_bales where userid=$userid and  seasonid=$seasonid";

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




 if ($barcode_found==0 && $grower_found>0 && $seasonid>0 && $bale_tag_booked>0 && $bale_tagid>0 && $mapped==0) {


   $insert_sql = "INSERT INTO sold_bales(userid,seasonid,growerid,barcode,mass,price,latitude,longitude,created_at,temp_barcode) VALUES ($userid,$seasonid,$grower_found,'$barcode',$mass,$price,'$latitude','$longitude','$created_at','$temp_barcode')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
     $last_id = $conn->insert_id;

     if($total_found==0){



     $insert_sql = "INSERT INTO bale_tag_to_sold_bale(userid,bale_tagid,sold_balesid,created_at) VALUES ($userid,$bale_tagid,$last_id,'$created_at')";
       //$gr = "select * from login";
       if ($conn->query($insert_sql)===TRUE) {
       
        // $last_id = $conn->insert_id;

               $insert_sql = "INSERT INTO total_sold_bales(userid,seasonid,quantity,created_at) VALUES ($userid,$seasonid,1,'$created_at')";
             //$gr = "select * from login";
             if ($conn->query($insert_sql)===TRUE) {
             
              // $last_id = $conn->insert_id;

               $temp=array("response"=>"success","grower_num"=>$grower_num,"barcode"=>$barcode,"temp_barcode"=>$temp_barcode,"created_at"=>$created_at);
                array_push($data1,$temp);

                }

          }


      }else{


      $insert_sql = "INSERT INTO bale_tag_to_sold_bale(userid,bale_tagid,sold_balesid,created_at) VALUES ($userid,$bale_tagid,$last_id,'$created_at')";
       //$gr = "select * from login";
       if ($conn->query($insert_sql)===TRUE) {  

             $user_sql1 = "update total_sold_bales set quantity=quantity+1 where id=$total_found";
               //$sql = "select * from login";
               if ($conn->query($user_sql1)===TRUE) {

                $temp=array("response"=>"success","grower_num"=>$grower_num,"barcode"=>$barcode,"temp_barcode"=>$temp_barcode,"created_at"=>$created_at);
                array_push($data1,$temp);

           
                }

          }


      }


   }


}else{



if ($barcode_found>0) {
 
 $temp=array("response"=>"barcode already used","grower_num"=>$grower_num,"barcode"=>$barcode,"temp_barcode"=>$temp_barcode,"created_at"=>$created_at);
 array_push($data1,$temp);

}else if ($grower_found==0) {
  
  $temp=array("response"=>"Grower Not Found","grower_num"=>$grower_num,"barcode"=>$barcode,"temp_barcode"=>$temp_barcode,"created_at"=>$created_at);
   array_push($data1,$temp);

}else if ($bale_tag_booked==0) {

$temp=array("response"=>"bale tag not booked","grower_num"=>$grower_num,"barcode"=>$barcode,"temp_barcode"=>$temp_barcode,"created_at"=>$created_at);
   array_push($data1,$temp);


}else if ($bale_tagid==0) {

$temp=array("response"=>"bale tag not found","grower_num"=>$grower_num,"barcode"=>$barcode,"temp_barcode"=>$temp_barcode,"created_at"=>$created_at);
   array_push($data1,$temp);


}else if ($mapped>0) {

$temp=array("response"=>"Already sold or mapped ","grower_num"=>$grower_num,"barcode"=>$barcode,"temp_barcode"=>$temp_barcode,"created_at"=>$created_at);
   array_push($data1,$temp);


}


}

}//else{

// Auction here






//}





echo json_encode($data1);

?>


