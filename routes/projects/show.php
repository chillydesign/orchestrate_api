<?php




$id = $_GET['id'];




$project = get_project($id);
if ($project) {
    $tasks = get_tasks(array('project_id' => $id));
    addUsersToTasks($tasks);

    $uploads = get_uploads($id);

    $project->tasks = $tasks;
    $project->uploads = $uploads;
    $project->id = intval($project->id);
    $project->client = get_client($project->client_id);


    echo json_encode($project);
} else {
    http_response_code(404);
    echo json_encode('error');
}
