<?php
  session_start();
  $user = $_SESSION['user'];
  $name = $user['name'];

  // Check user has admin rolee
  if ($user['role'] !== 'admin') {
    header("Location: ../login/login-form.php");
    exit;
  }

  include('../database/connection.php');


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

  // number of cases open 
  $query = "SELECT COUNT(*) AS cases_open FROM cases WHERE status = 'Open'";
  $result = $conn->query($query);
  $cases_open = $result->fetch_assoc()['cases_open'];

  // number of agents
  $query = "SELECT COUNT(*) AS row_count
  FROM agents";
  $result = $conn->query($query);
  $num_agents = $result->fetch_assoc()['row_count'];

  // number of clients
  $query = "SELECT COUNT(*) AS row_count
  FROM clients";
  $result = $conn->query($query);
  $num_clients = $result->fetch_assoc()['row_count'];

  // number of subjects
  $query = "SELECT COUNT(*) AS row_count
  FROM subjects";
  $result = $conn->query($query);
  $num_subjects = $result->fetch_assoc()['row_count'];
?>

<!DOCTYPE html>
<html>
  <head>
    <title>Admin Controls</title>
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="../css/admin.css">
    <link rel="stylesheet" type="text/css" href="../css/sidenav.css">
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

  <?php include("../nav/sidenav.php"); ?>

  <div id="admin-container">
    <h1>Administrative Controls</h1>
    <div class="admin-buttons">
      <a href="email.php" class="admin-button">Email</a>
      <a href="audit-logs.php" class="admin-button">View Audit Logs</a>
    </div>
      <h2>Metrics</h2>
      <table class="metrics-table">
        <tr>
          <td>Cases Closed in the Last Month:</td>
          <td><span class="metric-number"><?php echo $cases_solved; ?></span></td>
        </tr>
        <tr>
          <td>Cases Currently Open:</td>
          <td><span class="metric-number"><?php echo $cases_open; ?></span></td>
        </tr>
        <tr>
          <td>Current Number of Agents:</td>
          <td><span class="metric-number"><?php echo $num_agents; ?></span></td>
        </tr>
        <tr>
          <td>Current Number of Clients:</td>
          <td><span class="metric-number"><?php echo $num_clients; ?></span></td>
        </tr>
        <tr>
          <td>Current Number of Subjects:</td>
          <td><span class="metric-number"><?php echo $num_subjects; ?></span></td>
        </tr>
      </table>
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
            <td><a href="../details/agent_details.php?agent_id=<?php echo $recent_agent['agent_id']; ?>"><?php echo $recent_agent['agent_id']; ?></a></td>
            <td><?php echo $recent_agent['name']; ?></td>
            <td><?php echo $recent_agent['email']; ?></td>
            <td><?php echo $recent_agent['assoc_cases']; ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
    <div>
      <a href="../add/agent.php" class="admin-button">Add a New Agent</a>
    </div>
  </div>

</body>
</html>