<?php
    require("common.php");
    // Check for login!
    if (empty($_SESSION['user'])) {
        // If not logged in, redirect
        header("Location: login.php");
        die("Redirecting to login.php");
    }
    // If the form has been submitted, run the modifications
    if (!empty($_POST)) {
        // New passwword: fresh salt for the hash
        if (!empty($_POST['password'])) {
            $salt = dechex(mt_rand(0, 2147483647)) . dechex(mt_rand(0, 2147483647));
            $password = hash('sha256', $_POST['password'] . $salt);
            for ($round = 0; $round < 65536; $round++) {
                $password = hash('sha256', $password . $salt);
            }
        }
        // If the user did not enter a new password, don't touch the old one
        else {
            $password = null;
            $salt = null;
        }
      
        // Initial query params
        $query_params = array(':user_id' => $_SESSION['user']['id']);
        if ($password !== null) {
            $query_params[':password'] = $password;
            $query_params[':salt'] = $salt;
        }
        $query = "
            UPDATE users
            SET
        ";
        // Dynamically generate query
        if ($password !== null) {
            $query .= "
                password = :password,
                salt = :salt
            ";
        }
        $query .= "
            WHERE
                id = :user_id
        ";
        try {
            $stmt = $db->prepare($query);
            $result = $stmt->execute($query_params);
        } catch (PDOException $ex) {
            die("Failed to update user details.");
        }
        header("Location: index.php");
        die("Redirecting to index.php");
    }
?>
<head>
    <title>RJCommand: Edit Account</title>
    <link rel="stylesheet" href="style.css">
</head>
<div class="center">
    <div class="inner">
    <h1 class="head">Edit Account</h1>
    <form action="edit_account.php" method="post">
        Username:<br />
        <b class="name-display"><?php echo htmlentities($_SESSION['user']['username'], ENT_QUOTES, 'UTF-8'); ?></b>
        <br />
        Password:<br /><br />
        <input type="password" name="password" value="" /><br />
        <i class="small">enter a new password</i><br />
        <!--<i class="small">(leave blank if you do not wish to make changes)</i>-->
        <input type="password" name="password_confirm" value="" /><br />
        <i class="small">please re-enter your new password</i><br />
        <input type="submit" value="Update Account" />
    </form>
    <br />
    <a href="index.php">Go Back</a>
</div>
</div>