<?php

$task_id = $_GET['task_id'];



$comments = get_comments($task_id);



echo json_encode($comments);
