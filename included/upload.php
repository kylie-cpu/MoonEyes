<?php

$uploadDir = '../uploads/';

if (isset($_FILES['media'])) {
    $files = $_FILES['media'];
    foreach ($files['tmp_name'] as $key => $tmpName) {
        $fileName = $files['name'][$key];
        $uploadPath = $uploadDir . $fileName;
        $unique_file_id = "FILE-" . uniqid();
        // move file into uploads folder
        if (move_uploaded_file($tmpName, $uploadPath)) {
            // insert file info into DB
            $insertQuery = "INSERT INTO files (file_id, entity_id, fileName) 
                VALUES ('$unique_file_id', '$entity_id', '$fileName')";
            if ($conn->query($insertQuery) !== TRUE) {
                echo "Error inserting file details: " . $conn->error;
            }
        }
    }
}
?>
