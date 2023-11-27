<?php
  //check for current session
  session_start();
  if (!isset($_SESSION['user'])) {
    header("Location: ../login/login-form.php");
    exit();
  }
  $user = $_SESSION['user'];
  $name = $user['name'];

  //get case ID and initizialize agent_id for modified_by field
  $case_id = $_GET['case_id'];
  $new_agent_id = $user['agent_id'];


  include('../database/connection.php');

  //Create date for date modified field
  date_default_timezone_set('America/Detroit');
  $new_date = date('Y-m-d H:i:s');

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


  // if submit button is clicked
  if ($_POST) {
    $case_id = $_POST['case_id'];
    $title = $_POST['title'];
    $purpose = $_POST['purpose'];
    $status = $_POST['status'];
    $invoice = $_POST['invoice_info'];
    $assoc_client = $_POST['related_client'];
    $notes = $_POST['notes'];
    $ud1 = $_POST['ud1'];
    $ud2 = $_POST['ud2'];
    $ud3 = $_POST['ud3'];
    $ud4 = $_POST['ud4'];

    $modified_by = $new_agent_id;
    $day_modified = $new_date;

    // Prepare and bind 
    $update_case_details = ("UPDATE cases SET
    title = ?,
    purpose = ?,
    status = ?,
    invoice = ?,
    notes = ?,
    modified_by = ?,
    day_modified = ?,
    ud1 = ?,
    ud2 = ?,
    ud3 = ?,
    ud4 = ?
    WHERE case_id = ?");
    $stmt = $conn->prepare($update_case_details);
    $stmt->bind_param("ssssssssssss", $title, $purpose, $status, $invoice, $notes, $modified_by, $day_modified, $ud1, $ud2, $ud3, $ud4, $case_id);
    // Check if the update was successful
    if (!$stmt->execute()) {
        echo "Error updating cases: " . $stmt->error;
    }

    // Delete old client entry using prepared statement
    $delete_old_client = $conn->prepare("DELETE FROM case_client WHERE case_id = ?");
    $delete_old_client->bind_param("s", $case_id);
    $delete_old_client->execute();

    // Insert into case_client table using prepared statement
    $insert_case_client = $conn->prepare("INSERT INTO case_client(case_id, client_id) VALUES (?, ?)");

    foreach ($_POST['related_clients'] as $related_client) {
        $query = "SELECT client_id FROM clients WHERE client_name = ?";
        $get_client_id = $conn->prepare($query);
        $get_client_id->bind_param("s", $related_client);

        if (!$get_client_id->execute()) {
            echo "Error retrieving client ID: " . $get_client_id->error;
            exit();
        }

        $result = $get_client_id->get_result();
        if ($result->num_rows > 0) {
            $client_id = $result->fetch_assoc()['client_id'];

            $insert_case_client->bind_param("ss", $case_id, $client_id);
            if (!$insert_case_client->execute()) {
                echo "Error inserting into case_client: " . $insert_case_client->error;
                exit();
            }
        }
    }

    // Delete old subject entries using prepared statement
    $delete_old_subjects = $conn->prepare("DELETE FROM case_subject WHERE case_id = ?");
    $delete_old_subjects->bind_param("s", $case_id);
    $delete_old_subjects->execute();

    // Insert into case_subject table using prepared statement
    $insert_case_subject = $conn->prepare("INSERT INTO case_subject(case_id, subject_id) VALUES (?, ?)");

    foreach ($_POST['related_subjects'] as $related_subject) {
        $query = "SELECT subject_id FROM subjects WHERE subject_name = ?";
        $get_subject_id = $conn->prepare($query);
        $get_subject_id->bind_param("s", $related_subject);

        if (!$get_subject_id->execute()) {
            echo "Error retrieving subject ID: " . $get_subject_id->error;
            exit();
        }

        $result = $get_subject_id->get_result();
        if ($result->num_rows > 0) {
            $subject_id = $result->fetch_assoc()['subject_id'];

            $insert_case_subject->bind_param("ss", $case_id, $subject_id);
            if (!$insert_case_subject->execute()) {
                echo "Error inserting into case_subject: " . $insert_case_subject->error;
                exit();
            }
        }
    }

    // Delete old agent entries using prepared statement
    $delete_old_agents = $conn->prepare("DELETE FROM case_agent WHERE case_id = ?");
    $delete_old_agents->bind_param("s", $case_id);
    $delete_old_agents->execute();

    // Insert into case_agent table using prepared statement
    $insert_case_agent = $conn->prepare("INSERT INTO case_agent(case_id, agent_id) VALUES (?, ?)");

    foreach ($_POST['related_agents'] as $related_agent) {
        $query = "SELECT agent_id FROM agents WHERE name = ?";
        $get_agent_id = $conn->prepare($query);
        $get_agent_id->bind_param("s", $related_agent);

        if (!$get_agent_id->execute()) {
            echo "Error retrieving agent ID: " . $get_agent_id->error;
            exit();
        }

        $result = $get_agent_id->get_result();
        if ($result->num_rows > 0) {
            $Ragent_id = $result->fetch_assoc()['agent_id'];

            $insert_case_agent->bind_param("ss", $case_id, $Ragent_id);
            if (!$insert_case_agent->execute()) {
                echo "Error inserting into case_agent: " . $insert_case_agent->error;
                exit();
            }
        }
    }

    // Update tags table using prepared statement
    $delete_tags = $conn->prepare("DELETE FROM tag_assoc WHERE assoc_id = ?");
    $delete_tags->bind_param("s", $case_id);
    $delete_tags->execute();

    $insert_tag_assoc = $conn->prepare("INSERT INTO tag_assoc(tag_id, assoc_id) VALUES (?, ?)");

    foreach ($_POST['related_tags'] as $related_tag) {
        $query = "SELECT tag_id FROM tags WHERE name = ?";
        $get_tag_id = $conn->prepare($query);
        $get_tag_id->bind_param("s", $related_tag);

        if (!$get_tag_id->execute()) {
            echo "Error retrieving tag ID: " . $get_tag_id->error;
            exit();
        }

        $result = $get_tag_id->get_result();
        if ($result->num_rows > 0) {
            $tag_id = $result->fetch_assoc()['tag_id'];

            $insert_tag_assoc->bind_param("ss", $tag_id, $case_id);
            if (!$insert_tag_assoc->execute()) {
                echo "Error inserting into tag_assoc: " . $insert_tag_assoc->error;
                exit();
            }
        }
    }

    // delete old file assoc from DB if selected 
    $selected_files = $_POST['selected_files'];
    $entity_id = $case_id;
    foreach ($selected_files as $selected_file) {
      include '../included/delete_file.php';
    }

    // upload new files
    $entity_id = $case_id;
    include '../included/upload.php';

    // Add audit log
    include '../included/audit.php';
    $id = $case_id;
    $type = 'Edit';
    $audit_agent = $new_agent_id;
    $jsonDumpOfForm = json_encode($_POST);
    logAudit($id, $type, $audit_agent, $jsonDumpOfForm);

    // Redirect back to dashboard after submission
    header("Location: ../main/dashboard.php"); 
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
      <h2>Edit <?php echo $case_id ?></h2>
      <form action="edit_case.php" method="POST" class="case-form" enctype="multipart/form-data">
        <?php if (!empty($case_details)) {
        $case = $case_details[0]; ?>
          <div class="form-group1">
            <label for="case_id"><span class="required">*</span>Case ID</label>
            <input class="view-input" type="text" id="case_id" name="case_id" value="<?php echo $case_id ?>" readonly>
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
              <select id="status" name="status" style="width:35%; font-size: 19px; font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;" required>
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
            <label for="related_tags">Organizational Tags</label>
            <select id="related_tags" name="related_tags[]" class="multiple-tags" style="width: 100%;" multiple="multiple">
            <option value=""></option>
            <?php foreach ($assoc_tags as $tag) { ?>
              <option value="<?php echo $tag['name']; ?>" selected><?php echo $tag['name']; ?></option>
            <?php } ?>
            </select>
          </div>

          <div class="form-group">
              <label for="media">Additional Media:</label>
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
                echo "No attached files for this case.";
                echo "</ul>";
            }
          ?>

          <div class="form-group1">
            <label for="ud1">Field 1</label>
            <input class="edit-input" type="text" id="ud1" name="ud1" value="<?php echo $case['ud1']; ?>">
          </div> 
        
          <div class="form-group2">
            <label for="ud2">Field 2</label>
            <input type="text" id="ud2" name="ud2" value="<?php echo $case['ud2']; ?>">
          </div>
        
          <div class="form-group1">
            <label for="ud3"> Field 3</label>
            <input type="text" id="ud3" name="ud3" value="<?php echo $case['ud3']; ?>">
          </div>
        
          <div class="form-group2">
            <label for="ud4">Field 4</label>
            <input type="text" id="ud4" name="ud4" value="<?php echo $case['ud4']; ?>">
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
            <input class="view-input" type="datetime-local" id="day_modified" name="day_modified" value="<?php echo $new_date; ?>" readonly>
          </div>

          <div class="form-group2">
            <label for="modified_by"><span class="required">*</span>Modified By</label>
            <input class="view-input" type="text" id="modified_by" name="modified_by" value="<?php echo $name; ?>" readonly>
          </div>

          <div class="form-group">
            <button type="submit" class="submit-btn">Submit Changes</button>
            <a class="discard-btn" href='delete.php?entity_type=case&entity_id=<?php echo $case_id?>' onclick="return confirm('Are you sure you want to delete this entry from the entire database? This action is irreversible.')">Delete Entity</a>
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
        // multiple tags input field
        $('.multiple-tags').select2({
          placeholder: 'Select tags...',
          data: <?php echo json_encode($tag_names);?>,
        });
      });

    </script>
  </body>
</html>