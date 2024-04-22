<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Hautelook\Phpass\PasswordHash;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\BaconQrCodeProvider;


include 'functions_crypto.php';

function makeTwoFactorAuth() {
    return new TwoFactorAuth(
        issuer: 'Orchestrate',
        qrcodeprovider: new BaconQrCodeProvider(
            borderWidth: 1,
            format: 'svg'
        ),
    );
};

function getQRImageof2fa($encrypted_secret, $label) {
    $tfa = makeTwoFactorAuth();
    $secret = cryptoDecrypt($encrypted_secret);
    $qr_code = $tfa->getQRCodeImageAsDataUri($label, $secret, 250);
    // $qr_url = $tfa->getQRText('Orchestrate', $secret);
    return $qr_code;
}

function verifyCode($decrypted_secret, $two_factor_code) {
    $tfa = makeTwoFactorAuth();
    return $tfa->verifyCode($decrypted_secret, $two_factor_code, 1);
}


function userIdEncrypted($user_id) {
    return cryptoEncrypt(strval($user_id));
}





function add2faSecretToUser($user_id) {

    global $conn;

    try {

        deleteAll2FASecrets($user_id);
        $tfa = makeTwoFactorAuth();
        $secret = $tfa->createSecret();
        $encrypted_secret = cryptoEncrypt($secret);
        $sql = "INSERT INTO totps (user_id, encrypted_secret) VALUES (:user_id, :encrypted_secret)";
        $query = $conn->prepare($sql);
        $query->bindParam(':user_id', $user_id);
        $query->bindParam(':encrypted_secret', $encrypted_secret);
        $query->execute();
        $last_insert_id = $conn->lastInsertId();
        unset($conn);
        return ($encrypted_secret);
    } catch (PDOException $err) {
        echo json_encode($err->getMessage());
    };
}



function deleteAll2FASecrets($user_id) {

    global $conn;
    if ($user_id > 0) {

        try {
            $sql = "DELETE FROM totps  WHERE user_id = :user_id    ";
            $query = $conn->prepare($sql);
            $query->bindParam(':user_id', $user_id);
            $query->setFetchMode(PDO::FETCH_OBJ);
            $query->execute();
            unset($conn);
            return true;
        } catch (PDOException $err) {
            return false;
        };
    } else {
        return false;
    }
}



function encrypt_password($password) {
    $passwordHasher = new PasswordHash(8, false);
    $encrypted_password = $passwordHasher->HashPassword($password);
    return $encrypted_password;
}

function password_is_correct($password, $encrypted) {
    $passwordHasher = new PasswordHash(8, false);
    $passwordMatch = $passwordHasher->CheckPassword($password, $encrypted);
    return $passwordMatch;
}


function get_users($opts = null) {
    global $conn;


    $admin_sql = '';
    if ($opts) {
        if (isset($opts['admin'])) {
            $admin_sql = ' WHERE role = "admin"';
        }
    }


    try {
        $query = "SELECT  *  FROM users WHERE 1=1 $admin_sql  ORDER BY users.name ASC ";
        $users_query = $conn->prepare($query);
        $users_query->setFetchMode(PDO::FETCH_OBJ);
        $users_query->execute();
        $users_count = $users_query->rowCount();
        if ($users_count > 0) {
            $users =  $users_query->fetchAll();
            $users = processUsers($users);
        } else {
            $users =  [];
        }
        unset($conn);
        return $users;
    } catch (PDOException $err) {
        return [];
    };
}




function get_users_of_client($client_id) {
    global $conn;
    try {
        $query = "SELECT  users.*  FROM users
         LEFT JOIN clients_users ON clients_users.user_id = users.id  
         WHERE clients_users.client_id = :client_id
         ORDER BY users.name ASC ";
        $users_query = $conn->prepare($query);
        $users_query->bindParam(':client_id', $client_id);
        $users_query->setFetchMode(PDO::FETCH_OBJ);
        $users_query->execute();
        $users_count = $users_query->rowCount();
        if ($users_count > 0) {
            $users =  $users_query->fetchAll();
            $users = processUsers($users);
        } else {
            $users =  [];
        }
        unset($conn);
        return $users;
    } catch (PDOException $err) {
        return [];
    };
}


function get_user($user_id = null) {

    global $conn;
    if ($user_id != null) {


        try {
            $query = "SELECT * FROM users WHERE users.id = :id LIMIT 1";
            $user_query = $conn->prepare($query);
            $user_query->bindParam(':id', $user_id);
            $user_query->setFetchMode(PDO::FETCH_OBJ);
            $user_query->execute();

            $user_count = $user_query->rowCount();

            if ($user_count == 1) {
                $user =  $user_query->fetch();
                $user =  processUser($user);
            } else {
                $user =  null;
            }

            unset($conn);
            return $user;
        } catch (PDOException $err) {
            return null;
        };
    } else { // if user id is not greated than 0
        return null;
    }
}


function get_token_from_headers() {
    $token = '';
    foreach (getallheaders() as $name => $value) {
        if ($name === 'Authorization') {
            if ($value) {
                $token =  explode('Bearer ', $value)[1];
            }
        }
    }
    return $token;
}



function generate_jwt_token($user_id, $remember_me) {
    $secretKey  = JWT_SECRET;
    $issuedAt   = new DateTimeImmutable();
    if ($remember_me) {
        $time =  '+60480 minutes'; // 42 days
    } else {
        $time = '+1440 minutes'; // one days
    }

    $expire     = $issuedAt->modify($time);
    $data = [
        'iat'  => $issuedAt->getTimestamp(),         // Issued at: time when the token was generated
        'iss'  => JWT_SERVER,                        // Issuer
        'nbf'  => $issuedAt->getTimestamp(),         // Not before
        'exp'  => $expire->getTimestamp(),           // Expire
        'user_id' => intval($user_id, 10),           // User name
    ];

    return JWT::encode($data,  $secretKey,  JWT_ALG);
}



function get_current_user_id_from_jwt() {
    $current_user_id = null;
    $jwt_token = get_token_from_headers();
    if ($jwt_token) {
        try {
            JWT::$leeway = 60; // $leeway in seconds
            $token = JWT::decode($jwt_token,  new Key(JWT_SECRET, JWT_ALG));
            $now = new DateTimeImmutable();

            if (
                $token->iss !== JWT_SERVER ||
                $token->nbf > $now->getTimestamp() ||
                $token->exp < $now->getTimestamp()
            ) {
                //
            } else {
                $current_user_id = $token->user_id;
            }
        } catch (Exception $e) {
            var_dump($e);
        }
    }
    return $current_user_id;
}

function get_current_user_from_jwt() {

    $current_user_id = get_current_user_id_from_jwt();

    if ($current_user_id) {
        return get_user($current_user_id);
    }

    return null;
}


function get_user_from_email($email) {
    global $conn;
    if ($email != '') {
        try {
            $query = "SELECT password_digest, id, email, verification_method FROM users WHERE email = :email  LIMIT 1";
            $user_query = $conn->prepare($query);
            $user_query->bindParam(':email', $email);
            $user_query->setFetchMode(PDO::FETCH_OBJ);
            $user_query->execute();
            $users_count = $user_query->rowCount();
            if ($users_count == 1) {
                $user =  $user_query->fetch();
            } else {
                $user = null;
            }
            unset($conn);
            return $user;
        } catch (PDOException $err) {
            return null;
        };
    } else {
        return null;
    }
}




function update_user_verirification_method($user_id, $verification_method) {
    global $conn;
    if ($user_id > 0) {
        try {

            $query = "UPDATE users SET 
            `verification_method` = :verification_method
            WHERE id = :id";
            $comment_query = $conn->prepare($query);
            $comment_query->bindParam(':verification_method', $verification_method);
            $comment_query->bindParam(':id', $user_id);
            $comment_query->execute();
            unset($conn);
            return true;
        } catch (PDOException $err) {
            return false;
        };
    } else { // comment name was blank
        return false;
    }
}




function get_totp_encrypted_secret($user_id) {
    global $conn;
    if ($user_id != '') {
        try {
            $sql = "SELECT encrypted_secret FROM totps WHERE user_id = :user_id  LIMIT 1";
            $query = $conn->prepare($sql);
            $query->bindParam(':user_id', $user_id);
            $query->setFetchMode(PDO::FETCH_OBJ);
            $query->execute();
            $count = $query->rowCount();
            if ($count == 1) {
                $result =  $query->fetch();
            } else {
                $result = null;
            }
            unset($conn);
            return $result;
        } catch (PDOException $err) {
            return null;
        };
    } else {
        return null;
    }
}



function get_user_emails() {
    $users = get_users();

    $emails = array();
    foreach ($users as $user) {
        if ($user->email) {
            array_push($emails, $user->email);
        }
    }
    return $emails;
}


// $user = new stdClass();
// $user->name = 'Mary';
// $user->password = 'marymary';
// $user->email = 'mary@mary.mary';
// $user->dark_mode = 0;
// $user->role = 'user';
// add_user($user);


function add_user($user) {
    global $conn;


    $password_digest = encrypt_password(($user->password));

    try {
        $sql = "INSERT INTO users (email, password_digest, role, name, dark_mode ) VALUES (:email, :password_digest, :role, :name, :dark_mode)";
        $query = $conn->prepare($sql);
        $query->bindParam(':email', $user->email);
        $query->bindParam(':password_digest', $password_digest);
        $query->bindParam(':role', $user->role);
        $query->bindParam(':name', $user->name);
        $query->bindParam(':dark_mode', $user->dark_mode);
        $query->execute();
        $last_insert_id = $conn->lastInsertId();
        unset($conn);
        return ($last_insert_id);
    } catch (PDOException $err) {
        echo json_encode($err->getMessage());
    };
}


function processUser($user) {

    unset($user->password_digest);
    $user->id =  intval($user->id);
    $user->dark_mode =  ($user->dark_mode == 1);
    return $user;
}


function processUsers($users) {

    foreach ($users as $user) {
        processUser($user);
    }

    return $users;
}
