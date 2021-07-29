<?php



$opts = array();

if (isset($_GET['is_current'])) {
    $opts['is_current'] = true;
}

$tasks = get_tasks($opts);
addUsersToTasks($tasks);

echo json_encode($tasks);
