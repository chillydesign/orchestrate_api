<?php


$current_user = get_current_user_from_jwt();
if ($current_user) {



    $id = $_GET['id'];
    $deleted = delete_comment($id);

    if ($deleted) {
        // success but not returning any content
        http_response_code(204);
    } else {
        http_response_code(404);
        echo json_encode('error');
    }
} else {
    http_response_code(404);
    echo json_encode('error');
}
