<?php
  //check for current session
  session_start();
  $user = $_SESSION['user'];
  $name = $user[0]['name'];

  //Generate unique ID and initizialize agent_id for modified_by field
  $unique_case_id = "CASE-" . uniqid();
  $agent_id = $user[0]['agent_id'];

  //Connect to db
  include('database/connection.php');

  //Create date for date modified field
  date_default_timezone_set('America/Detroit');
  $date = date('Y-m-d H:i:s');

  //Client names for drop down
  $query = "SELECT * FROM clients ORDER BY day_modified DESC";
  $result = $conn->query($query);
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $client_names[] = $row['client_name'];
    }
  }

  //Subject names for drop down
  $query = "SELECT subject_name FROM subjects ORDER BY day_modified DESC";
  $result = $conn->query($query);
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $subject_names[] = $row['subject_name'];
    }
  }

  //Agent names for drop down
  $query = "SELECT name FROM agents ORDER BY modified_at DESC";
  $result = $conn->query($query);
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $agent_names[] = $row['name'];
    }
  }


  if ($_POST) {
    $case_id = $unique_case_id;
    $title = $_POST['title'];
    $purpose = $_POST['purpose'];
    $status = $_POST['status'];
    $invoice = $_POST['invoice_info'];
    // regex out single quotes from notes...
    $notes = preg_replace("/'/", "", $_POST['notes']);
    $modified_by = $agent_id;
    $day_modified = $date;


    //Submit form into cases database
    $insert_case = "INSERT INTO cases(case_id, title, purpose, status, invoice, notes, modified_by, day_modified) 
    VALUES ('$case_id', '$title', '$purpose', '$status', '$invoice', '$notes', '$modified_by', '$day_modified')";
    if ($conn->query($insert_case) !== TRUE) {
      echo "Error inserting into cases";
    }

    // Insert into case_client table
    $related_client = $_POST['related_client'];
    $query = "SELECT client_id FROM clients WHERE client_name = '$related_client'";
    $result = $conn->query($query);
    if ($result->num_rows > 0) {
      $client_id = $result->fetch_assoc()['client_id'];
    }
    $insert_case_client = "INSERT INTO case_client(case_id, client_id) VALUES ('$case_id', '$client_id')";
    if ($conn->query($insert_case_client) !== TRUE) {
      echo "Error inserting into case_client";
    }

    // Insert into case_subject table
    $related_subjects = $_POST['related_subjects'];
    foreach ($related_subjects as $related_subject) {
      $query = "SELECT subject_id FROM subjects WHERE subject_name = '$related_subject'";
      $result = $conn->query($query);
      if ($result->num_rows > 0) {
        $subject_id = $result->fetch_assoc()['subject_id'];
      }
      $insert_case_subject = "INSERT INTO case_subject(case_id, subject_id) VALUES ('$case_id', '$subject_id')";
      if ($conn->query($insert_case_subject) !== TRUE) {
          echo "Error inserting into case_subject";
      }
    }

    // Insert into case_agent table
    $related_agents = $_POST['related_agents'];
    foreach ($related_agents as $related_agent) {
      $query = "SELECT agent_id FROM agents WHERE name = '$related_agent'";
      $result = $conn->query($query);
      if ($result->num_rows > 0) {
        $Ragent_id = $result->fetch_assoc()['agent_id'];
      }
      $insert_case_agent = "INSERT INTO case_agent(case_id, agent_id) VALUES ('$case_id', '$Ragent_id')";
      if ($conn->query($insert_case_agent) !== TRUE) {
          echo "Error inserting into case_agent";
      }
    }
    // Redirect back to dashboard after submission
    header("Location: dashboard.php"); 
    exit;
  }

?>

<!-- HTML -->
<!DOCTYPE html>
<html>
  <head>
    <title>Add a Case</title>
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Sidenav and add php css -->
    <link rel="stylesheet" type="text/css" href="css/add.css">
    <link rel="stylesheet" href="css/sidenav.css">
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

    <?php include("sidenav.php"); ?>

    <!-- Database submission form -->
    <div class="content" id="content">
      <h2>Add a Case</h2>
      <form action="case.php" method="POST" class="case-form">
        <div class="form-group">
          <label for="case_id"><span class="required">*</span>Case ID:</label>
          <input type="text" id="case_id" name="case_id" value="<?php echo $unique_case_id ?>" readonly>
        </div>

        <div class="form-group">
          <label for="title"><span class="required">*</span>Title:</label>
          <input type="text" id="title" name="title" required>
        </div>

        <div class="form-group">
          <label for="purpose">Purpose of Case:</label>
          <textarea id="purpose" name="purpose" rows="4"></textarea>
        </div>

        <div class="form-group">
          <label for="status"><span class="required">*</span>Status:</label>
          <select id="status" name="status" style="width: 25%; font-size: large;" readonly>
            <option value="Open">Open</option>
            <option value="Closed">Closed</option>
            <option value="Pending">Pending</option>
          </select>
        </div>

        <div class="form-group">
          <label for="invoice_info">Invoice Information:</label>
          <textarea id="invoice_info" name="invoice_info" rows="4"></textarea>
        </div>

        <div class="form-group">
          <label for="notes">Notes:</label>
          <textarea id="notes" name="notes" rows="4"></textarea>
        </div>

        <div class="form-group">
          <label for="media">Additional Media:</label>
          <input type="file" id="media" name="media">
        </div>

        <div class="form-group">
          <label for="related_client"><span class="required">*</span>Associated Client:</label>
          <select id="related_client" name="related_client" class="js-example-basic-single" style="width: 100%;">
            <option value=""></option>
          </select>
        </div>

        <div class="form-group">
          <label for="related_subjects">Associated Subjects:</label>
          <select id="related_subjects" name="related_subjects[]" class="js-example-basic-multiple-subjects" multiple="multiple" style="width: 100%;">
          </select>
        </div>

        <div class="form-group">
          <label for="related_agents">Associated Agents:</label>
          <select id="related_agents" name="related_agents[]" class="js-example-basic-multiple-agents" multiple="multiple" style="width: 100%;">
          </select>
        </div>

        <div class="form-group">
          <label for="day_modified"><span class="required">*</span>Date Modified:</label>
          <input type="datetime-local" id="day_modified" name="day_modified" value="<?php echo $date ?>" readonly>
        </div>

        <div class="form-group">
          <label for="modified_by"><span class="required">*</span>Modified By:</label>
          <input type="text" id="modified_by" name="modified_by" value="<?php echo $name ?>" readonly>
        </div>
        
        <div class="form-group">
          <button type="submit" class="submit-btn">Submit</button>
          <a href="dashboard.php" class="discard-btn" onclick="return confirm('Are you sure you want to discard? No data will be saved.')">Discard</a>
        </div>
      </form>
    </div>

    <!-- Select 2 initialization for dropdown menus -->
    <script>
      $(document).ready(function() {
        // single client input field
        $('.js-example-basic-single').select2({
          placeholder: 'Select a client...',
          allowClear: true, 
          data: <?php echo json_encode($client_names); ?>,
        });

        // multiple subjects input field
        $('.js-example-basic-multiple-subjects').select2({
          placeholder: 'Select subjects...',
          data: <?php echo json_encode($subject_names); ?>,
        });

        // multiple agents input field
        $('.js-example-basic-multiple-agents').select2({
          placeholder: 'Select agents...',
          data: <?php echo json_encode($agent_names); ?>,
          });
        });
    </script>

  </body>
</html>
