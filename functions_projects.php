<?php



function get_projects($opts = null) {
    global $conn;

    if ($opts == null) {
        $opts =  array();
    }
    if (!isset($opts['offset'])) {
        $opts['offset'] = 0;
    }
    if (!isset($opts['limit'])) {
        $opts['limit'] = 10;
    }
    if (!isset($opts['status'])) {
        $opts['status'] = 'active';
    }
    if (!isset($opts['client_id'])) {
        $opts['client_id'] = null;
    }
    if (!isset($opts['current'])) {
        $opts['current'] = null;
    }

    $limit  = intval($opts['limit']);
    $offset = intval($opts['offset']);
    $status = $opts['status'];
    $client_id = $opts['client_id'];
    $current = ($opts['current']);

    $client_id_sql = '';
    if ($client_id) {
        $client_id_sql = '  AND client_id = :client_id';
    }
    $cur_dist_sql = '';
    $cur_join_sql = '';
    $cur_sql = '';
    if ($current) {
        $cur_dist_sql = ' DISTINCT';
        $cur_join_sql = ' LEFT JOIN tasks ON tasks.project_id = projects.id ';
        $cur_sql = ' AND tasks.is_current = 1 AND tasks.completed = 0';
    }

    try {
        $query = "SELECT  $cur_dist_sql projects.*  FROM projects $cur_join_sql
        WHERE status = :status
        $client_id_sql
        $cur_sql
        ORDER BY projects.status ASC, projects.updated_at DESC
        LIMIT :limit OFFSET :offset ";

        $projects_query = $conn->prepare($query);
        $projects_query->bindParam(':limit', $limit, PDO::PARAM_INT);
        $projects_query->bindParam(':offset', $offset, PDO::PARAM_INT);
        $projects_query->bindParam(':status', $status);
        if ($client_id_sql != '') {
            $projects_query->bindParam(':client_id', $client_id, PDO::PARAM_INT);
        }
        $projects_query->setFetchMode(PDO::FETCH_OBJ);
        $projects_query->execute();
        $projects_count = $projects_query->rowCount();

        if ($projects_count > 0) {
            $projects =  $projects_query->fetchAll();
            $projects = processProjects($projects);
        } else {
            $projects =  [];
        }

        unset($conn);
        return $projects;
    } catch (PDOException $err) {
        return [];
    };
}



function get_project($project_id = null) {

    global $conn;
    if ($project_id != null) {


        try {
            $query = "SELECT * FROM projects WHERE projects.id = :id LIMIT 1";
            $project_query = $conn->prepare($query);
            $project_query->bindParam(':id', $project_id);
            $project_query->setFetchMode(PDO::FETCH_OBJ);
            $project_query->execute();

            $project_count = $project_query->rowCount();

            if ($project_count == 1) {
                $project =  $project_query->fetch();
                $project = processProject($project);
            } else {
                $project =  null;
            }

            unset($conn);
            return $project;
        } catch (PDOException $err) {
            return null;
        };
    } else { // if project id is not greated than 0
        return null;
    }
}




function create_project($project) {
    global $conn;
    if (!empty($project->name)) {

        try {
            $query = "INSERT INTO projects (name, client_id) VALUES (:name, :client_id)";
            $project_query = $conn->prepare($query);
            $project_query->bindParam(':name', $project->name);
            $project_query->bindParam(':client_id', $project->client_id);
            $project_query->execute();
            $project_id = $conn->lastInsertId();
            unset($conn);

            return ($project_id);
        } catch (PDOException $err) {

            return false;
        };
    } else { // project name was blank
        return false;
    }
}





function update_project($project_id, $project) {
    global $conn;
    if ($project_id > 0) {
        try {

            $updated_at = updated_at_string();
            $query = "UPDATE projects SET
              `name` = :name,  
              `client_id` = :client_id,  
              `status` = :status, 
              `updated_at` = :updated_at 
              WHERE id = :id";
            $project_query = $conn->prepare($query);
            $project_query->bindParam(':name', $project->name);
            $project_query->bindParam(':status', $project->status);
            $project_query->bindParam(':client_id', $project->client_id);
            $project_query->bindParam(':updated_at', $updated_at);

            $project_query->bindParam(':id', $project_id);
            $project_query->execute();
            unset($conn);

            return true;
        } catch (PDOException $err) {
            return false;
        };
    } else { // project name was blank
        return false;
    }
}




// UPDATE `tasks` SET `project_id` = '2' WHERE `tasks`.`id` = 1; 




function move_incomplete_tasks($old_project_id, $new_project_id) {
    global $conn;

    $new_project = get_project($new_project_id);
    $old_project = get_project($old_project_id);
    $completed = 0;

    if ($new_project && $old_project) {
        try {

            $query = "UPDATE tasks SET  `project_id` = :new_project_id
            WHERE project_id = :old_project_id  AND completed = :completed";
            $task_query = $conn->prepare($query);
            $task_query->bindParam(':new_project_id', $new_project_id);
            $task_query->bindParam(':old_project_id', $old_project_id);
            $task_query->bindParam(':completed', $completed);
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



// change the updated_at date
function touch_project($project_id) {
    global $conn;
    if ($project_id > 0) {
        try {
            $updated_at = updated_at_string();
            $query = "UPDATE projects SET `updated_at` = :updated_at WHERE id = :id";
            $project_query = $conn->prepare($query);
            $project_query->bindParam(':updated_at', $updated_at);
            $project_query->bindParam(':id', $project_id);
            $project_query->execute();
            unset($conn);
            return true;
        } catch (PDOException $err) {
            return false;
        };
    } else { // project name was blank
        return false;
    }
}



function delete_project($project_id) {

    global $conn;
    if ($project_id > 0) {

        try {
            $query = "DELETE FROM projects  WHERE id = :id    ";
            $project_query = $conn->prepare($query);
            $project_query->bindParam(':id', $project_id);
            $project_query->setFetchMode(PDO::FETCH_OBJ);
            $project_query->execute();

            unset($conn);
            return true;
        } catch (PDOException $err) {
            return false;
        };
    } else {
        return false;
    }
}


function tasks_count($project_id) {
    global $conn;

    if ($project_id > 0) {

        try {
            // SIMPLE COUNT OF ALL TASKS IRRESPECTIVE OF IF COMPLETE OR NOT
            // $query = "SELECT COUNT(1) FROM tasks WHERE project_id = :id";
            // $task_count_query = $conn->prepare($query);
            // $task_count_query->bindParam(':id', $project_id);
            // $task_count_query->setFetchMode(PDO::FETCH_OBJ);
            // $task_count_query->execute();
            // $number_of_rows = $task_count_query->fetchColumn(); 
            // return $number_of_rows;

            // GIVES OBJECT OF COUNTS OF COMPLETED AND INCOMPLETED TASKS
            $query = "SELECT completed, COUNT(*) FROM tasks  WHERE project_id = :id GROUP BY completed";
            $task_count_query = $conn->prepare($query);
            $task_count_query->bindParam(':id', $project_id);
            $task_count_query->setFetchMode(PDO::FETCH_OBJ);
            $task_count_query->execute();
            $counts =  $task_count_query->fetchAll();
            $count_col = "COUNT(*)";
            $c  = new stdClass();
            $c->complete = 0;
            $c->incomplete = 0;
            $c->total = 0;
            if ($counts) {
                foreach ($counts as $count) {
                    if ($count->completed == 1) {
                        $c->complete = intval($count->$count_col);
                        $c->total += $c->complete;
                    } else if ($count->completed == 0) {
                        $c->incomplete = intval($count->$count_col);
                        $c->total += $c->incomplete;
                    }
                }
            }
            unset($conn);
            return $c;
        } catch (PDOException $err) {
            return 0;
        };
    }
}

if (!function_exists('api_save_csv_string')) {
    function api_save_csv_string($string) {

        $new_string = html_entity_decode($string);
        $new_string = str_replace(array("\r", "\n"), ' | ', $new_string);
        $new_string = str_replace(';', ' ', $new_string);
        $new_string = str_replace(',', ' ', $new_string);
        $new_string = strip_tags($new_string);
        return $new_string;
    }
}


function show_project_as_csv($json) {

    $csv_array = [];
    $csv_header = array(
        "Task",
        "Minutes",
    );
    array_push($csv_array,   implode(',', $csv_header));


    foreach ($json->tasks as $task) {
        $csv_row = [
            api_save_csv_string($task->content),
            $task->time_taken
        ];
        array_push($csv_array,   implode(',', $csv_row));
    }
    $csv_string =   implode("\n", $csv_array);
    return $csv_string;
}



function processProject($project) {
    if ($project->client_id) {
        $project->client_id =  intval($project->client_id);
    }
    $project->id =  intval($project->id);
    return $project;
}


function processProjects($projects) {

    foreach ($projects as $project) {
        processProject($project);
    }

    return $projects;
}
