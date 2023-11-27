<?php
  session_start();

  if (!isset($_SESSION['user'])) {
    header("Location: ../login/login-form.php");
    exit();
  }

  $user = $_SESSION['user'];
  $name = $user['name'];
  $user_id = $user['agent_id'];

  include("../database/connection.php");

  // num of open cases
  $query = "SELECT COUNT(*) AS num_open FROM cases WHERE status = 'Open' AND case_id in 
  (SELECT case_id from case_agent WHERE agent_id='$user_id')";
  $result = $conn->query($query);
  $num_open = $result->fetch_assoc()['num_open'];

  // Dashboard tables... Your Open Cases
  $query = "SELECT cases.*, GROUP_CONCAT(DISTINCT(agents.name) SEPARATOR ', ') AS assoc_agents
  FROM cases
  LEFT JOIN case_agent ON cases.case_id = case_agent.case_id
  LEFT JOIN agents ON case_agent.agent_id = agents.agent_id
  WHERE cases.status = 'Open' 
  AND cases.case_id IN (SELECT case_agent.case_id FROM case_agent WHERE agent_id = '$user_id')
  GROUP BY cases.case_id
  LIMIT 30";
  $result_your_open_cases = $conn->query($query);
  $your_open_cases = $result_your_open_cases->fetch_all(MYSQLI_ASSOC);

  // Dashboard tables... Recent Case Entries
  $query_recent_cases = "SELECT cases.*, GROUP_CONCAT(DISTINCT(agents.name) SEPARATOR ', ') AS assoc_agents
  FROM cases
  LEFT JOIN case_agent ON cases.case_id = case_agent.case_id
  LEFT JOIN agents ON case_agent.agent_id = agents.agent_id
  GROUP BY cases.case_id
  ORDER BY cases.day_modified DESC
  LIMIT 30";
  $result_recent_cases = $conn->query($query_recent_cases);
  $recent_cases = $result_recent_cases->fetch_all(MYSQLI_ASSOC);

  // Dashboard tables... Recent Client Entries
  $query_recent_clients = "SELECT clients.*, GROUP_CONCAT(DISTINCT(cases.title) SEPARATOR ', ') AS assoc_cases, GROUP_CONCAT(DISTINCT(cases.title) SEPARATOR ', ') AS assoc_cases, GROUP_CONCAT(DISTINCT(lawyers.lawyer_name) SEPARATOR ', ') AS assoc_lawyer 
  FROM clients
  LEFT JOIN case_client ON clients.client_id = case_client.client_id
  LEFT JOIN cases ON case_client.case_id = cases.case_id
  LEFT JOIN lawyers ON clients.lawyer = lawyers.lawyer_id
  GROUP BY clients.client_id
  ORDER BY clients.day_modified DESC
  LIMIT 30";
  $result_recent_clients = $conn->query($query_recent_clients);
  $recent_clients = $result_recent_clients->fetch_all(MYSQLI_ASSOC);

  // Dashboard tables... Recent Subject Entries
  $query_recent_subjects = "SELECT subjects.*, GROUP_CONCAT(DISTINCT(cases.title) SEPARATOR ', ') AS assoc_cases, GROUP_CONCAT(DISTINCT(lawyers.lawyer_name) SEPARATOR ', ') AS assoc_lawyer
  FROM subjects
  LEFT JOIN case_subject ON subjects.subject_id = case_subject.subject_id
  LEFT JOIN cases ON case_subject.case_id = cases.case_id
  LEFT JOIN lawyers ON subjects.lawyer = lawyers.lawyer_id
  GROUP BY subjects.subject_id
  ORDER BY subjects.day_modified DESC
  LIMIT 30";
  $result_recent_subjects = $conn->query($query_recent_subjects);
  $recent_subjects = $result_recent_subjects->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
  <head>
    <title>Moon Eyes - Dashboard</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/sidenav.css">
  </head>
  <?php include("../nav/sidenav.php"); ?>
  <body>
    <!-- Dashboard Tables -->
    <div id="content">
      <h1><?php echo $name?>'s Dashboard</h1>
      <h2>Your Open Cases: <?php echo $num_open?> </h2>
      <div id="scroll-wrapper">
        <table>
          <tr>
            <th>Case ID</th>
            <th>Title</th>
            <th>Purpose</th>
            <th>Associated Agents</th>
            <th>Status</th>
          </tr>
          <?php foreach ($your_open_cases as $case): ?>
            <tr> 
              <td><a href="../details/case_details.php?case_id=<?php echo $case['case_id']; ?>"><?php echo $case['case_id']; ?></a></td>
              <td><?php echo $case['title']; ?></td>
              <td><?php echo $case['purpose']; ?></td>
              <td><?php echo $case['assoc_agents']; ?></td>
              <td class="<?php echo ($case['status'] === 'Open') ? 'open-bg' : (($case['status'] === 'Closed') ? 'closed-bg' : 'pending-bg'); ?>">
                <?php echo $case['status']; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
      
      <h2>New Entries</h2>

      <h3>Recently Added Cases</h3>
      <div id="scroll-wrapper">
        <table>
          <tr>
            <th>Case ID</th>
            <th>Title</th>
            <th>Purpose</th>
            <th>Associated Agents</th>
            <th>Status</th>
          </tr>
          <?php foreach ($recent_cases as $recent_case): ?>
            <tr>
              <td><a href="../details/case_details.php?case_id=<?php echo $recent_case['case_id']; ?>"><?php echo $recent_case['case_id']; ?></a></td>
              <td><?php echo $recent_case['title']; ?></td>
              <td><?php echo $recent_case['purpose']; ?></td>
              <td><?php echo $recent_case['assoc_agents']; ?></td>
              <td class="<?php echo ($recent_case['status'] === 'Open') ? 'open-bg' : (($recent_case['status'] === 'Closed') ? 'closed-bg' : 'pending-bg'); ?>">
                <?php echo $recent_case['status']; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>

      <h3>Recently Added Clients</h3>
      <div id="scroll-wrapper">
        <table>
          <tr>
            <th>Client ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Associated Cases</th>
            <th>Lawyer</th> 
          </tr>
          <?php foreach ($recent_clients as $recent_client): ?>
            <tr>
              <td><a href="../details/client_details.php?client_id=<?php echo $recent_client['client_id']; ?>"><?php echo $recent_client['client_id']; ?></a></td>
              <td><?php echo $recent_client['client_name']; ?></td>
              <td><?php echo $recent_client['email']; ?></td>
              <td><?php echo $recent_client['assoc_cases']; ?></td>
              <td><?php echo $recent_client['assoc_lawyer']; ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>

      <h3>Recently Added Subjects</h3>
      <div id="scroll-wrapper">
        <table>
          <tr>
            <th>Subject ID</th>
            <th>Name</th>
            <th>Associated Cases</th>
            <th>Lawyer</th>
          </tr>
          <?php foreach ($recent_subjects as $recent_subject): ?>
            <tr>
              <td><a href="../details/subject_details.php?subject_id=<?php echo $recent_subject['subject_id']; ?>"><?php echo $recent_subject['subject_id']; ?></a></td>
              <td><?php echo $recent_subject['subject_name']; ?></td>
              <td><?php echo $recent_subject['assoc_cases']; ?></td>
              <td><?php echo $recent_subject['assoc_lawyer']; ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    </div>
  </body>
</html> 

