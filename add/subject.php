<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login/login-form.php");
    exit();
}

$user = $_SESSION['user'];
$name = $user['name'];

// Initialize unique ID for subject & lawyer and also initialize agent_id for modified_by field
$unique_subject_id = "SUBJECT-" . uniqid();
$unique_lawyer_id = "LAWYER-" . uniqid();
$agent_id = $user['agent_id'];

include('../database/connection.php');

// Create date for date modified field
date_default_timezone_set('America/Detroit');
$date = date('Y-m-d H:i:s');

include('../included/dropdowns.php');

if ($_POST) {
    // Submit form into subjects/lawyer database
    $subject_id = $unique_subject_id;
    $subject_name = $_POST['name'];
    $address = $_POST['address'];
    $phone_nums = $_POST['phone_numbers'];
    $lawyer_id = $unique_lawyer_id;
    $lawyer_name = $_POST['lawyer-name'];
    $lawyer_email = $_POST['lawyer-email'];
    $lawyer_ph = $_POST['lawyer-ph'];
    $notes = $_POST['notes'];
    $ud1 = $_POST['ud1'];
    $ud2 = $_POST['ud2'];
    $ud3 = $_POST['ud3'];
    $ud4 = $_POST['ud4'];
    $gps = $_POST['gps'];
    $modified_by = $agent_id;
    $day_modified = $date;
    $vehicle_info = $_POST['vehicle_info'];
    $pow = $_POST['place_of_work'];
    $associates = $_POST['associates'];

    // Prepare and bind stmt for subjects
    $insert_subject = $conn->prepare("INSERT INTO subjects (subject_id, subject_name, address, phone_nums, lawyer, notes, ud1, ud2, ud3, ud4, gps, modified_by, day_modified, vehicle_info, place_of_work, associates) 
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insert_subject->bind_param("ssssssssssssssss", $subject_id, $subject_name, $address, $phone_nums, $lawyer_id, $notes, $ud1, $ud2, $ud3, $ud4, $gps, $modified_by, $day_modified, $vehicle_info, $pow, $associates);

    // Check if successfully inserted
    if (!$insert_subject->execute()) {
        echo "Error inserting into subjects";
    }

    // Submit data into lawyer if lawyer email or name entered
    if (!empty($lawyer_name) || !empty($lawyer_email)) {
        // Prepare and bind lawyers
        $insert_lawyer = $conn->prepare("INSERT INTO lawyers (lawyer_id, lawyer_name, lawyer_email, lawyer_ph) 
                                         VALUES (?, ?, ?, ?)");
        $insert_lawyer->bind_param("ssss", $lawyer_id, $lawyer_name, $lawyer_email, $lawyer_ph);

        if (!$insert_lawyer->execute()) {
            echo "Error inserting into lawyers";
        }
    }

    // Prepare and bind  for case_subject 
    $insert_case_subject = $conn->prepare("INSERT INTO case_subject (case_id, subject_id) VALUES (?, ?)");

    // Submit data in case_subject
    $related_cases = $_POST['related_cases'];
    foreach ($related_cases as $related_case) {
        $query = $conn->prepare("SELECT case_id FROM cases WHERE title = ?");
        $query->bind_param("s", $related_case);
        $query->execute();
        $result = $query->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $Rcase_id = $row['case_id'];
        }

        $insert_case_subject->bind_param("ss", $Rcase_id, $subject_id);

        if (!$insert_case_subject->execute()) {
            echo "Error inserting into case_subject";
        }
    }

    // Prepare and bind the INSERT statement for tag_assoc table
    $insert_tag_assoc = $conn->prepare("INSERT INTO tag_assoc (tag_id, assoc_id) VALUES (?, ?)");

    // Insert into tag_assoc table
    $related_tags = $_POST['related_tags'];
    foreach ($related_tags as $related_tag) {
        $query = $conn->prepare("SELECT tag_id FROM tags WHERE name = ?");
        $query->bind_param("s", $related_tag);
        $query->execute();
        $result = $query->get_result();

        if ($result->num_rows > 0) {
            $tag_id = $result->fetch_assoc()['tag_id'];
        }

        $insert_tag_assoc->bind_param("ss", $tag_id, $subject_id);

        if (!$insert_tag_assoc->execute()) {
            echo "Error inserting into tag_assoc";
        }
    }

    // For file uploads
    $entity_id = $subject_id;
    include '../included/upload.php';

    // Add audit log
    include '../included/audit.php';
    $id = $subject_id;
    $type = 'Add';
    $audit_agent = $agent_id;
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
    <title>Add a Subject</title>
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
    <div id="content"class="content">
      <h2>Add a Subject</h2>
      <form action="subject.php" method="POST" class="subject-form" enctype="multipart/form-data">
        <div class="form-group1">
          <label for="subject_id"><span class="required">*</span>Subject ID</label>
          <input class="view-input" type="text" id="subject_id" name="subject_id" value="<?php echo $unique_subject_id ?>"readonly>
        </div>

        <div class="form-group2">
          <label for="name"><span class="required">*</span>Name</label>
          <input type="text" id="name" name="name" required>
        </div>

        <div class="form-group">
          <label for="address">Address</label>
          <textarea id="address" name="address" rows="4"></textarea>
        </div>

        <div class="form-group">
          <label for="phone_numbers">Phone Numbers</label>
          <textarea id="phone_numbers" name="phone_numbers" rows="3"></textarea>
        </div>

        <div class="form-group1">
          <label for="associates">Associates</label>
          <textarea id="associates" name="associates" rows="3"></textarea>
        </div>

        <div class="form-group2">
          <label for="place_of_work">Place of Work</label>
          <textarea id="place_of_work" name="place_of_work" rows="3"></textarea>
        </div>

        <div class="form-group1">
          <label for="gps">GPS Tracking</label>
          <textarea id="gps" name="gps" rows="3"></textarea>
        </div>

        <div class="form-group2">
          <label for="vehicle_info">Vehicle Information</label>
          <textarea id="vehicle_info" name="vehicle_info" rows="3"></textarea>
        </div>

        <div class="form-group">
          <h3>Lawyer Information</h3>
          <div class="sub-form-group">
            <label for="lawyer-name">Lawyer Name</label>
            <input type="text" id="lawyer-name" name="lawyer-name">
          </div>

          <div class="sub-form-group">
            <label for="lawyer-email">Lawyer Email</label>
            <input type="email" id="lawyer-email" name="lawyer-email">
          </div>

          <div class="sub-form-group">
            <label for="lawyer-ph">Lawyer Phone Number</label>
            <input type="tel" id="lawyer-ph" name="lawyer-ph">
          </div>
        </div>

        <div class="form-group">
          <label for="related_tags">Organizational Tags</label>
          <select id="related_tags" name="related_tags[]" class="multiple-tags" style="width: 100%;" multiple="multiple">
            <option value=""></option>
          </select>
        </div>

        <div class="form-group">
          <label for="media">Additional Media</label>
          <input type="file" id="media" name="media[]" style="width: 35%;" multiple>
        </div>

        <div class="form-group">
          <label for="notes">Notes</label>
          <textarea id="notes" name="notes" rows="4"></textarea>
        </div>

        <div class="form-group1">
          <label for="ud1">Field 1</label>
          <input type="text" id="ud1" name="ud1">
        </div> 
       
	      <div class="form-group2">
          <label for="ud2">Field 2</label>
          <input type="text" id="ud2" name="ud2">
        </div>
       
	      <div class="form-group1">
          <label for="ud3"> Field 3</label>
          <input type="text" id="ud3" name="ud3">
        </div>
       
	      <div class="form-group2">
          <label for="ud4">Field 4</label>
          <input type="text" id="ud4" name="ud4">
        </div>

        <div class="form-group">
          <label for="related_cases">Associated Cases</label>
          <select id="related_cases" name="related_cases[]" class="js-example-basic-multiple-cases" multiple="multiple" style="width: 44%;"></select>
        </div>

        <div class="form-group1">
          <label for="day_modified"><span class="required">*</span>Date Modified</label>
          <input class="view-input" type="datetime-local" id="day_modified" name="day_modified" value="<?php echo $date ; ?>" readonly>
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