<?php
session_start();
include('../database/connection.php');
// get user for audit logs
$user = $_SESSION['user'];

// type of entity and id
$entity_id = $_GET['entity_id'];
$entity_type = $_GET['entity_type'];

$tables = [];
$column = '';

// Select correct table names depending on entity type
if ($entity_type === 'case') {
    $tables = ["cases", "case_agent", "case_client", "case_subject"];
    $column = 'case_id';
} elseif ($entity_type === 'client') {
    $tables = ["clients", "case_client"];
    $column = 'client_id';
} elseif ($entity_type === 'agent') {
    $tables = ["agents", "case_agent"];
    $column = 'agent_id';
} elseif ($entity_type === 'subject') {
    $tables = ["subjects", "case_subject"];
    $column = 'subject_id';
} elseif ($entity_type === 'tag') {
    $tables = ["tags", "tag_assoc"];
    $column = 'tag_id';
}

// Combine all SQL statements into a single query
$sqlStatements = array();
foreach ($tables as $table) {
    $sqlStatements[] = "DELETE FROM $table WHERE $column = '$entity_id'";
}
$sql = implode(";", $sqlStatements);

if ($conn->multi_query($sql) !== TRUE) {
    echo "Error executing multi-query: " . $conn->error;
} else {
    
    // delete from tables
    while ($conn->more_results()) {
        $conn->next_result();
        $conn->use_result();
    }

    // Clean up DB, extra deletions

    // delete if lawyer has no matching client/subject
    $lawyer_query = "DELETE FROM `lawyers` WHERE
    lawyer_id NOT IN (SELECT lawyer FROM clients) 
    AND lawyer_id NOT IN (SELECT lawyer FROM subjects)";

    if ($conn->query($lawyer_query) !== TRUE) {
        echo "Error deleting lawyers: " . $conn->error;
    }

    // delete tag associations
    $tags_query = "DELETE FROM `tag_assoc` WHERE assoc_id = '$entity_id'";
    if ($conn->query($tags_query) !== TRUE) {
        echo "Error deleting tag associations: " . $conn->error;
    }

    // delete files from db and uploads folder
    $query_assoc_files = "SELECT fileName FROM files WHERE entity_id = '$entity_id'";
    $result_assoc_files = $conn->query($query_assoc_files);
    if ($result_assoc_files) {
        $assoc_files = $result_assoc_files->fetch_all(MYSQLI_ASSOC);
    }

    foreach ($assoc_files as $assoc_file) {
        $selected_file = $assoc_file['fileName'];
        include '../included/delete_file.php';
    }

    // Add audit log
    include '../included/audit.php';
    $id = $entity_id;
    $type = 'Delete';
    $audit_agent = $user['agent_id'];
    $jsonDumpOfForm = '';
    logAudit($id, $type, $audit_agent, $jsonDumpOfForm);


    // redirect to home
    header("Location: ../main/dashboard.php");
}

?>