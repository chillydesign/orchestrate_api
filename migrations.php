<?php


include('connect.php');
include('functions.php');


// $add_priority_to_tasks = "ALTER TABLE `tasks` ADD `priority` TINYINT(1) NOT NULL DEFAULT '0' AFTER `indentation`; ";
// if (klxc_add_migration($add_priority_to_tasks)) {
//     echo 'added add_priority_to_tasks';
// } else {
//     echo 'error add_priority_to_tasks';
// };


// $add_uploads_table = "CREATE TABLE `orchestrate_api`.`uploads` ( `id` INT(11) NOT NULL AUTO_INCREMENT , `filename` TEXT NOT NULL , `extension` VARCHAR(255) NOT NULL , `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP , `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP , `project_id` INT(11) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;";
// if (klxc_add_migration($add_uploads_table)) {
//     echo 'added add_uploads_table';
// } else {
//     echo 'error add_uploads_table';
// };

// function klxc_add_migration($query) {
//     global $conn;
//         try {
//             $migration_query = $conn->prepare($query);
//             $migration_query->execute();
//             unset($conn);
//             return true;

//         } catch(PDOException $err) {
//             return false;

//         };
// }

?>