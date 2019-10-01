<?php declare(strict_types=1);
include_once("../main.variables.php");

$db = new mysqli($mysql_host, $mysql_username_login, $mysql_password_login, $mysql_db);

if ($db->connect_errno) {
    die("Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error);
}

session_start();

if ($stmt = $db->prepare("SELECT a.username FROM accounts a JOIN sessions s ON (s.user_id=a.id) WHERE s.sessionId = ? LIMIT 1")) {
	$stmt->bind_param("s", session_id());
	$stmt->execute();
	$stmt->store_result();

	if ($stmt->num_rows == 1) {
		$stmt->bind_result($user_name);
		$stmt->fetch();
		$stmt->free_result();
		$stmt->close();
	} else {
		$stmt->free_result();
		$stmt->close();
		session_destroy();
		header("Location: index.php");
		exit();
	}
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8" />
<title>Dashboard</title>
<link rel="stylesheet" href="chat.css" type="text/css" />
</head>

<body>
<header>
	<a href="https://test.demosjarco.dev:8443/dashboard.php"><img src="img/header-icon.png" id="logo" /></a>
	<p id="topInfo"><span id="currentTime"></span> <span id="seperator">|</span> Welcome <?php echo $user_name; ?> <span id="seperator">|</span> <a href="#">Log out</a></p>
</header>
<main>
<div id="chat">
	<div class="row">
		<div class="info">
			<span class="username self">victor</span>
			<span class="date">date</span>
		</div>
		<p class="message">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras vitae erat vitae nunc malesuada venenatis ac in neque. Nullam aliquam fringilla elementum. Duis vulputate ultricies erat in tristique. Donec vel enim et libero laoreet placerat a blandit metus.</p>
	</div>
	<div id="input"><input type="text" maxlength="255" /><input type="submit" /></div>
</div>
</main>
<script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha384-vk5WoKIaW/vJyUAd9n/wmopsmNhiy+L2Z+SBxGYnUkunIxVxAv/UtMOhba/xskxh" crossorigin="anonymous"></script>
<script src="dashboard.js" type="text/javascript"></script>
</body>
</html>