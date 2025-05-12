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
    if (!isset($opts['assignee_id'])) {
        $opts['assignee_id'] = null;
    }
    if (!isset($opts['search_term'])) {
        $opts['search_term'] = null;
    }
    if (!isset($opts['project_id'])) {
        $opts['project_id'] = null;
    }

    $project_id  = ($opts['project_id']);
    $limit  = intval($opts['limit']);
    $offset = intval($opts['offset']);
    $status = $opts['status'];
    $client_id = $opts['client_id'];
    $current = ($opts['current']);
    $assignee_id = ($opts['assignee_id']);
    $search_term = $opts['search_term'];

    $client_id_sql = '';
    if ($client_id) {
        $client_id_sql = '  AND client_id = :client_id';
    }
    $cur_dist_sql = '';
    $status_sql = '';
    $cur_join_sql = '';
    $cur_sql = '';
    $sear_sql = '';


    $project_id_sql = '';
    if ($project_id) {
        $project_id_sql = '  AND projects.id = :project_id';
    }


    if (($current && $assignee_id) || $search_term) {
        $cur_dist_sql = ' DISTINCT';
        $cur_join_sql = ' LEFT JOIN tasks ON tasks.project_id = projects.id ';

        if ($current && $assignee_id) {
            $cur_sql = " AND tasks.is_current = 1 AND tasks.assignee_id = $assignee_id AND tasks.completed = 0";
        }
        if ($search_term) {
            $sear_sql = " AND  (tasks.content LIKE :search_term OR tasks.task_code LIKE :search_term  OR projects.name LIKE :search_term  )   ";
        }
    }

    if ($status) { {
            if ($status != 'all') {
                $status_sql = ' AND status = "' . $status . '"  ';
            }
        }
    }

    try {
        $query = "SELECT  $cur_dist_sql projects.* , clients.slug as client_slug  FROM projects $cur_join_sql
        LEFT JOIN clients ON  clients.id = projects.client_id
        WHERE 1 = 1
        $status_sql
        $client_id_sql
        $cur_sql
        $sear_sql
        $project_id_sql
        ORDER BY projects.updated_at DESC, projects.name ASC, projects.month DESC
        LIMIT :limit OFFSET :offset ";

        // ORDER BY projects.updated_at DESC , projects.status ASC,  projects.incomplete_tasks_count DESC


        $projects_query = $conn->prepare($query);
        $projects_query->bindParam(':limit', $limit, PDO::PARAM_INT);
        $projects_query->bindParam(':offset', $offset, PDO::PARAM_INT);

        if ($client_id_sql != '') {
            $projects_query->bindParam(':client_id', $client_id, PDO::PARAM_INT);
        }
        if ($project_id_sql != '') {
            $projects_query->bindParam(':project_id', $project_id, PDO::PARAM_INT);
        }
        if ($search_term) {
            $pst =  "%" . $search_term . "%";
            $projects_query->bindParam(':search_term', $pst);
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



function get_project($project_id = null, $try_slug = false) {

    global $conn;
    if ($project_id != null) {


        try {

            if ($try_slug) {
                $query = "SELECT * FROM projects WHERE projects.slug = :slug LIMIT 1";
            } else {
                $query = "SELECT * FROM projects WHERE projects.id = :id LIMIT 1";
            }

            $project_query = $conn->prepare($query);
            if ($try_slug) {
                $project_query->bindParam(':slug', $project_id);
            } else {
                $project_query->bindParam(':id', $project_id);
            }
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

        if (isset($project->month)) {
            if ($project->month == '') {
                $project->month = null;
            }
        }
        if (isset($project->slug)) {
            $project_slug = slugify($project->slug);
        } else {
            $project_slug = slugify($project->name);
        }



        $today = new DateTime();
        $slugdate_str = $today->format("Y-m-d");
        $project_slug = slugify($project_slug . '-' . $slugdate_str);


        try {
            $query = "INSERT INTO projects (name, client_id, month, slug) VALUES (:name, :client_id, :month, :slug)";
            $project_query = $conn->prepare($query);
            $project_query->bindParam(':name', $project->name);
            $project_query->bindParam(':client_id', $project->client_id);
            $project_query->bindParam(':slug', $project_slug);
            $project_query->bindParam(':month', $project->month);
            $project_query->execute();
            $project_id = $conn->lastInsertId();
            unset($conn);

            return ($project_id);
        } catch (PDOException $err) {
            // var_dump($err);
            return false;
        };
    } else { // project name was blank
        return false;
    }
}


function slugify($text,) {
    // replace non letter or digits by divider
    $divider = '-';
    $text = preg_replace('~[^\pL\d]+~u', $divider, $text);

    // transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);

    // trim
    $text = trim($text, $divider);

    // remove duplicate divider
    $text = preg_replace('~-+~', $divider, $text);

    // lowercase
    $text = strtolower($text);

    if (empty($text)) {
        return 'n-a';
    }

    return $text;
}



function update_project($project_id, $project) {
    global $conn;
    if ($project_id > 0) {
        try {


            if ($project->month == '') {
                $project->month = null;
            }

            $updated_at = updated_at_string();
            $query = "UPDATE projects SET
              `name` = :name,  
              `client_id` = :client_id,  
              `status` = :status, 
              `month` = :month, 
              `updated_at` = :updated_at 
              WHERE id = :id";
            $project_query = $conn->prepare($query);
            $project_query->bindParam(':name', $project->name);
            $project_query->bindParam(':status', $project->status);
            $project_query->bindParam(':client_id', $project->client_id);
            $project_query->bindParam(':month', $project->month);
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


function touch_all_projects() {
    $all_projects = get_projects(array('limit' => 9999999));
    foreach ($all_projects as $project) {
        touch_project($project->id);
    }
}



// change the updated_at date
function touch_project($project_id, $change_updated_date = true) {
    global $conn;
    if ($project_id > 0) {

        // get tasks count and get incomplete tasks count
        $tasks_count = tasks_count($project_id);
        $total = $tasks_count->total;
        $incomplete = $tasks_count->incomplete;

        $cud = '';
        if ($change_updated_date) {
            $cud  = " `updated_at` = :updated_at, ";
        }



        try {
            $updated_at = updated_at_string();
            $query = "UPDATE projects SET  $cud `tasks_count` = :total, `incomplete_tasks_count` = :incomplete WHERE id = :id";
            $project_query = $conn->prepare($query);
            if ($change_updated_date) {
                $project_query->bindParam(':updated_at', $updated_at);
            }
            $project_query->bindParam(':total', $total);
            $project_query->bindParam(':incomplete', $incomplete);
            $project_query->bindParam(':id', $project_id);
            $project_query->execute();
            unset($conn);

            touch_client_from_project_id($project_id);
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
        if ($string) {
            $new_string = html_entity_decode($string);
            $new_string = str_replace(array("\r", "\n"), ' | ', $new_string);
            $new_string = str_replace(';', ' ', $new_string);
            $new_string = str_replace(',', ' ', $new_string);
            $new_string = str_replace('"', '~~', $new_string);
            $new_string = strip_tags($new_string);
            return $new_string;
        }
        return '';
    }
}


function show_project_as_csv($json, $show_header = true) {

    $csv_array = [];
    $csv_header = array(
        "Client",
        "Project",
        "Completed at month",
        "Minutes",
        "Task",
        "Translation",
        "Code",
        "Completed at",
        "Created at",
        "Updated at",
    );

    if ($show_header) {
        array_push($csv_array,   implode(',', $csv_header));
    }

    if (property_exists($json, 'client')) {
        $client_name = $json->client->name;
    } else {
        $client_name = $json->client_id;
    }



    foreach ($json->tasks as $task) {


        $completed_at_month = month_of_date($task->completed_at);
        $csv_row = [
            $client_name,
            api_save_csv_string($json->name),
            $completed_at_month,
            $task->time_taken,
            api_save_csv_string($task->content),
            api_save_csv_string($task->translation),
            api_save_csv_string($task->task_code),
            $task->completed_at,
            $task->created_at,
            $task->updated_at,
        ];
        array_push($csv_array,   implode(',', $csv_row));
    }
    $csv_string =   implode("\n", $csv_array);
    return $csv_string;
}


function month_of_date($date) {
    if ($date) {
        return  date('Y-m', strtotime($date));
    }
    return '-';
}


function processProject($project) {
    if ($project->client_id) {
        $project->client_id =  intval($project->client_id);
    }
    $project->tasks_count =  intval($project->tasks_count);
    $project->incomplete_tasks_count =  intval($project->incomplete_tasks_count);
    $project->id =  intval($project->id);
    return $project;
}


function processProjects($projects) {

    foreach ($projects as $project) {
        processProject($project);
    }

    return $projects;
}


function send_hourly_email_reminder() {
    try {
        $current_time = new DateTime();
        $formatted_time = $current_time->format('Y-m-d H:00:00');
        $opts = array('start_date' => $formatted_time);
        $tasks = get_tasks($opts);


        if (sizeof($tasks) > 0) {
            $base_href = "https://webfactor.ch/orchestrate";
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->isHTML();
            $mail->isSMTP();                          // Set mailer to use SMTP
            $mail->Host = 'smtp.gmail.com';           // Specify main and backup SMTP servers
            $mail->SMTPAuth = true;                   // Enable SMTP authentication
            $mail->Username = MAIL_USERNAME;          // SMTP username
            $mail->Password = MAIL_PASSWORD;          // SMTP password
            $mail->SMTPSecure = 'tls';                // Enable TLS encryption, `ssl` also accepted
            $mail->Port = 587;
            $mail->Subject = 'New tasks on orchestrate';
            $body = '<p>New tasks on orchestrate</p><ul>';
            foreach ($tasks as $task) {
                $body .= '<li>' . $task->content . '<a href="' . $base_href  . '/projects/' . $task->project_id . '"> View </a></li>';
            }
            $body .= '</ul>';
            $mail->Body  = $body;
            $user = get_user(1);
            $mail->addAddress($user->email);
            // echo $mail->Body;
            $mail->send();
        }



        return true;
    } catch (Exception $e) {
        return  "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}


function send_email_project_created($project) {


    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();                          // Set mailer to use SMTP
        $mail->Host = 'smtp.gmail.com';           // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                   // Enable SMTP authentication
        $mail->Username = MAIL_USERNAME;          // SMTP username
        $mail->Password = MAIL_PASSWORD;          // SMTP password
        $mail->SMTPSecure = 'tls';                // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 587;
        $mail->Subject = 'A project was created on Orchestrate';
        if (property_exists($project, 'client')) {
            $body = 'Go to the project. https://webfactor.ch/orchestrate/clients/' . $project->client->slug;
        } else {
            $body = 'Go to the project. https://webfactor.ch/orchestrate/projects/' . $project->id;
        }

        $mail->Body    = $body;
        $user_emails =  get_user_emails();
        foreach ($user_emails as $email_address) {
            $mail->addAddress($email_address);
        }
        $mail->send();
        return true;
    } catch (Exception $e) {
        return  "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
