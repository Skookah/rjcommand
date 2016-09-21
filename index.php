<?php
// Common code make go
require("common.php");
// Check for user logged in
if (empty($_SESSION['user'])) {
    //Redirect to the login page if they're not
    header("Location: login.php");
    // ABSOLUTELY CRITICAL
    die("Redirecting to login.php");
}
// Everything below this point is secured behind the login
// We can display the username back to them from the session array,
// but remember to sanitize it - it came from user input!
?>
<head>
    <title>RJCommand Tools Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<div class="center">
    <div class="inner">
        <h1>Dashboard</h1>
        <p class="info">Welcome, <?php echo htmlentities($_SESSION['user']['username'], ENT_QUOTES, 'UTF-8'); ?>, to the
            RJCommand dashboard.</p>
        <h2>Roster Tasks</h2>
        <a href="ra_tracker.php">REDdit ALERT Tasks</a><br/>
        <a href="rse_tracker.php">Reddit Star Empire Tasks</a><br/>
        <a href="snoo_tracker.php">House of Snoo Tasks</a><br/>
        <a href="rising_tracker.php">House of the Rising Snoo Tasks</a><br/>
        <a href="rank_matching.php">Rank Matching</a><br/>
        <a href="illegal_alts.php">Illegal Alts (RA Only)</a><br/>
        <h2>Other Functions</h2>
        <a href="lookup.php">Member Lookup</a><br/>
        <a href="memberlist.php">Memberlist</a><br/>
        <a href="edit_account.php">Edit Account</a><br/>
        <a href="logout.php">Logout</a>
        <br/><br/>
        <img src="img/dickbutt.jpg" alt="Beautiful avian cartoon"/>
        <p class="caption">Courtesy of Zeronius Rex, artiste extroardinaire</p>
    </div>
</div>