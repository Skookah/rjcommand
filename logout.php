<?php
    require("common.php");
    // Remove the user date from the session
    unset($_SESSION['user']);

    // Redirect to login
    header("Location: login.php");
    die("Redirecting to: login.php");
