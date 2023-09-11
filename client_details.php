<?php
    session_start();
    $user = $_SESSION['user'];
    $name = $user[0]['name'];

    $client_id = $_GET['client_id'];

    // Initialize unique ID for subject & lawyer and also initizialize agent_id for modified_by field
    $unique_client_id = "CLIENT-" . uniqid();
    $unique_lawyer_id = "LAWYER-" . uniqid();
    $agent_id = $user[0]['agent_id'];

    include('database/connection.php');

    // Create date for date modified field
    date_default_timezone_set('America/Detroit');
    $date = date('Y-m-d H:i:s');

    //Case titles for drop down
    $query = "SELECT title FROM cases ORDER BY day_modified DESC";
    $result = $conn->query($query);
    if ($result->num_rows > 0) {
        $case_titles = $result->fetch_all(MYSQLI_ASSOC);
    }

    // get client details from clients
    $query_client_details = "SELECT clients.*, agents.name AS mod_agent, lawyers.lawyer_name AS lawyer_name, lawyers.lawyer_email AS lawyer_email
    FROM clients
    LEFT JOIN agents on clients.modified_by = agents.agent_id
    LEFT JOIN lawyers ON clients.lawyer = lawyers.lawyer_id
    WHERE clients.client_id = '$client_id'
    GROUP BY clients.client_id";
    $result_client_details = $conn->query($query_client_details);
    if ($result_client_details->num_rows > 0) {
        $client_details = $result_client_details->fetch_all(MYSQLI_ASSOC);
    }

    // get associated cases 
    $query_assoc_cases = "SELECT cases.title
    FROM cases
    LEFT JOIN case_client ON cases.case_id = case_client.case_id
    WHERE case_client.client_id = '$client_id'
    GROUP BY cases.case_id";
    $result_assoc_cases = $conn->query($query_assoc_cases);
    if ($result_assoc_cases->num_rows > 0) {
        $assoc_cases = $result_assoc_cases->fetch_all(MYSQLI_ASSOC);
    }

?>

<!DOCTYPE html>
<html>
  <head>
    <title>Client Details</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
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
        <h2><?php echo $client_id?> Details</h2>
        <form action="client_details.php" method="POST" class="client-form">
            <?php if (!empty($client_details)) {
            $client = $client_details[0]; ?>
                <div class="form-group">
                    <label for="client_id"><span class="required">*</span>Client ID:</label>
                    <input type="text" id="client_id" name="client_id" value="<?php echo $client['client_id']; ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="name"><span class="required">*</span>Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo $client['client_name']; ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo $client['email']; ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="address">Address:</label>
                    <textarea id="address" name="address" rows="4" readonly><?php echo $client['address']; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number:</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo $client['phone_num']; ?>" readonly>
                </div>

                <div class="form-group">
                    <h3>Lawyer Information:</h3>
                    <div class="sub-form-group">
                        <label for="lawyer-name">Lawyer Name:</label>
                        <input type="text" id="lawyer-name" name="lawyer-name" value="<?php echo $client['lawyer_name']; ?>" readonly>
                    </div>

                    <div class="sub-form-group">
                        <label for="lawyer-email">Lawyer Email:</label>
                        <input type="email" id="lawyer-email" name="lawyer-email" value="<?php echo $client['lawyer_email']; ?>" readonly>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Notes:</label>
                    <textarea id="notes" name="notes" rows="4"readonly><?php echo $client['notes']; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="organization_tags">Organization Tags:</label>
                    <input type="text" id="organization_tags" name="organization_tags" readonly>
                </div>

                <div class="form-group">
                    <label for="media">Additional Media:</label>
                </div>

                <div class="form-group">
                    <label for="related_cases">Associated Cases:</label>
                    <select id="related_cases" name="related_cases[]" class="js-example-basic-multiple-cases" multiple="multiple" style="width: 100%;">
                    <option value=""></option>
                    <?php foreach ($assoc_cases as $case) { ?>
                        <option value="<?php echo $case['title']; ?>" selected><?php echo $case['title']; ?></option>
                    <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="day_modified"><span class="required">*</span>Date Modified:</label>
                    <input type="datetime-local" id="day_modified" name="day_modified" value="<?php echo $client['day_modified']; ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="modified_by"><span class="required">*</span>Modified By:</label>
                    <input type="text" id="modified_by" name="modified_by" value="<?php echo $client['mod_agent']; ?>" readonly>
                </div>
                
                <div class="form-group">
                    <a href="edit_client.php?case_id=<?php echo $client['client_id']; ?>" class="edit-btn">Edit</a>
                </div>
            <?php } ?>
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