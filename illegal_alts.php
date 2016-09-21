<?php
require("common.php");
require("login_protect.php");
require("fleet_info.php");
$query = illegalAltsQueryBuilder("ra");
try {
    $stmt = $db->prepare($query);
    $stmt->execute();
} catch (PDOException $ex) {
    die("Failed to retrieve alts: " . var_dump($ex));
}
$rows = $stmt->fetchAll();
?>
<html>
<head>
    <title>RJCommand: Illegal Alts</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
    <script src="jquery-2.1.4.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
    <script src="jquery.tablesorter.min.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="center table-container">
    <h4>Upload rosters</h4>
    <h5>Command: /ExportGuildMemberList roster_name.csv</h5>
    <form action="upload.php" method="post" enctype="multipart/form-data">
        <div class="input-container"><div><span>RA: </span></div><input type="file" name="ra_upload" /></div><br /><br />
        <div class="input-container"><div><span>RSE: </span></div><input type="file" name="rse_upload" /></div><br /><br />
        <div class="input-container"><div><span>Snoo: </span></div><input type="file" name="snoo_upload" /></div><br /><br />
        <div class="input-container"><div><span>Rising Snoo: </span></div><input type="file" name="rising_upload" /></div><br /><br />
        <div class="input-container"><input name="upload" type="submit" value="Upload" /></div>
    </form>
    <br />
    <h2>RA Illegal Alts</h2>
    <?php echo genAltTable($rows); ?>
    <br />
    <a href="index.php">Go Back</a>
</div>
<script>
    $(document).ready(function() {
        $("table").tablesorter({sortList: [[2, 0], [3, 0]]});
    });
</script>
</body>
</html>
