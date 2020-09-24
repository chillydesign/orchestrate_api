<?php

// http://webeasystep.com/blog/view_article/How_to_comment_base64_file_in_PHP


$json = file_get_contents('php://input');
// Converts it into a PHP object
$data = json_decode($json);

if (!empty($data->attributes)) {


    $comment_attributes = $data->attributes;
    $comment_id = create_comment($comment_attributes);



    if ($comment_id) {

        $comment = get_comment($comment_id);
        if ($comment) {
            // do somethign to parent task
            update_task_comment_count($comment->task_id);
        }

        http_response_code(201);
        echo json_encode($comment);
    } else {
        http_response_code(404);
        echo json_encode('Error');
    }
} else {
    http_response_code(404);
    echo json_encode('Error');
}
