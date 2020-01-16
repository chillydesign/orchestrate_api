<?php




$id = $_GET['id'];

$project = get_project($id);
if ($project) {
    $tasks = get_tasks($id);
    $uploads = get_uploads($id);

    $project->tasks = $tasks;
    $project->uploads = $uploads;
    $project->id = intval($project->id);

    echo json_encode($project);
    
} else {
    http_response_code(404);
    echo json_encode('error'); 
}







?>