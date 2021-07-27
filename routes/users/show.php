<?php


$id = $_GET['id'];

if ($id == 'me') {

    $user = get_current_user_from_jwt();
} else {
    $user = get_user($id);
}


if ($user) {
    echo json_encode($user);
} else {
    http_response_code(404);
    echo json_encode('error');
}
