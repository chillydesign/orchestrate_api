<?php

$id = $_GET['id'];
$json = file_get_contents('php://input');
// Converts it into a PHP object
$data = json_decode($json);

if (!empty($data->attributes)) {


    $upload_attributes = $data->attributes;
    $updated = update_upload($id, $upload_attributes);

    if ($updated) {
        $upload = get_upload($id);
        if ($upload) {
            //  // change the updated+at date
            // touch_project($upload->project_id);
        }
        http_response_code(200);
        echo json_encode($upload);
    } else {
        http_response_code(404);
        echo json_encode( 'Error'  );
    }



} else {
    http_response_code(404);
    echo json_encode( 'Error'  );
}


?>