<?php
$servername = "localhost";
$username = "gbcdb_user";
$password = "kmsE0t@@IuM9E!g]";
$dbname = "gbcdb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
