<?php

/*$host = 'localhost';
$user = 'root';
$pass = "";
$db_name = "php_project";
$conn = new mysqli($host, $user, $pass, $db_name);
if($conn->connect_error){
    echo 'Failed to connect database' .$conn->connect_error;
}*/


$serverName = "TITANIUM-VORTEX\SQLEXPRESS"; // or use SERVER\\INSTANCE for named instance
$connectionOptions = array(
    "Database" => "php_project",
    "Uid" => "", 
    "PWD" => ""  
);

// Establishes the connection
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    echo "Failed to connect to database: ";
    die(print_r(sqlsrv_errors(), true));
}





?>