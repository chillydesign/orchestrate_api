<?php


include('connect.php');
include('functions.php');

$add_priority_to_tasks = "ALTER TABLE `tasks` ADD `priority` TINYINT(1) NOT NULL DEFAULT '0' AFTER `indentation`; ";
if (klxc_add_migration($add_priority_to_tasks)) {
    echo 'added add_priority_to_tasks';
} else {
    echo 'error add_priority_to_tasks';
};



function klxc_add_migration($query) {
    global $conn;
        try {
            $migration_query = $conn->prepare($query);
            $migration_query->execute();
            unset($conn);
            return true;

        } catch(PDOException $err) {
            return false;

        };
}

?>