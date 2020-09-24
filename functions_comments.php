<?php


function get_comments($task_id) {
    global $conn;

    if ($task_id !== null) {

        $query = "SELECT *  FROM comments
        WHERE task_id = :task_id 
        ORDER BY  comments.created_at ASC";

        try {

            $comments_query = $conn->prepare($query);
            $comments_query->bindParam(':task_id', $task_id);
            $comments_query->setFetchMode(PDO::FETCH_OBJ);
            $comments_query->execute();
            $comments_count = $comments_query->rowCount();

            if ($comments_count > 0) {
                $comments =  $comments_query->fetchAll();
                $comments = processComments($comments);
            } else {
                $comments =  [];
            }

            unset($conn);
            return $comments;
        } catch (PDOException $err) {
            return [];
        };
    }
}


function get_comment($comment_id = null) {
    global $conn;
    if ($comment_id != null) {

        try {
            $query = "SELECT * FROM comments WHERE comments.id = :id LIMIT 1";
            $comment_query = $conn->prepare($query);
            $comment_query->bindParam(':id', $comment_id);
            $comment_query->setFetchMode(PDO::FETCH_OBJ);
            $comment_query->execute();

            $comment_count = $comment_query->rowCount();

            if ($comment_count == 1) {
                $comment =  $comment_query->fetch();
                $comment =  processComment($comment);
            } else {
                $comment = null;
            }
            unset($conn);
            return $comment;
        } catch (PDOException $err) {
            return null;
        };
    } else { // if comment id is not greated than 0
        return null;
    }
}




function create_comment($comment) {
    global $conn;
    if (!empty($comment->task_id)  && !empty($comment->message)) {

        try {

            if (!isset($comment->author)) {
                $comment->author = 'unknown';
            }

            $query = "INSERT INTO comments (message,author, task_id ) VALUES (:message, :author, :task_id)";
            $comment_query = $conn->prepare($query);
            $comment_query->bindParam(':message', $comment->message);
            $comment_query->bindParam(':author', $comment->author);
            $comment_query->bindParam(':task_id', $comment->task_id);
            $comment_query->execute();
            $comment_id = $conn->lastInsertId();
            unset($conn);

            return ($comment_id);
        } catch (PDOException $err) {

            echo json_encode($err->getMessage());
        };
    } else { // comment task_id was blank
        return false;
    }
}




function update_comment($comment_id, $comment) {
    global $conn;
    if ($comment_id > 0) {
        try {

            $updated_at =   updated_at_string();
            $query = "UPDATE comments SET 
            `message` = :message, 
            `updated_at` = :updated_at 
            WHERE id = :id";
            $comment_query = $conn->prepare($query);
            $comment_query->bindParam(':message', $comment->message);
            $comment_query->bindParam(':updated_at', $updated_at);
            $comment_query->bindParam(':id', $comment_id);
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


function delete_comment($comment_id) {

    global $conn;
    if ($comment_id > 0) {

        try {
            $query = "DELETE FROM comments  WHERE id = :id    ";
            $comment_query = $conn->prepare($query);
            $comment_query->bindParam(':id', $comment_id);
            $comment_query->setFetchMode(PDO::FETCH_OBJ);
            $comment_query->execute();
            unset($conn);
            return true;
        } catch (PDOException $err) {
            return false;
        };
    } else {
        return false;
    }
}




function task_comment_count($task_id) {
    global $conn;

    if ($task_id !== null) {

        $query = "SELECT 1  FROM comments
        WHERE task_id = :task_id ";

        try {

            $comments_query = $conn->prepare($query);
            $comments_query->bindParam(':task_id', $task_id);
            $comments_query->setFetchMode(PDO::FETCH_OBJ);
            $comments_query->execute();
            $comments_count = $comments_query->rowCount();

            unset($conn);
            return $comments_count;
        } catch (PDOException $err) {
            return [];
        };
    }
}





function processComment($comment) {
    // if database is set as 1 it should return as true
    $comment->task_id =  intval($comment->task_id);
    $comment->id =  intval($comment->id);
    return $comment;
}


function processComments($comments) {

    foreach ($comments as $comment) {
        processComment($comment);
    }

    return $comments;
}
