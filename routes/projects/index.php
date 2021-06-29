<?php


$limit = isset($_GET['limit']) ? $_GET['limit'] : 20;
$offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : 'active';
$client_id = isset($_GET['client_id']) ? $_GET['client_id'] : null;

$projects = get_projects(array('limit' => $limit, 'offset' => $offset, 'status' => $status, 'client_id' => $client_id));
$clients = get_clients();


foreach ($projects as $project) {
    $project->tasks_count =  tasks_count($project->id);


    $p_clients =  array_filter($clients, function ($e) use ($project) {
        return $e->id == $project->client_id;
    });
    // var_dump(($p_clients));
    if (sizeof($p_clients) > 0) {
        $project->client = reset($p_clients);
    }

    // // if project is active and has incomplete tasks, give it some random tasks to show 
    // if ($project->status == 'active') {
    //     if ($project->tasks_count->incomplete > 0 ) {
    //         $project->random_tasks =  get_random_incomplete_tasks($project->id, 3);
    //     }
    // }


}


echo json_encode($projects);
