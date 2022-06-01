<?php

$id = $_GET['id'];
$json = file_get_contents('php://input');
// Converts it into a PHP object
$data = json_decode($json);
$should_change_updated_date = true;

if (!empty($data->attributes)) {

    $task_attributes = $data->attributes;
    $previous_task = get_task($id);

    if ($previous_task) {



        if ($previous_task->updated_at == $task_attributes->updated_at) {



            if (isset($_GET['single_field'])) {
                $updated = update_task_field($id, $task_attributes->field, $task_attributes->data);
                if (
                    $task_attributes->field == 'completed' ||
                    $task_attributes->field == 'is_current' ||
                    $task_attributes->field == 'is_public' ||
                    $task_attributes->field == 'time_taken' ||
                    $task_attributes->field == 'is_approved'
                ) {
                    $should_change_updated_date = false;
                }
            } else {
                $updated = update_task($id, $task_attributes);
            }

            if ($updated) {
                $task = get_task($id);
                if ($task) {
                    // change the updated+at date and task count
                    touch_project($task->project_id, $should_change_updated_date);
                }
                http_response_code(200);
                echo json_encode($task);
            } else {
                http_response_code(404);
                echo json_encode('Error - task couldnt be updated');
            }
        } else {
            http_response_code(404);
            echo json_encode('Error - task updated by someone else');
        }
    } else {
        http_response_code(404);
        echo json_encode('Error - no previous task');
    }
} else {
    http_response_code(404);
    echo json_encode('Error - attributes empty');
}
