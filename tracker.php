<?php
if (!empty($_POST)) {
    if (isset($_POST['promo_done'])) {
        $task_query = "INSERT INTO `{$fleet}_promo`
				(user) VALUES (:user);";
        $query_params = array(':user' => $_SESSION['user']['username']);
        try {
            $stmt = $db->prepare($task_query);
            $result = $stmt->execute($query_params);
        } catch (PDOException $ex) {
            die("Failed to update promo task row: " . var_dump($ex));
        }
        $task_query = "INSERT INTO `tasks` (fleet, task, user) VALUES (:fleet, 'promo', :user);";
        $query_params = array(':fleet' => $fleet, ':user' => $_SESSION['user']['username']);
        try {
            $stmt = $db->prepare($task_query);
            $result = $stmt->execute($query_params);
        } catch (PDOException $ex) {
            die("Failed to update task row!");
        }
        if (!file_put_contents("tasks/" . $fleet . "_promo", $_SESSION['user']['username'])) {
            die("Failed to update sentinel file!");
        }
    }
    if (isset($_POST['kicks_done'])) {
        if (isset($_POST['kicks_did'])) {
            // Get rid of any kicked members from the main roster table
            // Done to prevent dupes in account lookups
            $kicks_did = unserialize(base64_decode($_POST['kicks_did']));
            $remove_query = "DELETE FROM roster_$fleet
					WHERE
						cname = :toon &&
						account = :account
				;";
            foreach ($kicks_did as $kick) {
                $query_params = array(
                    ':toon' => $kick['cname'],
                    ':account' => $kick['account']
                ); // OOPS
                try {
                    $stmt = $db->prepare($remove_query);
                    $result = $stmt->execute($query_params);
                } catch (PDOException $ex) {
                    die("Failed to remove kicks from roster: " . var_dump($ex));
                }
            }
        }
        if (isset($_POST['kicks_save'])) {
            $kickeds = unserialize(base64_decode($_POST['kicks_save']));
            $old_members_query = "SELECT * FROM `old_members` WHERE fleet = :fleet;";
            $query_params = array(':fleet' => $fleet);
            try {
                $stmt = $db->prepare($old_members_query);
                $result = $stmt->execute($query_params);
            } catch (PDOException $ex) {
                die("Failed to retrieve old members: " . var_dump($ex));
            }
            $old_members = $stmt->fetchAll();
            $members_to_insert = array();
            $members_to_update = array();
            foreach ($kickeds as $kicked) {
                if (empty($old_members)) {
                    $members_to_insert[] = $kicked;
                    continue;
                }
                foreach ($old_members as $old_member) {
                    $insert = true;
                    if ($kicked['account'] === $old_member['account']
                        && $kicked['cname'] === $old_member['cname']
                    ) {

                        $insert = false;
                        $ranks = ${$fleet . "_ranks"};
                        if (array_search($kicked['rank'], $ranks) > array_search($old_member['rank'], $ranks)) {
                            $members_to_update[] = $kicked;
                        }
                    }
                }
                if ($insert) {
                    $members_to_insert[] = $kicked;
                }
            }

            $kicks_submit_query = "INSERT INTO `old_members`
						(cname, account, rank, jdate, mcomment, mcommentdate,
						ocomment, ocommentauth, ocommentdate, fleet, kdate)
					VALUES
						(:toon, :account, :rank, :jdate, :mcomment, :mcommentdate,
						:ocomment, :ocommentauth, :ocommentdate, :fleet, :kdate)
				;";
            foreach ($members_to_insert as $row) {
                $query_params = array(
                    ':toon' => $row['cname'],
                    ':account' => $row['account'],
                    ':rank' => $row['rank'],
                    ':jdate' => $row['jdate'],
                    ':mcomment' => $row['mcomment'],
                    ':mcommentdate' => $row['mcommentdate'],
                    ':ocomment' => $row['ocomment'],
                    ':ocommentauth' => $row['ocommentauth'],
                    ':ocommentdate' => $row['ocommentdate'],
                    ':fleet' => $fleet,
                    ':kdate' => date('Y-m-d h:i:s'));
                try {
                    $stmt = $db->prepare($kicks_submit_query);
                    $result = $stmt->execute($query_params);
                } catch (PDOException $ex) {
                    die("Failed to insert new old members: " . var_dump($ex));
                }
            }
            $kicks_update_query = "UPDATE `old_members`
					SET
						rank = :rank,
						mcomment = :mcomment,
						mcommentdate = :mcommentdate,
						ocomment = :ocomment,
						ocommentauth = :ocommentauth,
						ocommentdate = :ocommentdate,
						kdate = :kdate
					WHERE
						account = :account &&
						cname = :cname
				;";

            foreach ($members_to_update as $row) {
                $query_params = array(
                    ':rank' => $row['rank'],
                    ':mcomment' => $row['mcomment'],
                    ':mcommentdate' => $row['mcommentdate'],
                    ':ocomment' => $row['ocomment'],
                    ':ocommentauth' => $row['ocommentauth'],
                    ':ocommentdate' => $row['ocommentdate'],
                    ':kdate' => date('Y-m-d h:i:s'),
                    ':account' => $row['account'],
                    ':cname' => $row['cname']);
                try {
                    $stmt = $db->prepare($kicks_update_query);
                    $result = $stmt->execute($query_params);
                } catch (PDOException $ex) {
                    die("Failed to update old old members: " . var_dump($ex));
                }
            }
        }
        $task_query = "INSERT INTO `{$fleet}_kicks`
				(user) VALUES (:user);";
        $query_params = array(':user' => $_SESSION['user']['username']);
        try {
            $stmt = $db->prepare($task_query);
            $result = $stmt->execute($query_params);
        } catch (PDOException $ex) {
            die("Failed to update kick task row: " . var_dump($ex));
        }

        $task_query = "INSERT INTO `tasks` (fleet, task, user) VALUES (:fleet, 'kicks', :user);";
        $query_params = array(':fleet' => $fleet, ':user' => $_SESSION['user']['username']);
        try {
            $stmt = $db->prepare($task_query);
            $result = $stmt->execute($query_params);
        } catch (PDOException $ex) {
            die("Failed to update task row!");
        }

        if (!file_put_contents("tasks/" . $fleet . "_kicks", $_SESSION['user']['username'])) {
            die("Failed to update sentinel file!");
        }
    }
}

$r1_promo_query = promoQueryBuilder($fleet, "R1");
$r2_promo_query = promoQueryBuilder($fleet, "R2");
$r1_kick_query = kickQueryBuilder($fleet, "R1");
$r2_kick_query = kickQueryBuilder($fleet, "R2");
$r3_kick_query = kickQueryBuilder($fleet, "R3");
$r4_kick_query = kickQueryBuilder($fleet, "R4");

$tasks_query = "SELECT id, user, time, 'kick' AS task FROM `{$fleet}_kicks`
			UNION ALL
			SELECT id, user, time, 'promo' AS task FROM `{$fleet}_promo`
		;";

try {
    $stmt = $db->prepare($tasks_query);
    $stmt->execute();
} catch (PDOException $ex) {
    die("Failed to retrieve previous task completions: " . var_dump($ex));
}
$rows = $stmt->fetchAll();

foreach ($rows as $row) {
    if ($row['task'] === 'promo') {
        $last_promo = strtotime($row['time']);
        $promo_by = $row['user'];
    } else if ($row['task'] === 'kick') {
        $last_kick = strtotime($row['time']);
        $kick_by = $row['user'];
    }
}
if (!isset($last_promo) || !isset($promo_by)) {
    die("Failed to retrieve last promo data!");
}
if (!isset($last_kick) || !isset($kick_by)) {
    die("Failed to retrieve last kick data!");
}

$last_updated = stat("tasks/" . $fleet . "_update")['mtime'];
$updated_by = file_get_contents("tasks/" . $fleet . "_update");
// $last_promo = stat("tasks/" . $fleet . "_promo")['mtime'];
// $promo_by = file_get_contents("tasks/" . $fleet . "_promo");
// $last_kick = stat("tasks/" . $fleet . "_kicks")['mtime'];
// $kick_by = file_get_contents("tasks/" . $fleet . "_kicks");

try {
    $stmt = $db->prepare($r1_promo_query);
    $stmt->execute();
} catch (PDOException $ex) {
    die("Failed to retrieve R1 promos");
}
$r1_promos = $stmt->fetchAll();

try {
    $stmt = $db->prepare($r2_promo_query);
    $stmt->execute();
} catch (PDOException $ex) {
    die("Failed to retrieve R2 promos");
}
$r2_promos = $stmt->fetchAll();

try {
    $stmt = $db->prepare($r1_kick_query);
    $stmt->execute();
} catch (PDOException $ex) {
    die("Failed to retrieve R1 kicks");
}
$r1_kicks = $stmt->fetchAll();

try {
    $stmt = $db->prepare($r2_kick_query);
    $stmt->execute();
} catch (PDOException $ex) {
    die("Failed to retrieve R2 kicks");
}
$r2_kicks = $stmt->fetchAll();

try {
    $stmt = $db->prepare($r3_kick_query);
    $stmt->execute();
} catch (PDOException $ex) {
    die("Failed to retrieve R3 kicks");
}
$r3_kicks = $stmt->fetchAll();

try {
    $stmt = $db->prepare($r4_kick_query);
    $stmt->execute();
} catch (PDOException $ex) {
    die("Failed to retrieve R4 kicks");
}
$r4_kicks = $stmt->fetchAll();

$promos = array_merge($r1_promos, $r2_promos);
$kicks = array_merge($r1_kicks, $r2_kicks, $r3_kicks, $r4_kicks);
$kicks_to_save = array_merge($r2_kicks, $r3_kicks, $r4_kicks);

echo "<html>";
echo "<head>";
echo "	<title>RJCommand: " . expandFleetName($fleet) . " Tasks</title>";
echo "	<link rel=\"stylesheet\" href=\"https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css\">";
echo "    <script src=\"jquery-2.1.4.min.js\"></script>";
echo "    <script src=\"https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js\"></script>";
echo "    <script src=\"jquery.tablesorter.min.js\"></script>";
echo "    <link rel=\"stylesheet\" href=\"style.css\">";
echo "</head>";
echo "<body>";
echo "	<div class=\"center table-container\">";
echo "	<h2>" . "Roster last updated: " . date("D, d M Y H:i:s O", $last_updated) . "</h2>";
echo "	<h4>" . "by " . $updated_by . " " . timeSince($last_updated) . "</h4>";
echo "	<form action=\"upload.php\" method=\"post\" enctype=\"multipart/form-data\">";
echo "		Upload a roster CSV (exported with /ExportGuildMemberList roster_name.csv):";
echo "		<input type=\"file\" name=\"roster_upload\" />";
echo "		<input type=\"hidden\" name=\"fleet\" value=$fleet />";
echo "		<input type=\"submit\" value=\"Upload Roster\" name=\"submit\" />";
echo "	</form>";
genPromoTable($fleet, $promos, ${$fleet . "_ranks"}, $last_promo, $promo_by, $last_updated);
echo "	<form name=\"promo_done\" action=" . $fleet . "_tracker.php" . " method=\"post\">";
echo "		<input name=\"promo_done\" type=\"submit\" value=\"I Did This\" />";
echo "	</form>";
echo "	<br />";
genKickTable($fleet, $kicks, ${$fleet . "_ranks"}, $last_kick, $kick_by, $last_updated);
echo "	<form name=\"kicks_done\" action=" . $fleet . "_tracker.php" . " method=\"post\">";
echo "		<input name=\"kicks_did\" type=\"hidden\" value=" . base64_encode(serialize($kicks)) . " />";
echo "		<input name=\"kicks_save\" type=\"hidden\" value=" . base64_encode(serialize($kicks_to_save)) . " />";
echo "		<input name=\"kicks_done\" type=\"submit\" value=\"I Did This\" />";
echo "	</form>";
echo "	<br />";
if ($fleet === "snoo") echo "	<h3>KDF is Best DF</h3>";
if ($fleet === "rse") echo "	<img src=\"img/zooey.jpg\" alt=\"Kablooey\" /><br />";
echo "	<a href=\"index.php\">Go Back</a>";
echo "	</div>";
echo "	<script>";
echo "	$(document).ready(function() {";
echo "		$(\"table tbody\").each(function() {";
echo "			if ($(this).children(\"tr\").length > 0) {";
echo "				$(this).parent().tablesorter({sortList: [[0,0], [1,0]]});";
echo "			}";
echo "		});";
echo "	});";
echo "	</script>";
echo "</body>";
echo "</html>";