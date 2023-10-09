<?php
  session_start();
  $user = $_SESSION['user'];
  $name = $user[0]['name'];

  //Generate unique ID for subject and also initizialize agent_id for modified_by field
  $unique_client_id = "CLIENT-" . uniqid();
  $unique_lawyer_id = "LAWYER-" . uniqid();
  $agent_id = $user[0]['agent_id'];

  include('database/connection.php');

  //Create date for date modified field
  date_default_timezone_set('America/Detroit');
  $date = date('Y-m-d H:i:s');

  // Populate dropdowns
  include('dropdowns.php');
  
  //Submit form into clients database
  if ($_POST) {
    $client_id = $unique_client_id;
    $client_name = $_POST['name'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $phone_num = $_POST['phone'];
    $lawyer_id = $unique_lawyer_id;
    $lawyer_name = $_POST['lawyer-name'];
    $lawyer_email =  $_POST['lawyer-email'];
    $lawyer_ph =  $_POST['lawyer-ph'];
    // regex out single quotes from notes...
    $notes = preg_replace("/'/", "", $_POST['notes']);
    $ud1 = preg_replace("/'/", "", $_POST['ud1']);
    $ud2 = preg_replace("/'/", "", $_POST['ud2']);
    $ud3 = preg_replace("/'/", "", $_POST['ud3']);
    $ud4 = preg_replace("/'/", "", $_POST['ud4']);

    $modified_by = $agent_id;
    $day_modified = $date;

    //Submit data in clients
    $insert_client = "INSERT INTO clients(client_id, client_name, email, address, phone_num, lawyer, notes, ud1, ud2, ud3, ud4, modified_by, day_modified) 
    VALUES ('$client_id', '$client_name', '$email', '$address', '$phone_num', '$lawyer_id', '$notes', '$ud1', '$ud2', '$ud3', '$ud4', '$modified_by' ,'$day_modified')";
    if ($conn->query($insert_client) !== TRUE) {
      echo "Error inserting into clients";
   } 

   //Submit data into lawyer if email or name entered
   if (!empty($lawyer_name) || !empty($lawyer_email)) {
      $insert_lawyer = "INSERT INTO lawyers(lawyer_id, lawyer_name, lawyer_email, lawyer_ph) 
      VALUES ('$lawyer_id', '$lawyer_name', '$lawyer_email', '$lawyer_ph')";

      if ($conn->query($insert_lawyer) !== TRUE) {
          echo "Error inserting into lawyers";
      }
    }

    //Submit data in case_client
    $related_cases = $_POST['related_cases'];
    foreach ($related_cases as $related_case) {
      $query = "SELECT case_id FROM cases WHERE title = '$related_case'";
      $result = $conn->query($query);
      if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $Rcase_id = $row['case_id'];
      }
      $insert_case_client = "INSERT INTO case_client(case_id, client_id) VALUES ('$Rcase_id', '$client_id')";
      if ($conn->query($insert_case_client) !== TRUE) {
          echo "Error inserting into case_client";
      }
    }

    // Redirect back to dashboard after submission    
    header("Location: dashboard.php"); 
    exit;
    
  }
?>

<!DOCTYPE html>
<html>
  <head>
    <title>Add a Client</title>
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
      <h2>Add a Client</h2>
      <form action="client.php" method="POST" class="client-form">
        <div class="form-group1">
          <label for="client_id"><span class="required">*</span>Client ID</label>
          <input type="text" id="client_id" name="client_id" value="<?php echo $unique_client_id ?>"readonly>
        </div>

        <div class="form-group2">
          <label for="name"><span class="required">*</span>Name</label>
          <input type="text" id="name" name="name" required>
        </div>

        <div class="form-group1">
          <label for="email">Email</label>
          <input type="email" id="email" name="email">
        </div>

        <div class="form-group2">
          <label for="phone">Phone Number</label>
          <input type="tel" id="phone" name="phone">
        </div>

        <div class="form-group">
          <label for="address">Address</label>
          <textarea id="address" name="address" rows="4"></textarea>
        </div>

        <div class="form-group">
          <h3>Lawyer Information</h3>
          <div class="sub-form-group">
            <label for="lawyer-name">Lawyer Name</label>
            <input type="text" id="lawyer-name" name="lawyer-name">
          </div>

          <div class="sub-form-group">
            <label for="lawyer-email">Lawyer Email</label>
            <input type="email" id="lawyer-email" name="lawyer-email">
          </div>

          <div class="sub-form-group">
            <label for="lawyer-ph">Lawyer Phone Number</label>
            <input type="tel" id="lawyer-ph" name="lawyer-ph">
          </div>
        </div>

        <div class="form-group">
          <label for="organization_tags">Organization Tags</label>
          <input type="text" id="organization_tags" name="organization_tags">
        </div>

        <div class="form-group1">
          <label for="media">Additional Media</label>
          <input type="file" id="media" name="media" style="width: 35%;" multiple>
        </div>

        <div class="form-group">
          <label for="notes">Notes</label>
          <textarea id="notes" name="notes" rows="4"></textarea>
        </div>

        <div class="form-group1">
          <label for="ud1">Field 1</label>
          <input type="ud1" id="ud1" name="ud1">
        </div> 
       
	      <div class="form-group2">
          <label for="ud2">Field 2</label>
          <input type="ud2" id="ud2" name="ud2">
        </div>
       
	      <div class="form-group1">
          <label for="ud3"> Field 3</label>
          <input type="ud3" id="ud3" name="ud3">
        </div>
       
	      <div class="form-group2">
          <label for="ud4">Field 4</label>
          <input type="ud4" id="ud4" name="ud4">
        </div>

        <div class="form-group">
          <label for="related_cases">Associated Cases</label>
          <select id="related_cases" name="related_cases[]" class="js-example-basic-multiple-cases" multiple="multiple" style="width: 44%;"></select>
        </div>

        <div class="form-group1">
          <label for="day_modified"><span class="required">*</span>Date Modified</label>
          <input type="datetime-local" id="day_modified" name="day_modified" value="<?php echo $date ; ?>" readonly>
        </div>

        <div class="form-group2">
          <label for="modified_by"><span class="required">*</span>Modified By</label>
          <input type="text" id="modified_by" name="modified_by" value="<?php echo $name ?>" readonly>
        </div>
        
        <div class="form-group">
          <button type="submit" class="submit-btn">Submit</button>
          <a href="dashboard.php" class="discard-btn" onclick="return confirm('Are you sure you want to discard? No data will be saved.')">Discard</a>
        </div>
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