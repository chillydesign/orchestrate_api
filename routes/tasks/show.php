<?php










if (isset($_GET['id'])) {

    $id = $_GET['id'];


    $current_user = get_current_user_from_jwt();






    $task = get_task($id);


    $comments = get_comments($id);

    $task->comments = $comments;

    echo json_encode($task);
} else {
    http_response_code(404);
    echo json_encode('error');
}
