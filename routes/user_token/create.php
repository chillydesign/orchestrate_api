<?php

$json = file_get_contents('php://input');
// Converts it into a PHP object
$data = json_decode($json);


$tfa =  makeTwoFactorAuth();





$can_generate_jwt  = false;
$errors = false;
$session = null;
$two_factor_code = null;
$remember_me = false;
if (property_exists($data, 'two_factor_code')) {
    $two_factor_code = $data->two_factor_code;
}
if (property_exists($data, 'remember_me')) {
    $remember_me = $data->remember_me;
}
if (property_exists($data, 'session')) {
    // use this instead of username and password if present
    $session = $data->session;
    $user_id = cryptoDecrypt($session);
    $user = get_user($user_id);
} else if (property_exists($data, 'email')) {
    $email = $data->email;
    $password = $data->password;
    $user = get_user_from_email($email);
    if (!password_is_correct($password, $user->password_digest)) {
        $user = null;
    }
}



if ($user) {


    $response = new stdClass();
    if ($user->verification_method === 'totp-2fa') {
        if ($two_factor_code) {
            $totp = get_totp_encrypted_secret($user->id);
            if ($totp) {
                $decrypted_secret =  cryptoDecrypt($totp->encrypted_secret);
                $code_verified =  verifyCode($decrypted_secret, $two_factor_code);
                if ($code_verified) {
                    $can_generate_jwt = true;
                } else {
                    $errors = true;
                    //// DO SOMETHING IF WRONG CODE USED
                    //// CHECK FOR RECOVERY CODES MAYBE
                }
            } else {
                $errors = true;
            }
        } else {
            $response->response = 'totp-2fa';
            $response->session = userIdEncrypted($user->id);
            // todo use this session thing to allow the user to only enter the 
            // 2fa code, not the whole password and username again
            $errors = false;
        }
    } else {
        $can_generate_jwt = true;
        $errors = false;
    }

    if ($can_generate_jwt) {
        $user_token = generate_jwt_token($user->id, $remember_me);
        $response->response = 'jwt';
        $response->jwt = $user_token;
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
