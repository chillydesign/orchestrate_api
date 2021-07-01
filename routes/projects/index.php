<?php


$limit = isset($_GET['limit']) ? $_GET['limit'] : 20;
$offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : 'active';
$client_id = isset($_GET['client_id']) ? $_GET['client_id'] : null;
$current = isset($_GET['current']) ? $_GET['current'] : null;

$projects = get_projects(array(
    'limit' => $limit,
    'offset' => $offset,
    'status' => $status,
    'client_id' => $client_id,
    'current' => $current,
));
$clients = get_clients();


foreach ($projects as $project) {
    $project->tasks_count =  tasks_count($project->id);


    $p_clients =  array_filter($clients, function ($e) use ($project) {
        return $e->id == $project->client_id;
    });

    if (sizeof($p_clients) > 0) {
        $project->client = reset($p_clients);
    }

    // // if project is active and has incomplete tasks, give it some random tasks to show 
    // if ($project->status == 'active') {
    //     if ($project->tasks_count->incomplete > 0 ) {
    //         $project->random_tasks =  get_random_incomplete_tasks($project->id, 3);
    //     }
    // }

    if ($current) {
        $project->tasks = get_tasks(array('project_id' => $project->id, 'completed' => 0,  'is_current' => true));
    }
}


echo json_encode($projects);
