<?php

$errors = false;
$response = new stdClass();
$current_user = get_current_user_from_jwt();

if ($current_user) {


    $encrypted_secret = add2faSecretToUser($current_user->id);

    if ($encrypted_secret) {
        $qr_code = getQRImageof2fa($encrypted_secret);
        $response->qr_code = $qr_code;
    } else {
        $errors = true;
    }
} else {
    $errors = true;
}

if ($errors) {
    http_response_code(404);
    echo json_encode('error');
} else {
    http_response_code(200);
    echo json_encode($response);
}
