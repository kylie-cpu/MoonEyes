<?php
  session_start();
  $user = $_SESSION['user'];
  $name = $user[0]['name'];

  // Check user has admin rolee
  if ($user[0]['role'] !== 'admin') {
    header("Location: login-form.php");
    exit;
  }

  include('database/connection.php');

  // number of cases solved in the last month for metrics
  $query = "SELECT COUNT(*) AS cases_solved FROM cases WHERE status = 'Closed' AND day_modified >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
  $result = $conn->query($query);
  $cases_solved = $result->fetch_assoc()['cases_solved'];
?>

<!DOCTYPE html>
<html>
  <head>
    <title>Admin Controls</title>
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="css/admin.css">
    <link rel="stylesheet" type="text/css" href="css/sidenav.css">
  </head>
  <body>
    <!-- jQuery library -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Select2 library -->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <!-- Fixes x in multiple case selection -->
  <style>
    .select2-selection__choice__remove {
        margin-top: -1.5px !important; 
  }
  </style>

  <?php include("sidenav.php"); ?>

  <div class="content" id="content">
    <h1>Administrative Controls</h1>
    <div class="admin-buttons">
      <a href="email.php" class="admin-button">Email</a>
      <a href="audit-logs.php" class="admin-button">View Audit Logs</a>
      <a href="agent.php" class="admin-button">Add Agent</a>
    </div>
    <div class="admin-metrics">
    <h2>Metrics</h2>
    <table class="metrics-table">
      <tr>
        <td>Cases Solved in the Last Month:</td>
        <td><span class="metric-number"><?php echo $cases_solved; ?></span></td>
      </tr>
    </table>
  <div> 

  </body>
</html>