<?php




$id = $_GET['id'];
$show_csv = false;
if (isset($_GET['format'])) {
    $format = $_GET['format'];
    if ($format === 'csv') {
        $show_csv = true;
    }
}


$project = get_project($id);
if ($project) {
    $tasks = get_tasks($id);
    $uploads = get_uploads($id);

    $project->tasks = $tasks;
    $project->uploads = $uploads;
    $project->id = intval($project->id);
    $project->client = get_client($project->client_id);

    if ($show_csv) {
        $csv = (object) ['csv' =>  show_project_as_csv($project)];
        echo json_encode($csv);
    } else {
        echo json_encode($project);
    }
} else {
    http_response_code(404);
    echo json_encode('error');
}
