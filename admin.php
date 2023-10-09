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


  // Recent agents
  $query_recent_agents = "SELECT agents.*, GROUP_CONCAT(DISTINCT(cases.title) SEPARATOR ', ') AS assoc_cases
  FROM agents
  LEFT JOIN case_agent ON agents.agent_id = case_agent.agent_id
  LEFT JOIN cases ON case_agent.case_id = cases.case_id
  GROUP BY agents.agent_id
  ORDER BY agents.modified_at DESC
  LIMIT 30";
  $result_recent_agents = $conn->query($query_recent_agents);
  $recent_agents = $result_recent_agents->fetch_all(MYSQLI_ASSOC);

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
    </div>
    <h2>Recently Added Agents</h2>
    <div id="scroll-wrapper">
      <table id="agent">
        <tr>
          <th>Agent ID</th>
          <th>Name</th>
          <th>Email</th>
          <th>Associated Cases</th>
        </tr>
        <?php foreach ($recent_agents as $recent_agent): ?>
          <tr>
            <td><a href="agent_details.php?agent_id=<?php echo $recent_agent['agent_id']; ?>"><?php echo $recent_agent['agent_id']; ?></a></td>
            <td><?php echo $recent_agent['name']; ?></td>
            <td><?php echo $recent_agent['email']; ?></td>
            <td><?php echo $recent_agent['assoc_cases']; ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
    <div class="admin-buttons">
      <a href="agent.php" class="admin-button">Add a New Agent</a>
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