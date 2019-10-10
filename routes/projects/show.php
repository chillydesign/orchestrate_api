<?php




$id = $_GET['id'];

$project = get_project($id);
if ($project) {
    $tasks = get_tasks($id);

    $project->tasks = $tasks;

    echo json_encode($project);
    
} else {
    http_response_code(404);
    echo json_encode('error'); 
}







?>