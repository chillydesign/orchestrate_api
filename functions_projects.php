<?php



function get_projects($opts = null){
    global $conn;

    if($opts == null) {
        $opts =  array('limit' => 10, 'offset' => 0, 'status' => 'active');
    };


    try {
        $query = "SELECT *  FROM projects
        WHERE status = :status
        ORDER BY projects.status ASC, projects.updated_at DESC
        LIMIT :limit OFFSET :offset ";
        $projects_query = $conn->prepare($query);
        $projects_query->bindParam(':limit', intval($opts['limit']), PDO::PARAM_INT);
        $projects_query->bindParam(':offset', intval($opts['offset']), PDO::PARAM_INT);
        $projects_query->bindParam(':status', $opts['status']);
        $projects_query->setFetchMode(PDO::FETCH_OBJ);
        $projects_query->execute();
        $projects_count = $projects_query->rowCount();

        if ($projects_count > 0) {
            $projects =  $projects_query->fetchAll();
        } else {
            $projects =  [];
        }

        unset($conn);
        return $projects;

    } catch(PDOException $err) {
        return [];
    };
}



function get_project($project_id = null) {

    global $conn;
    if ( $project_id != null) {


        try {
            $query = "SELECT * FROM projects WHERE projects.id = :id LIMIT 1";
            $project_query = $conn->prepare($query);
            $project_query->bindParam(':id', $project_id);
            $project_query->setFetchMode(PDO::FETCH_OBJ);
            $project_query->execute();

            $project_count = $project_query->rowCount();

            if ($project_count == 1) {
                $project =  $project_query->fetch();
            } else {
                $project =  null;
            }

            unset($conn);
            return $project;
        } catch(PDOException $err) {
            return null;
        };
    } else { // if project id is not greated than 0
        return null;
    }
}




function create_project($project) {
    global $conn;
    if ( !empty($project->name )){

        try {
            $query = "INSERT INTO projects (name) VALUES (:name)";
            $project_query = $conn->prepare($query);
            $project_query->bindParam(':name', $project->name);
            $project_query->execute();
            $project_id = $conn->lastInsertId();
            unset($conn);

            return ($project_id);

        } catch(PDOException $err) {

            return false;

        };

    } else { // project name was blank
        return false;
    }


}





function update_project($project_id, $project) {
    global $conn;
    if ( $project_id > 0 ){
        try {

            $updated_at = updated_at_string();
            $query = "UPDATE projects SET `name` = :name,  `status` = :status, `updated_at` = :updated_at WHERE id = :id";
            $project_query = $conn->prepare($query);
            $project_query->bindParam(':name', $project->name);
            $project_query->bindParam(':status', $project->status);
            $project_query->bindParam(':updated_at', $updated_at);

            $project_query->bindParam(':id', $project_id);
            $project_query->execute();
            unset($conn);

            return true;

        } catch(PDOException $err) {
            return false;

        };

    } else { // project name was blank
        return false;
    }

}

// change the updated_at date
function touch_project($project_id) {
    global $conn;
    if ( $project_id > 0 ){
        try {
            $updated_at = updated_at_string();
            $query = "UPDATE projects SET `updated_at` = :updated_at WHERE id = :id";
            $project_query = $conn->prepare($query);
            $project_query->bindParam(':updated_at', $updated_at);
            $project_query->bindParam(':id', $project_id);
            $project_query->execute();
            unset($conn);
            return true;
        } catch(PDOException $err) {
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


        } catch(PDOException $err) {
            return false;
        };
    } else {
        return false;
    }

}


function tasks_count($project_id){
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
                foreach($counts as $count) {
                    if ( $count->completed == 1) {
                        $c->complete = intval($count->$count_col);
                        $c->total += $c->complete;
                    } else if ( $count->completed == 0) {
                        $c->incomplete = intval($count->$count_col);
                        $c->total += $c->incomplete;
                    }
                }
            }
            unset($conn);
            return $c;

          

        } catch(PDOException $err) {
            return 0;
        };

}
}



?>