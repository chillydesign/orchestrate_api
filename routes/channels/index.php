<?php


$current_user_id = get_current_user_id_from_jwt();



$client_id = isset($_GET['client_id']) ? $_GET['client_id'] : null;


$channels = get_channels(array(
    'client_id' => $client_id,
    'current_user_id' => $current_user_id
));


foreach ($channels as $channel) {
    // if ($channel->project_id) {
    //     $channel->project = get_project($channel->project_id);
    // }
    // if ($channel->client_id) {
    //     $channel->client = get_client($channel->client_id);
    // }
}



echo json_encode($channels);
