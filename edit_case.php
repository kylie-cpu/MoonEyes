<?php
  //check for current session
  session_start();
  $user = $_SESSION['user'];
  $name = $user[0]['name'];

  //get case ID and initizialize agent_id for modified_by field
  $case_id = $_GET['case_id'];
  $new_agent_id = $user[0]['agent_id'];


  include('database/connection.php');

  //Create date for date modified field
  date_default_timezone_set('America/Detroit');
  $new_date = date('Y-m-d H:i:s');

  // Populate dropdowns
  include('dropdowns.php');

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

  // get names of associated clients
  $assoc_clients = [];
  $query_assoc_clients = "SELECT client_name 
  FROM clients
  LEFT JOIN case_client ON clients.client_id = case_client.client_id
  WHERE case_client.case_id ='$case_id'
  GROUP BY case_client.client_id";
  $result_assoc_clients = $conn->query($query_assoc_clients);
  if ($result_assoc_clients) {
    $assoc_clients = $result_assoc_clients->fetch_all(MYSQLI_ASSOC);
  }

  // get names of associated subject
  $assoc_subjects = [];
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
  $assoc_agents = [];
  $query_assoc_agents = "SELECT name 
  FROM agents
  LEFT JOIN case_agent ON agents.agent_id = case_agent.agent_id
  WHERE case_agent.case_id ='$case_id'
  GROUP BY case_agent.agent_id";
  $result_assoc_agents = $conn->query($query_assoc_agents);
  if ($result_assoc_agents) {
    $assoc_agents = $result_assoc_agents->fetch_all(MYSQLI_ASSOC);
  }

  // if submit button is clicked
  if ($_POST) {
    $case_id = $_POST['case_id'];
    $title = preg_replace("/'/", "", $_POST['title']);
    $purpose = preg_replace("/'/", "", $_POST['purpose']);
    $status = $_POST['status'];
    $invoice = preg_replace("/'/", "", $_POST['invoice_info']);
    $assoc_client = $_POST['related_client'];
    // regex out single quotes from notes...
    $notes = preg_replace("/'/", "", $_POST['notes']);

    $ud1 = preg_replace("/'/", "", $_POST['ud1']);
    $ud2 = preg_replace("/'/", "", $_POST['ud2']);
    $ud3 = preg_replace("/'/", "", $_POST['ud3']);
    $ud4 = preg_replace("/'/", "", $_POST['ud4']);

    $modified_by = $new_agent_id;
    $day_modified = $new_date;

    // update cases table
    $update_case_details = "UPDATE cases SET
    title = '$title',
    purpose = '$purpose',
    status = '$status',
    invoice = '$invoice',
    notes = '$notes',
    modified_by = '$modified_by',
    day_modified = '$day_modified',
    ud1 = '$ud1',
    ud2 = '$ud2',
    ud3 = '$ud3',
    ud4 = '$ud4'
    WHERE case_id = '$case_id'";

    $conn->query($update_case_details); 

    //delete old client entry
    $delete_old_client = "DELETE FROM case_client WHERE case_id = '$case_id'";
    $conn->query($delete_old_client);    

    // Insert into case_client table
    $related_clients = $_POST['related_clients'];
    foreach ($related_clients as $related_client) {
      $query = "SELECT client_id FROM clients WHERE client_name = '$related_client'";
      $result = $conn->query($query);
      if ($result->num_rows > 0) {
        $client_id = $result->fetch_assoc()['client_id'];
      }
      $insert_case_client = "INSERT INTO case_client(case_id, client_id) VALUES ('$case_id', '$client_id')";
      if ($conn->query($insert_case_client) !== TRUE) {
        echo "Error inserting into case_client";
      }
    }

    // delete old subject entries
    $delete_old_subjects = "DELETE FROM case_subject WHERE case_id = '$case_id'";
    $conn->query($delete_old_subjects);

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

    //delete old agent entries
    $delete_old_agents = "DELETE FROM case_agent WHERE case_id = '$case_id'";
    $conn->query($delete_old_agents);

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
    <title>Edit Case</title>
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
      <h2>Edit <?php echo $case_id ?></h2>
      <form action="edit_case.php" method="POST" class="case-form" enctype="multipart/form-data">
        <?php if (!empty($case_details)) {
        $case = $case_details[0]; ?>
          <div class="form-group1">
            <label for="case_id"><span class="required">*</span>Case ID</label>
            <input type="text" id="case_id" name="case_id" value="<?php echo $case_id ?>" readonly>
          </div>
          <div class="form-group2">
              <label for="title"><span class="required">*</span>Title</label>
              <input type="text" id="title" name="title" value="<?php echo $case['title']; ?>">
          </div>

          <div class="form-group">
              <label for="purpose">Purpose of Case</label>
              <textarea id="purpose" name="purpose" rows="4"><?php echo $case['purpose']; ?></textarea>
          </div>

          <div class="form-group">
              <label for="status"><span class="required">*</span>Status</label>
              <select id="status" name="status" style="width: 10%; font-size: 19px; font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;" required>
                  <option value="Open"<?php if ($case['status'] === 'Open') echo ' selected'; ?>>Open</option>
                  <option value="Closed"<?php if ($case['status'] === 'Closed') echo ' selected'; ?>>Closed</option>
                  <option value="Pending"<?php if ($case['status'] === 'Pending') echo ' selected'; ?>>Pending</option>
              </select>
          </div>

          <div class="form-group">
              <label for="invoice_info">Invoice Information</label>
              <textarea id="invoice_info" name="invoice_info" rows="4"><?php echo $case['invoice']; ?></textarea>
          </div>

          <div class="form-group">
              <label for="notes">Notes</label>
              <textarea id="notes" name="notes" rows="4"><?php echo $case['notes']; ?></textarea>
          </div>

          <div class="form-group">
              <label for="media">Additional Media:</label>
              <input type="file" id="media" name="media" style="width: 35%;" multiple>
          </div>

          <div class="form-group">
            <label for="organization_tags">Organization Tags</label>
            <input type="text" id="organization_tags" name="organization_tags">
          </div>

          <div class="form-group1">
            <label for="ud1">Field 1</label>
            <input type="ud1" id="ud1" name="ud1" value="<?php echo $case['ud1']; ?>">
          </div> 
        
          <div class="form-group2">
            <label for="ud2">Field 2</label>
            <input type="ud2" id="ud2" name="ud2" value="<?php echo $case['ud2']; ?>">
          </div>
        
          <div class="form-group1">
            <label for="ud3"> Field 3</label>
            <input type="ud3" id="ud3" name="ud3" value="<?php echo $case['ud3']; ?>">
          </div>
        
          <div class="form-group2">
            <label for="ud4">Field 4</label>
            <input type="ud4" id="ud4" name="ud4" value="<?php echo $case['ud4']; ?>">
          </div>

          <div class="form-group">
              <label for="related_clients"><span class="required">*</span>Associated Client</label>
              <select id="related_clients" name="related_clients[]" class="js-example-basic-single" style="width: 43%;" multiple="multiple">
                  <option value=""></option>
                  <?php foreach ($assoc_clients as $client) { ?>
                    <option value="<?php echo $client['client_name']; ?>" selected><?php echo $client['client_name']; ?></option>
                  <?php } ?>
              </select>
          </div>

          <div class="form-group1">
              <label for="related_subjects">Associated Subjects</label>
              <select id="related_subjects" name="related_subjects[]" class="js-example-basic-multiple-subjects" multiple="multiple" style="width: 100%;">
                  <option value=""></option>
                  <?php foreach ($assoc_subjects as $subject) { ?>
                    <option value="<?php echo $subject['subject_name']; ?>" selected><?php echo $subject['subject_name']; ?></option>
                  <?php } ?>
              </select>
          </div>

          <div class="form-group2">
              <label for="related_agents">Associated Agents</label>
              <select id="related_agents" name="related_agents[]" class="js-example-basic-multiple-agents" multiple="multiple" style="width: 100%;" >
                  <option value=""></option>
                  <?php foreach ($assoc_agents as $agent) { ?>
                    <option value="<?php echo $agent['name']; ?>" selected><?php echo $agent['name']; ?></option>
                  <?php } ?>
              </select>
          </div>

          <div class="form-group1">
            <label for="day_modified"><span class="required">*</span>Date Modified</label>
            <input type="datetime-local" id="day_modified" name="day_modified" value="<?php echo $new_date; ?>" readonly>
          </div>

          <div class="form-group2">
            <label for="modified_by"><span class="required">*</span>Modified By</label>
            <input type="text" id="modified_by" name="modified_by" value="<?php echo $name; ?>" readonly>
          </div>

          <div class="form-group">
            <button type="submit" class="submit-btn">Submit Changes</button>
            <a href="dashboard.php" class="discard-btn" onclick="return confirm('Are you sure you want to discard? No data will be saved.')">Discard</a>
          </div>
        <?php } ?>
      </form>
    </div>

    <!-- Select 2 initialization for dropdown menus -->
    <script>
      $(document).ready(function() {
        // single client input field
        $('.js-example-basic-single').select2({
          placeholder: 'Select a client...',
          maximumSelectionLength: 1,
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