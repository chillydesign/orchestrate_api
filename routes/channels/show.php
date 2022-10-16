<?php




$id = $_GET['id'];




$channel = get_channel($id);
if ($channel) {



    $channel->messages = get_messages($channel->id);



    if ($channel->client_id) {
        $channel->client = get_client($channel->client_id);
    }

    echo json_encode($channel);
} else {
    http_response_code(404);
    echo json_encode('error');
}
