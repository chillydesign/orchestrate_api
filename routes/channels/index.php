<?php


$current_user = get_current_user_from_jwt();


$client_id = isset($_GET['client_id']) ? $_GET['client_id'] : null;


$channels = get_channels(array(
    'client_id' => $client_id,
));


foreach ($channels as $channel) {
}



echo json_encode($channels);
