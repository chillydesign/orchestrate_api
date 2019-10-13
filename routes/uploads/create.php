<?php


        // http://webeasystep.com/blog/view_article/How_to_upload_base64_file_in_PHP


$json = file_get_contents('php://input');
// Converts it into a PHP object
$data = json_decode($json);

if (!empty($data->attributes)) {


    $upload_attributes = $data->attributes;
    $upload_id = create_upload($upload_attributes);

   

    if ($upload_id) {
        
        $upload = get_upload($upload_id);
        if ($upload) {
            // change the updated+at date
            touch_project($upload->project_id);
        }
        
        http_response_code(201);
        echo json_encode($upload);
    } else {
        http_response_code(404);
        echo json_encode( 'Error' );
    }



} else {
    http_response_code(404);
    echo json_encode( 'Error'  );
}



?>