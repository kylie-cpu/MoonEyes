
<?php
  session_start();
  $user = $_SESSION['user'];
  $name = $user[0]['name'];

  // make sure user is an admin role
  if ($_SESSION['user'][0]['role'] !== 'admin') {
    header("Location: login-form.php");
    exit;
  }

  // Initialize unique ID for subject & lawyer and also initizialize agent_id for modified_by field
  $unique_agent_id = "AGENT-" . uniqid();
  $agent_id = $user[0]['agent_id'];

  include('database/connection.php');   

  // Create date for date modified field
  date_default_timezone_set('America/Detroit');
  $date = date('Y-m-d H:i:s');

 //Case titles for drop down
  $query = "SELECT title FROM cases ORDER BY day_modified DESC";
  $result = $conn->query($query);
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $case_titles[] = $row['title'];
    }
  }

  if ($_POST) {
    $new_agent_id = $unique_agent_id;
    $username = $_POST['username'];
    $password = $_POST['password'];
    $new_name = $_POST['name'];
    $badge_num = $_POST['badge_number'];
    $modified_by = $agent_id;
    $day_modified = $date;
    $role = $_POST['role'];
    $email = $_POST['email'];
 
    // Submit data into agents
    $insert_agent = "INSERT INTO agents(agent_id, username, password, name, badge_number, modified_at, modified_by, role, email) 
    VALUES ('$new_agent_id', '$username', '$password', '$new_name', '$badge_num', '$day_modified', '$modified_by', '$role', '$email' )";

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

    // Redirect back to dashboard after submission    
    header("Location: dashboard.php"); 
    exit;
    
  }
?>

<!DOCTYPE html>
<html>
  <head>
    <title>Add an Agent</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="css/add.css">
    <link rel="stylesheet" href="css/sidenav.css">
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

  <?php include("sidenav.php"); ?>

  <!-- Database submission form -->
  <div id="content" class="content">
    <h2>Add an Agent</h2>
    <form action="agent.php" method="POST" class="agent-form">
      <div class="form-group">
        <label for="agent_id"><span class="required">*</span>Agent ID:</label>
        <input type="text" id="agent_id" name="agent_id" value="<?php echo $unique_agent_id ?>" readonly>
      </div>

      <div class="form-group">
        <label for="badge_number">Badge:</label>
        <input type="text" id="badge_number" name="badge_number">
      </div>

      <div class="form-group">
        <label for="name"><span class="required">*</span>Name:</label>
        <input type="text" id="name" name="name" required>
      </div>

      <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email">
      </div>

      <div class="form-group">
        <label for="username"><span class="required">*</span>Username:</label>
        <input type="text" id="username" name="username" required>
      </div>

      <div class="form-group">
        <label for="email"><span class="required">*</span>Password:</label>
        <input type="text" id="password" name="password" required>
      </div>

      <div class="form-group">
        <label for="role"><span class="required">*</span>Role:</label>
        <select id="role" name="role" style="width: 25%; font-size: large;" readonly>
          <option value="agent">agent</option>
          <option value="admin">admin</option>
        </select>
      </div>

      <div class="form-group">
        <label for="organization_tags">Organization Tags:</label>
        <input type="text" id="organization_tags" name="organization_tags">
      </div>

      <div class="form-group">
        <label for="related_cases">Associated Cases:</label>
        <select id="related_cases" name="related_cases[]" class="js-example-basic-multiple-cases" multiple="multiple" style="width: 100%;"></select>
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
        <a href="dashboard.php" class="delete-btn" onclick="return confirm('Are you sure you want to discard? No data will be saved.')">Discard</a>
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