<?php

$json = file_get_contents('php://input');
// Converts it into a PHP object
$data = json_decode($json);

$email = $data->email;
$password = $data->password;
$remember_me = $data->remember_me;
$user_from_email = get_user_from_email($email);

if ($user_from_email) {
    if (password_is_correct($password, $user_from_email->password_digest)) {
        $user_token = generate_jwt_token($user_from_email->id, $remember_me);
        $jwt = (object) ['jwt' => $user_token];
        http_response_code(200);
        echo json_encode($jwt);
    } else {
        http_response_code(404);
        echo json_encode('error');
    }
} else {
    http_response_code(404);
    echo json_encode('error');
}
