<?php
/**
 * gDesk - Helpdesk Ticketing Software
 * @author Gökhan Kaya <0x90kh4n@gmail.com>
 */

define('gDesk', true);

// Display errors
error_reporting(
    ($_SERVER['HTTP_HOST'] == 'localhost') ? -1 : 0
);

// Session start
session_start([
    'name' => 'gDesk',
    'cookie_lifetime' => 60 * 60 * 24 * 365, // 365 days
    'cookie_httponly' => 1
]);

// Include config and helper functions
require_once __DIR__ . '/config.inc.php';
date_default_timezone_set(TIMEZONE);
require_once __DIR__ . '/helpers.inc.php';

// Database connection
try {

    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8',
        DB_USER,
        DB_PASS
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

} catch (PDOException $e) {

    http_response_code(500);
    exit('Error: ' . $e->getMessage());

}