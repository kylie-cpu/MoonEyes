<?php
    // check for current session
    session_start();
    $user = $_SESSION['user'];
    $name = $user['name'];

    $case_id = $_GET['case_id'];

    include('../database/connection.php');

    // Populate dropdowns
    include('../included/dropdowns.php');

    //get case details from cases
    $query_case_details = "SELECT cases.*, GROUP_CONCAT(DISTINCT(clients.client_name) SEPARATOR ', ') AS assoc_client, agents.name AS mod_agent
    FROM cases
    LEFT JOIN case_client ON cases.case_id = case_client.case_id
    LEFT JOIN clients ON case_client.client_id = clients.client_id
    LEFT JOIN agents ON cases.modified_by = agents.agent_id
    WHERE cases.case_id = '$case_id'
    GROUP BY cases.case_id";
    $result_case_details = $conn->query($query_case_details);
    if ($result_case_details) {
        $case_details = $result_case_details->fetch_all(MYSQLI_ASSOC);
    } 
    // get names of associated subject
    $query_assoc_subjects = "SELECT subject_name 
    FROM subjects
    LEFT JOIN case_subject ON subjects.subject_id = case_subject.subject_id
    WHERE case_subject.case_id ='$case_id'
    GROUP BY case_subject.subject_id";
    $result_assoc_subjects = $conn->query($query_assoc_subjects);
    if ($result_assoc_subjects) {
        $assoc_subjects = $result_assoc_subjects->fetch_all(MYSQLI_ASSOC);
    } 
    // get names of associated agents
    $query_assoc_agents = "SELECT name 
    FROM agents
    LEFT JOIN case_agent ON agents.agent_id = case_agent.agent_id
    WHERE case_agent.case_id ='$case_id'
    GROUP BY case_agent.agent_id";
    $result_assoc_agents = $conn->query($query_assoc_agents);
    if ($result_assoc_agents) {
        $assoc_agents = $result_assoc_agents->fetch_all(MYSQLI_ASSOC);
    }

    // get names of associated TAGS
    $query_assoc_tags = "SELECT name 
    FROM tags
    LEFT JOIN tag_assoc ON tags.tag_id = tag_assoc.tag_id
    WHERE tag_assoc.assoc_id ='$case_id'
    GROUP BY tag_assoc.tag_id";
    $result_assoc_tags = $conn->query($query_assoc_tags);
    if ($result_assoc_tags) {
        $assoc_tags = $result_assoc_tags->fetch_all(MYSQLI_ASSOC);
    }

    $query_assoc_files = "SELECT file_id, fileName FROM files WHERE entity_id = '$case_id'";
    $result_assoc_files = $conn->query($query_assoc_files);
    if ($result_assoc_files) {
        $assoc_files = $result_assoc_files->fetch_all(MYSQLI_ASSOC);
    }

?>

<!DOCTYPE html>
<html>
    <head>
        <title>Case Details</title>
        <!-- Select2 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <!-- Sidenav and add php css -->
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
            <h2><?php echo $case_id ?> Details</h2>
            <form action="case_details.php" method="POST" class="case-form" enctype="multipart/form-data">
                <?php if (!empty($case_details)) {
                $case = $case_details[0]; ?>
                    <div class="form-group1">
                        <label for="case_id"><span class="required">*</span>Case ID</label>
                        <input class="view-input" type="text" id="case_id" name="case_id" value="<?php echo $case['case_id']; ?>" readonly>
                    </div>

                    <div class="form-group2">
                        <label for="title"><span class="required">*</span>Title</label>
                        <input class="view-input" type="text" id="title" name="title" value="<?php echo $case['title']; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="purpose">Purpose of Case</label>
                        <textarea id="purpose" class="view-input" name="purpose" rows="4" readonly><?php echo $case['purpose']; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="status"><span class="required">*</span>Status</label>
                        <select id="status" name="status" style="width: 35%; font-size: 19px; font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;" disabled>
                            <option value="Open"<?php if ($case['status'] === 'Open') echo ' selected'; ?>>Open</option>
                            <option value="Closed"<?php if ($case['status'] === 'Closed') echo ' selected'; ?>>Closed</option>
                            <option value="Pending"<?php if ($case['status'] === 'Pending') echo ' selected'; ?>>Pending</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="invoice_info">Invoice Information</label>
                        <textarea class="view-input" id="invoice_info" name="invoice_info" rows="4" readonly><?php echo $case['invoice']; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea class="view-input" id="notes" name="notes" rows="4" readonly><?php echo $case['notes']; ?></textarea>
                    </div>

                    <div class="form-group">
                    <label for="related_tags">Organizational Tags</label>
                    <select id="related_tags" name="related_tags[]" class="multiple-tags" style="width: 100%;" multiple="multiple" disabled>
                    <option value=""></option>
                    <?php foreach ($assoc_tags as $tag) { ?>
                        <option value="<?php echo $tag['name']; ?>" selected><?php echo $tag['name']; ?></option>
                    <?php } ?>
                    </select>
                    </div>

                    <div class="form-group">
                        <label for="media">Additional Media</label>
                        <input class="view-input" type="file" id="media" name="media" style="width: 35%;" disabled>
                    </div>
                    <?php 
                        if ($result_assoc_files->num_rows > 0) {
                            echo "<h3>Attached Files:</h3>";
                            echo "<ul>";
                            foreach ($assoc_files as $file) {
                                $file_id = $file['file_id'];
                                $fileName = $file['fileName'];
                                $fileURL = "../included/download.php?file_id=" . $file_id; // Link to a script that handles file download
                                echo "<li><a href='$fileURL'>$fileName</a></li>";
                            }
                            echo "</ul>";
                    
                        } else {
                            echo "<ul>";
                            echo "No attached files for this case.";
                            echo "</ul>";
                        }
                    ?>

                    <div class="form-group1">
                        <label for="ud1">Field 1</label>
                        <input class="view-input" type="text" id="ud1" name="ud1" value="<?php echo $case['ud1']; ?>" readonly>
                    </div> 
                    
                    <div class="form-group2">
                        <label for="ud2">Field 2</label>
                        <input class="view-input" type="text" id="ud2" name="ud2" value="<?php echo $case['ud2']; ?>" readonly>
                    </div>
                    
                    <div class="form-group1">
                        <label for="ud3"> Field 3</label>
                        <input class="view-input" type="text" id="ud3" name="ud3" value="<?php echo $case['ud3']; ?>" readonly>
                    </div>
                    
                    <div class="form-group2">
                        <label for="ud4">Field 4</label>
                        <input class="view-input" type="text" id="ud4" name="ud4" value="<?php echo $case['ud4']; ?>" readonly>
                    </div>


                    <div class="form-group">
                        <label for="related_client"><span class="required">*</span>Associated Client</label>
                        <select id="related_client" name="related_client[]" class="js-example-basic-single" style="width: 43%;" multiple="multiple" disabled>
                        <option value=""></option>
                        <option value="<?php echo $case['assoc_client']; ?>" selected><?php echo $case['assoc_client']; ?></option>
                        </select>
                    </div>

                    <div class="form-group1">
                        <label for="related_subjects">Associated Subjects</label>
                        <select id="related_subjects" name="related_subjects[]" class="js-example-basic-multiple-subjects" style="width: 100%;" multiple="multiple"  disabled>
                        <option value=""></option>
                        <?php foreach ($assoc_subjects as $subject) { ?>
                            <option value="<?php echo $subject['subject_name']; ?>" selected><?php echo $subject['subject_name']; ?></option>
                        <?php } ?>
                        </select>
                    </div>

                    <div class="form-group2">
                        <label for="related_agents">Associated Agents</label>
                        <select id="related_agents" name="related_agents[]" class="js-example-basic-multiple-agents" style="width: 100%;" multiple="multiple"  disabled>
                        <option value=""></option>
                        <?php foreach ($assoc_agents as $agent) { ?>
                            <option value="<?php echo $agent['name']; ?>" selected><?php echo $agent['name']; ?></option>
                        <?php } ?>
                        </select>
                    </div>

                    <div class="form-group1">
                        <label for="day_modified"><span class="required">*</span>Date Modified</label>
                        <input class="view-input" type="datetime-local" id="day_modified" name="day_modified" value="<?php echo $case['day_modified']; ?>" readonly>
                    </div>

                    <div class="form-group2">
                        <label for="modified_by"><span class="required">*</span>Modified By</label>
                        <input class="view-input" type="text" id="modified_by" name="modified_by" value="<?php echo $case['mod_agent']; ?>" readonly>
                    </div><br><br>

                    <div class="form-group">
                        <a href="../edit/edit_case.php?case_id=<?php echo $case['case_id']; ?>" class="edit-btn">Edit</a>
                        <a href=# onclick="window.print();" class="print-btn">Print/PDF</a>
                    </div>
                <?php } ?>
            </form>
        </div>
        <!-- Select 2 initialization for dropdown menus -->
        <script>
            $(document).ready(function() {
                // single client input field
                $('.js-example-basic-single').select2({
                    data: <?php echo json_encode($client_names); ?>,
                });

                // multiple subjects input field
                $('.js-example-basic-multiple-subjects').select2({
                    data: <?php echo json_encode($subject_names); ?>,
                });

                // multiple agents input field
                $('.js-example-basic-multiple-agents').select2({
                    data: <?php echo json_encode($agent_names); ?>,
                });

                // multiple tags input field
                $('.multiple-tags').select2({
                data: <?php echo json_encode($tag_names); ?>,
                });
            });
        </script>
    </body>
</html>