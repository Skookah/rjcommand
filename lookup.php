<?php
require("common.php");
global $ranks;
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

$caption = isset($_POST['lookupval']) ? $_POST['lookupval'] : "";
$table = <<<HTML
<table id="lookup-table" class="table table-hover table-condensed tablesorter">
    <caption><h2>$caption</h2></caption>
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
HTML;


if (isset($rows)) {
    foreach ($rows as $row) {
        $rank_id = array_search($row['rank'], $ranks[strtolower($row['fleet'])]); // ${strtolower($row['fleet']) . "_ranks"});
        $fleet = expandFleetName(htmlspecialchars($row['fleet'], ENT_QUOTES));
        $active = $row['active'] ? "In Fleet" : "Kicked";
        $toon = htmlspecialchars($row['cname'], ENT_QUOTES);
        $account = htmlspecialchars($row['account'], ENT_QUOTES);
        $rank = $rank_id . ' ' . htmlspecialchars($row['rank'], ENT_QUOTES);
        $ocomment = htmlspecialchars($row['ocomment'], ENT_QUOTES);
        $jdate = htmlspecialchars($row['jdate'], ENT_QUOTES);
        $lastonline = isset($row['lastonline']) ? htmlspecialchars($row['lastonline'], ENT_QUOTES) : "";
        $table .= <<<HTML
<tr>
    <td class="td-fleet">$fleet</td>
    <td class="td-active">$active</td>
    <td class="td-toon">$toon</td>
    <td class="td-account">$account</td>
    <td class="td-rank">$rank</td>
    <td class="td-ocomment">$ocomment</td>
    <td class="td-jdate">$jdate</td>
    <td class="td-lastonline">$lastonline</td>
</tr>
HTML;
    }
}
$table .= <<<HTML
</tbody>
</table>
HTML;
?>
<html>
<head>
    <title>RJCommand: Member Lookup</title>
    <?= $head ?>
</head>
<body>
<div class="center">
    <div class="header">
        <div class="roster-uploads">
        <form action="lookup.php" method="post">
            <div class="input-container"><input id="lookup-account" type="text" name="lookupval" /></div>
            <br />
            <div class="input-container submit"><input type="submit" value="Lookup Member" /></div>
        </form>
        </div>
        <a href="index.php">Go Back</a>
    </div>
    <div class="content">
        <?= $table ?>
    </div>
    <br />
    <a href="index.php">Go Back</a>
</div>
</body>
</html>
