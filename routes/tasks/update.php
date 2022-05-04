<?php

$id = $_GET['id'];
$json = file_get_contents('php://input');
// Converts it into a PHP object
$data = json_decode($json);

if (!empty($data->attributes)) {


    $task_attributes = $data->attributes;

    if (isset($_GET['single_field'])) {
        $updated = update_task_field($id, $task_attributes->field, $task_attributes->data);

    } else {
        $updated = update_task($id, $task_attributes);

    }

    if ($updated) {
        $task = get_task($id);
        if ($task) {
            // change the updated+at date
            touch_project($task->project_id);
        }
        http_response_code(200);
        echo json_encode($task);
    } else {
        http_response_code(404);
        echo json_encode( 'Error'  );
    }



} else {
    http_response_code(404);
    echo json_encode( 'Error'  );
}
