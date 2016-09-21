<?php
	require("common.php");
	require("login_protect.php");
	require("fleet_info.php");

	if (!empty($_POST)) {
		if (isset($_POST['match_done'])) {
			if (!file_put_contents("tasks/rank_match", $_SESSION['user']['username'])) {
				die("Failed to update sentinel file!");
			}
		}
	}

	$updateds = array(
		"RA" => stat("tasks/ra_update")['mtime'],
		"RSE" => stat("tasks/rse_update")['mtime'],
		"Snoo" => stat("tasks/snoo_update")['mtime'],
		"Rising" => stat("tasks/rising_update")['mtime']);
	$first_updated = min($updateds);
	$first_roster = array_search($first_updated, $updateds);

	$last_matched = stat("tasks/rank_match")['mtime'];
	$matched_by = file_get_contents("tasks/rank_match");

	$fed_query = rankMatchQueryBuilderA("ra", "rse");
	$snoo_query = rankMatchQueryBuilderA("snoo", "rising");
	$cross_query = rankMatchQueryBuilderB("ra", "rse", "snoo", "rising");

	try {
		$stmt = $db->prepare($fed_query);
		$stmt->execute();
	} catch (PDOException $ex) {
		die("Failed to retrieve Fed-Fed rank match candidates: " . $ex);
	}
	$ra_rse_matches = $stmt->fetchAll();
	try {
		$stmt = $db->prepare($snoo_query);
		$stmt->execute();
	} catch (PDOException $ex) {
		die("Failed to retrieve KDF-KDF rank match candidates");
	}
	$snoo_rising_matches = $stmt->fetchAll();

	try {
		$stmt = $db->prepare($cross_query);
		$stmt->execute();
	} catch (PDOException $ex) {
		die("Failed to retrieve Fed-KDF rank match candidates");
	}
	$cross_matches = $stmt->fetchAll();

	$fed_rank_matches = rankMatch($ra_rse_matches, "R5");
	$kdf_rank_matches = rankMatch($snoo_rising_matches, "R5");
	$cross_rank_matches = rankMatch($cross_matches, "R5");
	$total_matches = array_merge($fed_rank_matches, $kdf_rank_matches, $cross_rank_matches);
?>
<html>
<head>
	<title>RJCommand: Rank Matching</title>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
    <script src="jquery-2.1.4.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
    <script src="jquery.tablesorter.min.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
	<div class="center table-container">
		<h2>Last done by <?php echo "$matched_by " . timeSince($last_matched); ?></h2>
		<h3><?php echo ($last_matched > $first_roster || empty($total_matches)) ? "Don't bother, it's done." : "You should probably do this."; ?></h3> 
		<h4><?php echo "Oldest roster, $first_roster, updated " . timeSince($first_updated); ?></h4>
		<br />
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
		<?php genMatchTable($total_matches); ?>
		<form name="match_done" action="rank_matching.php" method="post">
			<input name="match_done" type="submit" value="I Did This" />
		</form>
		<br />
		<a href="index.php">Go Back</a>
	</div>
	<script>
	$(document).ready(function() {
		$("table").tablesorter({sortList: [[3,0], [1,0]]});
	});
	</script>
</body>
</html>