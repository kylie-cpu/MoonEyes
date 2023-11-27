<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login/login-form.php");
    exit();
}
$user = $_SESSION['user'];
$name = $user['name'];

$tag_id = $_GET['tag_id']; // Assuming you have a tag_id in the URL to identify the tag to edit
$new_agent_id = $user['agent_id'];

include('../database/connection.php');

date_default_timezone_set('America/Detroit');
$new_date = date('Y-m-d H:i:s');

include('../included/dropdowns.php');

//get tag details
$query_tag_details = "SELECT tags.*, agents.name as mod_agent FROM tags 
LEFT JOIN agents ON tags.modified_by = agents.agent_id
WHERE tags.tag_id = '$tag_id'
GROUP BY tags.tag_id";
$result_tag_details = $conn->query($query_tag_details);

if ($result_tag_details) {
    $tag_details = $result_tag_details->fetch_all(MYSQLI_ASSOC);
}
// get case associations
$query_case_assoc = "SELECT cases.title fROM cases
LEFT JOIN tag_assoc ON cases.case_id = tag_assoc.assoc_id
WHERE tag_assoc.assoc_id LIKE '%CASE%'AND tag_assoc.tag_id = '$tag_id'";
$result_case_assoc = $conn->query($query_case_assoc);
if ($result_case_assoc) {   
    $case_details = $result_case_assoc->fetch_all(MYSQLI_ASSOC);    
}

// get client associations
$query_client_assoc = "SELECT clients.client_name fROM clients
LEFT JOIN tag_assoc ON clients.client_id = tag_assoc.assoc_id
WHERE tag_assoc.assoc_id LIKE '%CLIENT%'AND tag_assoc.tag_id = '$tag_id'";
$result_client_assoc = $conn->query($query_client_assoc);
if ($result_client_assoc) {   
    $client_details = $result_client_assoc->fetch_all(MYSQLI_ASSOC);    
}

//get subject associations
$query_subject_assoc = "SELECT subjects.subject_name fROM subjects
LEFT JOIN tag_assoc ON subjects.subject_id = tag_assoc.assoc_id
WHERE tag_assoc.assoc_id LIKE '%SUBJECT%'AND tag_assoc.tag_id = '$tag_id'";
$result_subject_assoc = $conn->query($query_subject_assoc);
if ($result_subject_assoc) {   
    $subject_details = $result_subject_assoc->fetch_all(MYSQLI_ASSOC);    
}

if ($_POST) {
    $tag_id = $_POST['tag_id'];
    $name = preg_replace("/'/", "", $_POST['name']);

    $day_modified = $new_date;
    $modified_by = $new_agent_id;

    // Update the tags table
    $update_tag = "UPDATE tags SET name = '$name', day_modified = '$day_modified', modified_by = '$modified_by' WHERE tag_id = '$tag_id'";

    if ($conn->query($update_tag) !== TRUE) {
        echo "Error updating tag";
    }

    // Clear existing associations
    $delete_old_associations = "DELETE FROM tag_assoc WHERE tag_id = '$tag_id'";
    $conn->query($delete_old_associations);

    // Insert associated data into tag_assoc
    $assoc_clients = $_POST['assoc_clients'];

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

    $assoc_cases = $_POST['assoc_cases'];
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

    $assoc_subjects = $_POST['assoc_subjects'];
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

    // Add to audit logs 
    include '../included/audit.php';
    $id = $tag_id;
    $type = 'Edit';
    $audit_agent = $new_agent_id;
    $jsonDumpOfForm = json_encode($_POST);
    logAudit($id, $type, $audit_agent, $jsonDumpOfForm);


    // Redirect back to the dashboard after submission
    header("Location: ../main/dashboard.php");
    exit;
}
?>

<!-- HTML -->
<!DOCTYPE html>
<html>
<head>
    <title>Edit Tag</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="../css/add.css">
    <link rel="stylesheet" href="../css/sidenav.css">
</head>
<body>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <style>
        .select2-selection__choice__remove {
            margin-top: -1.5px !important; 
        }
    </style>

    <?php include("../nav/sidenav.php"); ?>

    <div class="content" id="content">
        <h2>Edit <?php echo $tag_id; ?></h2>
        <form action="edit_tag.php" method="POST" class="tag-form">
            <?php if (!empty($tag_details)) {
                $tag = $tag_details[0];
            ?>
            <div class="form-group1">
                <label for="tag_id"><span class="required">*</span>Tag ID</label>
                <input class="view-input" type="text" id="tag_id" name="tag_id" value="<?php echo $tag_id; ?>" readonly>
            </div>

            <div class="form-group2">
                <label for="name"><span class="required">*</span>Name</label>
                <input type="text" id="name" name="name" value="<?php echo $tag['name']; ?>" required>
            </div>

            <div class="form-group">
                <label for="assoc_clients">Associated Clients</label>
                <select id="assoc_clients" name="assoc_clients[]" class="multiple-clients" multiple="multiple" style="width: 43%;">
                <?php foreach ($client_details as $client) { ?>
                    <option value="<?php echo $client['client_name']; ?>" selected><?php echo $client['client_name']; ?></option>
                  <?php } ?>                
                </select>
            </div>

            <div class="form-group1">
                <label for="assoc_cases">Associated Cases</label>
                <select id="assoc_cases" name="assoc_cases[]" class="multiple-cases" multiple="multiple" style="width: 100%;">
                <?php foreach ($case_details as $case) { ?>
                    <option value="<?php echo $case['title']; ?>" selected><?php echo $case['title']; ?></option>
                  <?php } ?>                
                </select>
            </div>

            <div class="form-group2">
                <label for="assoc_subjects">Associated Subjects</label>
                <select id="assoc_subjects" name="assoc_subjects[]" class="multiple-subjects" multiple="multiple" style="width: 100%;">
                <?php foreach ($subject_details as $subject) { ?>
                    <option value="<?php echo $subject['subject_name']; ?>" selected><?php echo $subject['subject_name']; ?></option>
                  <?php } ?>                
                </select>
            </div>

            <div class="form-group1">
                <label for="day_modified"><span class="required">*</span>Date Modified</label>
                <input class="view-input" type="datetime-local" id="day_modified" name="day_modified" value="<?php echo $new_date; ?>" readonly>
            </div>

            <div class="form-group2">
                <label for="modified_by"><span class="required">*</span>Modified By</label>
                <input class="view-input" type="text" id="modified_by" name="modified_by" value="<?php echo $name; ?>" readonly>
            </div>

            <div class="form-group">
                <button type="submit" class="submit-btn">Submit Changes</button>
                <a class="discard-btn" href='delete.php?entity_type=tag&entity_id=<?php echo $tag_id?>' onclick="return confirm('Are you sure you want to delete this entry from the entire database? This action is irreversible.')">Delete Entity</a>
            </div>
            <?php } ?>
        </form>
    </div>

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
</html>
