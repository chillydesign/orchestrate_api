<?php




$id = $_GET['id'];

if ($id == 'me') {

    $token = get_token_from_headers();
    $user = get_user_from_token($token);
} else {
    $user = get_user($id);
}


if ($user) {
    echo json_encode($user);
} else {
    http_response_code(404);
    echo json_encode('error');
}
