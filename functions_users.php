<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
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
            $token =  explode('Bearer ', $value)[1];
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
