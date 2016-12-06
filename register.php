<?php
require("common.php");

if (!empty($_POST)) {
    if (empty($_POST['username'])) {
        die("Please enter a username.");
    }
    if (empty($_POST['password-1']) || empty($_POST['password-2'])) {
        die("Please enter a password.");
    }
    if ($_POST['password-1'] !== $_POST['password-2']) {
        die("Passwords do not match!");
    }

    $query = "
    SELECT
      1
    FROM users
    WHERE
      username = :username
    ";
    $query_params = array(':username' => $_POST['username']);

    try {
        $stmt = $db->prepare($query);
        $result = $stmt->execute($query_params);
    } catch (PDOException $ex) {
        die("Failed to query user database.");
    }

    $row = $stmt->fetch();
    if ($row) {
        die("Username already in use!");
    }

    $query = "
        INSERT INTO users (
          username,
          password,
          salt
        ) VALUES (
          :username,
          :password,
          :salt
        )
    ";

    $salt = dechex(mt_rand(0, 2147483647)) . dechex(mt_rand(0, 2147483647));
    $password = hash('sha256', $_POST['password'] . $salt);
    for ($round = 0; $round < 65536; $round++) {
        $password = hash('sha256', $password . $salt);
    }

    $query_params = array(
        ':username' => $_POST['username'],
        ':password' => $password,
        ':salt' => $salt
    );

    try {
        $stmt = $db->prepare($query);
        $result = $stmt->execute($query_params);
    } catch (PDOException $ex) {
        die("Failed to create user!");
    }

    header("Location: login.php");
    die("Redirecting to login.php");
}
?>
<html>
<head>
    <title>RJCommand: Register</title>
    <?= $head ?>
</head>
<body>
<div class="center register">
    <h1>Register</h1>
    <form action="register.php" method="post">
        <p class="form-label">Username:</p>
        <input type="text" name="username" value="" />
        <p class="form-label">Password:</p>
        <input type="password" name="password-1" value="" />
        <p class="form-label">Retype password:</p>
        <input type="password" name="password-2" value="" />
        <br /><br />
        <input type="submit" value="Register" />
    </form>
</div>
</body>
</html>
