<?php
    session_start();
    $user = $_SESSION['user'];
    $name = $user[0]['name'];

    $subject_id = $_GET['subject_id'];
    $new_agent_id = $user[0]['agent_id'];

    include('database/connection.php');

    // Create date for date modified field
    date_default_timezone_set('America/Detroit');
    $new_date = date('Y-m-d H:i:s');

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
    $assoc_cases = [];
    $query_assoc_cases = "SELECT cases.title
    FROM cases
    LEFT JOIN case_subject ON cases.case_id = case_subject.case_id
    WHERE case_subject.subject_id = '$subject_id'
    GROUP BY cases.case_id";
    $result_assoc_cases = $conn->query($query_assoc_cases);
    $assoc_cases = $result_assoc_cases->fetch_all(MYSQLI_ASSOC);

    // if submit button is clicked
    if ($_POST) {
        $subject_id = $_POST['subject_id'];
        $subject_name = $_POST['name'];
        $address = $_POST['address'];
        $phone_nums = $_POST['phone_numbers'];
        $lawyer_name = $_POST['lawyer-name'];
        $lawyer_email =  $_POST['lawyer-email'];
        $lawyer_id = $_POST['lawyer-id'];
        // regex out single quotes from notes...
        $notes = preg_replace("/'/", "", $_POST['notes']);
        $modified_by = $new_agent_id;
        $day_modified = $new_date;
        $vehicle_info = preg_replace("/'/", "", $_POST['vehicle_info']);
        $pow = preg_replace("/'/", "", $_POST['place_of_work']);
        $associates = $_POST['associates'];

        // update subjects table
        $update_subject_details = "UPDATE subjects SET 
        subject_name = '$subject_name',
        address = '$address',
        phone_nums = '$phone_nums',
        associates = '$associates',
        vehicle_info = '$vehicle_info',
        place_of_work ='$pow',
        notes = '$notes',
        lawyer = '$lawyer_id',
        modified_by = '$modified_by',
        day_modified = '$day_modified'
        WHERE subject_id = '$subject_id'";

        $conn->query($update_subject_details); 

        // delete old cases entries
        $delete_old_cases = "DELETE FROM case_subject WHERE subject_id = '$subject_id'";
        $conn->query($delete_old_cases); 

        // insert new into case_subject table
        $related_cases = $_POST['related_cases'];
        foreach ($related_cases as $related_case) {
          $query = "SELECT case_id FROM cases WHERE title = '$related_case'";
          $result = $conn->query($query);
          if ($result->num_rows > 0) {
            $case_id = $result->fetch_assoc()['case_id'];
          }
          $insert_case_subject = "INSERT INTO case_subject(case_id, subject_id) VALUES ('$case_id', '$subject_id')";
          if ($conn->query($insert_case_subject) !== TRUE) {
              echo "Error inserting into case_subject";
          }
        }

        // update lawyers table
        $delete_existing_lawyers = "DELETE FROM lawyers WHERE lawyer_id = '$lawyer_id'";
        $conn->query($delete_existing_lawyers);
        $insert_lawyer_details = "INSERT INTO lawyers (lawyer_id, lawyer_name, lawyer_email) VALUES ('$lawyer_id', '$lawyer_name', '$lawyer_email')";
        $conn->query($insert_lawyer_details);

        // Redirect back to dashboard after submission
        header("Location: dashboard.php"); 
        exit;
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Edit Subject</title>
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
            <h2> Edit <?php echo $subject_id ?> </h2>
            <form action="edit_subject.php" method="POST" class="subject-form">
                <?php if (!empty($subject_details)) {
                $subject = $subject_details[0]; ?>
                    <div class="form-group">
                        <label for="subject_id"><span class="required">*</span>Subject ID:</label>
                        <input type="text" id="subject_id" name="subject_id" value="<?php echo $subject['subject_id']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="name"><span class="required">*</span>Name:</label>
                        <input type="text" id="name" name="name" value="<?php echo $subject['subject_name']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="address">Address:</label>
                        <textarea id="address" name="address" rows="4" > <?php echo $subject['address']; ?> </textarea>
                    </div>

                    <div class="form-group">
                        <label for="phone_numbers">Phone Numbers:</label>
                        <textarea id="phone_numbers" name="phone_numbers" rows="4" ><?php echo $subject['phone_nums']; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="associates">Associates:</label>
                        <textarea id="associates" name="associates" rows="4"  ><?php echo $subject['associates']; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="vehicle_info">Vehicle Information:</label>
                        <textarea id="vehicle_info" name="vehicle_info" rows="4" ><?php echo $subject['vehicle_info']; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="place_of_work">Place of Work:</label>
                        <input type="text" id="place_of_work" name="place_of_work" value="<?php echo $subject['place_of_work']; ?>">
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
                </div>

                    <div class="form-group">
                        <label for="notes">Notes:</label>
                        <textarea id="notes" name="notes" rows="4"><?php echo $subject['notes']; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="organization_tags">Organization Tags:</label>
                        <input type="text" id="organization_tags" name="organization_tags">
                    </div>

                    <div class="form-group">
                        <label for="media">Additional Media:</label>
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
                        <button type="submit" class="submit-btn">Submit</button>
                        <a href="dashboard.php" class="discard-btn" onclick="return confirm('Are you sure you want to discard? No data will be saved.')">Discard</a>
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