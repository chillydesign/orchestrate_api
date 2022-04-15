<?php


$start_date = (isset($_GET['start_date'])) ? $_GET['start_date'] : '';

// var_dump($start_date);

$days = last_30_days($start_date);
// var_dump($days);


$stats = array();

foreach ($days as $day) {

    $stat = new stdClass();
    $stat->date = $day;
    $stat->hours =   totalHoursOnDay($day);
    array_push($stats, $stat);
}

echo json_encode($stats);
