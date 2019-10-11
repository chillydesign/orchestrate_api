<?php


$limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
$offset = isset($_GET['offset']) ? $_GET['offset'] : 0;

$projects = get_projects( array('limit' => $limit, 'offset' => $offset)  );


foreach($projects as $project) {
    $project->tasks_count =    tasks_count($project->id);    
}
    

echo json_encode($projects);


?>