<?php 

require_once("conn.php");
$data=array();
if (!empty($_POST['image']) && isset($_POST['latitude']) && isset($_POST['longitude']) && isset($_POST['userid']) && isset($_POST['seasonid'])  && isset($_POST['grower_num']) && isset($_POST['id'])){


		$growerid=0;
		$image_found=0;
		$seasonid=$_POST['seasonid'];
		$userid=$_POST['userid'];
		$latitude=$_POST['latitude'];
		$longitude=$_POST['longitude'];
		$grower_num=$_POST['grower_num'];
		$sqliteid=$_POST['id'];
		$created_at=$_POST['created_at'];


		$sql = "Select growers.id from growers  where  grower_num='$grower_num'";
		$result = $conn->query($sql);
		    
		 if ($result->num_rows > 0) {
		   // output data of each row
		   while($row = $result->fetch_assoc()) {

		    // product id
		   
		   $growerid=$row["id"];
		   
		    
		   }

		 }



			 //check farm
		// $sql1 = "Select id from grower_farm_image  where  growerid=$growerid and seasonid=$seasonid";
		// $result = $conn->query($sql1);
		 
		//  if ($result->num_rows > 0) {
		//    // output data of each row
		//    while($row = $result->fetch_assoc()) {

		//     // product id
		//    $image_found=$row["id"];
		//   // $growerid=$row["id"];
		   
		    
		//    }

		//  }



		 if ($image_found==0 && $growerid>0) {


		 	$file_name=$_POST['grower_num'] ."-farm-".time().".jpg";
			$path="../images/".$file_name;

			if (file_put_contents($path, base64_decode($_POST['image']))) {
				// code...
				$grower_farm_sql = "INSERT INTO grower_farm_image(userid,seasonid,growerid,latitude,longitude,image_location,created_at) VALUES ($userid,$seasonid,$growerid,'$latitude','$longitude','$file_name','$created_at')";
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