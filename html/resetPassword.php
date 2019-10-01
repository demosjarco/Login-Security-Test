<?php declare(strict_types=1);
include_once("../main.variables.php");

$db = new mysqli($mysql_host, $mysql_username_login, $mysql_password_login, $mysql_db);

if ($db->connect_errno) {
    die("Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error);
}

if ($stmt = $db->prepare("SELECT a.username, a.locked FROM login_resets lr JOIN accounts a ON (lr.user_id=a.id) WHERE lr.reset_token = ? AND lr.date_requested > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 24 HOUR) LIMIT 1")) {
	$stmt->bind_param("s", $_GET["token"]);
	$stmt->execute();
	$stmt->store_result();

	// get variables from result
	$stmt->bind_result($db_username, $db_locked);
	$stmt->fetch();
}
?>
<!doctype html>
<html>
<head>
<meta name="viewport" content="width=device-width" />
<meta charset="utf-8" />
<title>Reset Password</title>
<link rel="stylesheet" href="css/default.css" type="text/css" />
<link rel="stylesheet" href="css/login.css" type="text/css" />
</head>

<body>
<div id="left">
	<a href="https://test.demosjarco.dev:8443/"><div id="logo"></div></a>
</div>
<div id="right">
	<form action="#" method="post">
		<?php
		if (isset($_REQUEST["submission"]) && $_REQUEST["submission"] == 1) {
			// Reset Attempt
			if ($resStmt = $db->prepare("SELECT a.id, a.password, a.email FROM accounts a JOIN login_resets lr ON (a.id=lr.user_id) WHERE lr.reset_token = ? LIMIT 1")) {
				$resStmt->bind_param("s", $_GET["token"]);
				$resStmt->execute();
				$resStmt->store_result();

				// get variables from result
				$resStmt->bind_result($user_id, $db_password, $db_email);
				$resStmt->fetch();

				if ($resStmt->num_rows == 1) {
					// User found
					if (!password_verify($_REQUEST["passwd"], $db_password)) {
						// Differend than last password
						if ($updatePassStmt = $db->prepare("UPDATE accounts SET password= ?, locked=0 WHERE id= ?")) {
							$newHash = password_hash($_REQUEST["passwd"], $password_hash, $password_options);
							$updatePassStmt->bind_param("si", $newHash, $user_id);
							$updatePassStmt->execute();
						}
						if ($emptyLoginAttStmt = $db->prepare("DELETE FROM login_attempts WHERE user_id= ?")) {
							$emptyLoginAttStmt->bind_param("i", $user_id);
							$emptyLoginAttStmt->execute();
						}
						if ($emptyLoginResStmt = $db->prepare("DELETE FROM login_resets WHERE user_id= ?")) {
							$emptyLoginResStmt->bind_param("i", $user_id);
							$emptyLoginResStmt->execute();
						}

						mail($db_email, "Website Test | Account Password Changed", "Your account password has been changed on " . date("M j, Y g:i:sa T") . ". If this was not you, please reset you password at: https://test.demosjarco.dev:8443/requestReset.php", "From: noreply@demosjarco.dev\r\nReply-To: noreply@demosjarco.dev");
						echo '<p class="text message success">Password has been successfully changed and is ready to be used. Click on our logo to go back to the login page.</p>';
						echo '<p>Your account password has been changed on ' . date("M j, Y g:i:sa T") . '. If this was not you, please reset you password at: <a href="https://test.demosjarco.dev:8443/requestReset.php">https://test.demosjarco.dev:8443/requestReset.php</a></p>';
					} else {
						// Same password retry
						echo '<p class="text message error">You cannot use your previous password as your new password. Please choose a different password.</p>
						<p><input type="text" disabled value="' . $db_username . '" /></p>
						<p><input name="passwd" type="password" required placeholder="Password" /></p>
						<p><input name="submission" type="hidden" value="1" /><input type="submit" value="Save" /></p>';
					}
				}
			}
		} else {
			// First time showing page
			if ($stmt->num_rows == 1) {
				// Reset code found and valid
				if ($db_locked == 1) {
					// Account locked
					echo '<p class="text message">Your account has been locked due to too many attempts to login in a short period of time. Please reset your password below.</p>';
				} else {
					// Account request to reset
					echo '<p class="text message">You have requested to change your password below. If this was a mistake, please close this page and you will remain with your current password.</p>';
				}
				echo '<p><input type="text" disabled value="' . $db_username . '" /></p>
				<p><input name="passwd" type="password" required placeholder="Password" /></p>
				<p><input name="submission" type="hidden" value="1" /><input type="submit" value="Save" /></p>';
			} else {
				// Reset code not found
				echo '<p class="text message error">You have followed an invalid account password link reset. Links are valid for 24 hours. Please request a new password agian.</p>';
			}
		}
    		?>
	</form>
</div>
</body>
</html>
<?php mysqli_close($db); ?>