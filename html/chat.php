<?php declare(strict_types=1);
include_once("../main.variables.php");

$db = new mysqli($mysql_host, $mysql_username_chat, $mysql_password_chat, $mysql_db);

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

$chatsql = "SELECT a.username, cm.user_id, cm.message, cm.`date` FROM chat_messages cm JOIN accounts a ON (a.id=cm.user_id)";
$dateFilter = false;
if (isset($_REQUEST["since"]) && preg_match('/^([0-9]{2,4})-([0-1][0-9])-([0-3][0-9])(?:( [0-2][0-9]):([0-5][0-9]):([0-5][0-9]))?$/', $_REQUEST["since"])) {
    $chatsql = $chatsql . " WHERE cm.`date` > ?";
    $dateFilter = true;
}

if ($chats = $db->prepare($chatsql)) {
    if ($dateFilter) {
        $chats->bind_param("s", $_REQUEST["since"]);
    }
    $chats->execute();
    
    if ($chats->num_rows > 0) {
        $result = $chats->get_result();
        echo json_encode(($result->fetch_assoc()));
		$chats->close();
	} else {
        //http_response_code(204);
		$chats->free_result();
		$chats->close();
	}
} else {
    echo 'test';
    //echo $db->error;
}
mysqli_close($db);
?>