<?php


$id = $_GET['id'];
$comment  = null;


$current_user = get_current_user_from_jwt();
if ($current_user) {
    $comment = get_comment($id);
}



if ($comment) {
    echo json_encode($comment);
} else {
    http_response_code(404);
    echo json_encode('error');
}
