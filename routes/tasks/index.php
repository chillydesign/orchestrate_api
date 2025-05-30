<?php



$opts = array();

if (isset($_GET['is_current'])) {
    $opts['is_current'] = true;
}
if (isset($_GET['client_id'])) {
    $opts['client_id'] = $_GET['client_id'];
}
if (isset($_GET['search_term'])) {
    $opts['search_term'] = $_GET['search_term'];
}
if (isset($_GET['limit'])) {
    $opts['limit'] = $_GET['limit'];
}
if (isset($_GET['order'])) {
    $opts['order'] = $_GET['order'];
}
if (isset($_GET['completed'])) {
    $opts['completed'] = $_GET['completed'];
}
if (isset($_GET['start_date'])) {
    $opts['start_date'] = $_GET['start_date'];
}
if (isset($_GET['end_date'])) {
    $opts['end_date'] = $_GET['end_date'];
}
if (isset($_GET['include_comments'])) {
    $opts['include_comments'] = $_GET['include_comments'];
} else {
    $opts['include_comments'] = false;
}

$current_user = get_current_user_from_jwt();
if (!$current_user) {
    $opts['is_public'] = true;
    $opts['include_comments'] = false;
}



if (isset($_GET['completed_today'])) {
    $tasks = get_tasks_completed_today();
} else {
    $tasks = get_tasks($opts);
}



addUsersToTasks($tasks);

echo json_encode($tasks);
