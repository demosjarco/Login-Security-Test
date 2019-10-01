<?php declare(strict_types=1);
include_once("../main.variables.php");

$db = new mysqli($mysql_host, $mysql_username_login, $mysql_password_login, $mysql_db);

if ($db->connect_errno) {
    die("Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error);
}

if ($stmt = $db->prepare("SELECT username, password, email FROM pending_accounts WHERE verify_token = ? LIMIT 1")) {
	$stmt->bind_param("s", $_GET["token"]);
	$stmt->execute();
	$stmt->store_result();

	// get variables from result
	$stmt->bind_result($db_username, $db_password, $db_email);
	$stmt->fetch();
}
?>
<!doctype html>
<html>
<head>
<meta name="viewport" content="width=device-width" />
<meta charset="utf-8" />
<title>Account Verification</title>
<link rel="stylesheet" href="css/default.css" type="text/css" />
<link rel="stylesheet" href="css/login.css" type="text/css" />
</head>

<body>
<div id="left">
	<a href="https://test.demosjarco.dev:8443/"><div id="logo"></div></a>
</div>
<div id="right">
	<form action="">
		<?php
		if ($stmt->num_rows == 1) {
			// Verify code found and valid
			if ($makeAccStmt = $db->prepare("INSERT INTO accounts (username, password, email) VALUES (?,?,?)")) {
				$makeAccStmt->bind_param("sss", $db_username, $db_password, $db_email);
				$makeAccStmt->execute();
			}
			if ($deletePendAccStmt = $db->prepare("DELETE FROM pending_accounts WHERE username= ?")) {
				$deletePendAccStmt->bind_param("s", $db_username);
				$deletePendAccStmt->execute();
			}
			echo '<p class="text message success">Your account has been verified and is ready to be used. Click on our logo to go back to the login page.</p>';
		} else {
			// Verify code not found
			echo '<p class="text message error">You have followed an invalid account registration link. Please create an account before verifying an account.</p>';
		}
		?>
	</form>
</div>
</body>
</html>
<?php mysqli_close($db); ?>