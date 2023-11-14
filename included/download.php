<?php
include('../database/connection.php');   
if (isset($_GET['file_id'])) {
    $file_id = $_GET['file_id'];

    // Retrieve the file name and path from the database
    $query = "SELECT fileName FROM files WHERE file_id = '$file_id'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $fileName = $row['fileName'];
        $filePath = '../uploads/' . $fileName;

        // Check if the file exists
        if (file_exists($filePath)) {
            // Set appropriate headers for file download
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));

            // Read and output the file
            readfile($filePath);
            exit;
        }
    }
}

echo "File not found or missing parameters.";
?>