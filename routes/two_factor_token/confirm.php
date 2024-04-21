<?php

$json = file_get_contents('php://input');
// Converts it into a PHP object
$data = json_decode($json);

$errors = false;
$response = new stdClass();
$current_user = get_current_user_from_jwt();
$two_factor_code = $data->two_factor_code;

if ($current_user) {
    $totp = get_totp_encrypted_secret($current_user->id);
    $decrypted_secret =  cryptoDecrypt($totp->encrypted_secret);
    $code_verified =  verifyCode($decrypted_secret, $two_factor_code);
    if ($code_verified) {

        /// set user verification_method to = totp-2fa
        $updated_user = update_user_verirification_method($current_user->id, 'totp-2fa');

        if ($updated_user) {
            $response->verified =  true;
            $response->user = get_user($current_user->id);;
        } else {
            $errors = true;
        }
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
