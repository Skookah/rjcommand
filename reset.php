<?php
require("common.php");
if (isset($_POST['i'])) {
    $query = "SELECT
      id, username, email, salt
    FROM users
    WHERE id = :id;";
    $query_params = array(':id' => $_POST['i']);
    try {
        $stmt = $db->prepare($query);
        if ($stmt->execute($query_params)) {
            $row = $stmt->fetch();
            if ($row['salt'] === $_POST['q']) {
                if ($_POST['password-1'] !== $_POST['password-2']) {
                    die("Passwords do not match!");
                }
                $salt = dechex(mt_rand(0, 2147483647)) . dechex(mt_rand(0, 2147483647));
                $password = hash('sha256', $_POST['password'] . $salt);
                for ($round = 0; $round < 65536; $round++) {
                    $password = hash('sha256', $password . $salt);
                }
                $query_params = array(
                    ':user_id' => $_POST['i'],
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
                    die("Failed to update user details!");
                }
            }
        }
    } catch (PDOException $ex) {
        die("Failed to retrieve user info!");
    }
}
?>
<html>
<head>
    <title>RJCommand: Password Reset</title>
    <?= $head ?>
</head>

<body>
<div class="center">
    <h1>Password Reset</h1>
    <form action="<?= "reset.php?q=" . $_POST['q'] . "&e=" . $_POST['e'] ?>" method="post">
        <p class="form-label">New password:</p>
        <input type="password" name="password-1" value="" /><br />
        <p class="form-label">Retype password:</p>
        <input type="password" name="password-2" value="" /><br />
        <input type="submit" value="Update Account" />
    </form>
</div>
</body>

</html>