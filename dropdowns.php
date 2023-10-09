<?php 
    //Case titles for drop down
    $query = "SELECT title FROM cases ORDER BY day_modified DESC";
    $result = $conn->query($query);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $case_titles[] = $row['title'];
        }
    }
    //Client names for drop down
    $query = "SELECT client_name FROM clients ORDER BY day_modified DESC";
    $result = $conn->query($query);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $client_names[] = $row['client_name'];
        }
    }

    //Subject names for drop down
    $query = "SELECT subject_name FROM subjects ORDER BY day_modified DESC";
    $result = $conn->query($query);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $subject_names[] = $row['subject_name'];
        }
    }

    //Agent names for drop down
    $query = "SELECT name FROM agents ORDER BY modified_at DESC";
    $result = $conn->query($query);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $agent_names[] = $row['name'];
        }
    }

?>