<?php
    session_start();

    // Check user logged in
    if (!isset($_SESSION['user'])) {
        header("Location: ../login/login-form.php");
        exit();
    }
    $user = $_SESSION['user'];
    $name = $user['name'];
    $agent_id = $user['agent_id'];

    $client_id = $_GET['client_id'];

    include('../database/connection.php');

    // Create date for date modified field
    date_default_timezone_set('America/Detroit');
    $date = date('Y-m-d H:i:s');

    // Populate dropdowns
    include('../included/dropdowns.php');

    // get client details from clients
    $query_client_details = "SELECT clients.*, agents.name AS mod_agent, lawyers.lawyer_name AS lawyer_name, lawyers.lawyer_email AS lawyer_email, lawyers.lawyer_ph as lawyer_ph
    FROM clients
    LEFT JOIN agents on clients.modified_by = agents.agent_id
    LEFT JOIN lawyers ON clients.lawyer = lawyers.lawyer_id
    WHERE clients.client_id = '$client_id'
    GROUP BY clients.client_id";
    $result_client_details = $conn->query($query_client_details);
    if ($result_client_details->num_rows > 0) {
        $client_details = $result_client_details->fetch_all(MYSQLI_ASSOC);
    }

    // get associated cases 
    $query_assoc_cases = "SELECT cases.title
    FROM cases
    LEFT JOIN case_client ON cases.case_id = case_client.case_id
    WHERE case_client.client_id = '$client_id'
    GROUP BY cases.case_id";
    $result_assoc_cases = $conn->query($query_assoc_cases);
    if ($result_assoc_cases->num_rows > 0) {
        $assoc_cases = $result_assoc_cases->fetch_all(MYSQLI_ASSOC);
    }

    // get names of associated TAGS
    $query_assoc_tags = "SELECT name 
    FROM tags
    LEFT JOIN tag_assoc ON tags.tag_id = tag_assoc.tag_id
    WHERE tag_assoc.assoc_id ='$client_id'
    GROUP BY tag_assoc.tag_id";
    $result_assoc_tags = $conn->query($query_assoc_tags);
    if ($result_assoc_tags) {
        $assoc_tags = $result_assoc_tags->fetch_all(MYSQLI_ASSOC);
    }

    // Get attached files
    $query_assoc_files = "SELECT file_id, fileName FROM files WHERE entity_id = '$client_id'";
    $result_assoc_files = $conn->query($query_assoc_files);
    if ($result_assoc_files) {
        $assoc_files = $result_assoc_files->fetch_all(MYSQLI_ASSOC);
    }

    // Add to audit logs 
    include '../included/audit.php';
    $id = $client_id;
    $type = 'View';
    $audit_agent = $agent_id;
    $jsonDumpOfForm = '';
    logAudit($id, $type, $audit_agent, $jsonDumpOfForm);

?>

<!DOCTYPE html>
<html>
  <head>
    <title>Client Details</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="../css/add.css">
    <link rel="stylesheet" href="../css/sidenav.css">

  </head>
  <body>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>

        <!-- Fixes x in multiple selection -->
        <style>
            .select2-selection__choice__remove {
                margin-top: -1.5px !important; 
            }
        </style>

        <?php include("../nav/sidenav.php"); ?>

        <!-- Database submission form -->
        <div id="content" class="content">
            <h2><?php echo $client_id?> Details</h2>
            <form action="client_details.php" method="POST" class="client-form">
                <?php if (!empty($client_details)) {
                $client = $client_details[0]; ?>
                    <div class="form-group1">
                        <label for="client_id"><span class="required">*</span>Client ID</label>
                        <input class="view-input" type="text" id="client_id" name="client_id" value="<?php echo $client['client_id']; ?>" readonly>
                    </div>

                    <div class="form-group2">
                        <label for="name"><span class="required">*</span>Name</label>
                        <input class="view-input" type="text" id="name" name="name" value="<?php echo $client['client_name']; ?>" readonly>
                    </div>

                    <div class="form-group1">
                        <label for="email">Email</label>
                        <input class="view-input" type="email" id="email" name="email" value="<?php echo $client['email']; ?>" readonly>
                    </div>

                    <div class="form-group2">
                        <label for="phone">Phone Number</label>
                        <input class="view-input" type="tel" id="phone" name="phone" value="<?php echo $client['phone_num']; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea class="view-input" id="address" name="address" rows="4" readonly><?php echo $client['address']; ?></textarea>
                    </div>

                    <div class="form-group">
                        <h3>Lawyer Information</h3>
                        <div class="sub-form-group">
                            <label for="lawyer-name">Lawyer Name</label>
                            <input class="view-input" type="text" id="lawyer-name" name="lawyer-name" value="<?php echo $client['lawyer_name']; ?>" readonly>
                        </div>

                        <div class="sub-form-group">
                            <label for="lawyer-email">Lawyer Email</label>
                            <input class="view-input" type="email" id="lawyer-email" name="lawyer-email" value="<?php echo $client['lawyer_email']; ?>" readonly>
                        </div>
                   
                        <div class="sub-form-group">
                            <label for="lawyer-ph">Lawyer Phone Number</label>
                            <input class="view-input" type="tel" id="lawyer-ph" name="lawyer-ph" value="<?php echo $client['lawyer_ph']; ?>" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea class="view-input" id="notes" name="notes" rows="4"readonly><?php echo $client['notes']; ?></textarea>
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
                        <input class="view-input" type="file" id="media" name="media" style="width: 35%;" multiple disabled>
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
                            echo "No attached files for this client.";
                            echo "</ul>";
                        }
                    ?>

                    <div class="form-group1">
                        <label for="ud1">Field 1</label>
                        <input class="view-input" type="text" id="ud1" name="ud1" value="<?php echo $client['ud1']; ?>" readonly>
                    </div> 
                    
                    <div class="form-group2">
                        <label for="ud2">Field 2</label>
                        <input class="view-input" type="text" id="ud2" name="ud2" value="<?php echo $client['ud2']; ?>" readonly>
                    </div>
                    
                    <div class="form-group1">
                        <label for="ud3"> Field 3</label>
                        <input class="view-input" type="text" id="ud3" name="ud3" value="<?php echo $client['ud3']; ?>" readonly>
                    </div>
                    
                    <div class="form-group2">
                        <label for="ud4">Field 4</label>
                        <input class="view-input" type="text" id="ud4" name="ud4" value="<?php echo $client['ud4']; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="related_cases">Associated Cases</label>
                        <select id="related_cases" name="related_cases[]" class="js-example-basic-multiple-cases" multiple="multiple" style="width: 44%;" disabled>
                        <option value=""></option>
                        <?php foreach ($assoc_cases as $case) { ?>
                            <option value="<?php echo $case['title']; ?>" selected><?php echo $case['title']; ?></option>
                        <?php } ?>
                        </select>
                    </div>

                    <div class="form-group1">
                        <label for="day_modified"><span class="required">*</span>Date Modified</label>
                        <input class="view-input" type="datetime-local" id="day_modified" name="day_modified" value="<?php echo $client['day_modified']; ?>" readonly>
                    </div>

                    <div class="form-group2">
                        <label for="modified_by"><span class="required">*</span>Modified By</label>
                        <input class="view-input" type="text" id="modified_by" name="modified_by" value="<?php echo $client['mod_agent']; ?>" readonly>
                    </div><br><br>
                    
                    <div class="form-group">
                        <a href="../edit/edit_client.php?client_id=<?php echo $client['client_id']; ?>" class="edit-btn">Edit</a>
                        <a href=# onclick="window.print();" class="print-btn">Print/PDF</a>
                    </div>

                <?php } ?>
            </form>
        </div>

        <!-- Select 2 initialization for dropdown menus -->
        <script>
            $(document).ready(function() {
                // cases input field
                $('.js-example-basic-multiple-cases').select2({
                    data: <?php echo json_encode($case_titles); ?>,
                });
                // multiple tags input field
                $('.multiple-tags').select2({
                    data: <?php echo json_encode($tag_names); ?>,
                });
            });
        </script>
  </body>
</html>