<?php

// Database credentials
require_once("conn.php");
require "validate.php";

// Get all tables
$tables = array();
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

$backup_file = 'database_backup_' . date("YmdHis") . '.sql';  // Timestamped filename
$backup_content = '';

// Loop through each table
foreach ($tables as $table) {
    $backup_content .= "DROP TABLE IF EXISTS `$table`;\n"; // Add DROP TABLE statement (important!)
    $create_table_query = $conn->query("SHOW CREATE TABLE `$table`");
    $row = $create_table_query->fetch_row();
    $backup_content .= $row[1] . ";\n\n";

    $data_query = $conn->query("SELECT * FROM `$table`");
    $num_fields = $data_query->field_count;

    while ($row = $data_query->fetch_row()) {
        $backup_content .= "INSERT INTO `$table` VALUES(";
        for ($j = 0; $j < $num_fields; $j++) {
            $row[$j] = addslashes($row[$j]); // Escape special characters
            $row[$j] = str_replace("\n", "\\n", $row[$j]); // Escape newlines
            if (isset($row[$j])) {
                $backup_content .= '"' . $row[$j] . '"';
            } else {
                $backup_content .= 'NULL';
            }
            if ($j < ($num_fields - 1)) {
                $backup_content .= ',';
            }
        }
        $backup_content .= ");\n";
    }
    $backup_content .= "\n\n";
}


// Save to file (optional - if you want to also save on the server)
// file_put_contents($backup_file, $backup_content);


// Download the SQL file
header('Content-Type: application/octet-stream');
header("Content-Disposition: attachment; filename=$backup_file");
echo $backup_content;

$conn->close();

?>