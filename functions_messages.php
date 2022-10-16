<?php


function get_messages($channel_id) {
    global $conn;

    if ($channel_id !== null) {

        $query = "SELECT messages.*, users.name as user_name   FROM messages
        LEFT JOIN users ON messages.user_id = users.id
        WHERE channel_id = :channel_id 
        ORDER BY  messages.created_at ASC";

        try {

            $messages_query = $conn->prepare($query);
            $messages_query->bindParam(':channel_id', $channel_id);
            $messages_query->setFetchMode(PDO::FETCH_OBJ);
            $messages_query->execute();
            $messages_count = $messages_query->rowCount();

            if ($messages_count > 0) {
                $messages =  $messages_query->fetchAll();
                $messages = processMessages($messages);
            } else {
                $messages =  [];
            }

            unset($conn);
            return $messages;
        } catch (PDOException $err) {
            return [];
        };
    }
}


function get_message($message_id = null) {
    global $conn;
    if ($message_id != null) {

        try {
            $query = "SELECT messages.*, users.name as user_name FROM messages   LEFT JOIN users ON messages.user_id = users.id WHERE messages.id = :id LIMIT 1";
            $message_query = $conn->prepare($query);
            $message_query->bindParam(':id', $message_id);
            $message_query->setFetchMode(PDO::FETCH_OBJ);
            $message_query->execute();

            $message_count = $message_query->rowCount();

            if ($message_count == 1) {
                $message =  $message_query->fetch();
                $message =  processMessage($message);
            } else {
                $message = null;
            }
            unset($conn);
            return $message;
        } catch (PDOException $err) {
            return null;
        };
    } else { // if message id is not greated than 0
        return null;
    }
}




function create_message($message) {
    global $conn;

    try {


        $query = "INSERT INTO messages (content , channel_id, user_id ) VALUES (:content, :channel_id, :user_id)";
        $message_query = $conn->prepare($query);
        $message_query->bindParam(':content', $message->content);
        $message_query->bindParam(':channel_id', $message->channel_id);
        $message_query->bindParam(':user_id', $message->user_id);
        $message_query->execute();
        $message_id = $conn->lastInsertId();
        unset($conn);

        return ($message_id);
    } catch (PDOException $err) {

        echo json_encode($err->getMessage());
    };
}




function update_message($message_id, $message) {
    global $conn;
    if ($message_id > 0) {
        try {

            $updated_at =   updated_at_string();
            $query = "UPDATE messages SET 
            `content` = :content, 
            `updated_at` = :updated_at 
            WHERE id = :id";
            $message_query = $conn->prepare($query);
            $message_query->bindParam(':content', $message->content);
            $message_query->bindParam(':updated_at', $updated_at);
            $message_query->bindParam(':id', $message_id);
            $message_query->execute();
            unset($conn);
            return true;
        } catch (PDOException $err) {
            return false;
        };
    } else { // message name was blank
        return false;
    }
}


function delete_message($message_id) {

    global $conn;
    if ($message_id > 0) {

        try {
            $query = "DELETE FROM messages  WHERE id = :id    ";
            $message_query = $conn->prepare($query);
            $message_query->bindParam(':id', $message_id);
            $message_query->setFetchMode(PDO::FETCH_OBJ);
            $message_query->execute();
            unset($conn);
            return true;
        } catch (PDOException $err) {
            return false;
        };
    } else {
        return false;
    }
}








function processMessage($message) {
    // if database is set as 1 it should return as true
    $message->channel_id =  intval($message->channel_id);
    $message->id =  intval($message->id);
    return $message;
}


function processMessages($messages) {

    foreach ($messages as $message) {
        processMessage($message);
    }

    return $messages;
}
