<?php

$id = $_GET['id'];
$json = file_get_contents('php://input');
// Converts it into a PHP object
$data = json_decode($json);

if (!empty($data->attributes)) {


    $message_attributes = $data->attributes;
    $updated = update_message($id, $message_attributes);

    if ($updated) {
        $message = get_message($id);
        if ($message) {
            // do something to parent task
        }
        http_response_code(200);
        echo json_encode($message);
    } else {
        http_response_code(404);
        echo json_encode('Error');
    }
} else {
    http_response_code(404);
    echo json_encode('Error');
}
