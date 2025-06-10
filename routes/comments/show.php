<?php


$id = $_GET['id'];

$comment = get_comment($id);


if ($comment) {
    echo json_encode($comment);
} else {
    http_response_code(404);
    echo json_encode('error');
}
