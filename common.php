<?php
    // Shared values
    $username = "";
    $password = "";
    $host = "localhost";
    $dbname = "rjc";
    // Use UTF-8 in the db
    $options = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8');
    try {
	// Open DB connection with PDO
	$db = new PDO("mysql:host={$host}; dbname={$dbname}; charset=utf8", $username, $password, $options);
    } catch (PDOException $ex) {
	// Failure to connect to db
	die("Failed to connect to the database!");
    }
    // Set PDO to throw an exception when it encounters an error
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set PDO to return db rows using an associative array
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Undo magic quotes.
    if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
        function undo_magic_quotes_gpc(&$array) {
            foreach ($array as &$value) {
                if (is_array($value)) {
                    undo_magic_quotes_gpc($value);
                } else {
                    $value = stripslashes($value);
                }
            }
        }
        undo_magic_quotes_gpc($_POST);
        undo_magic_quotes_gpc($_GET);
        undo_magic_quotes_gpc($_COOKIE);
    }
    // Tell the browser to use UTF-8
    header('Content-Type: text/html; charset=utf-8');
    // Start a session to store visitor info from one visit to another. Like a server-side cookie, but also cookies.
    session_start();
