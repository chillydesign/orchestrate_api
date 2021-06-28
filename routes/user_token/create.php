<?php

$json = file_get_contents('php://input');
// Converts it into a PHP object
$data = json_decode($json);

$email = $data->email;
$password = encrypt_password($data->password);


$user_id = get_user_id_from_password($email, $password);

if ($user_id) {


    $user_token = generate_jwt_token($user_id);
    $jwt = (object) ['jwt' => $user_token];
    echo json_encode($jwt);
} else {
    http_response_code(404);
    echo json_encode('error');
}
