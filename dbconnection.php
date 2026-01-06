<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$DB_SERVER = "localhost";
$DB_USERNAME = "root";
$DB_PASSWORD = "";
$DB_NAME = "mpesa_transactions"; 

$db = mysqli_connect($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_NAME);

if (!$db) {
    die("Database connection failed: " . mysqli_connect_error());
}
 
