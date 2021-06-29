<?php




$id = $_GET['id'];


$client = get_client($id);
if ($client) {

    $client->projects = get_projects(array('limit' => 99999,  'client_id' => $id));

    echo json_encode($client);
} else {
    http_response_code(404);
    echo json_encode('error');
}
