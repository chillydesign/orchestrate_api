<?php





$limit = isset($_GET['limit']) ? $_GET['limit'] : 20;
$offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : 'active';
$client_id = isset($_GET['client_id']) ? $_GET['client_id'] : null;
$current = isset($_GET['current']) ? $_GET['current'] : null;
$search_term = isset($_GET['search_term']) ? $_GET['search_term'] : null;
$include_tasks = isset($_GET['include_tasks']) ? $_GET['include_tasks'] : null;
$assignee_id = isset($_GET['assignee_id']) ? intval($_GET['assignee_id']) : null;
$project_id = isset($_GET['project_id']) ? $_GET['project_id'] : null;



$show_csv = false;
if (isset($_GET['format'])) {
    $format = $_GET['format'];
    if ($format === 'csv') {
        $show_csv = true;
    }
}






if ($client_id) {
    // $status = 'all';
    $limit = 100000;
}






$projects = get_projects(array(
    'project_id' => $project_id,
    'limit' => $limit,
    'offset' => $offset,
    'status' => $status,
    'client_id' => $client_id,
    'current' => $current,
    'include_tasks' => $include_tasks,
    'assignee_id' => $assignee_id,
    'search_term' => $search_term,
));
$clients = get_clients();


foreach ($projects as $project) {

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
        $project->tasks = get_tasks(
            array(
                'project_id' => $project->id,
                'completed' => 0,
                'assignee_id' => $assignee_id,
                'is_current' => true
            )
        );
        addUsersToTasks($project->tasks);
    } else if ($include_tasks || $show_csv) {
        $project->tasks = get_tasks(
            array(
                'project_id' => $project->id,
                // 'completed' => 0
            )
        );
    }
}


if ($show_csv) {

    $data = array();
    foreach ($projects as $project) {
        $d =  show_project_as_csv($project);
        array_push($data, $d);
        array_push($data, "\n");
    }

    $data = implode("\n", $data);
    $csv = (object) ['csv' => $data];
    echo json_encode($csv);
} else {
    echo json_encode($projects);
}
