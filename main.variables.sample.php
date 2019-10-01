<?php declare(strict_types=1);
function get_processor_cores_number() {
    $command = "cat /proc/cpuinfo | grep processor | wc -l";

    return  (int) shell_exec($command);
}

$mysql_host="";
$mysql_db="";

$mysql_username_login="";
$mysql_password_login="";

$password_hash=PASSWORD_ARGON2ID;
$password_options = ['memory_cost' => PASSWORD_ARGON2_DEFAULT_MEMORY_COST, 'time_cost' => PASSWORD_ARGON2_DEFAULT_TIME_COST, 'threads' => get_processor_cores_number()];

$mysql_username_chat="";
$mysql_password_chat="";

// seconds * minutes * hours
$session_duration = 60 * 60 * 1;
?>