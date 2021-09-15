<?php

$id = $_GET['id'];
$json = file_get_contents('php://input');
// Converts it into a PHP object
$data = json_decode($json);

if (!empty($data->attributes)) {


    $project_attributes = $data->attributes;
    $updated = update_project($id, $project_attributes);
    if (isset($project_attributes->move_incomplete_to_project_id)) {
        $new_project_id = $project_attributes->move_incomplete_to_project_id;
        move_incomplete_tasks($id, $new_project_id);

        touch_project($new_project_id); // update the new task counts
        touch_project($id); // update the new task counts
    }

    if ($updated) {
        $project = get_project($id);
        http_response_code(200);
        echo json_encode($project);
    } else {
        http_response_code(404);
        echo json_encode('Error');
    }
} else {
    http_response_code(404);
    echo json_encode('Error');
}
