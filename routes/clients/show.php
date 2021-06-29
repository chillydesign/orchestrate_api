<?php








if (isset($_GET['slug'])) {
    $slug = $_GET['slug'];
    $client = get_client_from_slug($slug);
} else {
    $id = $_GET['id'];
    $client = get_client($id);
}



if ($client) {


    echo json_encode($client);
} else {
    http_response_code(404);
    echo json_encode('error');
}
