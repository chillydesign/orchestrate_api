<?php


$days = last_30_days();

$stats = array();

foreach ($days as $day) {

    $stat = new stdClass();
    $stat->date = $day;
    $stat->hours =   totalHoursOnDay($day);
    array_push($stats, $stat);
}

echo json_encode($stats);
