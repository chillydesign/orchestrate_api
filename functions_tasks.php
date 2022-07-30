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

    $sear_sql = '';
    if (isset($opts['search_term'])) {
        $sear_sql = " AND  (content LIKE :search_term OR translation LIKE :search_term  )   ";
    }

    $com_sql = '';
    if (isset($opts['completed'])) {
        $com_sql = 'AND completed = :completed ';
    }

    $cli_sql = '';
    $left_join_sql = '';
    if (isset($opts['client_id'])) {
        $left_join_sql =  'LEFT JOIN projects ON projects.id = tasks.project_id';
        $cli_sql = '  AND projects.client_id = :client_id ';
    }

    // tasks.completed ASC,
    $query = "SELECT tasks.*  FROM tasks $left_join_sql  
     WHERE 1 = 1   $proj_sql $cur_sql $ass_sql $com_sql   $cli_sql    $sear_sql 
     ORDER BY  tasks.project_id DESC , tasks.ordering ASC, tasks.created_at ASC";


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
        if (isset($opts['client_id'])) {
            $tasks_query->bindParam(':client_id', $opts['client_id'],  PDO::PARAM_INT);
        }


        if (isset($opts['search_term'])) {
            $pst =  "%" . $opts['search_term'] . "%";
            $tasks_query->bindParam(':search_term', $pst);
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


function  last_30_days($start_date_string) {
    if ($start_date_string) {
        $start_date     = new DateTime($start_date_string);
        $end       = new DateTime($start_date_string);
    } else {
        $start_date     = new DateTime(); // today
        $end       = new DateTime();
    }
    $days = array();
    $begin     = $start_date->sub(new DateInterval('P30D')); //created 30 days interval back
    $end       = $end->modify('+1 day'); // interval generates upto last day
    $interval  = new DateInterval('P1D'); // 1d interval range
    $daterange = new DatePeriod($begin, $interval, $end); // it always runs forwards in date
    foreach ($daterange as $date) { // date object
        $days[] = $date->format("Y-m-d"); // your date
    }

    return $days;
}



function totalHoursOnDay($day) {
    global $conn;
    $s_a_e = start_and_end_for_tasks_completed_today($day);
    $s = $s_a_e[0];
    $e = $s_a_e[1];

    $query = "SELECT sum(time_taken) as t  FROM tasks
    WHERE completed_at > :completed_at_start  
    AND completed_at < :completed_at_end
    AND completed = 1 ";

    try {
        $tasks_query = $conn->prepare($query);
        $tasks_query->bindParam(':completed_at_start', $s);
        $tasks_query->bindParam(':completed_at_end', $e);
        $tasks_query->setFetchMode(PDO::FETCH_OBJ);
        $tasks_query->execute();
        $count =  $tasks_query->fetch();
        if ($count->t) {
            return intval($count->t);
        } else {
            return 0;
        }

        unset($conn);
    } catch (PDOException $err) {
        return [];
    };
}

function start_and_end_for_tasks_completed_today($day) {

    $today =  DateTime::createFromFormat('Y-m-d', $day);
    $tomorrow =  DateTime::createFromFormat('Y-m-d', $day);
    $yesterday =  DateTime::createFromFormat('Y-m-d', $day);

    $tomorrow   = $tomorrow->modify('+1 day');
    $yesterday   = $yesterday->modify('-1 day');

    $hour =  date('H');

    // get times from 3am to 3am 
    if ($hour < 3) {
        $s = $yesterday->format('Y-m-d') .  ' 03:00:00';
        $e = $today->format('Y-m-d') .  ' 02:59:59';
    } else {
        $s = $today->format('Y-m-d') .  ' 03:00:00';
        $e = $tomorrow->format('Y-m-d') .  ' 02:59:59';
    }


    return array($s, $e);
}



function get_tasks_completed_today() {
    global $conn;

    $today = date("Y-m-d");
    $s_a_e = start_and_end_for_tasks_completed_today($today);
    $s = $s_a_e[0];
    $e = $s_a_e[1];

    $query = "SELECT *  FROM tasks
        WHERE completed_at > :completed_at_start  
        AND completed_at < :completed_at_end
        AND completed = 1
        ORDER BY completed_at DESC ";

    try {
        $tasks_query = $conn->prepare($query);
        $tasks_query->bindParam(':completed_at_start', $s);
        $tasks_query->bindParam(':completed_at_end', $e);
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
        $is_public = 0;
        if ($task->is_public) {
            $is_public = 1;
        };

        try {
            $query = "INSERT INTO tasks
             (project_id, content, translation, ordering, is_public) VALUES 
             (:project_id, :content, :translation, :ordering, :is_public)";
            $task_query = $conn->prepare($query);
            $task_query->bindParam(':project_id', $task->project_id);
            $task_query->bindParam(':content', $task->content);
            $task_query->bindParam(':translation', $task->translation);
            $task_query->bindParam(':ordering', $task->ordering);
            $task_query->bindParam(':is_public', $is_public);
            $task_query->execute();
            $task_id = $conn->lastInsertId();
            unset($conn);
            return ($task_id);
        } catch (PDOException $err) {
            // var_dump($err);
            return false;
        };
    } else { // task project_id was blank
        return false;
    }
}


function task_comment_count($task_id) {
    global $conn;

    if ($task_id !== null) {

        $query = "SELECT 1  FROM comments
        WHERE task_id = :task_id ";

        try {

            $comments_query = $conn->prepare($query);
            $comments_query->bindParam(':task_id', $task_id);
            $comments_query->setFetchMode(PDO::FETCH_OBJ);
            $comments_query->execute();
            $comments_count = $comments_query->rowCount();

            unset($conn);
            return $comments_count;
        } catch (PDOException $err) {
            return [];
        };
    }
}

function task_upload_count($task_id) {
    global $conn;

    if ($task_id !== null) {

        $query = "SELECT 1  FROM uploads
        WHERE task_id = :task_id ";

        try {

            $comments_query = $conn->prepare($query);
            $comments_query->bindParam(':task_id', $task_id);
            $comments_query->setFetchMode(PDO::FETCH_OBJ);
            $comments_query->execute();
            $comments_count = $comments_query->rowCount();

            unset($conn);
            return $comments_count;
        } catch (PDOException $err) {
            return [];
        };
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

function update_task_uploads_count($task_id) {


    global $conn;
    if ($task_id > 0) {
        // try {
        $uploads_count = task_upload_count($task_id);
        $updated_at =   updated_at_string();

        $query = "UPDATE tasks SET 
                `uploads_count` = :uploads_count, 
                `updated_at` = :updated_at
                WHERE id = :id";
        $task_query = $conn->prepare($query);
        $task_query->bindParam(':uploads_count', $uploads_count);
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
            $is_public = 0;
            $is_approved = 0;
            $assignee_id = 0;
            if ($task->is_title) {
                $is_title = 1;
            };
            if ($task->is_current) {
                $is_current = 1;
            };
            if ($task->is_public) {
                $is_public = 1;
            };
            if ($task->is_approved) {
                $is_approved = 1;
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
            `is_approved` = :is_approved, 
            `is_public` = :is_public, 
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
            $task_query->bindParam(':is_approved', $is_approved);
            $task_query->bindParam(':is_public', $is_public);
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

function update_task_field($task_id, $field, $data) {
    global $conn;
    if ($task_id > 0) {
        try {

            if ($field == 'completed' || $field == 'is_title' || $field == 'is_current' || $field == 'is_public' || $field == 'is_approved' ||  $field == 'is_approved') {
                if ($data == true) {
                    $data = 1;
                } else {
                    $data = 0;
                }
            } else if ($field == 'time_taken') {
                if ($data == null) {
                    $data = 0;
                }
            }

            $ca_sql = '';
            if ($field == 'completed') {
                if ($data == 1) {
                    $task = get_task($task_id);
                    if ($task->completed_at) {
                    } else {
                        $now = updated_at_string();

                        $ca_sql = ",  completed_at = '$now' ";
                    }
                } else {
                    $ca_sql = ",  completed_at =  NULL ";
                }
            }


            $updated_at =   updated_at_string();
            $query = "UPDATE tasks SET 
            `" . $field . "` = :daata, 
            `updated_at` = :updated_at
            $ca_sql
            WHERE id = :id";
            $task_query = $conn->prepare($query);
            $task_query->bindParam(':daata', $data);
            $task_query->bindParam(':updated_at', $updated_at);
            $task_query->bindParam(':id', $task_id);
            $task_query->execute();
            unset($conn);
            return true;
        } catch (PDOException $err) {
            // var_dump($err);
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
    $task->is_public =  ($task->is_public == '1' || $task->is_public == 1);
    $task->is_approved =  ($task->is_approved == '1' || $task->is_approved == 1);
    $task->ordering =  intval($task->ordering);
    $task->indentation =  intval($task->indentation);
    $task->priority =  intval($task->priority);
    $task->time_taken =  intval($task->time_taken);
    $task->id =  intval($task->id);
    $task->project_id =  intval($task->project_id);
    $task->assignee_id =  intval($task->assignee_id);
    $task->uploads_count =  intval($task->uploads_count);
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
