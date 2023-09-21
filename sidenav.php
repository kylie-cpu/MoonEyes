<!-- Top navigation bar -->
<div class="top-navbar">
    <!-- Logo -->
    <div class="logo">
        <img src="images/logo2.png" alt="Logo">
    </div>

    <a href="dashboard.php">&#x1F3E0; Home</a>

    <!-- Dropdown for "Add" -->
    <div class="dropdown">
        <div class="dropbtn"><a href=#>&#128193; Add</a></div>
        <div class="dropdown-content">
            <a href="case.php">Add a Case</a>
            <a href="client.php">Add a Client</a>
            <a href="subject.php">Add a Subject</a>
            <a href="tags.php">Add a Tag</a>
        </div>
    </div>
    <!-- Other links -->
    <a href="search.php">&#x1F50D; Search</a>
    <a href="email.php">&#128232; Email</a>
    <?php
        // Check if the user has admin role before displaying admin controls
        if ($_SESSION['user'][0]['role'] == 'admin') {
            echo '<a href="admin.php">&#x1F4BB; Admin Controls</a>';
        }
    ?>
    <a href="login-form.php" onclick="return confirm('Are you sure you want to log out?')">&hookrightarrow; Log out</a>
</div>