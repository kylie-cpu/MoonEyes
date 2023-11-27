
<?php
  session_start();
  $user = $_SESSION['user'];
  $name = $user['name'];

  // make sure user is an admin role
  if ($_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login/login-form.php");
    exit;
  }

  // Initialize unique ID for subject & lawyer and also initizialize agent_id for modified_by field
  $unique_agent_id = "AGENT-" . uniqid();
  $agent_id = $user['agent_id'];

  include('../database/connection.php');   

  // Create date for date modified field
  date_default_timezone_set('America/Detroit');
  $date = date('Y-m-d H:i:s');

  // Populate dropdowns
  include('../included/dropdowns.php');


  if ($_POST) {
    $new_agent_id = $unique_agent_id;
    $username = $_POST['username'];
    $password = $_POST['password'];
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT); 
    $new_name = $_POST['name'];
    $badge_num = $_POST['badge_number'];
    $modified_by = $agent_id;
    $day_modified = $date;
    $role = $_POST['role'];
    $email = $_POST['email'];
 
    // Submit data into agents
    $insert_agent = "INSERT INTO agents(agent_id, username, password, name, badge_number, modified_at, modified_by, role, email) 
    VALUES ('$new_agent_id', '$username', '$hashedPassword', '$new_name', '$badge_num', '$day_modified', '$modified_by', '$role', '$email' )";

    //Check if successfully inserted
    if ($conn->query($insert_agent) !== TRUE) {
      echo "Error inserting into agents: ";
    } 

    // Submit data into case_agent
    $related_cases = $_POST['related_cases'];

    foreach ($related_cases as $related_case) {
      $query = "SELECT case_id FROM cases WHERE title = '$related_case'";
      $result = $conn->query($query);
      if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
          $Rcase_id= $row['case_id'];
        }
      }
      $insert_case_agent = "INSERT INTO case_agent(case_id, agent_id) VALUES ('$Rcase_id', '$new_agent_id')";
      if ($conn->query($insert_case_agent) !== TRUE) {
          echo "Error inserting into case_agent: ";
      }
    }

    // Add audit log
    include '../included/audit.php';
    $id = $new_agent_id;
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
    <title>Add an Agent</title>
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
    <h2>Add an Agent</h2>
    <form action="agent.php" method="POST" class="agent-form">
      <div class="form-group1">
        <label for="agent_id"><span class="required">*</span>Agent ID</label>
        <input class="view-input" type="text" id="agent_id" name="agent_id" value="<?php echo $unique_agent_id ?>" readonly>
      </div>

      <div class="form-group2">
        <label for="badge_number">Badge</label>
        <input type="text" id="badge_number" name="badge_number">
      </div>

      <div class="form-group1">
        <label for="name"><span class="required">*</span>Name</label>
        <input type="text" id="name" name="name" required>
      </div>

      <div class="form-group2">
        <label for="email">Email</label>
        <input type="email" id="email" name="email">
      </div>

      <div class="form-group1">
        <label for="username"><span class="required">*</span>Username</label>
        <input type="text" id="username" name="username" required>
      </div>

      <div class="form-group2">
        <label for="email"><span class="required">*</span>Password:</label>
        <input type="password" id="password" name="password" required>
      </div>

      <div class="form-group">
        <label for="role"><span class="required">*</span>Role</label>
        <select id="role" name="role" style="width: 25%; font-size: large;" readonly>
          <option value="agent">agent</option>
          <option value="admin">admin</option>
        </select>
      </div>

      <div class="form-group">
        <label for="related_cases">Associated Cases</label>
        <select id="related_cases" name="related_cases[]" class="js-example-basic-multiple-cases" multiple="multiple" style="width: 43%;"></select>
      </div>

      <div class="form-group1">
        <label for="day_modified"><span class="required">*</span>Date Modified</label>
        <input class="view-input" type="datetime-local" id="day_modified" name="day_modified" value="<?php echo $date ?>" readonly>
      </div>

      <div class="form-group2">
        <label for="modified_by"><span class="required">*</span>Modified By</label>
        <input class="view-input" type="text" id="modified_by" name="modified_by" value="<?php echo $name ?>" readonly>
      </div>
      
      <div class="form-group">
        <button type="submit" class="submit-btn">Submit</button>
        <a href="../other/admin.php" class="discard-btn" onclick="return confirm('Are you sure you want to discard? No data will be saved.')">Discard</a>
      </div>
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
      });
  </script>

  </body>
</html>