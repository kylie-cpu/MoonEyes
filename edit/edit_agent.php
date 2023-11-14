<?php
session_start();
$user = $_SESSION['user'];
$name = $user['name'];

// Check if the user has admin role
if ($_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login/login-form.php");
    exit;
}

// Agent id from parameters
$added_agent_id = $_GET['agent_id'];
$editing_agent_id = $user['agent_id'];

include('../database/connection.php');

// Create date for date modified field
date_default_timezone_set('America/Detroit');
$new_date = date('Y-m-d H:i:s');

// Populate dropdowns
include('../included/dropdowns.php');

// Get agent details from agents
$query_agent_details = "SELECT *
FROM agents
WHERE agents.agent_id = ?
GROUP BY agents.agent_id";
$stmt = $conn->prepare($query_agent_details);
$stmt->bind_param("s", $added_agent_id);
$stmt->execute();
$result_agent_details = $stmt->get_result();
$stmt->close();

if ($result_agent_details->num_rows > 0) {
    $agent_details = $result_agent_details->fetch_all(MYSQLI_ASSOC);
}

// Get associated cases
$query_assoc_cases = "SELECT cases.title
FROM cases
LEFT JOIN case_agent ON cases.case_id = case_agent.case_id
WHERE case_agent.agent_id = ?
GROUP BY cases.case_id";
$stmt = $conn->prepare($query_assoc_cases);
$stmt->bind_param("s", $added_agent_id);
$stmt->execute();
$result_assoc_cases = $stmt->get_result();
$stmt->close();

if ($result_assoc_cases->num_rows > 0) {
    $assoc_cases = $result_assoc_cases->fetch_all(MYSQLI_ASSOC);
}

if ($_POST) {
    // Get the submitted form values
    $the_agent_id = $_POST['agent_id'];
    $username = $_POST['username'];
    $new_name = $_POST['name'];
    $badge_num = $_POST['badge_number'];
    $modified_by = $editing_agent_id;
    $day_modified = $new_date;
    $role = $_POST['role'];
    $email = $_POST['email'];

    // Check if a new password is provided
    $new_password = $_POST['password'];
    $hashedPassword = password_hash($new_password, PASSWORD_BCRYPT);

    // Prepare an UPDATE statement for agent details
    $update_agent_details = "UPDATE agents SET
        username = ?,
        name = ?,
        badge_number = ?,
        modified_by = ?,
        modified_at = ?,
        role = ?,
        email = ?
        WHERE agent_id = ?";
    $stmt = $conn->prepare($update_agent_details);
    $stmt->bind_param("ssssssss", $username, $new_name, $badge_num, $modified_by, $day_modified, $role, $email, $the_agent_id);
    if ($stmt->execute() !== TRUE) {
        echo "Error updating agent details: " . $stmt->error;
    }

    // Update the password only if a new one is provided
    if (!empty($new_password) && $new_password !== $the_agent_id) {
        // Prepare an UPDATE statement for the password
        $updatePasswordQuery = "UPDATE agents SET password = ? WHERE agent_id = ?";
        $stmt = $conn->prepare($updatePasswordQuery);
        $stmt->bind_param("ss", $hashedPassword, $the_agent_id);
        if ($stmt->execute() !== TRUE) {
            echo "Error updating password: " . $stmt->error;
        }
    }

    $stmt->close();

    // Delete old associations
    $delete_old_agents = "DELETE FROM case_agent WHERE agent_id = ?";
    $stmt = $conn->prepare($delete_old_agents);
    $stmt->bind_param("s", $the_agent_id);
    $stmt->execute();
    $stmt->close();

    // Insert into case_agent table
    $related_cases = $_POST['related_cases'];
    foreach ($related_cases as $related_case) {
        $query = "SELECT case_id FROM cases WHERE title = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $related_case);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
        if ($result->num_rows > 0) {
            $Rcase_id = $result->fetch_assoc()['case_id'];
        }

        $insert_case_agent = "INSERT INTO case_agent(case_id, agent_id) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_case_agent);
        $stmt->bind_param("ss", $Rcase_id, $the_agent_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Redirect or display a success message
    header("Location: ../main/dashboard.php");
    exit;

}
?>

<!DOCTYPE html>
<html>
  <head>
    <title>Edit Agent</title>
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
    <h2>Edit <?php echo $added_agent_id?></h2>
    <form action="edit_agent.php" method="POST" class="agent-form">
        <?php if (!empty($agent_details)) { 
         $agent = $agent_details[0];?>
            <div class="form-group1">
                <label for="agent_id"><span class="required">*</span>Agent ID</label>
                <input class="view-input" type="text" id="agent_id" name="agent_id" value="<?php echo $agent['agent_id']; ?>" readonly>
            </div>

            <div class="form-group2">
                <label for="badge_number">Badge</label>
                <input  type="text" id="badge_number" name="badge_number" value="<?php echo $agent['badge_number']; ?>">
            </div>

            <div class="form-group1">
                <label for="name"><span class="required">*</span>Name</label>
                <input  type="text" id="name" name="name" value="<?php echo $agent['name']; ?>" required>
            </div>

            <div class="form-group2">
                <label for="email">Email</label>
                <input  type="email" id="email" name="email" value="<?php echo $agent['email']; ?>">
            </div>

            <div class="form-group1">
                <label for="username"><span class="required">*</span>Username</label>
                <input  type="text" id="username" name="username" value="<?php echo $agent['username']; ?>" required>
            </div>

            <div class="form-group2">
                <label for="email"><span class="required">*</span>Password:</label>
                <input  type="password" id="password" name="password" value="<?php echo $agent['agent_id']; ?>" required>
            </div>

            <div class="form-group">
                <label for="role"><span class="required">*</span>Role</label>
                <select id="role" name="role" style="width: 25%; font-size: large;">
                    <option value="agent"<?php if ($agent['role'] === 'agent')echo ' selected'; ?>>agent</option>
                    <option value="admin"<?php if ($agent['role'] === 'admin')echo ' selected'; ?>>admin</option>
                </select>
            </div>

            <div class="form-group">
                <label for="organization_tags">Organization Tags</label>
                <input type="text" id="organization_tags" name="organization_tags">
            </div>

            <div class="form-group">
                <label for="related_cases">Associated Cases</label>
                <select id="related_cases" name="related_cases[]" class="js-example-basic-multiple-cases" multiple="multiple" style="width: 44%;">
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
                <button type="submit" class="submit-btn">Submit Changes</button>
                <a class="discard-btn" href='delete.php?entity_type=agent&entity_id=<?php echo $added_agent_id?>' onclick="return confirm('Are you sure you want to delete this entry from the entire database? This action is irreversible.')">Delete Entity</a>
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