<?php declare(strict_types=1);
include_once("../main.variables.php");

$db = new mysqli($mysql_host, $mysql_username_login, $mysql_password_login, $mysql_db);

if ($db->connect_errno) {
    die("Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error);
}
?>
<!doctype html>
<html>
<head>
<meta name="viewport" content="width=device-width" />
<meta charset="utf-8" />
<title>Request Password Reset</title>
<link rel="stylesheet" href="css/default.css" type="text/css" />
<link rel="stylesheet" href="css/login.css" type="text/css" />
</head>

<body>
<div id="left">
	<a href="https://test.demosjarco.dev:8443/"><div id="logo"></div></a>
</div>
<div id="right">
	<form action="" method="post">
		<?php
		if (isset($_REQUEST["submission"]) && $_REQUEST["submission"] == 1) {
			echo '<p class="text message success">We have received your request. If the email you provided matches an account, you will receive an email containing the username and a password reset link. If do not need to change the password, just ignore that link and your password will remain the same. Password reset links expire after 24 hours.</p>';

			if ($stmt = $db->prepare("SELECT id, username FROM accounts WHERE email = ? LIMIT 1")) {
				$stmt->bind_param("s", $_REQUEST["uemail"]);
				$stmt->execute();
				$stmt->store_result();

				// get variables from result
				$stmt->bind_result($user_id, $db_username);
				$stmt->fetch();

				if ($stmt->num_rows == 1) {
					// Send user password reset email
					$reset_token = uniqid("", true);

					if ($lockAccStmt = $db->prepare("INSERT INTO login_resets (user_id, reset_token) VALUES (?,?) ON DUPLICATE KEY UPDATE reset_token= ?")) {
						$lockAccStmt->bind_param("iss", $user_id, $reset_token, $reset_token);
						$lockAccStmt->execute();
					}

					// create a new cURL resource
					$ch = curl_init();

					// set URL and other appropriate options
					curl_setopt($ch, CURLOPT_URL, "https://ipinfo.io/" . $_SERVER["REMOTE_ADDR"] . "/json");
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

					// grab URL and pass it to the browser
					$urlContent = curl_exec($ch);

					// Check if any error occured
					if (!curl_errno($ch)) {
						$ipLocationJson = json_decode($urlContent);
						mail($_REQUEST["uemail"], "Website Test | Forgot Account Info", "There has been a request to get get your username and/or change your password from " . $_SERVER["REMOTE_ADDR"] . " (" . $ipLocationJson->city . ", " . $ipLocationJson->region . ")\n\nUsername: " . $db_username . "\nPassword Reset Link: https://test.demosjarco.dev:8443/resetPassword.php?token=" . $reset_token, "From: noreply@demosjarco.dev\r\nReply-To: noreply@demosjarco.dev");
						echo '<p>There has been a request to get get your username and/or change your password from ' . $_SERVER["REMOTE_ADDR"] . ' (' . $ipLocationJson->city . ', ' . $ipLocationJson->region . ')</p><p>Username: ' . $db_username . '<br />Password Reset Link: <a href="https://test.demosjarco.dev:8443/resetPassword.php?token=' . $reset_token . '">https://test.demosjarco.dev:8443/resetPassword.php?token=' . $reset_token . '</a></p>';
					} else {
						mail($_REQUEST["uemail"], "Website Test | Forgot Account Info", "There has been a request to get get your username and/or change your password from " . $_SERVER["REMOTE_ADDR"] . " (Unknown Location)\n\nUsername: " . $db_username . "\nPassword Reset Link: https://test.demosjarco.dev:8443/resetPassword.php?token=" . $reset_token, "From: noreply@demosjarco.dev\r\nReply-To: noreply@demosjarco.dev");
						echo '<p>There has been a request to get get your username and/or change your password from ' . $_SERVER["REMOTE_ADDR"] . ' (Unknown Location)\n\nUsername: ' . $db_username . '\nPassword Reset Link: <a href="https://test.demosjarco.dev:8443/resetPassword.php?token=' . $reset_token . '">https://test.demosjarco.dev:8443/resetPassword.php?token=' . $reset_token . '</a></p>';
					}

					// close cURL resource, and free up system resources
					curl_close($ch);
				}
			}
		} else {
			// First time showing page
			echo '<p class="text">Enter the email address of the account you forgot your details to. If the email matches an account, you will receive an email with your username and a password reset link. If do not need to change the password, just ignore that link and your password will remain the same. Password reset links expire after 24 hours.</p>
			<p><input name="uemail" type="email" required placeholder="Email" /></p>
			<p><input name="submission" type="hidden" value="1" /><input type="submit" /></p>';
		}
		?>
	</form>
</div>
</body>
</html>
<?php mysqli_close($db); ?>