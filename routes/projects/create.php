<?php


$json = file_get_contents('php://input');
// Converts it into a PHP object
$data = json_decode($json);

if (!empty($data->attributes)) {


    $project_attributes = $data->attributes;
    $project_id = create_project($project_attributes);

    if ($project_id) {
        $project = get_project($project_id);
        http_response_code(201);
        echo json_encode($project);
    } else {
        http_response_code(404);
        echo json_encode( 'Error' );
    }



} else {
    http_response_code(404);
    echo json_encode( 'Error'  );
}




