<?php

use Firebase\JWT\JWT;
use Hautelook\Phpass\PasswordHash;

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


function get_users() {
    global $conn;


    try {
        $query = "SELECT  *  FROM users  ORDER BY users.name ASC ";
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
            $token =  explode('Bearer ', $value)[1];
        }
    }
    return $token;
}



function generate_jwt_token($user_id) {
    $secretKey  = JWT_SECRET;
    $issuedAt   = new DateTimeImmutable();
    $expire     = $issuedAt->modify('+360 minutes');
    $data = [
        'iat'  => $issuedAt->getTimestamp(),         // Issued at: time when the token was generated
        'iss'  => JWT_SERVER,                        // Issuer
        'nbf'  => $issuedAt->getTimestamp(),         // Not before
        'exp'  => $expire->getTimestamp(),           // Expire
        'user_id' => $user_id,                      // User name
    ];

    return JWT::encode($data,  $secretKey,  JWT_ALG);
}

function get_current_user_from_jwt() {
    $current_user = null;
    $jwt_token = get_token_from_headers();
    if ($jwt_token) {
        try {
            JWT::$leeway = 60; // $leeway in seconds
            $token = JWT::decode($jwt_token, JWT_SECRET, [JWT_ALG]);
            $now = new DateTimeImmutable();

            if (
                $token->iss !== JWT_SERVER ||
                $token->nbf > $now->getTimestamp() ||
                $token->exp < $now->getTimestamp()
            ) {
                //
            } else {
                $current_user = get_user($token->user_id);
            }
        } catch (Exception $e) {
        }
    }
    return $current_user;
}


function get_user_from_email($email) {
    global $conn;
    if ($email != '') {
        try {
            $query = "SELECT password_digest, id, email FROM users WHERE email = :email  LIMIT 1";
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


function processUser($user) {

    unset($user->password_digest);
    $user->id =  intval($user->id);
    return $user;
}


function processUsers($users) {

    foreach ($users as $user) {
        processUser($user);
    }

    return $users;
}
