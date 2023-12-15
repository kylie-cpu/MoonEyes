<!-- Top navigation bar -->
<div class="top-navbar">
    <!-- Logo -->
    <div class="logo">
        <img src="../images/logo2.png" alt="Logo">
    </div>

    <a href="../main/dashboard.php">&#x1F3E0; Home</a>

    <!-- Dropdown for "Add" -->
    <div class="dropdown">
        <div class="dropbtn"><a href=#>&#128193; Add</a></div>
        <div class="dropdown-content">
            <a href="../add/case.php">Add a Case</a>
            <a href="../add/client.php">Add a Client</a>
            <a href="../add/subject.php">Add a Subject</a>
            <a href="../add/tags.php">Add a Tag</a>
        </div>
    </div>
    <!-- Other links -->
    <a href="../other/search.php">&#x1F50D; Search</a>
    <?php
        // Check if the user has admin role before displaying admin controls
        if ($_SESSION['user']['role'] == 'admin') {
            echo '<a href="../other/admin.php">&#x1F4BB; Admin Controls</a>';
        }
    ?>
    <a href="../login/logout.php" onclick="return confirm('Are you sure you want to log out?')">&hookrightarrow; Log out</a>
</div>