<?php


$limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
$offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : 'active';

$projects = get_projects( array('limit' => $limit, 'offset' => $offset, 'status' => $status)  );


foreach($projects as $project) {
    $project->tasks_count =  tasks_count($project->id);   
    // if project is active and has incomplete tasks, give it some random tasks to show 
    if ($project->status == 'active') {
        if ($project->tasks_count->incomplete > 0 ) {
            $project->random_tasks =  get_random_incomplete_tasks($project->id, 3);
        }
    }
}
    

echo json_encode($projects);


?>