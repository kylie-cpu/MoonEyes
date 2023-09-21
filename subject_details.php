<?php
    session_start();
    $user = $_SESSION['user'];
    $name = $user[0]['name'];

    $subject_id = $_GET['subject_id'];

    // Initialize unique ID for subject & lawyer and also initizialize agent_id for modified_by field
    $unique_subject_id = "SUBJECT-" . uniqid();
    $unique_lawyer_id = "LAWYER-" . uniqid();
    $agent_id = $user[0]['agent_id'];

    include('database/connection.php');

    // Create date for date modified field
    date_default_timezone_set('America/Detroit');
    $date = date('Y-m-d H:i:s');

    // Populate dropdowns
    include('dropdowns.php');

    // get subject details from subjects
    $query_subject_details = "SELECT subjects.*, agents.name AS mod_agent, lawyers.lawyer_name AS lawyer_name, lawyers.lawyer_email AS lawyer_email
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
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Subject Details</title>
        <!-- Select 2 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <link rel="stylesheet" type="text/css" href="css/add.css">
        <link rel="stylesheet" href="css/sidenav.css">
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

        <?php include("sidenav.php"); ?>

        <!-- Database submission form -->
        <div id="content" class="content">
            <h2> <?php echo $subject_id ?> Details</h2>
            <form action="subject_details.php" method="POST" class="subject-form">
                <?php if (!empty($subject_details)) {
                $subject = $subject_details[0]; ?>
                    <div class="form-group">
                        <label for="subject_id"><span class="required">*</span>Subject ID:</label>
                        <input type="text" id="subject_id" name="subject_id" value="<?php echo $subject['subject_id']; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="name"><span class="required">*</span>Name:</label>
                        <input type="text" id="name" name="name" value="<?php echo $subject['subject_name']; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="address">Address:</label>
                        <textarea id="address" name="address" rows="4" readonly> <?php echo $subject['address']; ?> </textarea>
                    </div>

                    <div class="form-group">
                        <label for="phone_numbers">Phone Numbers:</label>
                        <textarea id="phone_numbers" name="phone_numbers" rows="4" readonly><?php echo $subject['phone_nums']; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="associates">Associates:</label>
                        <textarea id="associates" name="associates" rows="4"  readonly><?php echo $subject['associates']; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="vehicle_info">Vehicle Information:</label>
                        <textarea id="vehicle_info" name="vehicle_info" rows="4" readonly><?php echo $subject['vehicle_info']; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="place_of_work">Place of Work:</label>
                        <input type="text" id="place_of_work" name="place_of_work" value="<?php echo $subject['place_of_work']; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <h3>Lawyer Information:</h3>
                        <div class="sub-form-group">
                            <label for="lawyer-name">Lawyer Name:</label>
                            <input type="text" id="lawyer-name" name="lawyer-name" value="<?php echo $subject['lawyer_name']; ?>" readonly>
                        </div>

                        <div class="sub-form-group">
                            <label for="lawyer-email">Lawyer Email:</label>
                            <input type="email" id="lawyer-email" name="lawyer-email" value="<?php echo $subject['lawyer_email']; ?>" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes:</label>
                        <textarea id="notes" name="notes" rows="4" readonly><?php echo $subject['notes']; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="organization_tags">Organization Tags:</label>
                        <input type="text" id="organization_tags" name="organization_tags" readonly>
                    </div>

                    <div class="form-group">
                        <label for="media">Additional Media</label>
                        <input type="file" id="media" name="media">
                    </div>

                    <div class="form-group">
                        <label for="related_cases">Associated Cases:</label>
                        <select id="related_cases" name="related_cases[]" class="js-example-basic-multiple-cases" multiple="multiple" style="width: 100%;">
                            <?php foreach ($assoc_cases as $case) { ?>
                                <option value="<?php echo $case['title']; ?>" selected><?php echo $case['title']; ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="day_modified"><span class="required">*</span>Date Modified:</label>
                        <input type="datetime-local" id="day_modified" name="day_modified" value="<?php echo $subject['day_modified']; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="modified_by"><span class="required">*</span>Modified By:</label>
                        <input type="text" id="modified_by" name="modified_by" value="<?php echo $subject['mod_agent']; ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <a href="edit_subject.php?subject_id=<?php echo $subject['subject_id']; ?>" class="edit-btn">Edit</a>
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
                    placeholder: 'Select cases...',
                    data: <?php echo json_encode($case_titles); ?>,
                });
            });
        </script>
  </body>
</html>