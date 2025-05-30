<?php






if (isset($_GET['slug'])) {
    $slug = $_GET['slug'];
    $project = get_project($slug, true);
} else {
    $id = $_GET['id'];
    $project = get_project($id, false);
}



if ($project) {

    $current_user = get_current_user_from_jwt();


    $project_id = $project->id;

    $task_opts = array('project_id' => $project_id,  'include_comments' => true);

    if (!$current_user) {
        $task_opts['is_public'] = true;
        $task_opts['include_comments'] = false;
    }


    $tasks = get_tasks($task_opts);

    addUsersToTasks($tasks);

    $uploads = get_uploads($project_id);

    $project->tasks = $tasks;
    $project->uploads = $uploads;
    $project->client = get_client($project->client_id);


    echo json_encode($project);
} else {
    http_response_code(404);
    echo json_encode('error');
}
