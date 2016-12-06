<?php
require("common.php");
if (!empty($_POST)) {

    // If the user performed a promotion, insert their info into the tasks table
    if (isset($_POST['promo_done'])) {
        $task_query = "INSERT INTO tasks (fleet, task, user) VALUES (:fleet, 'promo', :user);";
        $query_params = array(':fleet' => $fleet, ':user' => $_SESSION['user']['username']);
        try {
            $stmt = $db->prepare($task_query);
            $result = $stmt->execute($query_params);
        } catch (PDOException $ex) {
            die("Failed to update task row!");
        }
    }

    // If the user performed a kicks, perform a series of fancy operations
    if (isset($_POST['kicks_done'])) {
        // Remove kicked toons from roster table so they don't show up again
        // Largely auxiliary, user should upload new roster w/o kicked members anyway
        if (isset($_POST['kicks_did'])) {
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
                );
                try {
                    $stmt = $db->prepare($remove_query);
                    $result = $stmt->execute($query_params);
                } catch (PDOException $ex) {
                    die("Failed to remove kicks from roster " . var_dump($ex));
                }
            }
        }

        // Update kicked-members table with new kicks
        if (isset($_POST['kicks_save'])) {
            $kickeds = unserialize(base64_decode($_POST['kicks_save']));
            $old_members_query = "SELECT * FROM `old_members` WHERE fleet=:fleet;";
            $query_params = array(':fleet' => $fleet);
            try {
                $stmt = $db->prepare($old_members_query);
                $result = $stmt->execute($query_params);
            } catch (PDOException $ex) {
                die("Failed to retrieve old members " . var_dump($ex));
            }
            $old_members = $stmt->fetchAll();
            $members_to_insert = array();
            $members_to_update = array();

            // Insert newly kicked members, or update existing kicked members (only if rank increased)
            foreach ($kickeds as $kicked) {
                if (empty($old_members)) {
                    $members_to_insert[] = $kicked;
                    continue;
                }
                foreach ($old_members as $old_member) {
                    $insert = true;
                    if ($kicked['account'] === $old_member['account']
                        && $kicked['cname'] === $old_member['cname']) {
                        $insert = false;
                        if (array_search($kicked['rank'], $ranks[$fleet]) > array_search($old_member['rank'], $ranks[$fleet])) {
                            $members_to_update[] = $kicked;
                        }
                    }
                }
                if ($insert) {
                    $members_to_insert[] = $kicked;
                }
            }

            // Submit new kicks to old members table
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
                    ':kdate' => date('Y-m-d h:i:s')
                );
                try {
                    $stmt = $db->prepare($kicks_submit_query);
                    $result = $stmt->execute($query_params);
                } catch (PDOException $ex) {
                    die("Failed to insert new old members " . var_dump($ex));
                }
            }

            // Update existing kicks with latest information
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
                    ':mcommentdate' => $row[':mcommentdate'],
                    ':ocomment' => $row['ocomment'],
                    ':ocommentauth' => $row['ocommentauth'],
                    ':ocommentdate' => $row['ocommentdate'],
                    ':kdate' => date('Y-m-d h:i:s'),
                    ':account' => $row['account'],
                    ':cname' => $row['cname']
                );
                try {
                    $stmt = $db->prepare($kicks_update_query);
                    $result = $stmt->execute($query_params);
                } catch (PDOException $ex) {
                    die("Failed to update old old members " . var_dump($ex));
                }
            }
        }

        // Finally, update tasks table with kick task data
        $task_query = "INSERT INTO `tasks` (fleet, task, user) VALUES (:fleet, 'kicks', :user);";
        $query_params = array(
            ':fleet' => $fleet,
            ':user' => $_SESSION['user']['username']
        );
        try {
            $stmt = $db->prepare($task_query);
            $result = $stmt->execute($query_params);
        } catch (PDOException $ex) {
            die("Failed to update task row " . var_dump($ex));
        }
    }
}

// Get roster update data (time, by)
$last_updated = stat("tasks/" . $fleet . "_update")['mtime'];
$updated_by = file_get_contents("tasks/" . $fleet . "_update");

// Build task queries
$r1_promo_query = promoQueryBuilder($fleet, "R1");
$r2_promo_query = promoQueryBuilder($fleet, "R2");
$r1_kick_query = kickQueryBuilder($fleet, "R1");
$r2_kick_query = kickQueryBuilder($fleet, "R2");
$r3_kick_query = kickQueryBuilder($fleet, "R3");
$r4_kick_query = kickQueryBuilder($fleet, "R4");

// Get last tasks data
$tasks_query = "
SELECT
  id, fleet, task, user, time
  FROM `tasks`
WHERE
  fleet='$fleet' AND
  task = :task
ORDER BY id DESC
LIMIT 1";

try {
    $stmt = $db->prepare($tasks_query);

    // Last promo
    $query_params = array(':task' => 'promo');
    $stmt->execute($query_params);
    $last_promo = $stmt->fetch();

    // Last kick
    $query_params = array(':task' => 'kicks');
    $stmt->execute($query_params);
    $last_kicks = $stmt->fetch();
} catch (PDOException $ex) {
    die("Failed to retrieve previous task completions " . var_dump($ex));
}

// Format previous task data for display
$promo_time = strtotime($last_promo['time']);
$promo_by = $last_promo['user'];

$kicks_time = strtotime($last_kicks['time']);
$kicks_by = $last_kicks['user'];

// Retrieve promotion and kick data
try {
    $stmt = $db->prepare($r1_promo_query);
    $stmt->execute();
    $r1_promos = $stmt->fetchAll();

    $stmt = $db->prepare($r2_promo_query);
    $stmt->execute();
    $r2_promos = $stmt->fetchAll();

    $stmt = $db->prepare($r1_kick_query);
    $stmt->execute();
    $r1_kicks = $stmt->fetchAll();

    $stmt = $db->prepare($r2_kick_query);
    $stmt->execute();
    $r2_kicks = $stmt->fetchAll();

    $stmt = $db->prepare($r3_kick_query);
    $stmt->execute();
    $r3_kicks = $stmt->fetchAll();

    $stmt = $db->prepare($r4_kick_query);
    $stmt->execute();
    $r4_kicks = $stmt->fetchAll();
} catch (PDOException $ex) {
    die("Failed to retrieve kicks and/or promos " . var_dump($ex));
}

// Pull together disparate data
$promos = array_merge($r1_promos, $r2_promos);
$kicks = array_merge($r1_kicks, $r2_kicks, $r3_kicks, $r4_kicks);
$kicks_to_save = array_merge($r2_kicks, $r3_kicks, $r4_kicks);

// Create task tables
$promo_table = genPromoTable($fleet, $promos, $promo_time, $promo_by, $last_updated);
$kick_table = genKickTable($fleet, $kicks, $kicks_time, $kicks_by, $last_updated);

// Format update data for display
$updated_date = date("D, d M Y H:i:s", $last_updated);
$updated_since = timeSince($last_updated);


// Encode kicks, to pass through for saving to old members table
$serial_kicks = base64_encode(serialize($kicks));
$serial_kicks_save = base64_encode(serialize($kicks_to_save));

?>
<html>
<head>
    <title>RJCommand: <?= expandFleetName($fleet) ?> Tasks</title>
    <?= $head ?>
</head>
<body>
<div class="center">
    <div class="header">
        <h2>Roster Last Updated: <?= $updated_date ?></h2>
        <h3><?= $updated_since ?> by <?= $updated_by ?> </h3>
        <div class="roster-uploads">
            <h4>Upload rosters</h4>
            <h5>Command: /ExportGuildMemberList roster_<?= $fleet ?>.csv</h5>
            <form action="upload.php" method="post" enctype="multipart/form-data">
                <div class="input-container"><input type="file" name="roster_upload" /></div>
                <input type="hidden" name="fleet" value=<?= $fleet ?> />
                <div class="input-container submit"><input type="submit" value="Upload Roster" name="submit" /></div>
            </form>
        </div>
        <a href="index.php">Go Back</a>
    </div>
    <div class="content">
        <?= $promo_table ?>
        <br />
        <div class="task-complete">
            <form name="promo_done" action="<?= $fleet ?>_tracker.php" method="post">
                <input name="promo_done" type="submit" value="Confirm Task Completion" />
            </form>
        </div>
        <br />
        <?= $kick_table ?>
        <br />
        <div class="task-complete">
            <form name="kicks_done" action="<?= $fleet ?>_tracker.php" method="post">
                <input name="kicks_did" type="hidden" value=<?= $serial_kicks ?> />
                <input name="kicks_save" type="hidden" value=<?= $serial_kicks_save ?> />
                <div class="input-container submit"><input name="kicks_done" type="submit" value="Confirm Task Completion" /></div>
            </form>
        </div>
        <br />
        <?php if ($fleet == "rse"): ?>
            <div class="pic">
                <img src="img/zooey.jpg" />
            </div>
        <?php endif; ?>
    </div>
    <a href="index.php">Go Back</a>
</div>
</body>
</html>
