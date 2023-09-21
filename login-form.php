<?php
	session_start();
	if($_POST){
		$username = $_POST['username'];
		$password = $_POST['password'];

		include('database/connection.php');

		$query="SELECT * FROM agents WHERE agents.username = '$username' AND agents.password = '$password' LIMIT 1";
		$stmt = $conn->prepare($query);
		$stmt->execute();
		$result = $stmt->get_result();
		$num_rows = $result->num_rows;
		if ($num_rows === 1) {
			$user = $result->fetch_all(MYSQLI_ASSOC);
			$_SESSION['user'] = $user;
			header('Location: dashboard.php');
			exit();
		} else {
			echo "<font color=red size=6px> ERROR: Invalid username or password.";
		}
	}

?>

<!DOCTYPE html>
<html>
	<head>
		<title>Moon Eyes - Login</title>
		<link rel="stylesheet" type="text/css" href="css/login-form.css">
</head>
<body>
	<div class="mainbox">
		<div class="logo">
			<img src="images/logo.png" alt="Logo">
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