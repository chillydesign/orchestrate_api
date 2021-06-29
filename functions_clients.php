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
