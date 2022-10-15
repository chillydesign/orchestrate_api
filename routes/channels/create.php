<?php


$json = file_get_contents('php://input');
// Converts it into a PHP object
$data = json_decode($json);

if (!empty($data->attributes)) {


    $channel_attributes = $data->attributes;
    $channel_id = create_channel($channel_attributes);

    if ($channel_id) {


        $channel = get_channel($channel_id);

        if ($channel->client_id) {
            $channel->client = get_client($channel->client_id);
        };




        http_response_code(201);
        echo json_encode($channel);
    } else {
        http_response_code(404);
        echo json_encode('Error');
    }
} else {
    http_response_code(404);
    echo json_encode('Error');
}
