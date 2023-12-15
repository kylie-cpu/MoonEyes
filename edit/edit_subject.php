<?php
    session_start();
    if (!isset($_SESSION['user'])) {
        header("Location: ../login/login-form.php");
        exit();
    }
    $user = $_SESSION['user'];
    $name = $user['name'];

    $subject_id = $_GET['subject_id'];
    $new_agent_id = $user['agent_id'];

    include('../database/connection.php');

    // Create date for date modified field
    date_default_timezone_set('America/Detroit');
    $new_date = date('Y-m-d H:i:s');

    // Populate dropdowns
    include('../included/dropdowns.php');

    // get subject details from subjects
    $query_subject_details = "SELECT subjects.*, agents.name AS mod_agent, lawyers.lawyer_name AS lawyer_name, lawyers.lawyer_email AS lawyer_email, lawyers.lawyer_ph AS lawyer_ph
    FROM subjects
    LEFT JOIN agents on subjects.modified_by = agents.agent_id
    LEFT JOIN lawyers ON subjects.lawyer = lawyers.lawyer_id
    WHERE subjects.subject_id = '$subject_id'
    GROUP BY subjects.subject_id";

    $result_subject_details = $conn->query($query_subject_details);
    $subject_details = $result_subject_details->fetch_all(MYSQLI_ASSOC);

    // get associated cases 
    $assoc_cases = [];
    $query_assoc_cases = "SELECT cases.title
    FROM cases
    LEFT JOIN case_subject ON cases.case_id = case_subject.case_id
    WHERE case_subject.subject_id = '$subject_id'
    GROUP BY cases.case_id";
    $result_assoc_cases = $conn->query($query_assoc_cases);
    $assoc_cases = $result_assoc_cases->fetch_all(MYSQLI_ASSOC);

    // get names of associated TAGS
    $query_assoc_tags = "SELECT name 
    FROM tags
    LEFT JOIN tag_assoc ON tags.tag_id = tag_assoc.tag_id
    WHERE tag_assoc.assoc_id ='$subject_id'
    GROUP BY tag_assoc.tag_id";
    $result_assoc_tags = $conn->query($query_assoc_tags);
    if ($result_assoc_tags) {
        $assoc_tags = $result_assoc_tags->fetch_all(MYSQLI_ASSOC);
    }

    $query_assoc_files = "SELECT file_id, fileName FROM files WHERE entity_id = '$subject_id'";
    $result_assoc_files = $conn->query($query_assoc_files);
    if ($result_assoc_files) {
        $assoc_files = $result_assoc_files->fetch_all(MYSQLI_ASSOC);
    }

    // if submit is clicked
    if ($_POST) {
        $subject_id = $_POST['subject_id'];
        $subject_name = $_POST['name'];
        $address = $_POST['address'];
        $phone_nums = $_POST['phone_numbers'];
        $lawyer_name = $_POST['lawyer-name'];
        $lawyer_email = $_POST['lawyer-email'];
        $lawyer_id = $_POST['lawyer-id'];
        $lawyer_ph = $_POST['lawyer-ph'];
        $notes = $_POST['notes'];
        $ud1 = $_POST['ud1'];
        $ud2 = $_POST['ud2'];
        $ud3 = $_POST['ud3'];
        $ud4 = $_POST['ud4'];
        $gps = $_POST['gps'];
    
        $modified_by = $new_agent_id;
        $day_modified = $new_date;
        $vehicle_info = preg_replace("/'/", "", $_POST['vehicle_info']);
        $pow = preg_replace("/'/", "", $_POST['place_of_work']);
        $associates = $_POST['associates'];
    
        // update subjects table with prepared statement
        $update_subject_details = $conn->prepare("UPDATE subjects SET 
            subject_name = ?,
            address = ?,
            phone_nums = ?,
            associates = ?,
            vehicle_info = ?,
            place_of_work = ?,
            notes = ?,
            lawyer = ?,
            modified_by = ?,
            day_modified = ?,
            ud1 = ?,
            ud2 = ?,
            ud3 = ?,
            ud4 = ?,
            gps = ?
            WHERE subject_id = ?");
    
        $update_subject_details->bind_param("ssssssssssssssss", $subject_name, $address, $phone_nums, $associates, $vehicle_info, $pow, $notes, $lawyer_id, $modified_by, $day_modified, $ud1, $ud2, $ud3, $ud4, $gps, $subject_id);
    
        if (!$update_subject_details->execute()) {
            echo "Error updating subject details: " . $update_subject_details->error;
            exit();
        }
    
        // delete old cases entries
        $delete_old_cases = $conn->prepare("DELETE FROM case_subject WHERE subject_id = ?");
        $delete_old_cases->bind_param("s", $subject_id);
        $delete_old_cases->execute();
    
        // insert new into case_subject table with prepared statement
        $insert_case_subject = $conn->prepare("INSERT INTO case_subject(case_id, subject_id) VALUES (?, ?)");
        $insert_case_subject->bind_param("ss", $case_id, $subject_id);
    
        $related_cases = $_POST['related_cases'];
        foreach ($related_cases as $related_case) {
            $query = $conn->prepare("SELECT case_id FROM cases WHERE title = ?");
            $query->bind_param("s", $related_case);
            $query->execute();
            $result = $query->get_result();
            if ($result->num_rows > 0) {
                $case_id = $result->fetch_assoc()['case_id'];
                $insert_case_subject->execute();
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
        $delete_tags->bind_param("s", $subject_id);
        $delete_tags->execute();
    
        $insert_tag_assoc = $conn->prepare("INSERT INTO tag_assoc(tag_id, assoc_id) VALUES (?, ?)");
        $insert_tag_assoc->bind_param("ss", $tag_id, $subject_id);
    
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
        $entity_id = $subject_id;
        foreach ($selected_files as $selected_file) {
            include '../included/delete_file.php';
        }
    
        // upload new files
        $entity_id = $subject_id;
        include '../included/upload.php';
    
        // Add audit log
        include '../included/audit.php';
        $id = $subject_id;
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
        <title>Edit Subject</title>
        <!-- Select 2 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <link rel="stylesheet" type="text/css" href="../css/add.css">
        <link rel="stylesheet" href="../css/sidenav.css">
    </head>
    <body>
        <!-- jQuery lib -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- Select2 lib -->
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
            <h2> Edit <?php echo $subject_id ?> </h2>
            <form action="edit_subject.php" method="POST" class="subject-form" enctype="multipart/form-data">
                <?php if (!empty($subject_details)) {
                $subject = $subject_details[0]; ?>
                    <div class="form-group1">
                        <label for="subject_id"><span class="required">*</span>Subject ID</label>
                        <input class="view-input" type="text" id="subject_id" name="subject_id" value="<?php echo $subject['subject_id']; ?>" readonly>
                    </div>

                    <div class="form-group2">
                        <label for="name"><span class="required">*</span>Name</label>
                        <input type="text" id="name" name="name" value="<?php echo $subject['subject_name']; ?>">
                    </div>

                    <div class="form-group">
                        <label for="address">Address:</label>
                        <textarea id="address" name="address" rows="4"> <?php echo $subject['address']; ?> </textarea>
                    </div>

                    <div class="form-group">
                        <label for="phone_numbers">Phone Numbers:</label>
                        <textarea id="phone_numbers" name="phone_numbers" rows="4"><?php echo $subject['phone_nums']; ?></textarea>
                    </div>

                    <div class="form-group1">
                        <label for="associates">Associates:</label>
                        <textarea id="associates" name="associates" rows="3"><?php echo $subject['associates']; ?></textarea>
                    </div>

                    <div class="form-group2">
                        <label for="place_of_work">Place of Work</label>
                        <textarea id="place_of_work" name="place_of_work" rows="3"><?php echo $subject['place_of_work']; ?></textarea>
                    </div>

                    <div class="form-group1">
                        <label for="gps">GPS Tracking</label>
                        <textarea id="gps" name="gps" rows="3"><?php echo $subject['gps']; ?></textarea>
                    </div>
                    
                    <div class="form-group2">
                        <label for="vehicle_info">Vehicle Information:</label>
                        <textarea id="vehicle_info" name="vehicle_info" rows="3"><?php echo $subject['vehicle_info']; ?></textarea>
                    </div>

                    <div class="form-group">
                    <h3>Lawyer Information:</h3>
                    <input type="hidden" id="lawyer-id" name="lawyer-id" value="<?php 
                    // get old lawyer or create new id if one did not previously exist
                    if (!empty($subject['lawyer'])){
                        echo $subject['lawyer'];
                    }
                    else {
                        $unique_lawyer_id = "LAWYER-" . uniqid();
                        echo $unique_lawyer_id;
                    }; ?>">

                    <div class="sub-form-group">
                        <label for="lawyer-name">Lawyer Name:</label>
                        <input type="text" id="lawyer-name" name="lawyer-name" value="<?php echo $subject['lawyer_name']; ?>">
                    </div>

                    <div class="sub-form-group">
                        <label for="lawyer-email">Lawyer Email:</label>
                        <input type="email" id="lawyer-email" name="lawyer-email" value="<?php echo $subject['lawyer_email']; ?>">
                    </div>

                    <div class="sub-form-group">
                        <label for="lawyer-ph">Lawyer Phone Number</label>
                        <input type="tel" id="lawyer-ph" name="lawyer-ph" value="<?php echo $subject['lawyer_ph']; ?>">
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="4"><?php echo $subject['notes']; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="related_tags">Organizational Tags</label>
                        <select id="related_tags" name="related_tags[]" class="multiple-tags" style="width: 100%;" multiple="multiple">
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
                                echo "<li><input type='checkbox' name='selected_files[]' class='file-checkbox' value='$fileName' > <a href='$fileURL'>$fileName</a></li>";
                            }
                            echo "</ul>";
                    
                        } else {
                            echo "<ul>";
                            echo "No attached files for this subject.";
                            echo "</ul>";
                        }
                    ?>

                    <div class="form-group1">
                        <label for="ud1">Field 1</label>
                        <input type="text" id="ud1" name="ud1" value="<?php echo $subject['ud1']; ?>">
                    </div> 
                    
                    <div class="form-group2">
                        <label for="ud2">Field 2</label>
                        <input type="text" id="ud2" name="ud2" value="<?php echo $subject['ud2']; ?>">
                    </div>
                    
                    <div class="form-group1">
                        <label for="ud3"> Field 3</label>
                        <input type="text" id="ud3" name="ud3" value="<?php echo $subject['ud3']; ?>">
                    </div>
                    
                    <div class="form-group2">
                        <label for="ud4">Field 4</label>
                        <input type="text" id="ud4" name="ud4" value="<?php echo $subject['ud4']; ?>" >
                    </div>

                    <div class="form-group">
                        <label for="related_cases">Associated Cases:</label>
                        <select id="related_cases" name="related_cases[]" class="js-example-basic-multiple-cases" multiple="multiple" style="width: 44%;">
                        <option value=""></option>
                            <?php foreach ($assoc_cases as $case) { ?>
                                <option value="<?php echo $case['title']; ?>" selected><?php echo $case['title']; ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group1">
                        <label for="day_modified"><span class="required">*</span>Date Modified:</label>
                        <input class="view-input" type="datetime-local" id="day_modified" name="day_modified" value="<?php echo $subject['day_modified']; ?>" readonly>
                    </div>

                    <div class="form-group2">
                        <label for="modified_by"><span class="required">*</span>Modified By:</label>
                        <input class="view-input" type="text" id="modified_by" name="modified_by" value="<?php echo $subject['mod_agent']; ?>" readonly>
                    </div>
                    

                    <div class="form-group">
                        <button type="submit" class="submit-btn">Submit Changes</button>
                        <a class="discard-btn" href='delete.php?entity_type=subject&entity_id=<?php echo $subject_id?>' onclick="return confirm('Are you sure you want to delete this entry from the entire database? This action is irreversible.')">Delete Entity</a>
                    </div>
                <?php } ?>
            </form>
        </div>

        <!-- Select 2 initialization for dropdown menu -->
        <script>
            // Add multiple cases
            $(document).ready(function() {
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