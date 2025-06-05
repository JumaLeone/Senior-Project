<?php


$serverName = "TITANIUM-VORTEX\SQLEXPRESS";
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
