<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login/login-form.php");
    exit();
}
include('../database/connection.php');
$user = $_SESSION['user'];
$name = $user['name'];

// Check user has admin rolee
if ($user['role'] !== 'admin') {
  header("Location: ../login/login-form.php");
  exit;
}

//populate Dropdown
$query = "SELECT client_name AS name
    FROM clients
    UNION
    SELECT lawyer_name AS name
    FROM lawyers";
$result = $conn->query($query);
$allNames = [];
while ($row = $result->fetch_assoc()) {
    $allNames[] = $row['name'];
}


function getDisplayClients() {
    include('../database/connection.php');
    $query = "SELECT client_name FROM clients WHERE email != ''";
    $result = $conn->query($query);
    $clientNames = [];
    while ($row = $result->fetch_assoc()) {
        $clientNames[] = $row['client_name'];
    }
    return $clientNames;
}

function getDisplayAttorneys() {
    include('../database/connection.php');
    $query = "SELECT lawyer_name FROM lawyers WHERE lawyer_email != ''";
    $result = $conn->query($query);
    $attorneyNames = [];
    while ($row = $result->fetch_assoc()) {
        $attorneyNames[] = $row['lawyer_name'];
    }
    return $attorneyNames;
}
 
if ($_POST) {
    include('../database/connection.php');
    $recipients = $_POST['recipients'];
    $emailSubject = $_POST['subField'];
    $emailBody = $_POST['emailBody'];

    $recipientList = implode(', ', array_map(function($recipient) use ($conn) {
        return "'" . $conn->real_escape_string($recipient) . "'";
    }, $recipients));

    $query = "SELECT email AS email_address
        FROM clients
        WHERE client_name IN ($recipientList)
        UNION
        SELECT lawyer_email AS email_address
        FROM lawyers
        WHERE lawyer_name IN ($recipientList)";

    $result = $conn->query($query);
    $emails = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $emails[] = $row['email_address'];
        }
    }

    // Construct the mailto link and open the email client
    $bccRecipients = implode(';', $emails);
    $mailtoLink = "mailto:?bcc=" . $bccRecipients . "&subject=" . $emailSubject . "&body=" . $emailBody;
    echo '<a id="emailLink" href="' . $mailtoLink . '" style="display: none;"></a>';
    echo '<script>
            var emailLink = document.getElementById("emailLink");
            emailLink.target = "_blank"; // Open in a new tab or window
            emailLink.click();
          </script>';

}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Email</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="../css/email.css">
    <link rel="stylesheet" type="text/css" href="../css/sidenav.css">
    
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

    <script>
        $(document).ready(function () {
            // Initialize Select2 for the "toField" element
            const $toField = $('.js-example-basic-multiple');
            $toField.select2({
                placeholder: 'Select recipients...',
                data: <?php echo json_encode($allNames); ?>,
            });

            // Store the state of checkboxes
            let allClientsChecked = false;
            let allAttorneysChecked = false;

            // Handle the "All Clients" checkbox
            $('#allClientsCheckbox').change(function () {
                allClientsChecked = this.checked;
                updateToField();
            });

            // Handle the "All Attorneys" checkbox
            $('#allAttorneysCheckbox').change(function () {
                allAttorneysChecked = this.checked;
                updateToField();
            });

            // Handle both checkboxes
            $('.checkbox').change(function () {
                allClientsChecked = $('#allClientsCheckbox').prop('checked');
                allAttorneysChecked = $('#allAttorneysCheckbox').prop('checked');
                updateToField();
            });

            // Update the "To:" field based on checkbox state
            function updateToField() {
                if (allClientsChecked && allAttorneysChecked) {
                    // Both checkboxes are checked, so select all names
                    const allNames = <?php echo json_encode($allNames); ?>;
                    $toField.val(allNames).trigger('change');
                } else if (allClientsChecked) {
                    const clientNames = <?php echo json_encode(getDisplayClients()); ?>;
                    $toField.val(clientNames).trigger('change');
                } else if (allAttorneysChecked) {
                    const attorneyNames = <?php echo json_encode(getDisplayAttorneys()); ?>;
                    $toField.val(attorneyNames).trigger('change');
                } else {
                    // Both checkboxes are unchecked
                    $toField.val(null).trigger('change');
                }
            }
           // Initialize Select2
            $toField.select2();
        });
                

    </script>
</head>
  <body>
    <?php include("../nav/sidenav.php"); ?>
    <div class="email-component">
        <h2>Email</h2>
        <form action="email.php" method="post">
            <div class="checkboxes">
                <label for="allClientsCheckbox">All Clients</label>
                <input type="checkbox" id="allClientsCheckbox" class="checkbox" data-type="clients">
                <label for="allAttorneysCheckbox">All Attorneys</label>
                <input type="checkbox" id="allAttorneysCheckbox" class="checkbox" data-type="attorneys">
            </div>
            <div class="to-field">
                <label for="toField">To:</label>
                <select id="toField" multiple="multiple" name="recipients[]" style="width: 70%;" class="js-example-basic-multiple" required></select>
            </div>
            <div class="sub-field">
                <label for="subField">Email Subject:</label>
                <input id="subField" name="subField">
            </div> 
            <div class="email-body">
                <label for="emailBody">Email Body:</label>
                <textarea id="emailBody" name="emailBody"></textarea>
            </div>
            <button type="submit" name="send" id="sendButton">Compose Email</button>
        </form>
    </div>
    <div style="color: #721c24; padding: 10px; text-align: center; font-style: italic;">
        <p>In order to compose an email to recipients, please ensure that your browser allows popups and redirects.</p>
        <p><a href="https://support.google.com/chrome/answer/95472?hl=en" target="_blank">Learn how to enable popups and redirects</a></p>
    </div>
    </body>
</html>