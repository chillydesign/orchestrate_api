<?php


function get_tasks($project_id){
    global $conn;

    if ($project_id !== null) {
        $query = "SELECT *  FROM tasks
        WHERE project_id = :project_id 
        ORDER BY tasks.ordering ASC, tasks.created_at ASC";
    } else {
        $query = "SELECT *  FROM tasks ORDER BY  tasks.project_id ASC, tasks.created_at DESC";
    }

    try {

        $tasks_query = $conn->prepare($query);
        $tasks_query->bindParam(':project_id', $project_id);
        $tasks_query->setFetchMode(PDO::FETCH_OBJ);
        $tasks_query->execute();
        $tasks_count = $tasks_query->rowCount();

        if ($tasks_count > 0) {
            $tasks =  $tasks_query->fetchAll();
            $tasks = processTasks($tasks);
        } else {
            $tasks =  [];
        }

        unset($conn);
        return $tasks;

    } catch(PDOException $err) {
        return [];
    };
}

function get_random_incomplete_tasks($project_id, $limit) {
    global $conn;

    if ($project_id !== null) {
        
        $query = "SELECT *  FROM tasks
        WHERE project_id = :project_id 
        AND indentation = 0
        AND completed = 0
        ORDER BY RAND() ASC LIMIT :limit";
 
        try {

            $tasks_query = $conn->prepare($query);
            $tasks_query->bindParam(':project_id', $project_id);
            $tasks_query->bindParam(':limit', $limit, PDO::PARAM_INT);
            $tasks_query->setFetchMode(PDO::FETCH_OBJ);
            $tasks_query->execute();
            $tasks_count = $tasks_query->rowCount();

            if ($tasks_count > 0) {
                $tasks =  $tasks_query->fetchAll();
                $tasks = processTasks($tasks);
            } else {
                $tasks =  [];
            }

            unset($conn);
            return $tasks;

        } catch(PDOException $err) {
            return [];
        };

    }
    
}



function get_task($task_id = null) {
    global $conn;
    if ( $task_id != null) {

        try {
            $query = "SELECT * FROM tasks WHERE tasks.id = :id LIMIT 1";
            $task_query = $conn->prepare($query);
            $task_query->bindParam(':id', $task_id);
            $task_query->setFetchMode(PDO::FETCH_OBJ);
            $task_query->execute();

            $task_count = $task_query->rowCount();

            if ($task_count == 1) {
                $task =  $task_query->fetch();
                $task =  processTask($task);
            } else {
                $task = null;
            }
            unset($conn);
            return $task;
        } catch(PDOException $err) {
            return null;
        };
    } else { // if task id is not greated than 0
        return null;
    }
}



function create_task($task) {
    global $conn;
    if ( !empty($task->project_id)  && !empty($task->content)  ){

        if ($task->ordering == null) {
            $task->ordering = 9999;
        }
        if ($task->translation == null) {
            $task->translation = '';
        }

        try {
            $query = "INSERT INTO tasks
             (project_id, content, translation, ordering) VALUES 
             (:project_id, :content, :translation, :ordering)";
            $task_query = $conn->prepare($query);
            $task_query->bindParam(':project_id', $task->project_id);
            $task_query->bindParam(':content', $task->content);
            $task_query->bindParam(':translation', $task->translation);
            $task_query->bindParam(':ordering', $task->ordering);
            $task_query->execute();
            $task_id = $conn->lastInsertId();
            unset($conn);
            return ($task_id);

        } catch(PDOException $err) {

            return false;

        };

    } else { // task project_id was blank
        return false;
    }


}




function update_task($task_id, $task) {
    global $conn;
    if ( $task_id > 0 ){
        try {

            $completed = 0;
            if ($task->completed == true) {
                $completed = 1;
            }

            $updated_at =   updated_at_string();
            $query = "UPDATE tasks SET 
            `content` = :content, 
            `translation` = :translation, 
            `completed` = :completed, 
            `indentation` = :indentation, 
            `ordering` = :ordering, 
            `priority` = :priority, 
            `updated_at` = :updated_at 
            WHERE id = :id";
            $task_query = $conn->prepare($query);
            $task_query->bindParam(':content', $task->content);
            $task_query->bindParam(':translation', $task->translation);
            $task_query->bindParam(':indentation',  $task->indentation);
            $task_query->bindParam(':priority',  $task->priority);
            $task_query->bindParam(':ordering',  $task->ordering);
            $task_query->bindParam(':completed', $completed);
            $task_query->bindParam(':updated_at', $updated_at);
            $task_query->bindParam(':id', $task_id);
            $task_query->execute();
            unset($conn);
            return true;

        } catch(PDOException $err) {
            return false;

        };

    } else { // task name was blank
        return false;
    }

}




function delete_task($task_id) {

    global $conn;
    if ($task_id > 0) {

        try {
            $query = "DELETE FROM tasks  WHERE id = :id    ";
            $task_query = $conn->prepare($query);
            $task_query->bindParam(':id', $task_id);
            $task_query->setFetchMode(PDO::FETCH_OBJ);
            $task_query->execute();
            unset($conn);
            return true;

        } catch(PDOException $err) {
            return false;
        };
    } else {
        return false;
    }

}


function processTask($task) {
    // if database is set as 1 it should return as true
    $task->completed =  ($task->completed == '1' || $task->completed == 1) ;
    $task->ordering =  intval($task->ordering);
    $task->indentation =  intval($task->indentation);
    $task->priority =  intval($task->priority);
    $task->id =  intval($task->id);
    $task->project_id =  intval($task->project_id);
    return $task;
}


function processTasks($tasks) {
    
    foreach ($tasks as $task) {
       processTask($task);
    }

    return $tasks;
}

?>