<?php

$channel_id = $_GET['channel_id'];



$messages = get_messages($channel_id);



echo json_encode($messages);
