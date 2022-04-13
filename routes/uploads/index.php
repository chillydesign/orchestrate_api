<?php




if (isset($_GET['project_id'])) {
    $project_id = $_GET['project_id'];

    $uploads = get_uploads($project_id);
    echo json_encode($uploads);
} else if (isset($_GET['task_id'])) {
    $task_id = $_GET['task_id'];

    $uploads = get_uploads_of_task($task_id);
    echo json_encode($uploads);
}
