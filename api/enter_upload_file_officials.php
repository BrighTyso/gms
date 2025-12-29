<?php 

require_once("conn.php");
$data=array();
if (!empty($_POST['image'])  && isset($_POST['userid']) && isset($_POST['seasonid'])  && isset($_POST['username']) && isset($_POST['id'])){


    $growerid=0;
    $image_found=0;
    $seasonid=$_POST['seasonid'];
    $userid=$_POST['userid'];
    $username=$_POST['username'];
    $sqliteid=$_POST['id'];
    $created_at=$_POST['created_at'];
    $file_type="";
    $location_url="";
    $datetimes="";
    $userid_1=0;
  

     $sql = "Select id from users  where  username='$username' limit 1";
    $result = $conn->query($sql);
        
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        // product id
        $userid_1=$row["id"];
       
       }

     }



     if ($userid_1>0) {


      $file_name=$_POST['username'] ."-".time().".jpg";
      $path="../images/".$file_name;

      if (file_put_contents($path, base64_decode($_POST['image']))) {
        // code...
        $grower_farm_sql = "INSERT INTO officials_signatures(userid,seasonid,image_location,created_at) VALUES ($userid_1,$seasonid,'$file_name','$created_at')";
         //$sql = "select * from login";
         if ($conn->query($grower_farm_sql)===TRUE) {
         
           $last_id = $conn->insert_id;

            $temp=array("id"=>$sqliteid);
            array_push($data,$temp);
        
        }else{
          $temp=array("id"=>$conn->error);
            array_push($data,$temp);
        }

      }

     }else{
      $temp=array("response"=>"Username not found");
          array_push($data,$temp);
     }

  
}else{
  $temp=array("response"=>"Field Empty");
  array_push($data,$temp);
}


echo json_encode($data);


?>