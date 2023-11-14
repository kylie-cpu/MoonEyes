<?php 

include('../database/connection.php');
$query = "SELECT COUNT(*) as entity_count FROM files WHERE fileName = '$selected_file'";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $entity_count = $row['entity_count'];
    echo $entity_count;
    echo $selected_file;
    echo $entity_id;

    if ($entity_count == 1) {
        // If this is the only entity associated with the file, delete the file from the uploads folder
        $query_delete = "DELETE FROM files WHERE fileName = '$selected_file' and entity_id = '$entity_id'";
        if ($conn->query($query_delete)) {
            $file_path = "../uploads/$selected_file"; // Adjust the file path as needed
            if (file_exists($file_path)) {
                unlink($file_path); // Delete the file
            }
            echo "File deleted successfully.";
        } else {
            echo "Failed to delete file from the database.";
        }
    } else {
        echo "File is associated with other entities. It won't be deleted.";
        $query_delete = "DELETE FROM files WHERE fileName = '$selected_file' and entity_id = '$entity_id'";
        if ($conn->query($query_delete)) {
            echo "Association deleted in database.";
        }
    }
} else {
    echo "File not found in the database.";
}

?>





