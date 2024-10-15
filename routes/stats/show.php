<?php

if (isset($_GET['slug'])) {
    $slug = $_GET['slug'];
    $client = get_client_from_slug($slug);
} else {
    $id = $_GET['id'];
    $client = get_client($id);
}

$start_date = null;
$end_date = null;

if (isset($_GET['start_date'])) {
    $start_date = $_GET['start_date'];
}
if (isset($_GET['end_date'])) {
    $end_date = $_GET['end_date'];
}


if ($client) {

    $stats = get_client_stats($client->id, $start_date, $end_date);


    echo json_encode($stats);
} else {
    http_response_code(404);
    echo json_encode('error');
}
