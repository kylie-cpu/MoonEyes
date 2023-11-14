<?php
// Check for the current session
session_start();
$user = $_SESSION['user'];
$name = $user['name'];

// Generate a unique tag ID
$unique_tag_id = "TAG-" . uniqid();
$agent_id = $user['agent_id'];

// Connect to the database
include('../database/connection.php');

// Create the date for the date modified field
date_default_timezone_set('America/Detroit');
$date = date('Y-m-d H:i:s');

// Populate dropdowns
include('../included/dropdowns.php');

if ($_POST) {
    $tag_id = $unique_tag_id;
    $name = preg_replace("/'/", "", $_POST['name']);

    // Insert the tag into the tags table
    $insert_tag = "INSERT INTO tags(tag_id, name, day_modified, modified_by) 
    VALUES ('$tag_id', '$name', '$date', '$agent_id')";

    if ($conn->query($insert_tag) !== TRUE) {
        echo "Error inserting into tags";
    }

    $assoc_clients = $_POST['assoc_clients'];
    $assoc_cases = $_POST['assoc_cases'];
    $assoc_subjects = $_POST['assoc_subjects'];

    foreach ($assoc_clients as $assoc_client) {
        // Insert into tag_assoc for clients
        $query = "SELECT client_id FROM clients WHERE client_name = '$assoc_client'";
        $result = $conn->query($query);
        if ($result->num_rows > 0) {
            $client_id = $result->fetch_assoc()['client_id'];
            $insert_tag_assoc = "INSERT INTO tag_assoc(tag_id, assoc_id) VALUES ('$tag_id', '$client_id')";
            if ($conn->query($insert_tag_assoc) !== TRUE) {
                echo "Error inserting into tag_assoc";
            }
        }
    }

    foreach ($assoc_cases as $assoc_case) {
        // Insert into tag_assoc for cases
        $query = "SELECT case_id FROM cases WHERE title = '$assoc_case'";
        $result = $conn->query($query);
        if ($result->num_rows > 0) {
            $case_id = $result->fetch_assoc()['case_id'];
            $insert_tag_assoc = "INSERT INTO tag_assoc(tag_id, assoc_id) VALUES ('$tag_id', '$case_id')";
            if ($conn->query($insert_tag_assoc) !== TRUE) {
                echo "Error inserting into tag_assoc";
            }
        }
    }
    foreach ($assoc_subjects as $assoc_subject) {
        // Insert into tag_assoc for subjects
        $query = "SELECT subject_id FROM subjects WHERE subject_name = '$assoc_subject'";
        $result = $conn->query($query);
        if ($result->num_rows > 0) {
            $subject_id = $result->fetch_assoc()['subject_id'];
            $insert_tag_assoc = "INSERT INTO tag_assoc(tag_id, assoc_id) VALUES ('$tag_id', '$subject_id')";
            if ($conn->query($insert_tag_assoc) !== TRUE) {
                echo "Error inserting into tag_assoc";
            }
        }
    }

    // Redirect back to the dashboard after submission
    header("Location: ../main/dashboard.php");
    exit;
}
?>

<!-- HTML -->
<!DOCTYPE html>
<html>
<head>
    <title>Add a Tag</title>
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Sidenav and additional CSS -->
    <link rel="stylesheet" type="text/css" href="../css/add.css">
    <link rel="stylesheet" href="../css/sidenav.css">
</head>
<body>
    <!-- jQuery library -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 library -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Fixes x in multiple selection UI -->
    <style>
        .select2-selection__choice__remove {
            margin-top: -1.5px !important; 
        }
    </style>

    <?php include("../nav/sidenav.php"); ?>

    <!-- Database submission form -->
    <div class="content" id="content">
        <h2>Add a Tag</h2>
        <form action="tags.php" method="POST" class="tag-form">
            <div class="form-group1">
                <label for="tag_id"><span class="required">*</span>Tag ID</label>
                <input class="view-input" type="text" id="tag_id" name="tag_id" value="<?php echo $unique_tag_id ?>" readonly>
            </div>

            <div class="form-group2">
                <label for="name"><span class="required">*</span>Name</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="assoc_clients">Associated Clients</label>
                <select id="assoc_clients" name="assoc_clients[]" class="multiple-clients" multiple="multiple" style="width: 43%;">
                </select>
            </div>

            <div class="form-group1">
                <label for="assoc_cases">Associated Cases</label>
                <select id="assoc_cases" name="assoc_cases[]" class="multiple-cases" multiple="multiple" style="width: 100%;">
                </select>
            </div>

            <div class="form-group2">
                <label for="assoc_subjects">Associated Subjects</label>
                <select id="assoc_subjects" name="assoc_subjects[]" class="multiple-subjects" multiple="multiple" style="width: 100%;">
    
                </select>
            </div>

            <div class="form-group1">
                <label for="day_modified"><span class="required">*</span>Date Modified</label>
                <input class="view-input" type="datetime-local" id="day_modified" name="day_modified" value="<?php echo $date ?>" readonly>
            </div>

            <div class="form-group2">
                <label for="modified_by"><span class="required">*</span>Modified By</label>
                <input class="view-input" type="text" id="modified_by" name="modified_by" value="<?php echo $name ?>" readonly>
            </div>

            <div class="form-group">
                <button type="submit" class="submit-btn">Submit</button>
                <a href="../main/dashboard.php" class="discard-btn" onclick="return confirm('Are you sure you want to discard? No data will be saved.')">Discard</a>
            </div>
        </form>
    </div>

    <!-- Select 2 initialization for dropdown menus -->
    <script>
        $(document).ready(function () {
            // Multiple clients input field
            $('.multiple-clients').select2({
                placeholder: 'Select clients...',
                data: <?php echo json_encode($client_names); ?>,
            });

            $('.multiple-cases').select2({
            placeholder: 'Select cases...',
            data: <?php echo json_encode($case_titles); ?>,
            });

            // multiple subjects input field
            $('.multiple-subjects').select2({
            placeholder: 'Select subjects...',
            data: <?php echo json_encode($subject_names); ?>,
            });

            // multiple agents input field
            $('.multiple-agents').select2({
            placeholder: 'Select agents...',
            data: <?php echo json_encode($agent_names); ?>,
            });
            });
    </script>
</body>
<html>

