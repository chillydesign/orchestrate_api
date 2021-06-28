<?php

ini_set('default_charset', 'UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");
header('Content-Type: application/json;charset=UTF-8');



include('connect.php');
include('functions.php');



if (isset($_GET['route'])) {
    $route = $_GET['route'];

    if ($route == 'projects') {
        if (isset($_GET['id'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                include('routes/projects/delete.php');
            } else if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
                include('routes/projects/update.php');
            } else {
                include('routes/projects/show.php');
            }
        } else 
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            include('routes/projects/create.php');
        } else {
            include('routes/projects/index.php');
        }
    } // end of if route is projects


    if ($route == 'tasks') {
        if (isset($_GET['id'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                include('routes/tasks/delete.php');
            } else if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
                include('routes/tasks/update.php');
            } else {
                include('routes/tasks/show.php');
            }
        } else 
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            include('routes/tasks/create.php');
        } else {
            include('routes/tasks/index.php');
        }
    } // end of if route is tasks



    if ($route == 'uploads') {
        if (isset($_GET['id'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                include('routes/uploads/delete.php');
            } else if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
                include('routes/uploads/update.php');
            } else {
                include('routes/uploads/show.php');
            }
        } else 
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            include('routes/uploads/create.php');
        } else {
            include('routes/uploads/index.php');
        }
    } // end of if route is uploads



    if ($route == 'comments') {
        if (isset($_GET['id'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                include('routes/comments/delete.php');
            } else if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
                include('routes/comments/update.php');
            } else {
                include('routes/comments/show.php');
            }
        } else 
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            include('routes/comments/create.php');
        } else {
            include('routes/comments/index.php');
        }
    } // end of if route is comments


    if ($route == 'users') {
        if (isset($_GET['id'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                include('routes/users/delete.php');
            } else if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
                include('routes/users/update.php');
            } else {
                include('routes/users/show.php');
            }
        } else 
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            include('routes/users/create.php');
        } else {
            include('routes/users/index.php');
        }
    } // end of if route is users


    if ($route == 'user_token') {
        include('routes/user_token/create.php');
    }
} else {
    //  error
    http_response_code(404);
    echo json_encode('error');
}
