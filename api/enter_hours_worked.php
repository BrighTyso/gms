<?php 

require_once("conn.php");
$data=array();

if (isset($_GET['hours'])  && isset($_GET['userid']) && isset($_GET['seasonid'])  && isset($_GET['sqliteid']) && isset($_GET['created_at'])){


		$growerid=0;
		$found=0;
		$seasonid=$_GET['seasonid'];
		$userid=$_GET['userid'];
		$sqliteid=$_GET['sqliteid'];
		$hours=$_GET['hours'];
		$created_at=$_GET['created_at'];


		$sql = "Select hours_worked.id from hours_worked  where  created_at='$created_at' and userid=$userid";
		$result = $conn->query($sql);
		    
		 if ($result->num_rows > 0) {
		   // output data of each row
		   while($row = $result->fetch_assoc()) {

		    // product id
		   
		   $found=$row["id"];
		   
		    
		   }

		 }



			 //check farm
		// $sql1 = "Select id from grower_signatures  where  growerid=$growerid and seasonid=$seasonid";
		// $result = $conn->query($sql1);
		 
		//  if ($result->num_rows > 0) {
		//    // output data of each row
		//    while($row = $result->fetch_assoc()) {

		//     // product id
		//    $image_found=$row["id"];
		//   // $growerid=$row["id"];
		   
		    
		//    }

		//  }

		 if ($found==0) {

				// code...
				$grower_farm_sql = "INSERT INTO hours_worked(userid,seasonid,hours,created_at) VALUES ($userid,$seasonid,$hours,'$created_at')";
			   //$sql = "select * from login";
			   if ($conn->query($grower_farm_sql)===TRUE) {
			   
			     $last_id = $conn->insert_id;

				$temp=array("id"=>$sqliteid);
               	array_push($data,$temp);
				
				}

			

		 }



	
}


echo json_encode($data);


?>