<?php


$json = file_get_contents('php://input');
// Converts it into a PHP object
$data = json_decode($json);

if (!empty($data->attributes)) {


    $channel_attributes = $data->attributes;
    $channel_id = create_channel($channel_attributes);

    if ($channel_id) {

        $channel = get_channel($channel_id);

        // todo make this list of users appropriate for the channel, client, project, etc

        if ($channel->client_id) {
            $users = get_users_of_client($channel->client_id);
        } else {
            $users = get_users();
        }
        add_users_to_channel($channel_id, $users);


        $admins = get_users(array(['admin' => true]));
        add_users_to_channel($channel_id, $admins);



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
