<?php

use Firebase\JWT\JWT;

$jwt_token = $_GET['token'];
$current_user_id = null;
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
            var_dump($token);
        }
    } catch (Exception $e) {
        var_dump($e);
    }
}
