<?php declare(strict_types=1);
include_once("../sdrowssap.shhh");

function createPasswordStars($passwordLength) {
	$stars = "";
	for ($i = 0; $i < $passwordLength; $i++) {
		$stars .= "*";
	}
	return $stars;
}
?>
<!doctype html>
<html>
<head>
<meta name="viewport" content="width=device-width" />
<meta charset="utf-8" />
<title>Register</title>
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
				$db = new mysqli($mysql_host, $mysql_username_login, $mysql_password_login, $mysql_db);

				if ($db->connect_errno) {
				    die("Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error);
				}

				if ($stmt = $db->prepare("SELECT id FROM accounts WHERE username = ? OR email = ? LIMIT 1")) {
					$stmt->bind_param("ss", $_REQUEST["uname"], $_REQUEST["uemail"]);
					$stmt->execute();
					$stmt->store_result();
					
					if ($stmt->num_rows == 1) {
						// Account Exists
						echo '<p class="text message error">An account with that email address and/or username already exists. Please check your details or click the forgot link below.</p>
						<p><input name="uemail" type="email" required maxlength="100" placeholder="Email" value="' . $_REQUEST["uemail"] . '" /></p>
						<p><input name="uname" type="text" required maxlength="50" placeholder="Username" value="' . $_REQUEST["uname"] . '" /></p>
						<p><input name="passwd" type="password" required maxlength="255" placeholder="Password" value="' . $_REQUEST["passwd"] . '" /></p>
						<p><input name="submission" type="hidden" value="1" /><a id="forgot" href="requestReset.php"><span id="forgot">Forgot?</span></a><input type="submit" value="Sign Up" /></p>';
					} else {
						$verify_token = uniqid("", true);
						$pass_hash = password_hash($_REQUEST["passwd"], $password_hash, $password_options);
						if ($lockAccStmt = $db->prepare("INSERT INTO pending_accounts (username, password, email, verify_token) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE username=VALUES(username), password=VALUES(password), email=VALUES(email), verify_token=VALUES(verify_token)")) {
							$lockAccStmt->bind_param("ssss", $_REQUEST["uname"], $pass_hash, $_REQUEST["uemail"], $verify_token);
							$lockAccStmt->execute();
						}
						mysqli_close($db);
						echo '<p class="text message success">Your account has been successfully created. Please check your email for a confirmation link to verify your account. (Note: email doesn\'t work on this server so what would be sent to your email is below)</p>';
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
							mail($_REQUEST["uemail"], "Website Test | Account Registration", "Thank you for creating an account with us. Please clink on the link below to verify your account.\n\nRequest Origin: " . $_SERVER["REMOTE_ADDR"] . " (" . $ipLocationJson->city . ", " . $ipLocationJson->region . ")\nUsername: " . $_REQUEST["uname"] . "\nPassword: " . createPasswordStars(strlen($_REQUEST["passwd"])) . "\nVerification Link: https://test.demosjarco.dev:8443/verifyAccount.php?token=" . $verify_token, "From: noreply@demosjarco.dev\r\nReply-To: noreply@demosjarco.dev");
								echo '<p>Thank you for creating an account with us. Please clink on the link below to verify your account.</p><p>Request Origin: ' . $_SERVER['REMOTE_ADDR'] . ' (' . $ipLocationJson->city . ', ' . $ipLocationJson->region . ')<br />Username: ' . $_REQUEST['uname'] . '<br />Password: ' . createPasswordStars(strlen($_REQUEST['passwd'])) . '<br />Verification Link: <a href="https://test.demosjarco.dev:8443/verifyAccount.php?token=' . $verify_token . '">https://test.demosjarco.dev:8443/verifyAccount.php?token=' . $verify_token . '</a></p>';
						} else {
							mail($_REQUEST["uemail"], "Website Test | Account Registration", "Thank you for creating an account with us. Please clink on the link below to verify your account.\n\nRequest Origin: " . $_SERVER["REMOTE_ADDR"] . " (Unknown Location)\nUsername: " . $_REQUEST["uname"] . "\nPassword: " . createPasswordStars(strlen($_REQUEST["passwd"])) . "\nVerification Link: https://test.demosjarco.dev:8443/verifyAccount.php?token=" . $verify_token, "From: noreply@demosjarco.dev\r\nReply-To: noreply@demosjarco.dev");
								echo '<p>Thank you for creating an account with us. Please clink on the link below to verify your account.</p><p>Request Origin: " . $_SERVER["REMOTE_ADDR"] . " (Unknown Location)<br />Username: " . $_REQUEST["uname"] . "<br />Password: " . createPasswordStars(strlen($_REQUEST["passwd"])) . "<br />Verification Link: <a href="https://test.demosjarco.dev:8443/verifyAccount.php?token=' . $verify_token . '">https://test.demosjarco.dev:8443/verifyAccount.php?token=' . $verify_token . '</a></p>';
						}

						// close cURL resource, and free up system resources
						curl_close($ch);
					}
				}
			} else {
				echo '<p><input name="uemail" type="email" required maxlength="100" placeholder="Email" /></p>
		<p><input name="uname" type="text" required maxlength="50" placeholder="Username" /></p>
		<p><input name="passwd" type="password" required maxlength="255" placeholder="Password" /></p>
		<p><input name="submission" type="hidden" value="1" /><a id="forgot" href="requestReset.php"><span id="forgot">Forgot?</span></a><input type="submit" value="Sign Up" /></p>';
			}
		?>
	</form>
</div>
</body>
</html>