<?php

$start_date = null;
$end_date = null;

if (isset($_GET['start_date'])) {
    $start_date = $_GET['start_date'];
}
if (isset($_GET['end_date'])) {
    $end_date = $_GET['end_date'];
}


$stats = get_all_client_stats($start_date, $end_date);



echo json_encode($stats);
