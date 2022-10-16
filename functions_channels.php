<?php





function get_channels($opts = null) {
    global $conn;

    if (!isset($opts['client_id'])) {
        $opts['client_id'] = null;
    }
    if (!isset($opts['current_user_id'])) {
        $opts['current_user_id'] = null;
    }

    $client_id = $opts['client_id'];
    $current_user_id = $opts['current_user_id'];


    $client_id_sql = '';
    if ($client_id) {
        $client_id_sql = '  AND client_id = :client_id';
    }


    $cur_us_sql = '';
    $left_join_sql = '';
    if ($current_user_id) {
        $left_join_sql = ' LEFT JOIN channels_users ON  channels_users.channel_id = channels.id ';
        $cur_us_sql = ' AND channels_users.user_id = :current_user_id  ';
    }

    try {
        $sql = "SELECT channels.* FROM channels 
        $left_join_sql
        WHERE 1 = 1
        $client_id_sql
        $cur_us_sql
        ORDER BY created_at DESC ";


        $query = $conn->prepare($sql);
        if ($client_id_sql != '') {
            $query->bindParam(':client_id', $client_id, PDO::PARAM_INT);
        }
        if ($current_user_id != '') {
            $query->bindParam(':current_user_id', $current_user_id, PDO::PARAM_INT);
        }
        $query->setFetchMode(PDO::FETCH_OBJ);
        $query->execute();
        $channels_count = $query->rowCount();
        if ($channels_count > 0) {
            $channels =  $query->fetchAll();
            $channels = processChannels($channels);
        } else {
            $channels =  [];
        }

        unset($conn);
        return $channels;
    } catch (PDOException $err) {
        // var_dump($err->getMessage());
        return [];
    };
}



function get_channel($channel_id = null) {

    global $conn;
    if ($channel_id != null) {


        try {
            $sql = "SELECT * FROM channels WHERE channels.id = :id LIMIT 1";
            $query = $conn->prepare($sql);
            $query->bindParam(':id', $channel_id);
            $query->setFetchMode(PDO::FETCH_OBJ);
            $query->execute();
            $row_count = $query->rowCount();
            if ($row_count == 1) {
                $channel =  $query->fetch();
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



function add_users_to_channel($channel_id, $users) {
    foreach ($users as $user) {
        create_channel_user($channel_id, $user->id);
    }
}


function create_channel_user($channel_id, $user_id) {
    global $conn;
    try {
        $sql = "INSERT INTO channels_users (channel_id, user_id) VALUES (:channel_id, :user_id)";
        $query = $conn->prepare($sql);
        $query->bindParam(':channel_id', $channel_id);
        $query->bindParam(':user_id', $user_id);
        $query->execute();
        $last_insert_id = $conn->lastInsertId();
        unset($conn);
        return ($last_insert_id);
    } catch (PDOException $err) {
        return false;
    };
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
