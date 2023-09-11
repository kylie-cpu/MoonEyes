
<!-- Side navigation for every page -->
<div class="sidenav-banner">
    <div id="sidenav" class="sidenav">
        <a href="javascript:void(0)" class="closebtn" onclick="close_menu ();">&times;</a>
        <p>Welcome, <br><?php echo $name; ?>!</p>
        <a href="dashboard.php">Dashboard</a>
        <a href="case.php">Add a case</a>
        <a href="client.php">Add a client</a>
        <a href="subject.php">Add a subject</a>
        <a href="search.php">Search</a>  
        <?php
            // Check if the user has admin role before displaying admin controls
            if ($_SESSION['user'][0]['role'] == 'admin') {
                echo '<a href="admin.php">Admin Controls</a>';
            }
        ?>
         <a href="login-form.php" onclick="return confirm('Are you sure you want to log out?')">Log out</a>
    </div>

    <!-- Menu button & top banner-->
    <div class="banner">
        <div class="menu">
            <span style="font-size:18px;margin: 16px; cursor:pointer; color:#fff;" onclick="open_menu()"><a href='#'></a>&#9776; Open Menu </span>
        </div>
        <p style="margin-left: 30%">Moon Eyes: Metro Detective Agency Case Tracking System</p>
    </div>
</div>

<script>
    // Sidenav open & close functionality
    function open_menu() {
        document.getElementById("sidenav").style.width = "210px";
        document.getElementById("content").style.marginLeft = "210px";
    }

    function close_menu() {
        document.getElementById("sidenav").style.width = "0";
        document.getElementById("content").style.marginLeft = "0";
    }
</script>