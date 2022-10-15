<?php




$id = $_GET['id'];




$project = get_project($id);
if ($project) {

    $task_opts = array('project_id' => $id);

    $current_user = get_current_user_from_jwt();
    if (!$current_user) {
        $task_opts['is_public'] = true;
    }
    $tasks = get_tasks($task_opts);

    addUsersToTasks($tasks);

    $uploads = get_uploads($id);

    $project->tasks = $tasks;
    $project->uploads = $uploads;
    $project->client = get_client($project->client_id);


    echo json_encode($project);
} else {
    http_response_code(404);
    echo json_encode('error');
}
