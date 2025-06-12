<?php 

require_once("conn.php");
$data=array();
if (!empty($_POST['image'])  && isset($_POST['userid']) && isset($_POST['seasonid'])  && isset($_POST['grower_num']) && isset($_POST['id'])){


    $growerid=0;
    $image_found=0;
    $seasonid=$_POST['seasonid'];
    $userid=$_POST['userid'];
    $grower_num=$_POST['grower_num'];
    $sqliteid=$_POST['id'];
    $created_at=$_POST['created_at'];
    $description=$_POST['description'];
    $file_type=$_POST['file_type'];
    $location_url="";
    $datetimes="";


    $sql = "Select growers.id from growers  where  grower_num='$grower_num'";
    $result = $conn->query($sql);
        
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        // product id
       $growerid=$row["id"];
       
        
       }

     }


     if ($growerid>0) {


      $file_name=$_POST['grower_num'] ."-".time().".jpg";
      $path="../images/".$file_name;

      if (file_put_contents($path, base64_decode($_POST['image']))) {
        // code...
        $grower_farm_sql = "INSERT INTO file_manager(userid,seasonid,growerid,location_url,description,file_type,storages,created_at,datetimes) VALUES ($userid,$seasonid,$growerid,'$file_name','$description','$file_type','Domain','$created_at','$datetimes')";
         //$sql = "select * from login";
         if ($conn->query($grower_farm_sql)===TRUE) {
         
           $last_id = $conn->insert_id;

          $temp=array("id"=>$sqliteid);
          array_push($data,$temp);
        
        }

      }

     }



  
}


echo json_encode($data);


?>