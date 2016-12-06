<?php
// General functionality
require("common.php");
global $fleets;
// If a rank match has been completed, update the sentinel file
if (!empty($_POST)) {
    if (isset($_POST['match_done'])) {
        if (!file_put_contents("tasks/rank_match", $_SESSION['user']['username'])) {
            die("Failed to update task sentinel");
        }
    }
}

// Populate roster update times
$update_times = [];
foreach ($fleets as $fleet) {
    $update_times[$fleet] = stat("tasks/" . $fleet . "_update")['mtime'];
}

// Determine which roster was updated last (oldest/lowest update time)
$first_update = min($update_times);
$first_roster = array_search($first_update, $update_times);

// Retrieve last rank match data
$last_matched = stat("tasks/rank_match")['mtime'];
$matched_by = file_get_contents("tasks/rank_match");

// Create rank match queries
$fed_query = rankMatchQueryBuilderA("ra", "rse");
$snoo_query = rankMatchQueryBuilderA("snoo", "rising");
$cross_query = rankMatchQueryBuilderB("ra", "rse", "snoo", "rising");

// Following queries only actually find same-account toons across fleets
// Retrieve matches part uno
try {
    $stmt = $db->prepare($fed_query);
    $stmt->execute();
} catch (PDOException $ex) {
    die("Failed to retrieve Fed-Fed rank match candidates: " . $ex);
}
$ra_rse_matches = $stmt->fetchAll();

// Retrieve matches part dux
try {
    $stmt = $db->prepare($snoo_query);
    $stmt->execute();
} catch (PDOException $ex) {
    die("Failed to retrieve KDF-KDF rank match candidates");
}
$snoo_rising_matches = $stmt->fetchAll();

// Retrieve matches part tris
try {
    $stmt = $db->prepare($cross_query);
    $stmt->execute();
} catch (PDOException $ex) {
    die("Failed to retrieve Fed-KDF rank match candidates");
}
$cross_matches = $stmt->fetchAll();

// Find mismatched ranks in previously fetched data
$fed_rank_matches = rankMatch($ra_rse_matches, "R5");
$kdf_rank_matches = rankMatch($snoo_rising_matches, "R5");
$cross_rank_matches = rankMatch($cross_matches, "R5");
$total_matches = array_merge($fed_rank_matches, $kdf_rank_matches, $cross_rank_matches);

// Format retrieved sentinel data for display
$time_since_match = timeSince($last_matched);
$time_since_updated = timeSince($first_update);
$should_do = ($last_matched > $first_roster || empty($total_matches)) ? "Don't bother, it's done." : "You should probably do this.";
$oldest_roster = expandFleetName($first_roster);

// Create match table
$match_table = genMatchTable($total_matches);
?>
<html>
<head>
    <title>RJCommand: Rank Matching</title>
    <?= $head ?>
</head>
<body>
    <div class="center">
        <div class="header">
            <h2>Last done <?= $time_since_match ?> by <?= $matched_by ?></h2>
            <h3><?= $should_do ?></h3>
            <h4>Oldest roster, <?= $oldest_roster ?>, updated <?= $time_since_updated ?></h4>
            <br />
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
                        <input name="upload" type="submit" value="Upload" />
                    </div>
                </form>
            </div>
            <a href="index.php">Go Back</a>
        </div>
        <div class="content">
            <?= $match_table ?>
            <br />
            <div class="task-complete">
                <form name="match_done" action="rank_matching.php" method="post">
                    <div class="input-container submit"><input name="match_done" type="submit" value="Confirm completion" /></div>
                </form>
            </div>
        </div>
        <br />
        <a href="index.php">Go Back</a>
    </div>
</body>
</html>
