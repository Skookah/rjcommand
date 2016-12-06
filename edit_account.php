<?php
require("common.php");

if (!empty($_POST)) {
    // If the user actually entered new passwords, and they successfully typed the same thing twice,
    // update their password
    if (!empty($_POST['password-1']) && !empty($_POST['password-2'])) {
        if ($_POST['password-1'] !== $_POST['password-2']) {
            die("Passwords do not match!");
        }
        $salt = dechex(mt_rand(0, 2147483647)) . dechex(mt_rand(0, 2147483647));
        $password = hash('sha256', $_POST['password'] . $salt);
        for ($round = 0; $round < 65536; $round++) {
            $password = hash('sha256', $password . $salt);
        }
        $query_params = array(
            ':user_id' => $_SESSION['user']['id'],
            ':password' => $password,
            ':salt' => $salt
        );

        $query = "
            UPDATE users
            SET
              password = :password,
              salt = :salt
            WHERE
              id = :user_id;
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

// Tentative password reset email - maybe finish implementing sendmail later?

//    if (!empty($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
//        $query = "UPDATE users
//        SET
//          `email` = :email
//        WHERE
//          id = :user_id;";
//        $query_params = array(
//            ':email' => htmlspecialchars($_POST['email'], ENT_QUOTES),
//            ':user_id' => $_SESSION['user']['id']
//        );
//        try {
//            $stmt = $db->prepare($query);
//            $result = $stmt->execute($query_params);
//        } catch (PDOException $ex) {
//            die("Failed to update user email!" . var_dump($ex));
//        }
//        header("Location: index.php");
//        die("Redirecting to index.php");
//    }
}
?>
<html>
<head>
    <title>RJCommand: Edit Account</title>
    <?= $head ?>
</head>
<body>
<div class="center">
    <h1>Edit Account</h1>
    <a href="index.php">Go Back</a>
    <div class="account">
        <form action="edit_account.php" method="post">
            Username: <b><?php echo htmlspecialchars($_SESSION['user']['username'], ENT_QUOTES); ?></b>
            <br /><br />
    <!--            Leave either email or both password fields blank to not alter either email or password, respectively.-->
    <!--        <br />-->
    <!--        <p class="form-label">Email:</p>-->
    <!--        <input type="text" name="email" value="" /><br />-->
            <p class="form-label">New password:</p>
            <input type="password" name="password-1" value="" /><br />
            <p class="form-label">Retype password:</p>
            <input type="password" name="password-2" value="" /><br />
            <input type="submit" value="Update Account" />
        </form>
    </div>
    <br />
    <a href="index.php">Go Back</a>
</div>
</body>
</html>
