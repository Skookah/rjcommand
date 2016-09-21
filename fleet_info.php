<?php
// Grab the last time the sentinel update file was modified -
// only ever touched during a roster import
$last_updated = stat("update_file")['mtime'];

$kick_criteria = array(
    "R1" => 14,
    "R2" => 14,
    "R3" => 21,
    "R4" => 28
);
$promo_criteria_time = array(
    "R1" => 14,
    "R2" => 90
);
$promo_criteria_contribs = array(
    "R1" => 20000,
    "R2" => 45000
);

$ra_ranks = array(
    "R1" => "Cadet",
    "R2" => "Ensign",
    "R3" => "Lieutenant",
    "R4" => "Commander",
    "R5" => "Captain",
    "R6" => "Admiral",
    "R7" => "Fleet Admiral"
);
$ra_promo_criteria_contribs = $promo_criteria_contribs; //array(
//		"R1" => 20000,
//		"R2" => 45000
//		);
$ra_promo_criteria_time = $promo_criteria_time; //array(
//		"R1" => 14,
//		"R2" => 90
//		);
$ra_kick_criteria = $kick_criteria; /*= array( //
		"R1" => 15,
		"R2" => 15,
		"R3" => 30,
		"R4" => 30
		);*/

$rse_ranks = array(
    "R1" => "Citizen",
    "R2" => "Uhlan",
    "R3" => "Lieutenant",
    "R4" => "Subcommander",
    "R5" => "Subadmiral",
    "R6" => "Senator",
    "R7" => "Praetor"
);
$rse_promo_criteria_contribs = $promo_criteria_contribs; //array(
//		"R1" => 20000,
//		"R2" => 45000
//		);
$rse_promo_criteria_time = $promo_criteria_time; // array(
//		"R1" => 14,
//		"R2" => 90
//		);
$rse_kick_criteria = $kick_criteria;/*array(
		"R1" => 15,
		"R2" => 15,
		"R3" => 30,
		"R4" => 30
		);*/

$snoo_ranks = array(
    "R1" => "Bekk",
    "R2" => "Warrior",
    "R3" => "Sergeant",
    "R4" => "Snoo",
    "R5" => "Snoogin",
    "R6" => "Major Snoo",
    "R7" => "Supreme Snoo"
);
$snoo_promo_criteria_contribs = $promo_criteria_contribs; // array(
//		"R1" => 20000,
//		"R2" => 50000,
//		"R3" => 100000
//		);
$snoo_promo_criteria_time = $promo_criteria_time; //array(
//		"R1" => 14,
//		"R2" => 30,
//		"R3" => 90
//		);
$snoo_kick_criteria = $kick_criteria; //array(
//		"R1" => 30,
//		"R2" => 30,
//		"R3" => 30,
//		"R4" => 30
//		);

$rising_ranks = array(
    "R1" => "Bekk",
    "R2" => "Warrior",
    "R3" => "Sergeant",
    "R4" => "Snoo",
    "R5" => "Snoogin",
    "R6" => "Major Snoo",
    "R7" => "Supreme Snoo"
);
$rising_promo_criteria_contribs = $promo_criteria_contribs;//array(
//		"R1" => 20000,
//		"R2" => 50000,
//		"R3" => 100000
//		);
$rising_promo_criteria_time = $promo_criteria_time; //array(
//		"R1" => 14,
//		"R2" => 30,
//		"R3" => 90
//		);
$rising_kick_criteria = $kick_criteria; //array(
//		"R1" => 30,
//		"R2" => 30,
//		"R3" => 90,
//		"R4" => 90
//		);

function startsWith($haystack, $needle)
{
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== False;
}

/**
 * Build a fleet rank promotion SQL query, given a short fleet name and rank ID.
 * @param  string $fleetAbbrev Fleet name identifier, i.e. RA for REDdit ALERT
 * @param  string $rankID Rank identifier of the form "R#", as "R3" for the 3rd rank
 * @return string              (Hopefully) valid SQL query to return members of the rank up for promo
 */
function promoQueryBuilder($fleetAbbrev, $rankID)
{
    global $ra_ranks, $ra_promo_criteria_contribs, $ra_promo_criteria_time,
           $rse_ranks, $rse_promo_criteria_contribs, $rse_promo_criteria_time,
           $snoo_ranks, $snoo_promo_criteria_contribs, $snoo_promo_criteria_time,
           $rising_ranks, $rising_promo_criteria_contribs, $rising_promo_criteria_time;
    return "SELECT SQL_NO_CACHE cname, account, ocomment, contribs, jdate, rank
			FROM roster_{$fleetAbbrev}
			WHERE
				rank = '{${$fleetAbbrev . '_ranks'}[$rankID]}'
				AND contribs >= {${$fleetAbbrev . '_promo_criteria_contribs'}[$rankID]}
				AND jdate < NOW() - INTERVAL {${$fleetAbbrev . '_promo_criteria_time'}[$rankID]} DAY
				;";
}

/**
 * Build a fleet rank kick SQL query, given a short fleet name and rank ID.
 * @param  string $fleetAbbrev Fleet name identifier, i.e. Rising for House of the Rising Snoo
 * @param  string $rankID Rank identifier of the form "R#", as "R6" for the 6th rank
 * @return string              (Hopefully) valid SQL query to return members of the given rank up for promo
 */
function kickQueryBuilder($fleetAbbrev, $rankID)
{
    global $ra_ranks, $ra_kick_criteria, $rse_ranks, $rse_kick_criteria,
           $snoo_ranks, $snoo_kick_criteria, $rising_ranks, $rising_kick_criteria;
    return "SELECT SQL_NO_CACHE cname, account, rank, mcomment, ocomment, lastonline, rank, jdate, mcommentdate, ocommentdate, ocommentauth
			FROM roster_{$fleetAbbrev}
			WHERE
				rank = '{${$fleetAbbrev . '_ranks'}[$rankID]}'
				AND lastonline < NOW() - INTERVAL {${$fleetAbbrev . '_kick_criteria'}[$rankID]} DAY
				;";
}

/**
 * Build a query to return all fleet members across two fleets with matching accounts.
 * @param  string $l_fleet Short name for the "left fleet"
 * @param  string $r_fleet Short name for the "right fleet"
 * @return string          MySQL query to catch all same-account rows
 */
function rankMatchQueryBuilderA($l_fleet, $r_fleet)
{
    $roster_l = 'roster_' . $l_fleet;
    $roster_r = 'roster_' . $r_fleet;
    return "SELECT SQL_NO_CACHE
				{$roster_l}.cname AS l_name, {$roster_l}.account AS l_account, {$roster_l}.rank AS l_rank, '$l_fleet' AS l_fleet,
				{$roster_r}.cname AS r_name, {$roster_r}.account AS r_account, {$roster_r}.rank AS r_rank, '$r_fleet' AS r_fleet
				FROM {$roster_l}, {$roster_r}
				WHERE ({$roster_l}.account = {$roster_r}.account)
				;";
}

/**
 * Build a query to return all fleet members across two sets of two fleets with matching accoungs
 * @param  string $f_l_fleet Fed fleet on the left
 * @param  string $f_r_fleet Fed fleet on the right
 * @param  string $k_l_fleet KDF fleet on the left
 * @param  string $k_r_fleet KDF fleet on the right
 * @return string            Hopefully valid MySQL query to find that which is described
 */
function rankMatchQueryBuilderB($f_l_fleet, $f_r_fleet, $k_l_fleet, $k_r_fleet)
{
    $roster_f_l = 'roster_' . $f_l_fleet;
    $roster_f_r = 'roster_' . $f_r_fleet;
    $roster_k_l = 'roster_' . $k_l_fleet;
    $roster_k_r = 'roster_' . $k_r_fleet;
    return "SELECT SQL_NO_CACHE
					roster_l.cname AS l_name, roster_l.account AS l_account, roster_l.rank AS l_rank, roster_l.fleet AS l_fleet,
					roster_r.cname AS r_name, roster_r.account AS r_account, roster_r.rank AS r_rank, roster_r.fleet AS r_fleet
				FROM (
						SELECT cname, account, rank, '$f_l_fleet' AS fleet FROM $roster_f_l
						UNION ALL
						SELECT cname, account, rank, '$f_r_fleet' AS fleet FROM $roster_f_r
					) AS roster_l,
					(
						SELECT cname, account, rank, '$k_l_fleet' AS fleet FROM $roster_k_l
						UNION ALL
						SELECT cname, account, rank, '$k_r_fleet' AS fleet FROM $roster_k_r
					) AS roster_r
				WHERE (roster_l.account = roster_r.account);";
}

/**
 * Build a query to find all members in the specified fleet with more than 1 toon who do not have a toon of rank
 * 4 or above.
 * @param    string $fleetAbbrev Fleet (short version) to search
 * @return    string                A valid MySQL query to retrieve illegal altage
 */
function illegalAltsQueryBuilder($fleetAbbrev)
{
    global $ra_ranks, $rse_ranks, $snoo_ranks, $rising_ranks;
    return "SELECT SQL_NO_CACHE
  cname,
  account,
  rank,
 '{$fleetAbbrev}' AS fleet
FROM roster_{$fleetAbbrev}
WHERE account IN (
  SELECT account
  FROM (
         SELECT
           account,
           COUNT(*) AS count
         FROM roster_{$fleetAbbrev}
         GROUP BY account
         HAVING count > 1
         ORDER BY count DESC) AS t)
  AND account NOT IN (
  SELECT account
  FROM roster_{$fleetAbbrev}
  WHERE rank IN ('{${$fleetAbbrev . '_ranks'}['R4']}', '{${$fleetAbbrev . '_ranks'}['R5']}', '{${$fleetAbbrev . '_ranks'}['R6']}', '{${$fleetAbbrev . '_ranks'}['R7']}')
);";
}

/**
 * Expand a shortened fleet name (abbrev. or other) into the display version.
 * @param  string $fleetShort Shortened fleet name
 * @return string             Full fleet name
 */
function expandFleetName($fleetShort)
{
    $fleetName = null;
    switch ($fleetShort) {
        case "ra":
        case "RA":
            $fleetName = "REDdit ALERT";
            break;
        case "rse":
        case "RSE":
            $fleetName = "Reddit Star Empire";
            break;
        case "snoo":
        case "Snoo":
        case "hos":
        case "HoS":
            $fleetName = "House of Snoo";
            break;
        case "rs":
        case "RS":
        case "rising":
        case "Rising":
            $fleetName = "House of the Rising Snoo";
            break;
    }
    return $fleetName;
}

/**
 * Generates an HTML table out of a MySql query result of illegal alts.
 * @param   array   $rows   The results of the query
 * @return  string          HTML table containing query results
 */
function genAltTable($rows) {
    global $ra_ranks, $rse_ranks, $snoo_ranks, $rising_ranks;
    $table = <<<HTML
<table id="match-table" class="table table-hover table-condensed tablesorter">
<caption><h2>Illegal Alts</h2></caption>
<thead>
<tr>
<th class="th-rank">Character Rank</th>
<th class="th-toon">Character Name</th>
<th class="th-account">Account</th>
<th class="th-fleet">Fleet</th>
</tr>
</thead>
<tbody>
HTML;

    foreach($rows as $row) {
        $table .= <<<HTML
<tr>
<td class="td-rank">{$row['rank']}</td>
<td class="td-toon">{$row['cname']}</td>
<td class="td-account">{$row['account']}</td>
<td class="td-fleet">{$row['fleet']}</td>
</tr>
HTML;

    }
    $table .= <<<HTML
</tbody>
</table>
HTML;
    return $table;
}

/**
 * Generate an HTML table of members up for promotion.
 * @param  string $fleet Short version of the fleet to look at
 * @param  array $promo_list Array of members up for promotion, from SQL
 * @param  array $ranks Array of fleet ranks
 * @param  int $last_done Unix timestamp when it was last one
 * @param  string $last_done_by Who last did this
 * @param  int $roster_updated Unix timestamp of when the last roster was imported
 */
function genPromoTable($fleet, $promo_list, $ranks, $last_done, $last_done_by, $roster_updated)
{
    $table_cap = expandFleetName($fleet) . " Promotions";
    $table_id = $fleet . "-promo";
    $table_class = "table table-hover table-condensed tablesorter";
    $last_did = timeSince($last_done);
    $should_do = ($last_done > $roster_updated || empty($promo_list)) ? "Don't bother, it's done." : "You should probably do this.";

    echo "<table id=\"$table_id\" class=\"$table_class\">";
    echo "<caption><h2>$table_cap</h2><h3>Last done by $last_done_by $last_did</h3><h4>$should_do</h4></caption>";
    echo "<thead>";
    echo "<th class=\"th-rank\">Current Rank</th>";
    echo "<th class=\"th-toon\">Character Name</th>";
    echo "<th class=\"th-account\">Account</th>";
    echo "<th class=\"th-ocomment\">Officer Comment</th>";
    echo "<th class=\"th-contribs\">Contributions</th>";
    echo "<th class=\"th-jdate\">Join Date</th>";
    echo "</thead>";
    echo "<tbody>";

    foreach ($promo_list as $row) {
        echo "<tr>";
        echo "<td class=\"td-rank\">" . array_search($row['rank'], $ranks) . ' ' . htmlentities($row['rank'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td class=\"td-toon\">" . htmlentities($row['cname'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td class=\"td-account\">" . htmlentities($row['account'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td class=\"td-ocomment\">" . htmlentities($row['ocomment'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td class=\"td-contribs\">" . number_format(htmlentities($row['contribs'], ENT_QUOTES, 'UTF-8')) . "</td>";
        echo "<td class=\"td-jdate\">" . htmlentities($row['jdate'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
}

/**
 * Generate an HTML table of members up for inactivity kicks.
 * @param  string $fleet Short version of the fleet to look at
 * @param  array $kick_list Array of members up for kicks, from SQL
 * @param  array $ranks Array of fleet ranks
 * @param  int $last_done Unix timestamp when it was last one
 * @param  string $last_done_by Who last did this
 * @param  int $roster_updated Unix timestamp of when the last roster was imported
 */
function genKickTable($fleet, $kick_list, $ranks, $last_done, $last_done_by, $roster_updated)
{
    $table_cap = expandFleetName($fleet) . " Kicks";
    $table_id = $fleet . "-kicks";
    $table_class = "table table-hover table-condensed tablesorter";
    $last_did = timeSince($last_done);
    $should_do = ($last_done > $roster_updated || empty($kick_list)) ? "Don't bother, it's done." : "You should probably do this.";

    echo "<table id=\"$table_id\" class=\"$table_class\">";
    echo "<caption><h2>$table_cap</h2><h3>Last done by $last_done_by $last_did</h3><h4>$should_do</h4></caption>";
    echo "<thead>";
    echo "<th class=\"th-rank\">Current Rank</th>";
    echo "<th class=\"th-toon\">Character Name</th>";
    echo "<th class=\"th-account\">Account</th>";
    echo "<th class=\"th-mcomment\">Member Comment</th>";
    echo "<th class=\"th-ocomment\">Officer Comment</th>";
    echo "<th class=\"th-lastonline\">Last Online</th>";
    echo "</thead>";

    echo "<tbody>";
    foreach ($kick_list as $row) {
        echo "<tr>";
        echo "<td class=\"td-rank\">" . array_search($row['rank'], $ranks) . ' ' . htmlentities($row['rank'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td class=\"td-toon\">" . htmlentities($row['cname'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td class=\"td-account\">" . htmlentities($row['account'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td class=\"td-mcomment\">" . htmlentities($row['mcomment'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td class=\"td-ocomment\">" . htmlentities($row['ocomment'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td class=\"td-lastonline\">" . htmlentities($row['lastonline'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
}

function genMatchTable($matches)
{
    global $ra_ranks, $rse_ranks, $snoo_ranks, $rising_ranks;
    echo "<table id=\"match-table\" class=\"table table-hover table-condensed tablesorter\">";
    echo "<caption><h2>Rank Matching Needed</h2></caption>";
    echo "</form>";
    echo "<thead>";
    echo "<tr>";
    echo "<th class=\"th-rank\">Current Rank</th>";
    echo "<th class=\"th-toon\">Character Name</th>";
    echo "<th class=\"th-account\">Account</th>";
    echo "<th class=\"th-fleet\">Fleet</th>";
    echo "<th class=\"th-spacer\"></th>";
    echo "<th class=\"th-rank\">Current Rank</th>";
    echo "<th class=\"th-toon\">Character Name</th>";
    echo "<th class=\"th-account\">Account</th>";
    echo "<th class=\"th-fleet\">Fleet</th>";
    echo "</tr></thead>";

    echo "<tbody>";
    $toons = array();
    foreach ($matches as $row) {
        $l_ranks = $row['l_fleet'] . "_ranks";
        $r_ranks = $row['r_fleet'] . "_ranks";
        if (array_search($row['l_rank'], ${$l_ranks}) > array_search($row['r_rank'], ${$r_ranks})) {
            $temp = array();
            foreach ($row as $key => &$val) {
                if (startsWith($key, 'l_')) {
                    $temp[$key] = $val;
                    $val = $row['r_' . substr($key, 2)];
                } else if (startsWith($key, 'r_')) {
                    // We can assume at this point that $temp is set, as l_ values always come before r_
                    $val = $temp['l_' . substr($key, 2)];
                }
            }
            // Get rid of this reference before it all goes to pot
            unset($val);
            // Need to reset variables as we've shuffled r_fleet and l_fleet around
            $l_ranks = $row['l_fleet'] . "_ranks";
            $r_ranks = $row['r_fleet'] . "_ranks";
        }
        if (in_array($row['l_name'], $toons)) {
            continue;
        }
        $toons[] = $row['l_name'];
        $match_to = "R0";

        $match_to = array_search($row['r_rank'], ${$r_ranks});

        $match_name = ${$l_ranks}[$match_to];
        echo "<tr>";
        echo "<td class=\"td-rank\">" . array_search($row['l_rank'], ${$l_ranks}) . " " . htmlentities($row['l_rank'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td class=\"td-toon\">" . htmlentities($row['l_name'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td class=\"td-account\">" . htmlentities($row['l_account'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td class=\"td-fleet\">" . htmlentities(expandFleetName($row['l_fleet']), ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td class=\"td-spacer\">...match to $match_to: $match_name from...</td>";
        echo "<td class=\"td-rank\">" . array_search($row['r_rank'], ${$r_ranks}) . " " . htmlentities($row['r_rank'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td class=\"td-toon\">" . htmlentities($row['r_name'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td class=\"td-account\">" . htmlentities($row['r_account'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td class=\"td-fleet\">" . htmlentities(expandFleetName($row['r_fleet']), ENT_QUOTES, 'UTF-8') . "</td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
}

/**
 * Format the time elapsed since a Unix timestamp for human reading.
 * @param  int $time_stamp Time since epoch, in seconds
 * @return string          Time elapsed, in useful values that are easy to read
 */
function timeSince($time_stamp)
{
    $difference = time() - $time_stamp;
    $bits = array(
        ' year' => $difference / 31556926 % 12,
        ' week' => $difference / 604800 % 52,
        ' day' => $difference / 86400 % 7,
        ' hour' => $difference / 3600 % 24,
        ' minute' => $difference / 60 % 60,
        ' second' => $difference % 60
    );

    foreach ($bits as $name => $val) {
        if ($val > 1) $return_val[] = $val . $name . 's';
        else if ($val == 1) $return_val[] = $val . $name;
    }
    if (!empty($return_val)) {
        if (count($return_val) > 1) array_splice($return_val, count($return_val) - 1, 0, 'and');
        $return_val[] = 'ago.';
        return join(' ', $return_val);
    } else {
        return "basically right now.";
    }
}

/**
 * Searches a list of SQL same-account matches for mismatched ranks (up to a maximum)
 * @param  array $matches Entries returned by rankMatchQueryBuilder()
 * @param  string $max_rank Maximum rank to match to (of form "R2")
 * @param  string $l_fleet Shorthand of "left" fleet name
 * @param  string $r_fleet Shorthand of "right" fleet name
 * @return array            Entries in $matches that have mismatched ranks
 */
// function rankMatch($matches, $max_rank, $l_fleet, $r_fleet) {
// 	global $ra_ranks, $rse_ranks, $snoo_ranks, $rising_ranks;
// 	$results = array();
// 	foreach ($matches as $match) {
// 		$l_rank = array_search($match['l_rank'], ${$l_fleet . '_ranks'});
// 		$r_rank = array_search($match['r_rank'], ${$r_fleet . '_ranks'});
// 		if (strcmp($l_rank, $r_rank) != 0 && (strcmp($l_rank, $max_rank) <= 0 || strcmp($r_rank, $max_rank) <= 0)) {
// 			$results[] = $match;
// 		}
// 	}
// 	return $results;
// }
function rankMatch($matches, $max_rank)
{
    global $ra_ranks, $rse_ranks, $snoo_ranks, $rising_ranks;
    $results = array();
    foreach ($matches as $match) {
        $l_rank = array_search($match['l_rank'], ${$match['l_fleet'] . '_ranks'});
        $r_rank = array_search($match['r_rank'], ${$match['r_fleet'] . '_ranks'});
        if (strcmp($l_rank, $r_rank) != 0 && (strcmp($l_rank, $max_rank) < 0 || strcmp($r_rank, $max_rank) < 0)) {
            $results[] = $match;
        }
    }
    return $results;
}