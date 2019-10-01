<?php declare(strict_types=1);
include_once("../main.variables.php");

$db = new mysqli($mysql_host, $mysql_username_login, $mysql_password_login, $mysql_db);

if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}

function checkbrute($user_id, $db) {
	// All login attempts from last 1 hours
	if ($stmt = $db->prepare("SELECT time FROM login_attempts WHERE user_id = ? AND time > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 HOUR)")) {
		$stmt->bind_param("i", $user_id);
		$stmt->execute();
		$stmt->store_result();
		
		// More than 5 failed logins
		if ($stmt->num_rows > 5) {
			$stmt->close();
			return true;
		} else {
			$stmt->close();
			return false;
		}
	}
}

if (isset($_REQUEST["submission"]) && $_REQUEST["submission"] == 1) {
	// Login Attempt
	if ($stmt = $db->prepare("SELECT id, username, password, email, locked FROM accounts WHERE username = ? LIMIT 1")) {
		$stmt->bind_param("s", $_REQUEST["uname"]);
		$stmt->execute();
		$stmt->store_result();

		// get variables from result
		$stmt->bind_result($user_id, $db_username, $db_password, $db_email, $db_locked);
		$stmt->fetch();

		if ($stmt->num_rows == 1) {
			// User exists
			// Check for bruteforce
			if (checkbrute($user_id, $db) || $db_locked == 1) {
				// Account is locked
				if ($lockAccStmt = $db->prepare("UPDATE accounts SET locked=1 WHERE id= ?")) {
					$lockAccStmt->bind_param("i", $user_id);
					$lockAccStmt->execute();
				}
			} else {
				// Account good
				if (password_verify($_REQUEST["passwd"], $db_password)) {
					// Correct password
					session_start();
					session_regenerate_id(true);

					if (password_needs_rehash($db_password, $password_hash, $password_options)) {
						// New hashing algo available -> rehash
						$newHash = password_hash($_REQUEST["passwd"], $password_hash, $password_options);
						if ($updatePassHashStmt = $db->prepare("UPDATE accounts SET password= ? WHERE id= ?")) {
							$updatePassHashStmt->bind_param("si", $newHash, $user_id);
							$updatePassHashStmt->execute();
						}
					}
					
					if ($sessionStmt = $db->prepare("INSERT INTO `sessions` (sessionId, `user_id`, ip_address) VALUES (?,?,?) ON DUPLICATE KEY UPDATE sessionId=VALUES(sessionId), ip_address=VALUES(ip_address)")) {
						$ip = $_SERVER["REMOTE_ADDR"];
						$sessionStmt->bind_param("sis", session_id(), $user_id, $ip);
						$sessionStmt->execute();
					}
					
					header("Location: dashboard.php");
					exit();
				} else {
					// Wrong password
					// Save in bruteforce db
					if ($wrongPasswordStmt = $db->prepare("INSERT INTO login_attempts (`user_id`, ip_address) VALUES (?,?)")) {
						$ip = $_SERVER["REMOTE_ADDR"];
						$wrongPasswordStmt->bind_param("is", $user_id, $ip);
						$wrongPasswordStmt->execute();
					}
				}
			}
		}
	}
}
?>
<!doctype html>
<html>
<head>
<meta name="viewport" content="width=device-width" />
<meta charset="utf-8" />
<title>Login</title>
</head>

<body>
	<form action="" method="post">
		<?php
		if (isset($_REQUEST["submission"]) && $_REQUEST["submission"] == 1) {
			// Login Attempt
			if ($stmt = $db->prepare("SELECT id, username, password, email, locked FROM accounts WHERE username = ? LIMIT 1")) {
				$stmt->bind_param("s", $_REQUEST["uname"]);
				$stmt->execute();
				$stmt->store_result();

				// get variables from result
				$stmt->bind_result($user_id, $db_username, $db_password, $db_email, $db_locked);
				$stmt->fetch();

				if ($stmt->num_rows == 1) {
					// User exists
					// Check for bruteforce
					if (checkbrute($user_id, $db) || $db_locked == 1) {
						// Account is locked
						if ($lockAccStmt = $db->prepare("UPDATE accounts SET locked=1 WHERE id= ?")) {
							$lockAccStmt->bind_param("i", $user_id);
							$lockAccStmt->execute();
						}

						// Send user password reset email
						$reset_token = uniqid("", true);
						mail($db_email, "M&M Mail Services | Account Locked", "Your account has been locked due to more than 5 failed logins in the past hour. To regain access to your account, please follow the password reset link below. The link below is valid for 24 hours.\n\nhttps://test.demosjarco.dev:8443/resetPassword.php?token=" . $reset_token, "From: accounts@mmmailservices.com\r\nReply-To: accounts@mmmailservices.com");

						if ($lockAccStmt = $db->prepare("INSERT INTO login_resets (user_id, reset_token) VALUES (?,?) ON DUPLICATE KEY UPDATE reset_token= ?")) {
							$lockAccStmt->bind_param("iss", $user_id, $reset_token, $reset_token);
							$lockAccStmt->execute();
						}

						echo '<p class="text message error">Your account has been locked due to too many attempts to login in a short period of time. Please check your email for instructions.</p>
						<p><input name="uname" type="text" required maxlength="50" placeholder="Username" /></p>
						<p><input name="passwd" type="password" required maxlength="255" placeholder="Password" /></p>
						<p><input name="submission" type="hidden" value="1" /><a id="forgot" href="requestReset.php"><span id="forgot">Forgot?</span></a><input type="submit" value="Log In" /><a href="createAccount.php"><span id="signup">Sign Up</span></a></p>';
					} else {
						// Account good
						if (password_verify($_REQUEST["passwd"], $db_password)) {
							// Correct password

//							echo '<p class="text message success">Logged in, session id: ' . session_id() . '</p>';
						} else {
							// Wrong password

							echo '<p class="text message error">The username or password is incorrect.</p>
							<p><input name="uname" type="text" required maxlength="50" placeholder="Username" /></p>
							<p><input name="passwd" type="password" required maxlength="255" placeholder="Password" /></p>
							<p><input name="submission" type="hidden" value="1" /><a id="forgot" href="requestReset.php"><span id="forgot">Forgot?</span></a><input type="submit" value="Log In" /><a href="createAccount.php"><span id="signup">Sign Up</span></a></p>';
						}
					}
				} else {
					// Wrong Username
					echo '<p class="text message error">The username or password is incorrect.</p>
					<p><input name="uname" type="text" required maxlength="50" placeholder="Username" /></p>
					<p><input name="passwd" type="password" required maxlength="255" placeholder="Password" /></p>
					<p><input name="submission" type="hidden" value="1" /><a id="forgot" href="requestReset.php"><span id="forgot">Forgot?</span></a><input type="submit" value="Log In" /><a href="createAccount.php"><span id="signup">Sign Up</span></a></p>';
				}
			}
		} else {
			// First time showing page
			echo '<p><input name="uname" type="text" required maxlength="50" placeholder="Username" /></p>
			<p><input name="passwd" type="password" required maxlength="255" placeholder="Password" /></p>
			<p><input name="submission" type="hidden" value="1" /><a id="forgot" href="requestReset.php"><span id="forgot">Forgot?</span></a><input type="submit" value="Log In" /><a href="createAccount.php"><span id="signup">Sign Up</span></a></p>';
		}
		?>
	</form>
</body>
</html>
<?php mysqli_close($db); ?>