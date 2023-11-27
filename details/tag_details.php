<?php
    // Check for the current session
    session_start();
    if (!isset($_SESSION['user'])) {
        header("Location: ../login/login-form.php");
        exit();
    }
    $user = $_SESSION['user'];
    $name = $user['name'];
    $agent_id = $user['agent_id'];

    $tag_id = $_GET['tag_id']; // Assuming you have a tag_id in the URL to identify the tag to view

    include('../database/connection.php');

    // Include code for populating dropdowns
    include('../included/dropdowns.php');

    // Create date for date modified field
    date_default_timezone_set('America/Detroit');
    $date = date('Y-m-d H:i:s');

    // Get tag details from the "tags" table
    $query_tag_details = "SELECT tags.*, agents.name as mod_agent FROM tags 
    LEFT JOIN agents ON tags.modified_by = agents.agent_id
    WHERE tags.tag_id = '$tag_id'
    GROUP BY tags.tag_id";
    $result_tag_details = $conn->query($query_tag_details);

    if ($result_tag_details) {
        $tag_details = $result_tag_details->fetch_all(MYSQLI_ASSOC);
    }

    // case associations
    $query_case_assoc = "SELECT cases.title fROM cases
    LEFT JOIN tag_assoc ON cases.case_id = tag_assoc.assoc_id
    WHERE tag_assoc.assoc_id LIKE '%CASE%'AND tag_assoc.tag_id = '$tag_id'";
    $result_case_assoc = $conn->query($query_case_assoc);
    if ($result_case_assoc) {   
        $case_details = $result_case_assoc->fetch_all(MYSQLI_ASSOC);    
    }

    // client associations
    $query_client_assoc = "SELECT clients.client_name fROM clients
    LEFT JOIN tag_assoc ON clients.client_id = tag_assoc.assoc_id
    WHERE tag_assoc.assoc_id LIKE '%CLIENT%'AND tag_assoc.tag_id = '$tag_id'";
    $result_client_assoc = $conn->query($query_client_assoc);
    if ($result_client_assoc) {   
        $client_details = $result_client_assoc->fetch_all(MYSQLI_ASSOC);    
    }

    // subject associations
    $query_subject_assoc = "SELECT subjects.subject_name fROM subjects
    LEFT JOIN tag_assoc ON subjects.subject_id = tag_assoc.assoc_id
    WHERE tag_assoc.assoc_id LIKE '%SUBJECT%'AND tag_assoc.tag_id = '$tag_id'";
    $result_subject_assoc = $conn->query($query_subject_assoc);
    if ($result_subject_assoc) {   
        $subject_details = $result_subject_assoc->fetch_all(MYSQLI_ASSOC);    
    }

    // Add to audit logs 
    include '../included/audit.php';
    $id = $tag_id;
    $type = 'View';
    $audit_agent = $agent_id;
    $jsonDumpOfForm = '';
    logAudit($id, $type, $audit_agent, $jsonDumpOfForm);
    
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tag Details</title>
    <!-- Include Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Include your CSS files for styling -->
    <link rel="stylesheet" type="text/css" href="../css/add.css">
    <link rel="stylesheet" href="../css/sidenav.css">
</head>
<body>
    <!-- Include jQuery and Select2 library -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Fix the 'x' in multiple selection UI -->
    <style>
    .select2-selection__choice__remove {
        margin-top: -1.5px !important;
    }
    </style>

    <?php include("../nav/sidenav.php"); ?>

    <div class="content" id="content">
        <h2><?php echo $tag_id; ?> Details</h2>
        <form action="edit_tags.php" method="POST" class="tag-form">
            <?php if (!empty($tag_details)) {
                $tag = $tag_details[0];
            ?>
            <div class="form-group1">
                <label for="tag_id"><span class="required">*</span>Tag ID</label>
                <input class="view-input" type="text" id="tag_id" name="tag_id" value="<?php echo $tag_id; ?>" readonly>
            </div>

            <div class="form-group2">
                <label for="name"><span class="required">*</span>Name</label>
                <input type="text" id="name" class="view-input" name="name" value="<?php echo $tag['name']; ?>" readonly>
            </div>


            <div class="form-group">
                <label for="assoc_clients">Associated Clients</label>
                <select id="assoc_clients" name="assoc_clients[]" class="multiple-clients" multiple="multiple" style="width: 43%;" disabled>
                <?php foreach ($client_details as $client) { ?>
                    <option value="<?php echo $client['client_name']; ?>" selected><?php echo $client['client_name']; ?></option>
                  <?php } ?>                
                </select>
            </div>

            <div class="form-group1">
                <label for="assoc_cases">Associated Cases</label>
                <select id="assoc_cases" name="assoc_cases[]" class="multiple-cases" multiple="multiple" style="width: 100%;" disabled>
                <?php foreach ($case_details as $case) { ?>
                    <option value="<?php echo $case['title']; ?>" selected><?php echo $case['title']; ?></option>
                  <?php } ?>                
                </select>
            </div>

            <div class="form-group2">
                <label for="assoc_subjects">Associated Subjects</label>
                <select id="assoc_subjects" name="assoc_subjects[]" class="multiple-subjects" multiple="multiple" style="width: 100%;" disabled>
                <?php foreach ($subject_details as $subject) { ?>
                    <option value="<?php echo $subject['subject_name']; ?>" selected><?php echo $subject['subject_name']; ?></option>
                  <?php } ?>                
                </select>
            </div>

            <div class="form-group1">
                <label for="day_modified"><span class="required">*</span>Date Modified</label>
                <input class="view-input" type="datetime-local" id="day_modified" name="day_modified" value="<?php echo $tag['day_modified']; ?>" readonly>
            </div>

            <div class="form-group2">
                <label for="modified_by"><span class="required">*</span>Modified By</label>
                <input class="view-input" type="text" id="modified_by" name="modified_by" value="<?php echo $tag['mod_agent']; ?>" readonly>
            </div>

            <div class="form-group">
                <a href="../edit/edit_tag.php?tag_id=<?php echo $tag['tag_id']; ?>" class="edit-btn">Edit</a>
                <a href=# onclick="window.print();" class="print-btn">Print/PDF</a>
            </div>
            <?php } ?>
        </form>
    </div>

    <!-- Initialize Select2 for dropdown menus -->

    <script>
        $(document).ready(function() {
            // multiple clients
            $('.multiple-clients').select2({
                data: <?php echo json_encode($client_names); ?>,
            });

            // multiple subjects input field
            $('.multiple-subjects').select2({
                data: <?php echo json_encode($subject_names); ?>,
            });

            // multiple cases input field
            $('.multiple-cases').select2({
                data: <?php echo json_encode($case_titles); ?>,
            });
        });
    </script>
</body>
</html>
