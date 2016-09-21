<?php
	require("common.php");
    require("login_protect.php");
    require("fleet_info.php");
	if (!empty($_POST)) {
		if ($_POST['lookupval'][0] === "@") {

			$query = "
				SELECT cname, account, rank, ocomment, jdate, lastonline, 'RA' AS fleet, 1 AS active
				FROM roster_ra
				WHERE account=:account
				UNION ALL
				SELECT cname, account, rank, ocomment, jdate, lastonline, 'RSE' AS fleet, 1 AS active
				FROM roster_rse
				WHERE account=:account
				UNION ALL
				SELECT cname, account, rank, ocomment, jdate, lastonline, 'Snoo' AS fleet, 1 AS active
				FROM roster_snoo
				WHERE account=:account
				UNION ALL
				SELECT cname, account, rank, ocomment, jdate, lastonline, 'Rising' AS fleet, 1 AS active
				FROM roster_rising
				WHERE account=:account
				UNION ALL
				SELECT cname, account, rank, ocomment, jdate, kdate AS lastonline, fleet, 0 AS active
				FROM old_members
				WHERE account=:account
			;";
			$query_params = array(':account' => $_POST['lookupval']);
		} else {
			$query = "
				SELECT cname, account, rank, ocomment, jdate, lastonline, 'RA' AS fleet, 1 AS active
				FROM roster_ra
				WHERE cname=:cname
				UNION ALL
				SELECT cname, account, rank, ocomment, jdate, lastonline, 'RSE' AS fleet, 1 AS active
				FROM roster_rse
				WHERE cname=:cname
				UNION ALL
				SELECT cname, account, rank, ocomment, jdate, lastonline, 'Snoo' AS fleet, 1 AS active
				FROM roster_snoo
				WHERE cname=:cname
				UNION ALL
				SELECT cname, account, rank, ocomment, jdate, lastonline, 'Rising' AS fleet, 1 AS active
				FROM roster_rising
				WHERE cname=:cname
				UNION ALL
				SELECT cname, account, rank, ocomment, jdate, kdate AS lastonline, fleet, 0 AS active
				FROM old_members
				WHERE cname=:cname
			;";
			$query_params = array(':cname' => $_POST['lookupval']);
		}
		try {
			$stmt = $db->prepare($query);
			$result = $stmt->execute($query_params);
		} catch (PDOException $ex) {
			die("Failed to retrieve character records: " . var_dump($ex));
		}
		$rows = $stmt->fetchAll();

	}
?>
<html>
<head>
	<title>RJCommand: Member Lookup</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
    <script src="jquery-2.1.4.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
    <script src="jquery.tablesorter.min.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="center table-container"><div class="inner">
<form action="lookup.php" method="post">
	<input id="lookup-account" type="text" name="lookupval" />
	<br />
	<input type="submit" value="Lookup Member" />
</form>
<br /><br />
<table id="members" class="table table-hover table-condensed tablesorter">
	<caption><?php if (isset($_POST['lookupval'])) { echo $_POST['lookupval']; }?> </caption>
	<thead>
	<tr>
		<th class="th-fleet">Fleet</th>
		<th class="th-active">Status</th>
		<th class="th-toon">Character Name</th>
		<th class="th-account">Account</th>
		<th class="th-rank">Fleet Rank</th>
		<th class="th-ocomment">Officer Comment</th>
		<th class="th-jdate">Join Date</th>
		<th class="th-lastonline">Last Online / Kicked Date</th>
	</tr>
	</thead>
	<tbody>
	<?php  if (isset($rows)): foreach($rows as $row): ?>
		<?php
			$rankID = array_search($row['rank'], ${strtolower($row['fleet']) . "_ranks"});
		?>
		<tr>
			<td class="td-fleet"><?php echo expandFleetName(htmlentities($row['fleet'], ENT_QUOTES, 'UTF-8')); ?></td>
			<td class="td-active"><?php echo ($row['active']) ? "In Fleet" : "Kicked"; ?></td>
			<td class="td-toon"><?php echo htmlentities($row['cname'], ENT_QUOTES, 'UTF-8'); ?></td>
			<td class="td-account"><?php echo htmlentities($row['account'], ENT_QUOTES, 'UTF-8'); ?></td>
			<td class="td-rank"><?php echo $rankID . ' ' . htmlentities($row['rank'], ENT_QUOTES, 'UTF-8'); ?></td>
			<td class="td-ocomment"><?php echo htmlentities($row['ocomment'], ENT_QUOTES, 'UTF-8'); ?></td>
			<td class="td-jdate"><?php echo htmlentities($row['jdate'], ENT_QUOTES, 'UTF-8'); ?></td>
			<td class="td-lastonline">
			<?php 
				if (isset($row['lastonline'])) {
					echo htmlentities($row['lastonline'], ENT_QUOTES, 'UTF-8');
				} 
			?>
			</td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	</table>
<br /><br />
<a href="index.php">Go Back</a>
</div></div>
<script>
	$(document).ready(function() {
		$('#members').tablesorter({sortList: [[1, 0], [0, 0]]});
	});
</script>
</body>
</html>