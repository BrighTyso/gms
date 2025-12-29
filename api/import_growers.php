<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";

$data = json_decode(file_get_contents("php://input"));


$data1=array();


$userid=$data->userid;
$name=$data->name;
$surname=$data->surname;
$grower_num=$data->grower_num;
$area=$data->area;
$province=$data->province;
$phone=$data->phone;
$id_num=$data->id_num;
$created_at=$data->created_at;
$hectares=$data->hectares;
$field_officer=$data->field_officer;
$field_officerid=0;
$seasonid=$data->seasonid;
$response=0;
$growerid=0;
$scheme_hectaresid=0;
$already_in=0;
$scheme_hectares_to_verify=0;
$found=0;
$grower_found=0;

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($userid) && isset($name)  && isset($surname)  && isset($grower_num)  && isset($area)  &&  isset($province)  && isset($phone)  && isset($id_num)   && isset($created_at)){



// checks if grower is already in database

$sql = "Select growers.id from growers  where  grower_num='$grower_num' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $response=$row["id"];
   $growerid=$row["id"];
   
    
   }

 }



if ($response==0) {


	$grower_sql = "INSERT INTO growers(userid,name,surname,grower_num,area,province,phone,id_num,seasonid,created_at) VALUES ($userid,'$name','$surname','$grower_num','$area','$province','$phone','$id_num',$seasonid,'$created_at')";
   //$sql = "select * from login";
   if ($conn->query($grower_sql)===TRUE) {
   
     $last_id = $conn->insert_id;
     
     $growerid= $conn->insert_id;

	   	$temp=array("response"=>"success");
      array_push($data1,$temp);


   }else{

    $temp=array("response"=>$conn->error);
      array_push($data1,$temp);

   }

 }else{


  $user_sql = "update growers set surname='$surname',name='$name',id_num='$id_num',phone='$phone',area='$area',province='$province'  where id = $growerid";
       //$sql = "select * from login";
       if ($conn->query($user_sql)===TRUE) {

      $temp=array("response"=>"grower updated");
      array_push($data1,$temp);

       }

 }

}else{

     $temp=array("response"=>"Field Empty");
      array_push($data1,$temp);
}







$sql = "Select * from scheme_hectares where  quantity='$hectares' and seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $scheme_hectaresid=$row["id"];
   
   }

 }




$sql = "Select scheme_hectares.id,scheme_hectares.quantity from scheme_hectares_growers  join scheme_hectares  on scheme_hectares.id=scheme_hectares_growers.scheme_hectaresid where scheme_hectares.seasonid=$seasonid and growerid=$growerid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // product id
   $already_in=$row["id"];
   $scheme_hectares_to_verify=$row["quantity"];
   
   }

 }

 


$sql = "Select * from scheme_hectares_growers where  scheme_hectaresid=$scheme_hectaresid and growerid=$growerid ";
$result = $conn->query($sql);
 $scheme_grower_quantity=$result->num_rows;
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $found=$row["id"];
   
   }

 }

//echo $scheme_grower_quantity;

if ($scheme_grower_quantity>1) {
  
  $select_limit=$scheme_grower_quantity-1;

  $sql = "Select * from scheme_hectares_growers where  scheme_hectaresid=$scheme_hectaresid and growerid=$growerid limit $select_limit";
  $result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
     $found_new=$row["id"];

     $user_sql111 = "DELETE FROM scheme_hectares_growers where id = $found_new and growerid=$growerid ";
     //$sql = "select * from login";
     if ($conn->query($user_sql111)===TRUE) {
//echo "delete";
     }
   
   }

 }

}





if ($found==0 && $growerid>0 && $already_in==0 && $scheme_hectaresid>0 ) {
  
$user_sql = "INSERT INTO scheme_hectares_growers(userid,scheme_hectaresid,growerid) VALUES ($userid,$scheme_hectaresid,$growerid)";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
  
   }else{

 
   }

}else{

  $user_sql1 = "update scheme_hectares_growers set scheme_hectaresid=$scheme_hectaresid where growerid=$growerid";
       //$sql = "select * from login";
       if ($conn->query($user_sql1)===TRUE) {
   

       }
}



$sql = "Select * from users where username='$field_officer' or name='$field_officer' or surname='$field_officer' limit 1";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $field_officerid=$row["id"];
      
     }

  }






$sql = "Select * from grower_field_officer where growerid=$growerid and seasonid=$seasonid";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $grower_found=$row["id"];
      
     }

  }

    $active_grower_found=0;

    $sql = "Select * from active_growers where growerid=$growerid and seasonid=$seasonid";
        $result = $conn->query($sql);
         
         if ($result->num_rows > 0) {
           // output data of each row
           while($row = $result->fetch_assoc()) {
           
           $active_grower_found=$row["id"];
          
            
           }

         }


         if ($active_grower_found==0) {
          $user_sql = "INSERT INTO active_growers(userid,growerid,seasonid) VALUES ($userid,$growerid,$seasonid)";
           //$sql = "select * from login";
               if ($conn->query($user_sql)===TRUE) {


               }
            }



   if ($grower_found==0 && $growerid>0) {

     $user_sql = "INSERT INTO grower_field_officer(userid,seasonid,growerid,field_officerid,created_at) VALUES ($userid,$seasonid,$growerid,$field_officerid,'$created_at')";
                   //$sql = "select * from login";
       if ($conn->query($user_sql)===TRUE) {


       }else{
          
       }
       
   }else{

    $user_sql = "update grower_field_officer set field_officerid=$field_officerid  where growerid = $growerid and seasonid=$seasonid ";
       //$sql = "select * from login";
       if ($conn->query($user_sql)===TRUE) {


       }

   }









echo json_encode($data1);



?>


