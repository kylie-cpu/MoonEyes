<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login/login-form.php");
    exit();
}
$user = $_SESSION['user'];
$name = $user['name'];

// Check user has admin rolee
if ($user['role'] !== 'admin') {
    header("Location: ../login/login-form.php");
    exit;
}

include('../database/connection.php');

IF ($_GET) {
    $searchID = $_GET['normal'];
    $query_logs = "SELECT * FROM audit_log
    WHERE id = '$searchID'
    ORDER BY timestamp DESC";
    $result_logs = $conn->query($query_logs);
    $logs = $result_logs->fetch_all(MYSQLI_ASSOC);
}

?>

<!DOCTYPE html>
<html>
    <head>
        <title>Audit Logs</title>
        <link rel="stylesheet" type="text/css" href="../css/audit.css">
        <link rel="stylesheet" type="text/css" href="../css/sidenav.css">
    </head>
    <body>
        <?php include("../nav/sidenav.php"); ?>
        <div id="content" class="content">
            <h1>Audit Logs</h1>
            <div class="search">
                <form action="audit-logs.php"  method="GET">
                    <input type="text" id="search" name="normal" placeholder="Search any entity ID to view recent actions...">
                    <button type="submit" class="search-btn" >&#x2315; Search Logs</button><br>
                </form><br>
            </div>
            <p>Example Search: CASE-96912c2e76d54,  SUBJECT-64e40b7476f41, etc.</p>
            <div class="results">
                <table class= "LogTable">
                    <tr>
                        <th>Type</th>
                        <th>Link to View Log</th>
                        <th>Timestamp</th>
                    </tr>
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo $log['type'] ?></td>
                                    <td><a href="../details/log_details.php?id=<?php echo $log['id'] ?>&timestamp=<?php echo $log['timestamp'] ?>"><?php echo $log['id'] ?></a>
                                    <td><?php echo $log['timestamp']?> </td>
                                </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </table>
            </div>
    </body>
</html>