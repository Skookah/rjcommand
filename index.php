<?php
require("common.php");
?>
<html>
<head>
    <title>RJCommand Tools Dashboard</title>
    <?= $head ?>
</head>
<body>
<div class="center">
    <h1>Dashboard</h1>
    <p class="sub">Welcome, <?= htmlspecialchars($_SESSION['user']['username'], ENT_QUOTES) ?>, to the
        RJCommand dashboard.</p>
    <h2>Roster Tasks</h2>
    <a href="ra_tracker.php">REDdit ALERT Tasks</a><br />
    <a href="rse_tracker.php">Reddit Star Empire Tasks</a><br />
    <a href="snoo_tracker.php">House of Snoo Tasks</a><br />
    <a href="rising_tracker.php">House of the Rising Snoo Tasks</a><br />
    <a href="rank_matching.php">Rank Matching</a><br />
    <a href="illegal_alts.php">Illegal Alts (RA Only)</a><br />
    <h2>Other Functions</h2>
    <a href="lookup.php">Member Lookup</a><br />
    <a href="edit_account.php">Edit Account</a><br />
    <a href="logout.php">Logout</a>
    <br /><br />
</div>
</body>
</html>