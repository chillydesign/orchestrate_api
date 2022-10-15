<?php

// http://webeasystep.com/blog/view_article/How_to_message_base64_file_in_PHP


$json = file_get_contents('php://input');
// Converts it into a PHP object
$data = json_decode($json);

if (!empty($data->attributes)) {


    $message_attributes = $data->attributes;
    $message_id = create_message($message_attributes);



    if ($message_id) {

        $message = get_message($message_id);


        http_response_code(201);
        echo json_encode($message);
    } else {
        http_response_code(404);
        echo json_encode('Error');
    }
} else {
    http_response_code(404);
    echo json_encode('Error');
}
