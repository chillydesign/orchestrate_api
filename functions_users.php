<?php

function encrypt_password($password) {
    $salt = PW_SALT;
    $encrypted_password =  crypt($password, $salt);
    return $encrypted_password;
}

function random_token() {
    return  uniqid(rand(), true);
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


function set_user_token($user_id) {
    global $conn;
    if ($user_id > 0) {
        $user_token_date =  date('Y-m-d H:i:s');
        $user_token =   random_token();

        $query = "UPDATE users SET 
                `user_token_date` = :user_token_date, 
                `user_token` = :user_token
                WHERE id = :id";
        $task_query = $conn->prepare($query);
        $task_query->bindParam(':user_token_date', $user_token_date);
        $task_query->bindParam(':user_token', $user_token);
        $task_query->bindParam(':id', $user_id);
        $task_query->execute();
        unset($conn);
        return true;
    } else {
        return false;
    }
}


function get_user_id_from_password($email, $password_digest) {
    global $conn;
    if ($email != '' && $password_digest != '') {
        try {
            $query = "SELECT * FROM users WHERE email = :email AND  password_digest = :password_digest LIMIT 1";
            $user_query = $conn->prepare($query);
            $user_query->bindParam(':email', $email);
            $user_query->bindParam(':password_digest', $password_digest);
            $user_query->setFetchMode(PDO::FETCH_OBJ);
            $user_query->execute();
            $users_count = $user_query->rowCount();
            if ($users_count == 1) {
                $user_id =  $user_query->fetch()->id;
            } else {
                $user_id = null;
            }
            unset($conn);
            return $user_id;
        } catch (PDOException $err) {
            return false;
        };
    } else {
        return false;
    }
}

function get_user_from_token($user_token) {
    global $conn;
    if ($user_token) {
        try {
            $query = "SELECT * FROM users WHERE user_token = :user_token";
            $user_query = $conn->prepare($query);
            $user_query->bindParam(':user_token', $user_token);
            $user_query->setFetchMode(PDO::FETCH_OBJ);
            $user_query->execute();
            $users_count = $user_query->rowCount();
            if ($users_count == 1) {
                $user =  $user_query->fetch();
                $user =  processUser($user);
            } else {
                $user = null;
            }
            unset($conn);
            return $user;
        } catch (PDOException $err) {
            return false;
        };
    } else {
        return false;
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
