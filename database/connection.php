<?php 
    $host= 'localhost';
    $user = 'root';
    $pass = '';
    $database = 'moon eyes';

    // Connecting to database
    $conn = new mysqli($host, $user, $pass, $database);
    if (!$conn){
        die("Could not connect to Moon Eyes database: "  . mysqli_connect_errno());
    }
    
?>