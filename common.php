<?php
// Shared DB values
$username = "";
$password = "";
$host = "localhost";
$dbname = "rjc";
$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");

// Open DB connection
try {
    $db = new PDO("mysql:host={$host}; dbname={$dbname}; charset=utf8", $username, $password, $options);
} catch (PDOException $ex) {
    die("Failed to open database connection!" . var_dump($ex));
}

$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Tell the browser to use UTF-8
header('Content-Type: text/html; charset=utf-8');
// Start a session to store visitor info from one visit to another. Like a server-side cookie, but also cookies.
session_start();

$valid_uris = array('/login.php', '/forgot.php', '/reset.php');

// Ensure the user is logged in, but prevent a massive redirect loop on every single page
if (!in_array($_SERVER['REQUEST_URI'], $valid_uris) && empty($_SESSION['user'])) {
    $_SESSION['redirect'] = urlencode(trim($_SERVER['REQUEST_URI'], '/'));
    header("Location: login.php");
    die("Redirecting to login.php...");
}

// Shared stylesheet, scripts
$head = <<<HTML
<link rel="stylesheet" href="css/style.css">
<script src="jquery.tablesorter.min.js"></script>
HTML;

// Master list of fleets (short names)
$fleets = [
    "ra",
    "rse",
    "snoo",
    "rising",
    "mirror"
];

// Fed fleets (for styling)
$fed = [
    "ra",
    "rse",
    "mirror"
];

// KDF fleets (for styling)
$kdf = [
    "snoo",
    "rising"
];

// Time critera for members to be kicked, by rank & in days
$kick_criteria = array(
    "R1" => 14,
    "R2" => 14,
    "R3" => 21,
    "R4" => 28
);
// Promotion criteria, broken into time (days) and contributions, by rank
$promo_criteria = array(
    "time" => array(
        "R1" => 14,
        "R2" => 90
    ),
    "contribs" => array(
        "R1" => 20000,
        "R2" => 45000
    )
);

// Master assoc. list of ranks, by fleet. Used to translate R#-style ranks into meaningful names, and vice-versa
$ranks = array(
    "ra" => array(
        "R1" => "Cadet",
        "R2" => "Ensign",
        "R3" => "Lieutenant",
        "R4" => "Commander",
        "R5" => "Captain",
        "R6" => "Admiral",
        "R7" => "Fleet Admiral"
    ),
    "rse" => array(
        "R1" => "Citizen",
        "R2" => "Uhlan",
        "R3" => "Lieutenant",
        "R4" => "Subcommander",
        "R5" => "Subadmiral",
        "R6" => "Senator",
        "R7" => "Praetor"
    ),
    "snoo" => array(
        "R1" => "Bekk",
        "R2" => "Warrior",
        "R3" => "Sergeant",
        "R4" => "Snoo",
        "R5" => "Snoogin",
        "R6" => "Major Snoo",
        "R7" => "Supreme Snoo"
    ),
    "rising" => array(
        "R1" => "Bekk",
        "R2" => "Warrior",
        "R3" => "Sergeant",
        "R4" => "Snoo",
        "R5" => "Snoogin",
        "R6" => "Major Snoo",
        "R7" => "Supreme Snoo"
    ),
    "mirror" => array(
        "R1" => "Cadet",
        "R2" => "Ensign",
        "R3" => "Lieutenant",
        "R4" => "Commander",
        "R5" => "Captain",
        "R6" => "Admiral",
        "R7" => "Fleet Admiral"
    )
);

// Tiny function wrapper to make typing easier
function hsc($var) {
    return htmlspecialchars($var, ENT_QUOTES);
}

/**
 * Determine if a string starts with a substring
 * @param $haystack String to test in
 * @param $needle   String to test for
 * @return bool     If the $haystack starts with $needle
 */
function startsWith($haystack, $needle)
{
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== False;
}

/**
 * Build a query to return toons that are eligible for a promotion.
 * @param   string $fleetAbbrev Short name for the fleet in question
 * @param   string $rankID Rank identifier (R#)
 * @return  string                  MySQL query to find eligible toons
 */
function promoQueryBuilder($fleetAbbrev, $rankID)
{
    global $ranks, $promo_criteria;
    return "SELECT SQL_NO_CACHE cname, account, ocomment, contribs, jdate, rank
        FROM roster_{$fleetAbbrev}
        WHERE
          rank = '{$ranks[$fleetAbbrev][$rankID]}'
          AND contribs >= {$promo_criteria["contribs"][$rankID]}
          AND jdate < NOW() - INTERVAL {$promo_criteria["time"][$rankID]} DAY
        ;";
}

/**
 * Build a query to find toons who need to be kicked for inactivity.
 * @param   string $fleetAbbrev Short name for the fleet in question
 * @param   string $rankID Rank identifier (R#)
 * @return  string                  MySQL query to find eligible toons
 */
function kickQueryBuilder($fleetAbbrev, $rankID)
{
    global $ranks, $kick_criteria;
    return "SELECT SQL_NO_CACHE cname, account, rank, mcomment, ocomment, lastonline, rank, jdate, mcommentdate, ocommentdate, ocommentauth
        FROM roster_{$fleetAbbrev}
        WHERE
          rank = '{$ranks[$fleetAbbrev][$rankID]}'
          AND lastonline < NOW() - INTERVAL {$kick_criteria[$rankID]} DAY
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
    global $ranks;
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
  WHERE rank IN ('{$ranks[$fleetAbbrev]['R4']}', '{$ranks[$fleetAbbrev]['R5']}', '{$ranks[$fleetAbbrev]['R6']}', '{$ranks[$fleetAbbrev]['R7']}')
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
        case "mirror":
        case "mr":
        case "MR":
        case "Mirror":
            $fleetName = "Mirror Reddit";
            break;
    }
    return $fleetName;
}

/**
 * Generates an HTML table out of a MySql query result of illegal alts.
 * @param   array $rows The results of the query
 * @return  string          HTML table containing query results
 */
function genAltTable($rows)
{
    global $ranks;
    $table = <<<HTML
<table id="match-table" class="table table-hover table-condensed tablesorter">
<caption><h2>Illegal Alts</h2><h5></h5></caption>
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

    foreach ($rows as $row) {
        $fleet =    expandFleetName($row['fleet']);
        $table .= <<<HTML
<tr>
<td class="td-rank">{$row['rank']}</td>
<td class="td-toon">{$row['cname']}</td>
<td class="td-account">{$row['account']}</td>
<td class="td-fleet">$fleet</td>
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
 * @param   string $fleet Short version of the fleet to look at
 * @param   array $promo_list Array of members up for promotion, from SQL
 * @param   int $last_done Unix timestamp when it was last one
 * @param   string $last_done_by Who last did this
 * @param   int $roster_updated Unix timestamp of when the last roster was imported
 * @return  string  HTML table containing members up for promo
 */
function genPromoTable($fleet, $promo_list, $last_done, $last_done_by, $roster_updated)
{
    global $ranks, $fed;
    $table_cap = expandFleetName($fleet) . " Promotions";
    $table_id = $fleet . "-promo";
    $table_class = "table table-hover table-condensed tablesorter";
    if (in_array($fleet, $fed)) {
        $table_class .= " fed";
    } else {
        $table_class .= " kdf";
    }
    $last_did = timeSince($last_done);
    $should_do = ($last_done > $roster_updated || empty($promo_list)) ? "Don't bother, it's done." : "You should probably do this.";

    $table = <<<HTML
<table id="$table_id" class="$table_class">
<caption><h2>$table_cap</h2><h3>Last done by $last_done_by $last_did</h3><h4>$should_do</h4></caption>
<thead>
<th class="th-rank">Current Rank</th>
<th class="th-toon">Character Name</th>
<th class="th-account">Account</th>
<th class="th-ocomment">Officer Comment</th>
<th class="th-contribs">Contributions</th>
<th class="th-jdate">Join Date</th>
</thead>
<tbody>
HTML;
    foreach ($promo_list as $row) {
        $rank = array_search($row['rank'], $ranks[$fleet]) . ' ' . htmlspecialchars($row['rank'], ENT_QUOTES);
        $toon = htmlspecialchars($row['cname'], ENT_QUOTES);
        $account = htmlspecialchars($row['account'], ENT_QUOTES);
        $ocomment = htmlspecialchars($row['ocomment'], ENT_QUOTES);
        $contribs = number_format(htmlspecialchars($row['contribs'], ENT_QUOTES));
        $jdate = htmlspecialchars($row['jdate'], ENT_QUOTES);
        $table .= <<<HTML
<tr>
<td class="td-rank">$rank</td>
<td class="td-toon">$toon</td>
<td class="td-account">$account</td>
<td class="td-ocomment">$ocomment</td>
<td class="td-contribs">$contribs </td>
<td class="td-jdate">$jdate</td>
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
 * Generate an HTML table of members up for inactivity kicks.
 * @param  string $fleet Short version of the fleet to look at
 * @param  array $kick_list Array of members up for kicks, from SQL
 * @param  int $last_done Unix timestamp when it was last one
 * @param  string $last_done_by Who last did this
 * @param  int $roster_updated Unix timestamp of when the last roster was imported
 * @return string   HTML table containing members to be kicked for inactivity
 */
function genKickTable($fleet, $kick_list, $last_done, $last_done_by, $roster_updated)
{
    global $ranks, $fed;
    $table_cap = expandFleetName($fleet) . " Kicks";
    $table_id = $fleet . "-kicks";
    $table_class = "table table-hover table-condensed tablesorter";
    if (in_array($fleet, $fed)) {
        $table_class .= " fed";
    } else {
        $table_class .= " kdf";
    }
    $last_did = timeSince($last_done);
    $should_do = ($last_done > $roster_updated || empty($kick_list)) ? "Don't bother, it's done." : "You should probably do this.";

    $table = <<<HTML
<table id="$table_id" class="$table_class">
<caption><h2>$table_cap</h2><h3>Last done by $last_done_by $last_did</h3><h4>$should_do</h4></caption>
<thead>
<th class="th-rank">Current Rank</th>
<th class="th-toon">Character Name</th>
<th class="th-account">Account</th>
<th class="th-mcomment">Member Comment</th>
<th class="th-ocomment">Officer Comment</th>
<th class="th-lastonline">Last Online</th>
</thead>
<tbody>
HTML;

    foreach ($kick_list as $row) {
        $rank = array_search($row['rank'], $ranks[$fleet]) . ' ' . htmlspecialchars($row['rank'], ENT_QUOTES);
        $toon = htmlspecialchars($row['cname'], ENT_QUOTES);
        $account = htmlspecialchars($row['account'], ENT_QUOTES);
        $mcomment = htmlspecialchars($row['mcomment'], ENT_QUOTES);
        $ocomment = htmlspecialchars($row['ocomment'], ENT_QUOTES);
        $lastonline = htmlspecialchars($row['lastonline'], ENT_QUOTES);
        $table .= <<<HTML
<tr>
<td class="td-rank">$rank</td>
<td class="td-toon">$toon</td>
<td class="td-account">$account</td>
<td class="td-mcomment">$mcomment</td>
<td class="td-ocomment">$ocomment</td>
<td class="td-lastonline">$lastonline</td>
</tr>
HTML;
    }

    $table .= <<<HTML
</thead>
</table>
HTML;
    return $table;
}

/** Generate an HTML table of rank matches pending
 * @param array $matches List of rank matches needed
 * @return string Full HTML table of pending rank matches
 */
function genMatchTable($matches)
{
    global $ranks;
    $table = <<<HTML
<table id="match-table" class="table table-hover table-condensed tablesorter">
<caption><h2>Rank Matching Needed</h2></caption>
<thead>
<th class="th-rank">Current Rank</th>
<th class="th-toon">Character Name</th>
<th class="th-account">Account</th>
<th class="th-fleet">Fleet</th>
<th class="th-spacer"></th>
<th class="th-rank">Current Rank</th>
<th class="th-toon">Character Name</th>
<th class="th-account">Account</th>
<th class="th-fleet">Fleet</th>
</thead>
<tbody>
HTML;
    $toons = array();
    foreach ($matches as $row) {

        if (array_search($row['l_rank'], $ranks[$row['l_fleet']]) > array_search($row['r_rank'], $ranks[$row['r_fleet']])) {
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
        }
        if (in_array($row['l_name'], $toons)) {
            continue;
        }
        $toons[] = $row['l_name'];
        $match_to = "R0";

        $match_to = array_search($row['r_rank'], $ranks[$row['r_fleet']]);

        $match_name = $ranks[$row['l_fleet']][$match_to];

        $l_rank = array_search($row['l_rank'], $ranks[$row['l_fleet']]) . " " . hsc($row['l_rank']);
        $l_toon = hsc($row['l_name']);
        $l_account = hsc($row['l_account']);
        $l_fleet = hsc(expandFleetName($row['l_fleet']));
        $spacer = "...match to $match_to: $match_name from...";
        $r_rank = array_search($row['r_rank'], $ranks[$row['r_fleet']]) . " " . hsc($row['r_rank']);
        $r_toon = hsc($row['r_name']);
        $r_account = hsc($row['r_account']);
        $r_fleet = hsc(expandFleetName($row['r_fleet']));

        $table .= <<<HTML
<tr>
<td class="td-rank">$l_rank</td>
<td class="td-toon">$l_toon</td>
<td class="td-account">$l_account</td>
<td class="td-fleet">$l_fleet</td>
<td class="td-spacer">$spacer</td>
<td class="td-rank">$r_rank</td>
<td class="td-toon">$r_toon</td>
<td class="td-account">$r_account</td>
<td class="td-fleet">$r_fleet</td>

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
        $return_val[] = 'ago';
        return join(' ', $return_val);
    } else {
        return "basically right now";
    }
}

/**
 * Searches a list of SQL same-account matches for mismatched ranks (up to a maximum)
 * @param  array $matches Entries returned by rankMatchQueryBuilder()
 * @param  string $max_rank Maximum rank to match to (of form "R2")
 * @return array            Entries in $matches that have mismatched ranks
 */
function rankMatch($matches, $max_rank)
{
    global $ranks;
    $results = array();
    foreach ($matches as $match) {
        $l_rank = array_search($match['l_rank'], $ranks[$match['l_fleet']]);
        $r_rank = array_search($match['r_rank'], $ranks[$match['r_fleet']]);
        if (strcmp($l_rank, $r_rank) != 0 && (strcmp($l_rank, $max_rank) < 0 || strcmp($r_rank, $max_rank) < 0)) {
            $results[] = $match;
        }
    }
    return $results;
}