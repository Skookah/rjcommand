<?php
    require("common.php");
    // Check for logged in session
    if (empty($_SESSION['user'])) {
        // Presever current page
        header("Location: login.php?location=" . urlencode(trim($_SERVER['REQUEST_URI'], '/')));
        die("Redirecting to login.php");
    }
    // Secure bits after this point
    if (!empty($_POST)) {
        if ($_POST['id'] == 0) {
            $query = "
                INSERT INTO members
                (toon, account, fleet, warnings, comments, reddit)
                VALUES
                (:toon, :account, :fleet, :warnings, :comments, :reddit)
            ";
            $query_params = array(
                ':toon' => $_POST['toon'],
                ':account' => $_POST['account'],
                ':fleet' => $_POST['fleet'],
                ':warnings' => $_POST['warnings'],
                ':comments' => $_POST['comments'],
                ':reddit' => $_POST['reddit']);
        } else {
            $query = "
                UPDATE members
                SET 
                    toon = :toon,
                    account = :account,
                    fleet = :fleet,
                    warnings = :warnings,
                    comments = :comments,
                    reddit = :reddit
                WHERE 
                    id = :id
            ;";

            $query_params = array(
                ':toon' => $_POST['toon'],
                ':account' => $_POST['account'],
                ':fleet' => $_POST['fleet'],
                ':warnings' => $_POST['warnings'],
                ':comments' => $_POST['comments'],
                ':reddit' => $_POST['reddit'],
                ':id' => $_POST['id']);

        }
        try {
            $stmt = $db->prepare($query);
            $result = $stmt->execute($query_params);
        } catch (PDOException $ex) {
            die("Failed to update member records.");
        }
    }
    $query = "
        SELECT
            *
        FROM members
    ";

    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
    } catch(PDOException $ex) {
        die("Failed to retrieve member list");
    }
    $rows = $stmt->fetchAll();
?>
<html>
<head>
    <title>RJCommand: Memberlist</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
    <script src="jquery-2.1.4.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
    <script src="jquery.tablesorter.min.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<div class="center table-container">
<div class="inner">
<table id="members" class="table table-hover table-condensed tablesorter">
    <caption>All Members</caption>
    <thead>
    <tr>
        <th class="th-toon">Character Name</th>
        <th class="th-account">Account</th>
        <th class="th-fleet">Fleet</th>
        <th class="th-warns">Warnings</th>
        <th class="th-comment">Comments</th>
        <th class="th-reddit">Reddit Username</th>
        <th class="th-edit"></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach($rows as $row): ?>
    <tr>
        <form id="edit-form" name="edit-form" action="memberlist.php" method="post">
        <td class="td-toon">
            <div class="show"><?php echo htmlentities($row['toon'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="hidden"><input type="text" class="edit-text" name="toon" value="<?php echo htmlentities($row['toon'], ENT_QUOTES, 'UTF-8'); ?>" /></div>
        </td>
        <td class="td-account">
            <div class="show"><?php echo htmlentities($row['account'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="hidden"><input type="text" class="edit-text" name="account" value="<?php echo htmlentities($row['account'], ENT_QUOTES, 'UTF-8'); ?>" /></div>
        </td>
        <td class="td-fleet">
            <div class="show"><?php echo htmlentities($row['fleet'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="hidden"><select name="fleet" class="edit-text">
                <option value="Unknown" <?=htmlentities($row['fleet'], ENT_QUOTES, 'UTF-8') == 'Unknown' ? 'selected' : '';?>>Unknown</option>
                <option value="RA" <?=htmlentities($row['fleet'], ENT_QUOTES, 'UTF-8') == 'RA' ? 'selected' : '';?>>RA</option>
                <option value="RSE" <?=htmlentities($row['fleet'], ENT_QUOTES, 'UTF-8') == 'RSE' ? 'selected' : '';?>>RSE</option>
                <option value="Snoo" <?=htmlentities($row['fleet'], ENT_QUOTES, 'UTF-8') == 'Snoo' ? 'selected' : '';?>>Snoo</option>
                <option value="Rising Snoo" <?=htmlentities($row['fleet'], ENT_QUOTES, 'UTF-8') == 'Rising Snoo' ? 'selected' : '';?>>Rising Snoo</option>
            </select> </div>
        </td>
        <td class="td-warns">
            <div class="show"><?php echo htmlentities($row['warnings'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="hidden"><input type="text" class="edit-text" name="warnings" value="<?php echo htmlentities($row['warnings'], ENT_QUOTES, 'UTF-8'); ?>" /></div>
        </td>
        <td class="td-comment">
            <div class="show"><?php echo htmlentities($row['comments'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="hidden"><input type="text" class="edit-text" name="comments" value="<?php echo htmlentities($row['comments'], ENT_QUOTES, 'UTF-8'); ?>" /></div>
        </td>
        <td class="td-reddit">
            <div class="show"><?php echo htmlentities($row['reddit'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="hidden"><input type="text" class="edit-text" name="reddit" value="<?php echo htmlentities($row['reddit'], ENT_QUOTES, 'UTF-8'); ?>" /></div>
        </td>
        <td class="td-edit">
            <button type="button" class="end-button edit-button">Edit</button>
            <div class="invis"><input type="text" name="id" value="<?php echo htmlentities($row['id'], ENT_QUOTES, 'UTF-8'); ?>" /></div>
        </td>
        </form>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table><br /><br />
<button type="button" class="new-button">New Member</button>
<br /><br />
<a href="index.php">Go Back</a><br />
</div>
</div>
<script>
    $(document).ready(function() {  
        $('#members').tablesorter();
        $('.end-button').click(function() {
            $(this).closest('tr').find('.show, .hidden').toggleClass('show hidden');
            if ($(this).hasClass('submit-changes')) {
                $(this).closest('tr').find('form[name=edit-form]').submit();
            }
            $(this).toggleClass('edit-button submit-changes');
            var text = $(this).hasClass('edit-button') ? 'Edit' : 'Submit';
            $(this).text(text);
        });

        $('.new-button').click(function() {
            if (!$('#create-form').length) {}
                $('#members > tbody:last').append('\
                    // <tr><form id="create-form" name="create-form" action="memberlist.php" method="post">\
                    // <td><input type="text" class="edit-text" form="create-form" name="toon" value="" /></td>\
                    // <td><input type="text" class="edit-text" form="create-form" name="account" value="" /></td>\
                    // <td><select name="fleet" form="create-form" class="edit-text">\
                    // <option value="RA" <?=htmlentities($row['fleet'], ENT_QUOTES, 'UTF-8') == 'RA' ? 'selected' : '';?>>RA</option>\
                    // <option value="RSE" <?=htmlentities($row['fleet'], ENT_QUOTES, 'UTF-8') == 'RSE' ? 'selected' : '';?>>RSE</option>\
                    // <option value="Snoo" <?=htmlentities($row['fleet'], ENT_QUOTES, 'UTF-8') == 'Snoo' ? 'selected' : '';?>>Snoo</option>\
                    // <option value="Rising Snoo" <?=htmlentities($row['fleet'], ENT_QUOTES, 'UTF-8') == 'Rising Snoo' ? 'selected' : '';?>>Rising Snoo</option>\
                    // </select></td>\
                    // <td><input type="text" class="edit-text" form="create-form" name="warnings" value="" /></td>\
                    // <td><input type="text" class="edit-text" form="create-form" name="comments" value="" /></td>\
                    // <td><input type="text" class="edit-text" form="create-form" name="reddit" value="" /></td>\
                    // <td><button type="button" class="submit-changes">Submit</button>\
                    // <div class="invis"><input type="text" name="id" value="0" /></div></td>\
                    // </form>\
                    // </tr>');
                $('button.submit-changes').click(function() {
                    $(this).closest('tr').find('form[name=create-form]').submit();
                    $('.new-button').prop('disabled', true);
                });
                $(this).prop('disabled', true);
        });
    });
</script>
</html>