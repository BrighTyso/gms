<?php

require_once("../api/conn.php");

$sql2 = "Select distinct users.id,surname,name from  users where active=1 and (rightsid=7 or rightsid=8 or rightsid=9)";

$result1 = $conn->query($sql2);

$field_officers=$result1->num_rows;

echo $field_officers;


?>