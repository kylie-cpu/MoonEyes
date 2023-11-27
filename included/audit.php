<?php

// Function to log audit information
function logAudit($id, $type, $agent, $jsonDumpOfForm)
{
    date_default_timezone_set('America/Detroit');
    $timestamp = date('Y-m-d H:i:s');

    include('../database/connection.php');

    $type = $conn->real_escape_string($type);
    $agent = $conn->real_escape_string($agent);
    $jsonDumpOfForm = $conn->real_escape_string($jsonDumpOfForm);
    $id = $conn->real_escape_string($id);

    $sql = "INSERT INTO audit_log (type, timestamp, agent, form, id) VALUES ('$type', '$timestamp', '$agent', '$jsonDumpOfForm', '$id')";

    if ($conn->query($sql) !== TRUE) {
        echo "Error: " . $sql . "<br>";
    }

    $conn->close();
}

?>