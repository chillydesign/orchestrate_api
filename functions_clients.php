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
