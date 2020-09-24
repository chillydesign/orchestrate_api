<?php

$id = $_GET['id'];
$json = file_get_contents('php://input');
// Converts it into a PHP object
$data = json_decode($json);

if (!empty($data->attributes)) {


    $comment_attributes = $data->attributes;
    $updated = update_comment($id, $comment_attributes);

    if ($updated) {
        $comment = get_comment($id);
        if ($comment) {
            // do something to parent task
        }
        http_response_code(200);
        echo json_encode($comment);
    } else {
        http_response_code(404);
        echo json_encode('Error');
    }
} else {
    http_response_code(404);
    echo json_encode('Error');
}
