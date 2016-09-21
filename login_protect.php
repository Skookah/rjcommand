<?php
	// Check for logged in session
    if (empty($_SESSION['user'])) {
    	// Presever current page
    	$_SESSION['redirect'] = urlencode(trim($_SERVER['REQUEST_URI'], '/'));
        header("Location: login.php");
        die("Redirecting to login.php");
    }
