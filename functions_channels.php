<?php





function get_channels($opts = null) {
    global $conn;

    if (!isset($opts['client_id'])) {
        $opts['client_id'] = null;
    }

    $client_id = $opts['client_id'];


    $client_id_sql = '';
    if ($client_id) {
        $client_id_sql = '  AND client_id = :client_id';
    }

    try {
        $query = "SELECT * FROM channels 
        WHERE 1 = 1
        $client_id_sql

        ORDER BY created_at DESC ";

        $channels_query = $conn->prepare($query);
        if ($client_id_sql != '') {
            $channels_query->bindParam(':client_id', $client_id, PDO::PARAM_INT);
        }
        $channels_query->setFetchMode(PDO::FETCH_OBJ);
        $channels_query->execute();
        $channels_count = $channels_query->rowCount();
        if ($channels_count > 0) {
            $channels =  $channels_query->fetchAll();
            $channels = processChannels($channels);
        } else {
            $channels =  [];
        }

        unset($conn);
        return $channels;
    } catch (PDOException $err) {
        return [];
    };
}



function get_channel($channel_id = null) {

    global $conn;
    if ($channel_id != null) {


        try {
            $query = "SELECT * FROM channels WHERE channels.id = :id LIMIT 1";
            $channel_query = $conn->prepare($query);
            $channel_query->bindParam(':id', $channel_id);
            $channel_query->setFetchMode(PDO::FETCH_OBJ);
            $channel_query->execute();

            $channel_count = $channel_query->rowCount();

            if ($channel_count == 1) {
                $channel =  $channel_query->fetch();
                $channel = processChannel($channel);
            } else {
                $channel =  null;
            }

            unset($conn);
            return $channel;
        } catch (PDOException $err) {
            return null;
        };
    } else { // if channel id is not greated than 0
        return null;
    }
}




function create_channel($channel) {
    global $conn;
    if (!empty($channel->name)) {


        try {
            $query = "INSERT INTO channels (name, client_id) VALUES (:name, :client_id)";
            $channel_query = $conn->prepare($query);
            $channel_query->bindParam(':name', $channel->name);
            $channel_query->bindParam(':client_id', $channel->client_id);
            $channel_query->execute();
            $channel_id = $conn->lastInsertId();
            unset($conn);

            return ($channel_id);
        } catch (PDOException $err) {
            return false;
        };
    } else { // channel name was blank
        return false;
    }
}





function update_channel($channel_id, $channel) {
    global $conn;
    if ($channel_id > 0) {
        try {



            $updated_at = updated_at_string();
            $query = "UPDATE channels SET
              `name` = :name,  
              `client_id` = :client_id,  
              `status` = :status, 
              `updated_at` = :updated_at 
              WHERE id = :id";
            $channel_query = $conn->prepare($query);
            $channel_query->bindParam(':name', $channel->name);
            $channel_query->bindParam(':status', $channel->status);
            $channel_query->bindParam(':client_id', $channel->client_id);
            $channel_query->bindParam(':updated_at', $updated_at);
            $channel_query->bindParam(':id', $channel_id);
            $channel_query->execute();
            unset($conn);

            return true;
        } catch (PDOException $err) {
            return false;
        };
    } else { // channel name was blank
        return false;
    }
}








function delete_channel($channel_id) {

    global $conn;
    if ($channel_id > 0) {

        try {
            $query = "DELETE FROM channels  WHERE id = :id    ";
            $channel_query = $conn->prepare($query);
            $channel_query->bindParam(':id', $channel_id);
            $channel_query->setFetchMode(PDO::FETCH_OBJ);
            $channel_query->execute();

            unset($conn);
            return true;
        } catch (PDOException $err) {
            return false;
        };
    } else {
        return false;
    }
}




function processChannel($channel) {
    if ($channel->client_id) {
        $channel->client_id =  intval($channel->client_id);
    }
    if ($channel->project_id) {
        $channel->project_id =  intval($channel->project_id);
    }
    $channel->id =  intval($channel->id);
    return $channel;
}


function processChannels($channels) {

    foreach ($channels as $channel) {
        processChannel($channel);
    }

    return $channels;
}
