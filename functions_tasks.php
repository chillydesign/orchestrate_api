<?php


function get_tasks($opts) {
    global $conn;


    $proj_sql = '';
    if (isset($opts['project_id'])) {
        $proj_sql = 'AND project_id = :project_id ';
    }

    $cur_sql = '';
    if (isset($opts['is_current'])) {
        $cur_sql =  ' AND is_current = 1 ';
    }
    $ass_sql = '';
    if (isset($opts['assignee_id'])) {
        $ass_sql =  ' AND assignee_id = :assignee_id ';
    }



    $com_sql = '';
    if (isset($opts['completed'])) {
        $com_sql = 'AND completed = :completed ';
    }

    // tasks.completed ASC,
    $query = "SELECT *  FROM tasks  WHERE 1 = 1 $proj_sql $cur_sql $ass_sql $com_sql ORDER BY   tasks.ordering ASC, tasks.created_at ASC";


    try {

        $tasks_query = $conn->prepare($query);
        if (isset($opts['project_id'])) {
            $tasks_query->bindParam(':project_id', $opts['project_id'],  PDO::PARAM_INT);
        }
        if (isset($opts['completed'])) {
            $tasks_query->bindParam(':completed', $opts['completed'],  PDO::PARAM_INT);
        }
        if (isset($opts['assignee_id'])) {
            $tasks_query->bindParam(':assignee_id', $opts['assignee_id'],  PDO::PARAM_INT);
        }


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
    } catch (PDOException $err) {
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
        } catch (PDOException $err) {
            return [];
        };
    }
}



function get_tasks_completed_today() {
    global $conn;

    $hour =  date('H');
    $today = date("Y-m-d");
    $tm = new DateTime('tomorrow');
    $tomorrow = $tm->format('Y-m-d ');
    $ys = new DateTime('yesterday');
    $yesterday = $ys->format('Y-m-d');
    // get times from 3am to 3am 
    if ($hour < 3) {
        $completed_at_start = $yesterday .  ' 03:00:00';
        $completed_at_end = $today .  ' 02:59:59';
    } else {
        $completed_at_start = $today .  ' 03:00:00';
        $completed_at_end = $tomorrow .  ' 02:59:59';
    }


    $query = "SELECT *  FROM tasks
        WHERE completed_at > :completed_at_start  
        AND completed_at < :completed_at_end
        AND completed = 1
        ORDER BY completed_at DESC ";

    try {
        $tasks_query = $conn->prepare($query);
        $tasks_query->bindParam(':completed_at_start', $completed_at_start);
        $tasks_query->bindParam(':completed_at_end', $completed_at_end);
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
    } catch (PDOException $err) {
        return [];
    };
}



function get_task($task_id = null) {
    global $conn;
    if ($task_id != null) {

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
        } catch (PDOException $err) {
            return null;
        };
    } else { // if task id is not greated than 0
        return null;
    }
}



function create_task($task) {
    global $conn;
    if (!empty($task->project_id)  && !empty($task->content)) {

        if ($task->ordering == null) {
            $task->ordering = 9999;
        }

        if (property_exists($task, 'translation') == false) {
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
        } catch (PDOException $err) {
            var_dump($err);
            return false;
        };
    } else { // task project_id was blank
        return false;
    }
}



function update_task_comment_count($task_id) {


    global $conn;
    if ($task_id > 0) {
        // try {
        $comments_count = task_comment_count($task_id);
        $updated_at =   updated_at_string();

        $query = "UPDATE tasks SET 
                `comments_count` = :comments_count, 
                `updated_at` = :updated_at
                WHERE id = :id";
        $task_query = $conn->prepare($query);
        $task_query->bindParam(':comments_count', $comments_count);
        $task_query->bindParam(':updated_at', $updated_at);
        $task_query->bindParam(':id', $task_id);
        $task_query->execute();
        unset($conn);
        return true;
        // } catch (PDOException $err) {
        return false;
        // };
    } else { // task id was less than 0
        return false;
    }
}



function update_task($task_id, $task) {
    global $conn;
    if ($task_id > 0) {
        try {

            $completed = 0;
            $is_title = 0;
            $is_current = 0;
            $assignee_id = 0;
            if ($task->is_title) {
                $is_title = 1;
            };
            if ($task->is_current) {
                $is_current = 1;
            };
            if ($task->assignee_id) {
                $assignee_id = $task->assignee_id;
            };



            if ($task->completed == true) {
                $completed = 1;
                if ($task->completed_at == null) {
                    $task->completed_at = updated_at_string();
                }
            } else {
                $task->completed_at = null;
            }

            $updated_at =   updated_at_string();
            $query = "UPDATE tasks SET 
            `content` = :content, 
            `translation` = :translation, 
            `completed` = :completed, 
            `indentation` = :indentation, 
            `ordering` = :ordering, 
            `priority` = :priority, 
            `is_title` = :is_title, 
            `is_current` = :is_current, 
            `assignee_id` = :assignee_id, 
            `updated_at` = :updated_at,
            `completed_at` = :completed_at ,
            `time_taken` = :time_taken 
            WHERE id = :id";
            $task_query = $conn->prepare($query);
            $task_query->bindParam(':content', $task->content);
            $task_query->bindParam(':translation', $task->translation);
            $task_query->bindParam(':indentation',  $task->indentation);
            $task_query->bindParam(':priority',  $task->priority);
            $task_query->bindParam(':ordering',  $task->ordering);
            $task_query->bindParam(':is_title',  $is_title);
            $task_query->bindParam(':is_current',  $is_current);
            $task_query->bindParam(':assignee_id',  $assignee_id);
            $task_query->bindParam(':completed', $completed);
            $task_query->bindParam(':updated_at', $updated_at);
            $task_query->bindParam(':completed_at', $task->completed_at);
            $task_query->bindParam(':time_taken', $task->time_taken);
            $task_query->bindParam(':id', $task_id);
            $task_query->execute();
            unset($conn);
            return true;
        } catch (PDOException $err) {
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
        } catch (PDOException $err) {
            return false;
        };
    } else {
        return false;
    }
}


function processTask($task) {
    // if database is set as 1 it should return as true
    $task->completed =  ($task->completed == '1' || $task->completed == 1);
    $task->is_title =  ($task->is_title == '1' || $task->is_title == 1);
    $task->is_current =  ($task->is_current == '1' || $task->is_current == 1);
    $task->ordering =  intval($task->ordering);
    $task->indentation =  intval($task->indentation);
    $task->priority =  intval($task->priority);
    $task->time_taken =  intval($task->time_taken);
    $task->id =  intval($task->id);
    $task->project_id =  intval($task->project_id);
    $task->assignee_id =  intval($task->assignee_id);
    return $task;
}


function processTasks($tasks) {

    foreach ($tasks as $task) {
        processTask($task);
    }

    return $tasks;
}

function  addUsersToTasks($tasks) {
    $users = get_users();
    foreach ($tasks as $task) {
        foreach ($users as $user) {
            if ($user->id === $task->assignee_id) {
                $task->assignee = $user;
            }
        }
    }
}
