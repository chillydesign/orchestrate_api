<?php

$channel_id = $_GET['channel_id'];



$messages = get_messages($channel_id);

$uploads = get_uploads(7);


foreach ($messages as $message) {
    // if (rand(2, 10) < 4) {
    // $message->uploads = $uploads;
    // }
}

echo json_encode($messages);
