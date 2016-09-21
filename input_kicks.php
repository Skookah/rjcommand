<?php
    require("common.php");
    require("login_protect.php");
    require("fleet_info.php");

?>
<html>
<head>
    <title>RJCommand: Manual Kick Input</title>
    <script src="jquery-2.1.4.min.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="center table-container">
        <h2>Manual Kick Input</h2>
        <br />
        <form action="input_kicks.php" method="post">
            <div class="input-container"><span>Rank:</span><div class="input-text"><input type="text" name="rank" /></div>
            </div>
            <br />
            <div class="input-container"><span>Toon:</span><div class="input-text"><input type="text" name="toon" /></div>
            </div>
            <br />
            <div class="input-container"><span>Account:</span><div class="input-text"><input type="text" name="account" /></div>
            </div>
            <br />
            <div class="input-container"><span>Note:</span><div class="input-text"><input type="text" name="note" /></div>
            </div>
            <br />
            <div class="input-container"><input name="submit" type="submit" value="Submit" /></div>
        </form>
        <br />
        <a href="index.php">Go Back</a>
        <br />
    </div>
</body>
</html>
