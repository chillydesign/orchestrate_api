<?php



$projects = get_projects();


foreach($projects as $project) {
    $project->tasks_count =    tasks_count($project->id);    
}
    

echo json_encode($projects);


?>