<?php
/**
 * gDesk - Helpdesk Ticketing Software
 * @author Gökhan Kaya <0x90kh4n@gmail.com>
 */

defined('gDesk') or die();

// Database connection
function db_connection() {
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8',
            DB_USER,
            DB_PASS
        );

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
    } catch (PDOException $e) {
        exit('Error: ' . $e->getMessage());
    }

    return $pdo;
}

// Load module
function load_module($module = null) {
    $module = 'modules/' . ($module ?: DEFAULT_MODULE) . '.php';

    if (!file_exists($module)) {
        show_404();
    }

    include $module;
}

// 404 error page
function show_404() {
    exit(http_response_code(404));
}