<?php



$opts = array();

if (isset($_GET['is_current'])) {
    $opts['is_current'] = true;
}
if (isset($_GET['client_id'])) {
    $opts['client_id'] = $_GET['client_id'];
}



if (isset($_GET['completed_today'])) {
    $tasks = get_tasks_completed_today();
} else {
    $tasks = get_tasks($opts);
}



addUsersToTasks($tasks);

echo json_encode($tasks);
