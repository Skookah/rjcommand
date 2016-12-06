<?php
require("common.php");

function checkCSV($file) {
	ini_set("auto_detect_line_endings", True);
	$file_handle = fopen($file, 'rb');
	$is_csv = fgetcsv($file_handle);
	if (is_array($is_csv)) {
        if (count($is_csv) !== 15) {
            $is_csv = False;
        } else {
            $is_csv = True;
        }
    } else {
        $is_csv = False;
    }
	fclose($file_handle);
	ini_set("auto_detect_line_endings", False);
	return $is_csv; //($is_csv != False && $is_csv != Null);
}

function uploadFile($file, $target, $ok) {
	if (!$ok) {
		die("Uploaded file not valid CSV! (Probably!)");
	} else {
		if (move_uploaded_file($file, $target)) {
			echo "Successfully uploaded file";
		} else {
			die("Failed to upload file");
		}
	}
}

function updateRoster($roster_file, $fleet) {
	global $db;
	$query = "TRUNCATE roster_$fleet;";
	try {
		$stmt = $db->prepare($query);
		$stmt->execute();
	} catch (PDOException $ex) {
		die("Failed to truncate table!");
	}
	$query = "LOAD DATA INFILE '/usr/share/nginx/html/$roster_file'
		INTO TABLE roster_$fleet
		FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"'
		IGNORE 1 LINES
		(`cname`, `account`, `level`, `class`, `rank`, `contribs`, @jdate, @pdate, @lastonline, `status`, `mcomment`, @mcommentdate, `ocomment`, `ocommentauth`, @ocommentdate)
		SET
			`jdate`=STR_TO_DATE(@jdate, '%m/%e/%Y %r'),
	        `pdate`=STR_TO_DATE(@pdate, '%m/%e/%Y %r'),
	        `lastonline`=STR_TO_DATE(@lastonline, '%m/%e/%Y %r'),
	        `mcommentdate`=STR_TO_DATE(@mcommentdate, '%m/%e/%Y %r'),
	        `ocommentdate`=STR_TO_DATE(@ocommentdate, '%m/%e/%Y %r');";
	try {
		$stmt = $db->prepare($query);
		$stmt->execute();
	} catch (PDOException $ex) {
		die("Failed to load data from roster file!");
	}
	if (!file_put_contents("tasks/" . $fleet . "_update", $_SESSION['user']['username'])) {
		die("Failed to update sentinel file!");
	}
    $query = "INSERT INTO `fleet_pop`
            (fleet, pop) VALUES ('$fleet', (SELECT COUNT(*) FROM roster_$fleet))
            ;";
	try {
        $stmt = $db->prepare($query);
        $stmt->execute();
    } catch (PDOException $ex) {
        die("Failed to take fleet count snapshot!");
    }

	$query = "SELECT * FROM `old_members` 
				WHERE 
					fleet=:fleet";
	$query_params = array(':fleet' => $fleet);
	try {
		$stmt = $db->prepare($query);
		$stmt->execute($query_params);
	} catch (PDOException $ex) {
		die("Failed to retrieve previously kicked member list: " . var_dump($ex));
	}
	$old_members = $stmt->fetchAll();
	$other_fleet = "null";
	if ($fleet === "ra" || $fleet === "rse") {
		$other_fleet = ($fleet === "ra") ? "rse" : "ra";
	} else if ($fleet === "snoo" || $fleet === "rising") {
		$other_fleet = ($fleet === "snoo") ? "rising" : "snoo";
	}
	$query = "SELECT * FROM `roster_$fleet` UNION ALL
		SELECT * FROM `roster_$other_fleet`;";
	try {
		$stmt = $db->prepare($query);
		$stmt->execute();
	} catch (PDOException $ex) {
		die("Failed to retrieve $fleet (or $other_fleet) roster");
	}
	$roster = $stmt->fetchAll();

	foreach ($old_members as $old_member) {
		foreach ($roster as $cur_member) {
			if ($cur_member['account'] === $old_member['account'] &&
				$cur_member['cname'] === $old_member['cname']) {

				$query = "DELETE FROM `old_members` WHERE cname='" . htmlentities($cur_member['cname'], ENT_QUOTES, "UTF-8") . "' && account='" . $cur_member['account'] . "';";
				try {
					$stmt = $db->prepare($query);
					$stmt->execute();
				} catch (PDOException $ex) {
					die("Failed to remove newly added old members from old member table: " . var_dump($ex));
				}
			}
		}
	}	
}

$referrer = parse_url($_SERVER['HTTP_REFERER']);
$valid_referrer_paths = array(
	"/ra_tracker.php",
	"/rse_tracker.php",
	"/snoo_tracker.php",
	"/rising_tracker.php",
    "/mirror_tracker.php",
	"/rank_matching.php",
	"/illegal_alts.php");
if ($referrer['host'] != "104.131.168.53" || !in_array($referrer['path'], $valid_referrer_paths)) {
	die("How did you get here?");
}

$target_dir = "uploads/";

if ($referrer['path'] === "/rank_matching.php") {
	$rosters = array(
		'ra' =>		&$_FILES['ra_upload'],
		'rse' =>	&$_FILES['rse_upload'],
		'snoo' =>	&$_FILES['snoo_upload'],
		'rising' => &$_FILES['rising_upload'],
        'mirror' => &$_FILES['mirror_upload']
		);
	foreach ($rosters as $roster => &$roster_file) {
		if (!is_uploaded_file($roster_file['tmp_name'])) {
			continue;
		}
		$target_file = $target_dir . "roster_" . $roster . ".csv";
		$file_ok = checkCSV($roster_file['tmp_name']);
		uploadFile($roster_file['tmp_name'], $target_file, $file_ok);
		updateRoster($target_file, $roster);
	}
} else {
	if (!expandFleetName($_POST['fleet'])) {
		die("Invalid fleet. How did you do that?");
	}
	$fleet_short = $_POST['fleet'];
	$target_file = $target_dir . "roster_" . $fleet_short . ".csv";
	$file_ok = checkCSV($_FILES['roster_upload']['tmp_name']);
	uploadFile($_FILES['roster_upload']['tmp_name'], $target_file, $file_ok);
	updateRoster($target_file, $fleet_short);
}
echo "\nRedirecting...";
$redirect = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
sleep(3);

header("Location: $redirect");
die("Redirecting to $redirect");
?>