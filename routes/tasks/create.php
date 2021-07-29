<?php


$json = file_get_contents('php://input');
// Converts it into a PHP object
$data = json_decode($json);

if (!empty($data->attributes)) {


    $task_attributes = $data->attributes;
    $task_id = create_task($task_attributes);

    if ($task_id) {
        
        $task = get_task($task_id);
        if ($task) {
            // change the updated+at date
            touch_project($task->project_id);
        }
        http_response_code(201);
        echo json_encode($task);
    } else {
        http_response_code(404);
        echo json_encode( 'Error 1' );
    }



} else {
    http_response_code(404);
    echo json_encode( 'Error 2'  );
}
