
<?php 
    session_start();
    $user = $_SESSION['user'];
    $name = $user[0]['name'];

    include('database/connection.php');
    $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

    if($_GET) {
        // Search clients
        $query_clients = "SELECT * FROM clients WHERE client_name LIKE '%$searchTerm%' email LIKE '%$searchTerm%' OR address LIKE '%$searchTerm%' OR phone_num LIKE '%$searchTerm%' OR lawyer_name LIKE '%$searchTerm%' OR lawyer_email LIKE '%$searchTerm%'";
        $result_clients = $conn->query($query_clients);
        $clients = $result_clients->fetch_all(MYSQLI_ASSOC);



        // Search cases
        $query_cases = "SELECT * FROM cases WHERE title LIKE '%$searchTerm%' OR status LIKE '%$searchTerm%' OR invoice LIKE '%$searchTerm%' OR assoc_client LIKE '%$searchTerm%'";
        $result_cases = $conn->query($query_cases);
        $cases = $result_cases->fetch_all(MYSQLI_ASSOC);

 

        // Search subjects
        $query_subjects = "SELECT * FROM subjects WHERE subject_name LIKE '%$searchTerm%' OR address LIKE '%$searchTerm%' OR phone_nums LIKE '%$searchTerm%' OR vehicle_info LIKE '%$searchTerm%' OR pow LIKE '%$searchTerm%' OR associates LIKE '%$searchTerm%'";
        $result_subjects = $conn->query($query_subjects);
        $subjects = $result_subjects->fetch_all(MYSQLI_ASSOC);



        // Search agents
        $query_agents = "SELECT * FROM agent WHERE username LIKE '%$searchTerm%' OR new_name LIKE '%$searchTerm%' OR badge_num LIKE '%$searchTerm%' OR email LIKE '%$searchTerm%' OR role LIKE '%$searchTerm%' OR modified_by LIKE '%$searchTerm%' OR new_agent_id LIKE '%$searchTerm%'";
        $result_agents = $conn->query($query_agents);
        $agents = $result_agents->fetch_all(MYSQLI_ASSOC);
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Search</title>
        <link rel="stylesheet" type="text/css" href="css/search.css">
        <link rel="stylesheet" type="text/css" href="css/sidenav.css">
    </head>
    <body>
        <?php include("sidenav.php"); ?>
        <div id="content" class="content">
            <h1>Search Moon Eyes Database</h1>
            <div class="search">
                <form action="search.php" method="GET">
                    <input type="text" name="search" placeholder="Search any term..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <button type="submit" class="search-btn" >&#128270;Search</button>
                </form>
            </div>
            <div class="results">
                <?php if (!empty($clients)): ?>
                    <?php foreach ($clients as $client): ?>
                        <p>Client, <a href="client_details.php?client_id=<?php echo $client['client_id']; ?>"><?php echo $client['client_id']; ?></a>, 
                        <?php echo $client['client_name']; ?></p>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!empty($cases)): ?>
                    <?php foreach ($cases as $case): ?>
                        <p>Case, <a href="case_details.php?case_id=<?php echo $case['case_id']; ?>"><?php echo $case['case_id']; ?></a>,
                        <?php echo $case['title']; ?></p>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!empty($subjects)): ?>
                    <?php foreach ($subjects as $subject): ?>
                        <p>Subject, <a href="subject_details.php?subject_id=<?php echo $subject['subject_id']; ?>"><?php echo $subject['subject_id']; ?></a>,
                        <p><?php echo $subject['subject_name']; ?></p>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!empty($agent)): ?>
                    <?php foreach ($agent as $agent): ?>
                        <p>Agent, <a href="agent.php?subject_id=<?php echo $agent['new_agent_id']; ?>"><?php echo $agent['new_agent_id']; ?></a>,
                        <p><?php echo $agent['new_agent_name']; ?></p>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (empty($clients) && empty($cases) && empty($subjects) && empty($agent)) ?>
                    <p>No results found.</p>
                <?php endif; ?>
            </div>
        </div>
    </body>
</html>