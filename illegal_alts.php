<?php
require("common.php");

$query = illegalAltsQueryBuilder("ra");
try {
    $stmt = $db->prepare($query);
    $stmt->execute();
} catch (PDOException $ex) {
    die(var_dump($ex));
}
$rows = $stmt->fetchAll();
$alt_table = genAltTable($rows);
?>
<html>
<head>
    <title>RJCommand: Illegal Alts</title>
    <?= $head ?>
</head>
<body>
<div class="center">
    <div class="header">
        <div class="roster-uploads">
            <h4>Upload rosters</h4>
            <h5>Command: /ExportGuildMemberList roster_name.csv</h5>
            <form action="upload.php" method="post" enctype="multipart/form-data">
                <div class="input-container">
                    <span>RA: </span><input type="file" name="ra_upload" /><br />
                </div>
                <div class="input-container">
                    <span>RSE: </span><input type="file" name="rse_upload" /><br />
                </div>
                <div class="input-container">
                    <span>Snoo: </span><input type="file" name="snoo_upload" /><br />
                </div>
                <div class="input-container">
                    <span>Rising Snoo: </span><input type="file" name="rising_upload" /><br />
                </div>
                <div class="input-container submit">
                    <input name="upload" type="submit" value="Upload"
                </div>
            </form>
        </div>
        <a href="index.php">Go Back</a>
        <br />
    </div>
    <div class="content"><?= $alt_table ?></div>
    <br />
    <a href="index.php">Go Back</a>
</div>
</body>
</html>
