<?php 
    // $host= 'moon-eyes.cqrmcypqphmq.us-east-2.rds.amazonaws.com';
    // $user = 'admin';
    // $pass = '12345678';
    // $database = 'moon eyes';

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