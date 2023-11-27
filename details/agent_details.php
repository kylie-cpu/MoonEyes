
<?php
    session_start();

    if (!isset($_SESSION['user'])) {
        header("Location: ../login/login-form.php");
        exit();
    }

    $user = $_SESSION['user'];
    $name = $user['name'];

  // make sure user is an admin role
  if ($_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login/login-form.php");
    exit;
  }

  $added_agent_id = $_GET['agent_id'];
  // Initialize unique ID for subject & lawyer and also initizialize agent_id for modified_by field
  $agent_id = $user['agent_id'];

  include('../database/connection.php');   

  // Create date for date modified field
  date_default_timezone_set('America/Detroit');
  $new_date = date('Y-m-d H:i:s');

    // Populate dropdowns
    include('../included/dropdowns.php');


    // get agent details from agents
    $query_agent_details = "SELECT *
    FROM agents
    WHERE agents.agent_id = '$added_agent_id'
    GROUP BY agents.agent_id";
    $result_agent_details = $conn->query($query_agent_details);
    if ($result_agent_details->num_rows > 0) {
        $agent_details = $result_agent_details->fetch_all(MYSQLI_ASSOC);
    }

    // get associated cases 
    $query_assoc_cases = "SELECT cases.title
    FROM cases
    LEFT JOIN case_agent ON cases.case_id = case_agent.case_id
    WHERE case_agent.agent_id = '$added_agent_id'
    GROUP BY cases.case_id";
    $result_assoc_cases = $conn->query($query_assoc_cases);
    if ($result_assoc_cases->num_rows > 0) {
        $assoc_cases = $result_assoc_cases->fetch_all(MYSQLI_ASSOC);
    }

    // Add to audit logs 
    include '../included/audit.php';
    $id = $added_agent_id;
    $type = 'View';
    $audit_agent = $agent_id;
    $jsonDumpOfForm = '';
    logAudit($id, $type, $audit_agent, $jsonDumpOfForm);

?>

<!DOCTYPE html>
<html>
  <head>
    <title>Agent Details</title>
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
    <h2><?php echo $added_agent_id?> Details</h2>
    <form action="agent_details.php" method="POST" class="agent-form">
        <?php if (!empty($agent_details)) { 
         $agent = $agent_details[0];?>
            <div class="form-group1">
                <label for="agent_id"><span class="required">*</span>Agent ID</label>
                <input class="view-input" type="text" id="agent_id" name="agent_id" value="<?php echo $agent['agent_id']; ?>" readonly>
            </div>

            <div class="form-group2">
                <label for="badge_number">Badge</label>
                <input class="view-input" type="text" id="badge_number" name="badge_number" value="<?php echo $agent['badge_number']; ?>"readonly>
            </div>

            <div class="form-group1">
                <label for="name"><span class="required">*</span>Name</label>
                <input class="view-input" type="text" id="name" name="name" value="<?php echo $agent['name']; ?>" readonly>
            </div>

            <div class="form-group2">
                <label for="email">Email</label>
                <input class="view-input" type="email" id="email" name="email" value="<?php echo $agent['email']; ?>"readonly>
            </div>

            <div class="form-group1">
                <label for="username"><span class="required">*</span>Username</label>
                <input class="view-input" type="text" id="username" name="username" value="<?php echo $agent['username']; ?>" readonly>
            </div>

            <div class="form-group2">
                <label for="email"><span class="required">*</span>Password:</label>
                <input class="view-input" type="password" id="password" name="password" value="<?php echo $agent['agent_id']; ?>" readonly>
            </div>

            <div class="form-group">
                <label for="role"><span class="required">*</span>Role</label>
                <select id="role" name="role" style="width: 25%; font-size: large;" disabled>
                    <option value="agent"<?php if ($agent['role'] === 'agent')echo ' selected'; ?>>agent</option>
                    <option value="admin"<?php if ($agent['role'] === 'admin')echo ' selected'; ?>>admin</option>
                </select>
            </div>

            <div class="form-group">
                <label for="related_cases">Associated Cases</label>
                <select id="related_cases" name="related_cases[]" class="js-example-basic-multiple-cases" multiple="multiple" style="width: 44%;" disabled>
                <option value=""></option>
                <?php foreach ($assoc_cases as $case) { ?>
                    <option value="<?php echo $case['title']; ?>" selected><?php echo $case['title']; ?></option>
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
            </div><br><br>

            <div class="form-group">
                <a href="../edit/edit_agent.php?agent_id=<?php echo $agent['agent_id']; ?>" class="edit-btn">Edit</a>
                <a href=# onclick="window.print();" class="print-btn">Print/PDF</a>
            </div>
        <?php } ?>
    </form>
  </div>

  <!-- Select 2 initialization for dropdown menus -->
  <script>
      $(document).ready(function() {
        // cases input field
        $('.js-example-basic-multiple-cases').select2({
          data: <?php echo json_encode($case_titles); ?>,
        });
      });
  </script>

  </body>
</html>