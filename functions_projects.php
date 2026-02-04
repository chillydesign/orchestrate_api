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
            if (isset($project->month)) {
                $project_slug = slugify($project->name . '-' . $project->month);
            } else {
                $today = new DateTime();
                $slugdate_str = $today->format("Y-m-d");
                $project_slug = slugify($project->name . '-' . $slugdate_str);
            }
        }





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

function comments_as_string($comments) {
    $messages = array_map(function ($comment) {
        return $comment->message;
    }, $comments);
    return api_save_csv_string(implode(' | ', $messages));
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
        "Comments",
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
            comments_as_string($task->comments),
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
        $six_hours_ago = $current_time->sub(new DateInterval('PT6H'))->format('Y-m-d H:00:00');
        $tasks = get_tasks(array('start_date' => $six_hours_ago, 'completed' => 0, 'include_comments' => false));


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
            $mail->Subject = 'New tasks on orchestrate from ' .  $six_hours_ago;


            $ulstyle = ' style="list-style:none;margin: 0 0 20px;padding:0;border-top: 1px solid #dde"';
            $listyle = ' style="padding:20px;margin:0;border: 1px solid #dde;border-top:0;"';
            $morestyle = ' style="padding:3px;margin:2px 0 0;display:inline-block;font-size:11px;font-weight:bold;color:#3369ff"';


            $content = '<h3>New tasks on orchestrate</h3><ul ' . $ulstyle . '>';
            foreach ($tasks as $task) {
                $content .= '<li ' . $listyle . '>' . $task->content . '<div><a   ' .  $morestyle . ' href="' . $base_href  . '/projects/' . $task->project_id . '"> View </a></div></li>';
            }
            $content .= '</ul>';
            $mail->Body  =   emailTemplate($content);
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


function emailTemplate($content) {

    $str =  '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8"> <!-- utf-8 works for most cases -->
    <meta name="viewport" content="width=device-width"> <!-- Forcing initial-scale shouldnt be necessary -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge"> <!-- Use the latest (edge) version of IE rendering engine -->
    <title></title> <!-- The title tag shows in email notifications, like Android 4.4. -->


    <!-- CSS Reset -->
    <style>

        /* What it does: Remove spaces around the email design added by some email clients. */
        /* Beware: It can remove the padding / margin and add a background color to the compose a reply window. */
        html,
        body {
            margin: 0 auto !important;
            padding: 0 !important;
            height: 100% !important;
            width: 100% !important;
        }

        /* What it does: Stops email clients resizing small text. */
        * {
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
        }

        /* What is does: Centers email on Android 4.4 */
        div[style*="margin: 16px 0"] {
            margin:0 !important;
        }

        /* What it does: Stops Outlook from adding extra spacing to tables. */
        table,
        td {
            mso-table-lspace: 0pt !important;
            mso-table-rspace: 0pt !important;
        }

        /* What it does: Fixes webkit padding issue. Fix for Yahoo mail table alignment bug. Applies table-layout to the first 2 tables then removes for anything nested deeper. */
        table {
            border-spacing: 0 !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            margin: 0 auto !important;
        }
        table table table {
            table-layout: auto;
        }

        /* What it does: Uses a better rendering method when resizing images in IE. */
        img {
            -ms-interpolation-mode:bicubic;
        }

        /* What it does: A work-around for iOS meddling in triggered links. */
        .mobile-link--footer a,
        a[x-apple-data-detectors] {
            color:inherit !important;
            text-decoration: underline !important;
        }

    </style>

    <!-- Progressive Enhancements -->
    <style>

        /* What it does: Hover styles for buttons */
        .button-td,
        .button-a {
            transition: all 100ms ease-in;
        }


    </style>

</head>
<body width="100%" bgcolor="#f5f5f5" style="margin: 0;">
    <center style="width: 100%; background: no-repeat center center #f5f5f5; background-size:cover">

        <!-- Visually Hidden Preheader Text : BEGIN -->
        <!--         <div style="display:none;font-size:1px;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;mso-hide:all;font-family: sans-serif;">
        (Optional) This text will appear in the inbox preview, but not the email body.
    </div> -->
    <!-- Visually Hidden Preheader Text : END -->

    <!--
    Set the email width. Defined in two places:
    1. max-width for all clients except Desktop Windows Outlook, allowing the email to squish on narrow but never go wider than 600px.
    2. MSO tags for Desktop Windows Outlook enforce a 600px width.
-->
<div style="max-width: 600px; margin: auto;">
    <!--[if mso]>
    <table cellspacing="0" cellpadding="0" border="0" width="600" align="center">
    <tr>
    <td>
    <![endif]-->

    <!-- Email Header : BEGIN -->
    <!--             <table cellspacing="0" cellpadding="0" border="0" align="center" width="100%" style="max-width: 600px;" role="presentation">
    <tr>
    <td style="padding: 20px 0; text-align: center">
    <img src="http://placehold.it/200x50" width="200" height="50" alt="alt_text" border="0">
</td>
</tr>
</table> -->
<!-- Email Header : END -->

<!-- Clear Spacer : BEGIN -->
<table cellspacing="0" cellpadding="0" border="0" align="center" width="100%" style="max-width: 600px;" role="presentation">
    <tr>
        <td height="40" style="font-size: 0; line-height: 0;">
            &nbsp;
        </td>
    </tr>
</table>
<!-- Clear Spacer : END -->

<!-- Email Body : BEGIN -->
<table cellspacing="0" cellpadding="0" border="0" align="center" width="100%" style="max-width: 600px;" role="presentation">

    <!-- Hero Image, Flush : BEGIN -->
    <tr>
        <td bgcolor="#3369ff">
            <div style="width: 100%; max-width: 400px;margin:30px auto 0;">
                <h3 style="font-family: sans-serif;color: white;font-size:30px;padding:15px 0 ;margin:auto;text-align: center">Orchestrate</h3>

            </div>

        </td>
    </tr>
    <!-- Hero Image, Flush : END -->


    <!-- 1 Column Text + Button : BEGIN -->
    <tr>
        <td bgcolor="#ffffff">
            <table cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td style="padding: 20px 40px 20px; font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555;">

                     ' . $content . '



                    </td>
                </tr>


            </table>
        </td>
    </tr>

    <!-- 1 Column Text + Button : BEGIN -->

</table>

<!-- Email Footer : BEGIN -->
<table cellspacing="0" cellpadding="0" border="0" align="center" width="100%" style="max-width: 680px;background: #eee;padding:0" role="presentation">
    <tr>
        <td style="padding: 15px 10px ;width: 100%;font-size: 12px; font-family: sans-serif; mso-height-rule: exactly; line-height:18px; text-align: center; color: #333;">
            <!--  <webversion style="color:#666666; text-decoration:underline; font-weight: bold;">View as a Web Page</webversion> -->
        Orchestrate
             

            <!--   <unsubscribe style="color:#888888; text-decoration:underline;"><a href="">unsubscribe</a></unsubscribe> -->
        </td>
    </tr>
</table>
<!-- Email Footer : END -->

<!--[if mso]>
</td>
</tr>
</table>
<![endif]-->
</div>
</center>
</body>
</html>
';



    return $str;
}
