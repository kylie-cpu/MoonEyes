
<?php 
    session_start();

    if (!isset($_SESSION['user'])) {
        header("Location: ../login/login-form.php");
        exit();
    }
    
    $user = $_SESSION['user'];
    $name = $user['name'];

    include('../database/connection.php');

    function getMatchingColumn($row, $searchTerm) {
        foreach ($row as $column => $value) {
            $position = stripos($value, $searchTerm);
            if ($position !== false) {
                $start = max(0, $position - 15);
                $end = min(strlen($value), $position + strlen($searchTerm) + 25);
                $substring = substr($value, $start, $end - $start);
                return "$column: ...$substring...";
            }
        }
        return '';
    }

    $searchTerm = isset($_GET['normal']) ? $_GET['normal'] : '';
    $searchTerm = preg_replace("/'/", "", $searchTerm);
    if ($_GET && isset($_GET['normal']))  {

        // Search clients
        $query_clients = "SELECT DISTINCT clients.*, lawyers.lawyer_name AS lawyer_name, cases.title AS case_title, tags.name as tag_name
            FROM clients
            LEFT JOIN lawyers ON clients.lawyer = lawyers.lawyer_id
            LEFT JOIN case_client ON clients.client_id = case_client.client_id
            LEFT JOIN cases ON cases.case_id = case_client.case_id
            LEFT JOIN tag_assoc ON tag_assoc.assoc_id = clients.client_id
            LEFT JOIN tags ON tags.tag_id = tag_assoc.tag_id
            WHERE
            clients.client_id LIKE '%$searchTerm%' OR
            clients.client_name LIKE '%$searchTerm%' OR
            clients.email LIKE '%$searchTerm%' OR
            clients.address LIKE '%$searchTerm%' OR
            clients.phone_num LIKE '%$searchTerm%' OR
            clients.notes LIKE '%$searchTerm%' OR
            clients.ud1 LIKE '%$searchTerm%' OR
            clients.ud2 LIKE '%$searchTerm%' OR
            clients.ud3 LIKE '%$searchTerm%' OR
            clients.ud4 LIKE '%$searchTerm%' OR 
            lawyers.lawyer_name LIKE '%$searchTerm%' OR 
            cases.title LIKE '%$searchTerm%' OR
            tags.name LIKE '%$searchTerm%'";
        $result_clients = $conn->query($query_clients);
        $clients = $result_clients->fetch_all(MYSQLI_ASSOC);
    
        // Search cases
        $query_cases = "SELECT DISTINCT cases.*, clients.client_name AS client_name, subjects.subject_name AS subject_name, agents.name AS agent_name, tags.name AS tag_name
            FROM cases
            LEFT JOIN case_client ON cases.case_id = case_client.case_id
            LEFT JOIN clients ON case_client.client_id = clients.client_id 
            LEFT JOIN case_subject ON cases.case_id = case_subject.case_id
            LEFT JOIN subjects ON case_subject.subject_id = subjects.subject_id
            LEFT JOIN case_agent ON cases.case_id = case_agent.case_id
            LEFT JOIN agents ON case_agent.agent_id = agents.agent_id
            LEFT JOIN tag_assoc ON tag_assoc.assoc_id = cases.case_id
            LEFT JOIN tags ON tags.tag_id = tag_assoc.tag_id
            WHERE
            cases.case_id LIKE '%$searchTerm%' OR
            cases.title LIKE '%$searchTerm%' OR
            cases.purpose LIKE '%$searchTerm%' OR
            cases.status LIKE '%$searchTerm%' OR
            cases.invoice LIKE '%$searchTerm%' OR
            cases.notes LIKE '%$searchTerm%' OR
            cases.ud1 LIKE '%$searchTerm%' OR
            cases.ud2 LIKE '%$searchTerm%' OR
            cases.ud3 LIKE '%$searchTerm%' OR
            cases.ud4 LIKE '%$searchTerm%' OR
            clients.client_name LIKE '%$searchTerm%' OR
            subjects.subject_name LIKE '%$searchTerm%' OR
            agents.name LIKE '%$searchTerm%' OR
            tags.name LIKE '%$searchTerm%'";
        $result_cases = $conn->query($query_cases);
        $cases = $result_cases->fetch_all(MYSQLI_ASSOC);
    
        // Search subjects
        $query_subjects = "SELECT DISTINCT subjects.*, cases.title AS case_title, lawyers.lawyer_name AS lawyer_name, tags.name AS tag_name
            FROM subjects
            LEFT JOIN case_subject ON subjects.subject_id = case_subject.subject_id
            LEFT JOIN cases ON case_subject.case_id = cases.case_id
            LEFT JOIN lawyers ON subjects.lawyer = lawyers.lawyer_id
            LEFT JOIN tag_assoc ON subjects.subject_id = tag_assoc.assoc_id
            LEFT JOIN tags ON tag_assoc.tag_id = tags.tag_id
            WHERE 
            subjects.subject_id LIKE '%$searchTerm%' OR
            subjects.subject_name LIKE '%$searchTerm%' OR
            subjects.address LIKE '%$searchTerm%' OR
            subjects.phone_nums LIKE '%$searchTerm%' OR
            subjects.associates LIKE '%$searchTerm%' OR
            subjects.vehicle_info LIKE '%$searchTerm%' OR
            subjects.place_of_work LIKE '%$searchTerm%' OR
            subjects.gps LIKE '%$searchTerm%' OR
            subjects.notes LIKE '%$searchTerm%' OR
            subjects.ud1 LIKE '%$searchTerm%' OR
            subjects.ud2 LIKE '%$searchTerm%' OR
            subjects.ud3 LIKE '%$searchTerm%' OR
            subjects.ud4 LIKE '%$searchTerm%' OR
            cases.title LIKE '%$searchTerm%' OR
            tags.name LIKE '%$searchTerm%'";
        $result_subjects = $conn->query($query_subjects);
        $subjects = $result_subjects->fetch_all(MYSQLI_ASSOC);
    
        // Search Tags
        $query_tags = "SELECT DISTINCT tags.*, clients.client_name AS client_name, subjects.subject_name AS subject_name, agents.name AS agent_name
            FROM tags
            LEFT JOIN tag_assoc ON tags.tag_id = tag_assoc.tag_id
            LEFT JOIN clients ON tag_assoc.assoc_id = clients.client_id
            LEFT JOIN subjects ON tag_assoc.assoc_id = subjects.subject_id
            LEFT JOIN agents ON tag_assoc.assoc_id = agents.agent_id
            WHERE 
            tags.tag_id LIKE '%$searchTerm%' OR
            tags.name LIKE '%$searchTerm%' OR
            clients.client_name LIKE '%$searchTerm%' OR
            subjects.subject_name LIKE '%$searchTerm%' OR
            agents.name LIKE '%$searchTerm%'";
        $result_tags = $conn->query($query_tags);
        $tags = $result_tags->fetch_all(MYSQLI_ASSOC);

        // Search agents
        $query_agents = "SELECT DISTINCT agents.*, cases.title AS case_title, tags.name AS tag_name
            FROM agents
            LEFT JOIN case_agent ON agents.agent_id = case_agent.agent_id
            LEFT JOIN cases ON case_agent.case_id = cases.case_id
            LEFT JOIN tag_assoc ON agents.agent_id = tag_assoc.assoc_id
            LEFT JOIN tags ON tag_assoc.tag_id = tags.tag_id
            WHERE 
            agents.agent_id LIKE '%$searchTerm%' OR
            agents.username LIKE '%$searchTerm%' OR
            agents.name LIKE '%$searchTerm%' OR
            agents.email LIKE '%$searchTerm%' OR
            cases.title LIKE '%$searchTerm%' OR
            tags.name LIKE '%$searchTerm%'";
        $result_agents = $conn->query($query_agents);
        $agents = $result_agents->fetch_all(MYSQLI_ASSOC);
    }


    if ($_GET && isset($_GET['filter'])) {
        $searchTerm = isset($_GET['filter']) ? $_GET['filter'] : '';
        $selectedColumns = isset($_GET['columns']) ? $_GET['columns'] : [];

        foreach ($selectedColumns as $tableName => $columns) {
            $resultData[$tableName] = [];

            foreach ($columns as $column) {
                $query = "SELECT DISTINCT *
                    FROM $tableName
                    WHERE $column LIKE '%$searchTerm%'";

                $result = $conn->query($query);

                if ($result) {
                    $resultData[$tableName] = array_merge($resultData[$tableName], $result->fetch_all(MYSQLI_ASSOC));
                }
            }
        }

        $clients = isset($resultData['clients']) ? $resultData['clients'] : [];
        $cases = isset($resultData['cases']) ? $resultData['cases'] : [];
        $subjects = isset($resultData['subjects']) ? $resultData['subjects'] : [];
        $tags = isset($resultData['tags']) ? $resultData['tags'] : [];
        $agents = isset($resultData['agents']) ? $resultData['agents']  : [];
    }

?>

<!DOCTYPE html>
<html>
    <head>
        <title>Search</title>
        <link rel="stylesheet" type="text/css" href="../css/search.css">
        <link rel="stylesheet" type="text/css" href="../css/sidenav.css">
    </head>
    <body>
        <?php include("../nav/sidenav.php"); ?>
        <div id="content" class="content">
            <h1>Search Database</h1>
            <div class="search">
            <button class="filter" onclick="openFilter();">&#9207; Filter Search</button>
                <form action="search.php"  method="GET">
                    <input type="text" id="search" name="normal" placeholder="Search any term..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <button type="submit" class="search-btn" >&#x2315; Search</button>
                </form>
            </div>
            <div class="results">
                <table class= "resultsTable">
                    <tr>
                        <th>Result Type</th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Matching Field</th>
                    </tr>
                    <?php $seen_client_ids = []; ?>
                    <?php if (!empty($clients)): ?>
                        <?php foreach ($clients as $client): ?>
                            <?php if (!in_array($client['client_id'], $seen_client_ids)): ?>
                                <tr>
                                    <td>Client</td>
                                    <td><a href="../details/client_details.php?client_id=<?php echo $client['client_id']; ?>"><?php echo $client['client_id']; ?></a></td>
                                    <td><?php echo $client['client_name']; ?></td>
                                    <td><?php echo getMatchingColumn($client, $searchTerm); ?></td>
                                </tr>
                                <?php $seen_client_ids[] = $client['client_id']; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php $seen_case_ids = []; ?>
                    <?php if (!empty($cases)): ?>
                        <?php foreach ($cases as $case): ?>
                            <?php if (!in_array($case['case_id'], $seen_case_ids)): ?>
                                <tr>
                                    <td>Case</td>
                                    <td><a href="../details/case_details.php?case_id=<?php echo $case['case_id']; ?>"><?php echo $case['case_id']; ?></a></td>
                                    <td><?php echo $case['title']; ?></td>
                                    <td><?php echo getMatchingColumn($case, $searchTerm); ?></td>
                                </tr>
                                <?php $seen_case_ids[] = $case['case_id']; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php $seen_subject_ids = []; ?>
                    <?php if (!empty($subjects)): ?>
                        <?php foreach ($subjects as $subject): ?>
                            <?php if (!in_array($subject['subject_id'], $seen_subject_ids)): ?>
                                <tr>
                                    <td>Subject</td>
                                    <td><a href="../details/subject_details.php?subject_id=<?php echo $subject['subject_id']; ?>"><?php echo $subject['subject_id']; ?></a></td>
                                    <td><?php echo $subject['subject_name']; ?></td>
                                    <td><?php echo getMatchingColumn($subject, $searchTerm); ?></td>
                                </tr>
                                <?php $seen_subject_ids[] = $subject['subject_id']; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php $seen_tag_ids = []; ?>
                    <?php if (!empty($tags)): ?>
                        <?php foreach ($tags as $tag): ?>
                            <?php if (!in_array($tag['tag_id'], $seen_tag_ids)): ?>
                                <tr>
                                    <td>Tag</td>
                                    <td><a href="../details/tag_details.php?tag_id=<?php echo $tag['tag_id']; ?>"><?php echo $tag['tag_id']; ?></a></td>
                                    <td><?php echo $tag['name']; ?></td>
                                    <td><?php echo getMatchingColumn($tag, $searchTerm); ?></td>
                                </tr>
                                <?php $seen_tag_ids[] = $tag['tag_id']; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php $seen_agent_ids = []; ?>
                    <?php if (!empty($agents)): ?>
                        <?php foreach ($agents as $agent): ?>
                            <?php if (!in_array($agent['agent_id'], $seen_agent_ids)): ?>
                                <tr>
                                    <td>Agent</td>
                                    <td><a href="../details/agent_details.php?agent_id=<?php echo $agent['agent_id']; ?>"><?php echo $agent['agent_id']; ?></a></td>
                                    <td><?php echo $agent['name']; ?></td>
                                    <td><?php echo getMatchingColumn($agent, $searchTerm); ?></td>
                                </tr>
                                <?php $seen_agent_ids[] = $agent['agent_id']; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (empty($seen_client_ids) && empty($seen_case_ids) && empty($seen_subject_ids) && empty($seen_tag_ids) && empty($seen_agent_ids)): ?>
                        <tr>
                            <td colspan="4">No results found.</td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
            <div id="mySidebar" class="sidebar">
                <a href="javascript:void(0)" class="closebtn" onclick="closeFilter();">&times;</a>
                <form action="search.php" name="filter" method="GET">
                    <input type="text" id="search" name="filter" placeholder="Search by a specific field..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <button type="submit" class="search-btn">&#x2315; Filtered Search</button>

                    <!-- Checkboxes for Agents -->
                    <h2>Search Filtering</h2>
                    <h3>Agents</h3>
                    <label><input type="checkbox" name="columns[agents][]" value="agent_id">Agent ID</label>
                    <label><input type="checkbox" name="columns[agents][]" value="username">Username</label><br>
                    <label><input type="checkbox" name="columns[agents][]" value="name">Name</label>
                    <label><input type="checkbox" name="columns[agents][]" value="email">Email</label><br>

                    <!-- Checkboxes for Clients -->
                    <h3>Clients</h3>
                    <label><input type="checkbox" name="columns[clients][]" value="client_id">Client ID</label>
                    <label><input type="checkbox" name="columns[clients][]" value="client_name">Name</label><br>
                    <label><input type="checkbox" name="columns[clients][]" value="email">Email</label>
                    <label><input type="checkbox" name="columns[clients][]" value="address">Address</label><br>
                    <label><input type="checkbox" name="columns[clients][]" value="phone_num">Phone Number</label><br>
                    <label><input type="checkbox" name="columns[clients][]" value="notes">Notes</label><br>
                    <label><input type="checkbox" name="columns[clients][]" value="ud1">Field 1</label>
                    <label><input type="checkbox" name="columns[clients][]" value="ud2">Field 2</label><br>
                    <label><input type="checkbox" name="columns[clients][]" value="ud3">Field 3</label>
                    <label><input type="checkbox" name="columns[clients][]" value="ud4">Field 4</label><br>

                    <!-- Checkboxes for Cases -->
                    <h3>Cases</h3>
                    <label><input type="checkbox" name="columns[cases][]" value="case_id">Case ID</label>
                    <label><input type="checkbox" name="columns[cases][]" value="title">Title</label><br>
                    <label><input type="checkbox" name="columns[cases][]" value="purpose">Purpose</label>
                    <label><input type="checkbox" name="columns[cases][]" value="status">Status</label><br>
                    <label><input type="checkbox" name="columns[cases][]" value="invoice">Invoice</label>
                    <label><input type="checkbox" name="columns[cases][]" value="notes">Notes</label><br>
                    <label><input type="checkbox" name="columns[cases][]" value="ud1">Field 1</label>
                    <label><input type="checkbox" name="columns[cases][]" value="ud2">Field 2</label><br>
                    <label><input type="checkbox" name="columns[cases][]" value="ud3">Field 3</label>
                    <label><input type="checkbox" name="columns[cases][]" value="ud4">Field 4</label><br>

                    <!-- Checkboxes for Subjects -->
                    <h3>Subjects</h3>
                    <label><input type="checkbox" name="columns[subjects][]" value="subject_id">Subject ID</label><br>
                    <label><input type="checkbox" name="columns[subjects][]" value="subject_name">Name</label>
                    <label><input type="checkbox" name="columns[subjects][]" value="address">Address</label><br>
                    <label><input type="checkbox" name="columns[subjects][]" value="phone_nums">Phone Numbers</label><br>
                    <label><input type="checkbox" name="columns[subjects][]" value="associates">Associates</label><br>
                    <label><input type="checkbox" name="columns[subjects][]" value="vehicle_info">Vehicle Information</label><br>
                    <label><input type="checkbox" name="columns[subjects][]" value="place_of_work">Place of Work</label><br>
                    <label><input type="checkbox" name="columns[subjects][]" value="gps">GPS Tracking</label>
                    <label><input type="checkbox" name="columns[subjects][]" value="notes">Notes</label><br>
                    <label><input type="checkbox" name="columns[subjects][]" value="ud1">Field 1</label>
                    <label><input type="checkbox" name="columns[subjects][]" value="ud2">Field 2</label><br>
                    <label><input type="checkbox" name="columns[subjects][]" value="ud3">Field 3</label>
                    <label><input type="checkbox" name="columns[subjects][]" value="ud4">Field 4</label><br>`

                    <!-- Checkboxes for Tags -->
                    <h3>Tags</h3>
                    <label><input type="checkbox" name="columns[tags][]" value="tag_id">Tag ID</label>
                    <label><input type="checkbox" name="columns[tags][]" value="name">Name</label><br>
                </form>
            </div>
        <script>
        // JS functions to open and close the sidebar
        function openFilter() {
            document.getElementById("mySidebar").style.width = "400px";
            document.getElementById("content").style.marginLeft = "250px";
        }

        function closeFilter() {
            document.getElementById("mySidebar").style.width = "0";
            document.getElementById("content").style.marginLeft = "0";
        }
        </script>
    </body>
</html>