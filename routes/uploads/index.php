<?php

$project_id = $_GET['project_id'];



$uploads = get_uploads($project_id);



echo json_encode($uploads);



?>