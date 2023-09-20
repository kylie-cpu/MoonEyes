<?php
    session_start();
    $user = $_SESSION['user'];
    $name = $user[0]['name'];

    //get client ID and initizialize agent_id for modified_by field
    $client_id = $_GET['client_id'];
    $new_agent_id = $user[0]['agent_id'];

    include('database/connection.php');

    // Create date for date modified field
    date_default_timezone_set('America/Detroit');
    $new_date = date('Y-m-d H:i:s');
    
    // Populate dropdowns
    include('dropdowns.php');

    // get client details from clients
    $query_client_details = "SELECT clients.*, agents.name AS mod_agent, lawyers.lawyer_name AS lawyer_name, lawyers.lawyer_email AS lawyer_email, lawyers.lawyer_id AS lawyer_id
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
    $assoc_cases = [];
    $query_assoc_cases = "SELECT cases.title
    FROM cases
    LEFT JOIN case_client ON cases.case_id = case_client.case_id
    WHERE case_client.client_id = '$client_id'
    GROUP BY cases.case_id";
    $result_assoc_cases = $conn->query($query_assoc_cases);
    if ($result_assoc_cases->num_rows > 0) {
        $assoc_cases = $result_assoc_cases->fetch_all(MYSQLI_ASSOC);
    }


    // if submit button is clicked
    if ($_POST) {
        $client_id = $_POST['client_id'];
        $client_name = $_POST['name'];
        $email = $_POST['email'];
        $address = $_POST['address'];
        $phone_num = $_POST['phone'];
        $lawyer_name = $_POST['lawyer-name'];
        $lawyer_email =  $_POST['lawyer-email'];
        $lawyer_id = $_POST['lawyer-id'];
        // regex out single quotes from notes...
        $notes = preg_replace("/'/", "", $_POST['notes']);
        $modified_by = $new_agent_id;
        $day_modified = $new_date;

        // update clients table
        $update_client_details = "UPDATE clients SET 
        client_name = '$client_name',
        email = '$email',
        address = '$address',
        phone_num = '$phone_num',
        notes = '$notes',
        lawyer = '$lawyer_id',
        modified_by = '$modified_by',
        day_modified = '$day_modified'
        WHERE client_id = '$client_id'";

        $conn->query($update_client_details); 

        // delete old cases entries
        $delete_old_cases = "DELETE FROM case_client WHERE client_id = '$client_id'";
        $conn->query($delete_old_cases); 

        // insert new into case_client table
        $related_cases = $_POST['related_cases'];
        foreach ($related_cases as $related_case) {
          $query = "SELECT case_id FROM cases WHERE title = '$related_case'";
          $result = $conn->query($query);
          if ($result->num_rows > 0) {
            $case_id = $result->fetch_assoc()['case_id'];
          }
          $insert_case_client = "INSERT INTO case_client(case_id, client_id) VALUES ('$case_id', '$client_id')";
          if ($conn->query($insert_case_client) !== TRUE) {
              echo "Error inserting into case_client";
          }
        }

        // update lawyers table
        $delete_existing_lawyers = "DELETE FROM lawyers WHERE lawyer_id = '$lawyer_id'";
        $conn->query($delete_existing_lawyers);
        $insert_lawyer_details = "INSERT INTO lawyers (lawyer_id, lawyer_name, lawyer_email) VALUES ('$lawyer_id', '$lawyer_name', '$lawyer_email')";
        $conn->query($insert_lawyer_details);

        // Redirect back to dashboard after submission
        header("Location: dashboard.php"); 
        exit;
    }

?>

<!DOCTYPE html>
<html>
  <head>
    <title>Edit Client</title>
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
        <h2>Edit <?php echo $client_id?></h2>
        <form action="edit_client.php" method="POST" class="client-form">
            <?php if (!empty($client_details)) {
            $client = $client_details[0];  ?>
                <div class="form-group">
                    <label for="client_id"><span class="required">*</span>Client ID:</label>
                    <input type="text" id="client_id" name="client_id" value="<?php echo $client['client_id']; ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="name"><span class="required">*</span>Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo $client['client_name']; ?>"required>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo $client['email']; ?>">
                </div>

                <div class="form-group">
                    <label for="address">Address:</label>
                    <textarea id="address" name="address" rows="4" ><?php echo $client['address']; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number:</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo $client['phone_num']; ?>">
                </div>

                <div class="form-group">
                    <h3>Lawyer Information:</h3>
                    <input type="hidden" id="lawyer-id" name="lawyer-id" value="<?php 
                    // get old lawyer or create new id if one did not previously exist
                    if (!empty($client['lawyer'])){
                        echo $client['lawyer'];
                    }
                    else {
                        $unique_lawyer_id = "LAWYER-" . uniqid();
                        echo $unique_lawyer_id;
                    }; ?>">

                    <div class="sub-form-group">
                        <label for="lawyer-name">Lawyer Name:</label>
                        <input type="text" id="lawyer-name" name="lawyer-name" value="<?php echo $client['lawyer_name']; ?>">
                    </div>

                    <div class="sub-form-group">
                        <label for="lawyer-email">Lawyer Email:</label>
                        <input type="email" id="lawyer-email" name="lawyer-email" value="<?php echo $client['lawyer_email']; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Notes:</label>
                    <textarea id="notes" name="notes" rows="4"><?php echo $client['notes']; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="organization_tags">Organization Tags:</label>
                    <input type="text" id="organization_tags" name="organization_tags">
                </div>

                <div class="form-group">
                    <label for="media">Additional Media:</label>
                    <input type="file" id="media" name="media">
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
                    <input type="datetime-local" id="day_modified" name="day_modified" value="<?php echo $new_date; ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="modified_by"><span class="required">*</span>Modified By:</label>
                    <input type="text" id="modified_by" name="modified_by" value="<?php echo $name; ?>" readonly>
                </div>

                <div class="form-group">
                    <button type="submit" class="submit-btn">Submit</button>
                    <a href="dashboard.php" class="discard-btn" onclick="return confirm('Are you sure you want to discard? No data will be saved.')">Discard</a>
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