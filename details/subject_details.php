<?php
    session_start();
    $user = $_SESSION['user'];
    $name = $user['name'];

    $subject_id = $_GET['subject_id'];

    // Initialize unique ID for subject & lawyer and also initizialize agent_id for modified_by field
    $unique_subject_id = "SUBJECT-" . uniqid();
    $unique_lawyer_id = "LAWYER-" . uniqid();
    $agent_id = $user['agent_id'];

    include('../database/connection.php');

    // Create date for date modified field
    date_default_timezone_set('America/Detroit');
    $date = date('Y-m-d H:i:s');

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
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Subject Details</title>
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
            <h2> <?php echo $subject_id ?> Details</h2>
            <form action="subject_details.php" method="POST" class="subject-form">
                <?php if (!empty($subject_details)) {
                $subject = $subject_details[0]; ?>
                    <div class="form-group1">
                        <label for="subject_id"><span class="required">*</span>Subject ID</label>
                        <input class="view-input" type="text" id="subject_id" name="subject_id" value="<?php echo $subject['subject_id']; ?>" readonly>
                    </div>

                    <div class="form-group2">
                        <label for="name"><span class="required">*</span>Name</label>
                        <input class="view-input" type="text" id="name" name="name" value="<?php echo $subject['subject_name']; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="address">Address:</label>
                        <textarea class="view-input" id="address" name="address" rows="4" readonly> <?php echo $subject['address']; ?> </textarea>
                    </div>

                    <div class="form-group">
                        <label for="phone_numbers">Phone Numbers:</label>
                        <textarea class="view-input" id="phone_numbers" name="phone_numbers" rows="4" readonly><?php echo $subject['phone_nums']; ?></textarea>
                    </div>

                    <div class="form-group1">
                        <label for="associates">Associates:</label>
                        <textarea class="view-input" id="associates" name="associates" rows="3"  readonly><?php echo $subject['associates']; ?></textarea>
                    </div>

                    <div class="form-group2">
                        <label for="place_of_work">Place of Work</label>
                        <textarea class="view-input" id="place_of_work" name="place_of_work" rows="3" readonly><?php echo $subject['place_of_work']; ?></textarea>
                    </div>

                    <div class="form-group1">
                        <label for="gps">GPS Tracking</label>
                        <textarea class="view-input" id="gps" name="gps" rows="3" readonly><?php echo $subject['gps']; ?></textarea>
                    </div>
                    
                    <div class="form-group2">
                        <label for="vehicle_info">Vehicle Information:</label>
                        <textarea class="view-input" id="vehicle_info" name="vehicle_info" rows="3" readonly><?php echo $subject['vehicle_info']; ?></textarea>
                    </div>

                    <div class="form-group">
                        <h3>Lawyer Information</h3>
                        <div class="sub-form-group">
                            <label for="lawyer-name">Lawyer Name:</label>
                            <input class="view-input" type="text" id="lawyer-name" name="lawyer-name" value="<?php echo $subject['lawyer_name']; ?>" readonly>
                        </div>

                        <div class="sub-form-group">
                            <label for="lawyer-email">Lawyer Email</label>
                            <input class="view-input" type="email" id="lawyer-email" name="lawyer-email" value="<?php echo $subject['lawyer_email']; ?>" readonly>
                        </div>

                       <div class="sub-form-group">
                            <label for="lawyer-ph">Lawyer Phone Number</label>
                            <input class="view-input" type="tel" id="lawyer-ph" name="lawyer-ph" value="<?php echo $subject['lawyer_ph']; ?>" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea class="view-input" id="notes" name="notes" rows="4" readonly><?php echo $subject['notes']; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="related_tags">Organizational Tags</label>
                        <select id="related_tags" name="related_tags[]" class="multiple-tags" style="width: 100%;" multiple="multiple" disabled>
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
                            echo "No attached files for this subject.";
                            echo "</ul>";
                        }
                    ?>

                    <div class="form-group1">
                        <label for="ud1">Field 1</label>
                        <input class="view-input" type="text" id="ud1" name="ud1" value="<?php echo $subject['ud1']; ?>" readonly>
                    </div> 
                    
                    <div class="form-group2">
                        <label for="ud2">Field 2</label>
                        <input class="view-input" type="text" id="ud2" name="ud2" value="<?php echo $subject['ud2']; ?>" readonly>
                    </div>
                    
                    <div class="form-group1">
                        <label for="ud3"> Field 3</label>
                        <input class="view-input" type="text" id="ud3" name="ud3" value="<?php echo $subject['ud3']; ?>" readonly>
                    </div>
                    
                    <div class="form-group2">
                        <label for="ud4">Field 4</label>
                        <input class="view-input" type="text" id="ud4" name="ud4" value="<?php echo $subject['ud4']; ?>" readonly>
                    </div>


                    <div class="form-group">
                        <label for="related_cases">Associated Cases:</label>
                        <select id="related_cases" name="related_cases[]" class="js-example-basic-multiple-cases" multiple="multiple" style="width: 44%;" disabled>
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
                    </div><br><br>
                    
                    <div class="form-group">
                        <a href="../edit/edit_subject.php?subject_id=<?php echo $subject['subject_id']; ?>" class="edit-btn">Edit</a>
                        <a href=# onclick="window.print();" class="print-btn">Print/PDF</a>
                    </div>
                <?php } ?>
            </form>
        </div>

        <!-- Select 2 initialization for dropdown menu -->
        <script>
            // Add multiple cases
            $(document).ready(function() {
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