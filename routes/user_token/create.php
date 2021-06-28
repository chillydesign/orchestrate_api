<?php

$json = file_get_contents('php://input');
// Converts it into a PHP object
$data = json_decode($json);

$email = $data->email;
$password =   encrypt_password($data->password);


$user_id = get_user_id_from_password($email, $password);

if ($user_id) {


    if (set_user_token($user_id)) {
        $user = get_user($user_id);
        echo json_encode($user);
    } else {
        http_response_code(404);
        echo json_encode('error');
    };
} else {

    http_response_code(404);
    echo json_encode('error');
}
