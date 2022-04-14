<?php


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


require_once('./vendor/autoload.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

include('env.php');



define("DB_HOST",  getenv('DB_HOST'));
define("DB_NAME",  getenv('DB_NAME'));
define("DB_USER",  getenv('DB_USER'));
define("DB_PASSWORD",  getenv('DB_PASSWORD'));


define("UPLOADDIR",  '/public/uploads/');
define("FILELOC", dirname(__FILE__));

define("MAIL_USERNAME", getenv('MAIL_USERNAME'));
define("MAIL_PASSWORD", getenv('MAIL_PASSWORD'));

define("JWT_SECRET", getenv('JWT_SECRET'));
define("JWT_ALG", getenv('JWT_ALG'));
define("JWT_SERVER", getenv('JWT_SERVER'));

try {

    $conn = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,  DB_USER, DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->query("SET CHARACTER SET utf8");
} catch (PDOException $e) {
    echo 'ERROR: ' . $e->getMessage();
}
