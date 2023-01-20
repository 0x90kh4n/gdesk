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

// Session
session_start([
    'name' => 'gDesk',
    'cookie_lifetime' => 60 * 60 * 24 * 365, // 365 days
    'cookie_httponly' => 1
]);

// Autoload
spl_autoload_register(function ($className) {
    $classFile = 'libraries/' . $className . '.php';

    if (file_exists($classFile)) {
        require_once $classFile;
    }
});

// Config
require_once 'config.inc.php';

// Default date time zone
if (date_default_timezone_get() !== DATE_TIMEZONE) {
    date_default_timezone_set(DATE_TIMEZONE);
}

// Helper functions
require_once 'helpers.inc.php';

// Database
$pdo = db_connection();

// Load module
load_module($_GET['module'] ?? null);