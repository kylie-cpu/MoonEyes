<?php 
session_start();

//Check if user is logged in
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

include('../database/connection.php');

$id = $_GET['id'];
$timestamp = $_GET['timestamp'];

//select correct log
$log_details = "SELECT audit_log.*, agents.name AS agent_name
FROM audit_log 
LEFT JOIN agents ON audit_log.agent = agents.agent_id
WHERE 
audit_log.id = '$id' AND 
audit_log.timestamp = '$timestamp'";
$result_log_details = mysqli_query($conn, $log_details);
$logs = $result_log_details->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>
<title>Log Details</title>
<link rel="stylesheet" type="text/css" href="../css/sidenav.css">
<style>
    .details {
        margin-top: 10%;
        font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif; 
    }
    h1{
       font-size: 15px;
       font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif; 
    }
</style>
</head>

<body>

    <div class='details'>
        <!-- display log details -->
        <?php foreach($logs as $log): ?>
            <?php include("../nav/sidenav.php"); ?>
            <h1>ID: </h1>
            <?php echo $log['id'] ?>
            <h1>Type: </h1>
            <?php echo $log['type'] ?>
            <h1>Timestamp: </h1>
            <?php echo $log['timestamp'] ?>
            <h1>Agent: </h1>
            <?php echo $log['agent'] ?>
            <?php echo $log['agent_name'] ?>
            <h1>Content: </h1>
            <?php echo $log['form'] ?>
        <?php endforeach; ?>
    </div>
</body>

</html>