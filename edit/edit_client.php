<?php
    session_start();
    if (!isset($_SESSION['user'])) {
        header("Location: ../login/login-form.php");
        exit();
    }

    $user = $_SESSION['user'];
    $name = $user['name'];

    //get client ID and initizialize agent_id for modified_by field
    $client_id = $_GET['client_id'];
    $new_agent_id = $user['agent_id'];

    include('../database/connection.php');

    // Create date for date modified field
    date_default_timezone_set('America/Detroit');
    $new_date = date('Y-m-d H:i:s');
    
    // Populate dropdowns
    include('../included/dropdowns.php');

    // get client details from clients
    $query_client_details = "SELECT clients.*, agents.name AS mod_agent, lawyers.lawyer_name AS lawyer_name, lawyers.lawyer_email AS lawyer_email, lawyers.lawyer_ph AS lawyer_ph, lawyers.lawyer_id AS lawyer_id
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
    $assoc_cases = [];
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

    $query_assoc_files = "SELECT file_id, fileName FROM files WHERE entity_id = '$client_id'";
    $result_assoc_files = $conn->query($query_assoc_files);
    if ($result_assoc_files) {
        $assoc_files = $result_assoc_files->fetch_all(MYSQLI_ASSOC);
    }


    // if submit button is clicked
    if ($_POST) {
        $client_id = $_POST['client_id'];
        $client_name = $_POST['name'];
        $email = $_POST['email'];
        $address = $_POST['address'];
        $phone_num = $_POST['phone'];
        $lawyer_name = $_POST['lawyer-name'];
        $lawyer_email = $_POST['lawyer-email'];
        $lawyer_ph = $_POST['lawyer-ph'];
        $lawyer_id = $_POST['lawyer-id'];
        $notes = $_POST['notes'];
        $ud1 = $_POST['ud1'];
        $ud2 = $_POST['ud2'];
        $ud3 = $_POST['ud3'];
        $ud4 = $_POST['ud4'];
    
        $modified_by = $new_agent_id;
        $day_modified = $new_date;
    
        // update clients table with prepared statement
        $update_client_details = $conn->prepare("UPDATE clients SET 
            client_name = ?,
            email = ?,
            address = ?,
            phone_num = ?,
            notes = ?,
            lawyer = ?,
            modified_by = ?,
            day_modified = ?,
            ud1 = ?,
            ud2 = ?,
            ud3 = ?,
            ud4 = ?
            WHERE client_id = ?");
    
        $update_client_details->bind_param("sssssssssssss", $client_name, $email, $address, $phone_num, $notes, $lawyer_id, $modified_by, $day_modified, $ud1, $ud2, $ud3, $ud4, $client_id);
    
        if (!$update_client_details->execute()) {
            echo "Error updating client details: " . $update_client_details->error;
            exit();
        }
    
        // delete old cases entries
        $delete_old_cases = $conn->prepare("DELETE FROM case_client WHERE client_id = ?");
        $delete_old_cases->bind_param("s", $client_id);
        $delete_old_cases->execute();
    
        // insert new into case_client table with prepared statement
        $insert_case_client = $conn->prepare("INSERT INTO case_client(case_id, client_id) VALUES (?, ?)");
        $insert_case_client->bind_param("ss", $case_id, $client_id);
    
        $related_cases = $_POST['related_cases'];
        foreach ($related_cases as $related_case) {
            $query = $conn->prepare("SELECT case_id FROM cases WHERE title = ?");
            $query->bind_param("s", $related_case);
            $query->execute();
            $result = $query->get_result();
            if ($result->num_rows > 0) {
                $case_id = $result->fetch_assoc()['case_id'];
                $insert_case_client->execute();
            }
        }
    
        // update lawyers table with prepared statements
        $delete_existing_lawyers = $conn->prepare("DELETE FROM lawyers WHERE lawyer_id = ?");
        $delete_existing_lawyers->bind_param("s", $lawyer_id);
        $delete_existing_lawyers->execute();
    
        $insert_lawyer_details = $conn->prepare("INSERT INTO lawyers (lawyer_id, lawyer_name, lawyer_email, lawyer_ph) VALUES (?, ?, ?, ?)");
        $insert_lawyer_details->bind_param("ssss", $lawyer_id, $lawyer_name, $lawyer_email, $lawyer_ph);
        $insert_lawyer_details->execute();
    
        // update tags table with prepared statements
        $delete_tags = $conn->prepare("DELETE FROM tag_assoc WHERE assoc_id = ?");
        $delete_tags->bind_param("s", $client_id);
        $delete_tags->execute();
    
        $insert_tag_assoc = $conn->prepare("INSERT INTO tag_assoc(tag_id, assoc_id) VALUES (?, ?)");
        $insert_tag_assoc->bind_param("ss", $tag_id, $client_id);
    
        $related_tags = $_POST['related_tags'];
        foreach ($related_tags as $related_tag) {
            $query = $conn->prepare("SELECT tag_id FROM tags WHERE name = ?");
            $query->bind_param("s", $related_tag);
            $query->execute();
            $result = $query->get_result();
            if ($result->num_rows > 0) {
                $tag_id = $result->fetch_assoc()['tag_id'];
                $insert_tag_assoc->execute();
            }
        }
    
        // delete old file assoc from DB if selected 
        $selected_files = $_POST['selected_files'];
        $entity_id = $client_id;
        foreach ($selected_files as $selected_file) {
            include '../included/delete_file.php';
        }
    
        // upload new files
        $entity_id = $client_id;
        include '../included/upload.php';
    
        // Add audit log
        include '../included/audit.php';
        $id = $client_id;
        $type = 'Edit';
        $audit_agent = $new_agent_id;
        $jsonDumpOfForm = json_encode($_POST);
        logAudit($id, $type, $audit_agent, $jsonDumpOfForm);
    
        // Redirect back to dashboard after submission
        header("Location: ../main/dashboard.php");
        exit;
    }
    

?>

<!DOCTYPE html>
<html>
  <head>
    <title>Edit Client</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="../css/add.css">
    <link rel="stylesheet" href="../css/sidenav.css">
  </head>
  <body>
    <!-- jQuery library -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 library -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Fixes x in multiple selection -->
    <style>
      .select2-selection__choice__remove {
          margin-top: -1.5px !important; 
      }
    </style>

    <?php include("../nav/sidenav.php"); ?>

    <!-- Database submission form -->
    <div id="content" class="content">
        <h2>Edit <?php echo $client_id?></h2>
        <form action="edit_client.php" method="POST" class="client-form" enctype="multipart/form-data">
            <?php if (!empty($client_details)) {
            $client = $client_details[0];  ?>
                <div class="form-group1">
                    <label for="client_id"><span class="required">*</span>Client ID</label>
                    <input class="view-input" type="text" id="client_id" name="client_id" value="<?php echo $client['client_id']; ?>" readonly>
                </div>

                <div class="form-group2">
                    <label for="name"><span class="required">*</span>Name</label>
                    <input type="text" id="name" name="name" value="<?php echo $client['client_name']; ?>"required>
                </div>

                <div class="form-group1">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo $client['email']; ?>">
                </div>

                <div class="form-group2">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo $client['phone_num']; ?>">
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="4" ><?php echo $client['address']; ?></textarea>
                </div>

                <div class="form-group">
                    <h3>Lawyer Information</h3>
                    <input type="hidden" id="lawyer-id" name="lawyer-id" value="<?php 
                    // get old lawyer or create new id if one did not previously exist
                    if (!empty($client['lawyer'])){
                        echo $client['lawyer'];
                    }
                    else {
                        $unique_lawyer_id = "LAWYER-" . uniqid();
                        echo $unique_lawyer_id;
                    }; ?>">

                    <div class="sub-form-group">
                        <label for="lawyer-name">Lawyer Name</label>
                        <input type="text" id="lawyer-name" name="lawyer-name" value="<?php echo $client['lawyer_name']; ?>" >
                    </div>

                    <div class="sub-form-group">
                        <label for="lawyer-email">Lawyer Email</label>
                        <input type="email" id="lawyer-email" name="lawyer-email" value="<?php echo $client['lawyer_email']; ?>" >
                    </div>
                
                    <div class="sub-form-group">
                        <label for="lawyer-ph">Lawyer Phone Number</label>
                        <input type="tel" id="lawyer-ph" name="lawyer-ph" value="<?php echo $client['lawyer_ph']; ?>" >
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="4"><?php echo $client['notes']; ?></textarea>
                </div>

                <div class="form-group">
                <label for="related_tags">Organizational Tags</label>
                <select id="related_tags" name="related_tags[]" class="multiple-tags" style="width: 100%;" multiple="multiple">
                <option value=""></option>
                <?php foreach ($assoc_tags as $tag) { ?>
                    <option value="<?php echo $tag['name']; ?>" selected><?php echo $tag['name']; ?></option>
                <?php } ?>
                </select>
                </div>

                <div class="form-group">
                    <label for="media">Additional Media</label>
                    <input type="file" id="media" name="media[]" style="width: 35%;" multiple>
                </div>
                <?php 
                    if ($result_assoc_files->num_rows > 0) {
                        echo "<h3>Attached Files:</h3>";
                        echo "<p class='remove-message'>Select any files you'd like to REMOVE by clicking the checkbox to the left of the file name. All selected files will be deleted upon hitting submit. </p>";
                        echo "<ul>";
                        foreach ($assoc_files as $file) {
                            $file_id = $file['file_id'];
                            $fileName = $file['fileName'];
                            $fileURL = "../included/download.php?file_id=" . $file_id; // Link to a script that handles file download
                            echo "<li><input type='checkbox' name='selected_files[]' class='file-checkbox' value=$fileName > <a href='$fileURL'>$fileName</a></li>";
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
                    <input type="text" id="ud1" name="ud1" value="<?php echo $client['ud1']; ?>" >
                </div> 
                
                <div class="form-group2">
                    <label for="ud2">Field 2</label>
                    <input type="text" id="ud2" name="ud2" value="<?php echo $client['ud2']; ?>" >
                </div>
                
                <div class="form-group1">
                    <label for="ud3"> Field 3</label>
                    <input type="text" id="ud3" name="ud3" value="<?php echo $client['ud3']; ?>" >
                </div>
                
                <div class="form-group2">
                    <label for="ud4">Field 4</label>
                    <input type="text" id="ud4" name="ud4" value="<?php echo $client['ud4']; ?>" >
                </div>

                <div class="form-group">
                    <label for="related_cases">Associated Cases</label>
                    <select id="related_cases" name="related_cases[]" class="js-example-basic-multiple-cases" multiple="multiple" style="width: 44%;">
                    <option value=""></option>
                    <?php foreach ($assoc_cases as $case) { ?>
                        <option value="<?php echo $case['title']; ?>" selected><?php echo $case['title']; ?></option>
                    <?php } ?>
                    </select>
                </div>

                <div class="form-group1">
                    <label for="day_modified"><span class="required">*</span>Date Modified</label>
                    <input class="view-input" type="datetime-local" id="day_modified" name="day_modified" value="<?php echo $new_date; ?>" readonly>
                </div>

                <div class="form-group2">
                    <label for="modified_by"><span class="required">*</span>Modified By:</label>
                    <input class="view-input" type="text" id="modified_by" name="modified_by" value="<?php echo $name; ?>" readonly>
                </div>

                <div class="form-group">
                    <button type="submit" class="submit-btn">Submit Changes</button>
                    <a class="discard-btn" href='delete.php?entity_type=client&entity_id=<?php echo $client_id?>' onclick="return confirm('Are you sure you want to delete this entry from the entire database? This action is irreversible.')">Delete Entity</a>
                </div>
            <?php } ?>
        </form>
    </div>

    <!-- Select 2 initialization for dropdown menus -->
    <script>
        $(document).ready(function() {
            // cases input field
            $('.js-example-basic-multiple-cases').select2({
                placeholder: 'Select cases...',
                data: <?php echo json_encode($case_titles); ?>,
            });

            // multiple tags input field
            $('.multiple-tags').select2({
                placeholder: 'Select tags...',
                data: <?php echo json_encode($tag_names); ?>,
            });
        });
    </script>
  </body>
</html>