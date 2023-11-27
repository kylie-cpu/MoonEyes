<?php
	session_start();
	include('../database/connection.php');

	if ($_POST) {
		$username = $_POST['username'];
		$password = $_POST['password'];
	
		$query = "SELECT * FROM agents WHERE username = ?";
		$stmt = $conn->prepare($query);
		$stmt->bind_param('s', $username);
		$stmt->execute();
		$result = $stmt->get_result();
		$user = $result->fetch_assoc();
		
		// See if hash matches
		if (password_verify($password, $user['password'])) {
			// Password is correct
			$_SESSION['user'] = $user;
			header('Location: ../main/dashboard.php');
			exit();
		} else {
			// Password incorrect
			echo "<font color=red size=6px> ERROR: Invalid username or password.";
		}
	}

?>

<!DOCTYPE html>
<html>
	<head>
		<title>Moon Eyes - Login</title>
		<link rel="stylesheet" type="text/css" href="../css/login-form.css">
</head>
<body>
	<div class="mainbox">
		<div class="logo">
			<img src="../images/logo.png" alt="Logo">
		</div>
		<div class="login-Body">
			<form action ="login-form.php" method="POST">
				<div class="loginbox">
					<input name="username" placeholder="username" type="text" />
				</div>
				<div class= "loginbox">
					<input name="password" placeholder="password" type="password" />
				</div>
				<div class="buttonbox">  <input type="submit" value="Sign In"></div>
			</form>
		</div> 
	</div>
</body>
</html>