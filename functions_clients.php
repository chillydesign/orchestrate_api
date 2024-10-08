<?php


function get_clients() {
    global $conn;
    try {
        $query = "SELECT *  FROM clients ORDER BY clients.updated_at DESC ";
        $clients_query = $conn->prepare($query);
        $clients_query->setFetchMode(PDO::FETCH_OBJ);
        $clients_query->execute();
        $clients_count = $clients_query->rowCount();

        if ($clients_count > 0) {
            $clients =  $clients_query->fetchAll();
            $clients = processClients($clients);
        } else {
            $clients =  [];
        }

        unset($conn);
        return $clients;
    } catch (PDOException $err) {
        return [];
    };
}



function get_client($client_id = null) {
    global $conn;
    if ($client_id != null) {
        try {
            $query = "SELECT * FROM clients WHERE clients.id = :id LIMIT 1";
            $client_query = $conn->prepare($query);
            $client_query->bindParam(':id', $client_id);
            $client_query->setFetchMode(PDO::FETCH_OBJ);
            $client_query->execute();
            $clients_count = $client_query->rowCount();
            if ($clients_count == 1) {
                $client =  $client_query->fetch();
                $client = processClient($client);
            } else {
                $client =  null;
            }
            unset($conn);
            return $client;
        } catch (PDOException $err) {
            return null;
        };
    } else { // if client id is not greated than 0
        return null;
    }
}




function get_client_from_slug($slug = null) {
    global $conn;
    if ($slug != null) {
        try {
            $query = "SELECT * FROM clients WHERE clients.slug = :slug LIMIT 1";
            $client_query = $conn->prepare($query);
            $client_query->bindParam(':slug', $slug);
            $client_query->setFetchMode(PDO::FETCH_OBJ);
            $client_query->execute();
            $clients_count = $client_query->rowCount();
            if ($clients_count == 1) {
                $client =  $client_query->fetch();
                $client = processClient($client);
            } else {
                $client =  null;
            }
            unset($conn);
            return $client;
        } catch (PDOException $err) {
            return null;
        };
    } else { // if client id is not greated than 0
        return null;
    }
}




function create_client($client) {
    global $conn;
    if (!empty($client->name) && !empty($client->slug)) {

        try {
            $query = "INSERT INTO clients (name, slug) VALUES (:name, :slug)";
            $client_query = $conn->prepare($query);
            $client_query->bindParam(':name', $client->name);
            $client_query->bindParam(':slug', $client->slug);
            $client_query->execute();
            $client_id = $conn->lastInsertId();
            unset($conn);

            return ($client_id);
        } catch (PDOException $err) {

            return false;
        };
    } else { // client name was blank
        return false;
    }
}





function update_client($client_id, $client) {
    global $conn;
    if ($client_id > 0) {
        try {



            $updated_at = updated_at_string();
            $query = "UPDATE clients SET
              `name` = :name,  
              `slug` = :slug,  
              `updated_at` = :updated_at 
              WHERE id = :id";
            $client_query = $conn->prepare($query);
            $client_query->bindParam(':name', $client->name);
            $client_query->bindParam(':slug', $client->slug);
            $client_query->bindParam(':updated_at', $updated_at);
            $client_query->bindParam(':id', $client_id);
            $client_query->execute();
            unset($conn);

            return true;
        } catch (PDOException $err) {
            // var_dump($err);
            return false;
        };
    } else { // client name was blank
        return false;
    }
}

// change the updated_at date
function touch_client($client_id) {
    global $conn;
    if ($client_id > 0) {

        try {
            $updated_at = updated_at_string();
            $query = "UPDATE clients SET `updated_at` = :updated_at WHERE id = :id";
            $client_query = $conn->prepare($query);
            $client_query->bindParam(':updated_at', $updated_at);
            $client_query->bindParam(':id', $client_id);
            $client_query->execute();
            unset($conn);
            return true;
        } catch (PDOException $err) {
            return false;
        };
    } else { // project name was blank
        return false;
    }
}



function touch_client_from_project_id($project_id) {
    $project = get_project($project_id);
    if ($project) {
        if ($project->client_id) {
            touch_client($project->client_id);
        }
    }
}



function processClient($client) {

    $client->id =  intval($client->id);
    return $client;
}


function processClients($clients) {
    foreach ($clients as $client) {
        processClient($client);
    }

    return $clients;
}


function get_client_stats($client_id) {

    global $conn;
    $query = "SELECT sum(time_taken) as t,completed_at
    FROM tasks
    LEFT JOIN projects on tasks.project_id = projects.id
    WHERE completed = 1 
    AND projects.client_id = :client_id
    group by month(tasks.completed_at)
    ORDER by tasks.completed_at";
    try {
        $tasks_query = $conn->prepare($query);
        $tasks_query->bindParam(':client_id', $client_id);
        $tasks_query->setFetchMode(PDO::FETCH_OBJ);
        $tasks_query->execute();
        $stats_count = $tasks_query->rowCount();
        if ($stats_count > 0) {
            $stats =  $tasks_query->fetchAll();
            $stats = processStats($stats);
        } else {
            $stats =  [];
        }
        return $stats;
        unset($conn);
    } catch (PDOException $err) {
        return [];
    };
}


function processStats($stats) {
    $ret = array();
    foreach ($stats as $stat) {
        $ca = $stat->completed_at;

        if ($ca) {
            $month =   date('Y-m', strtotime($ca));

            $h =  new stdClass();
            $h->month = $month;
            $h->data = intval($stat->t);

            array_push($ret, $h);
        }
    }
    return $ret;
}
