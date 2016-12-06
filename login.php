<?php
require("common.php");
$submitted_username = '';

if (!empty($_POST)) {
    $query = "
    SELECT
        id,
        username,
        password,
        salt,
        active
    FROM users
    WHERE
      username = :username
    ";

    $query_params = array(':username' => $_POST['username']);
    try {
        $stmt = $db->prepare($query);
        $result = $stmt->execute($query_params);
    } catch (PDOException $ex) {
        die("Failed to retrieve user information.");
    }
    $login_ok = false;
    $row = $stmt->fetch();

    if ($row) {
        $check_password = hash('sha256', $_POST['password'] . $row['salt']);
        for ($round = 0; $round < 65536; $round++) {
            $check_password = hash('sha256', $check_password . $row['salt']);
        }
        if ($check_password === $row['password'] && $row['active']) {
            $login_ok = true;
        }
    }

    if ($login_ok) {
        unset($row['salt']);
        unset($row['password']);
        $_SESSION['user'] = $row;

        $redirect = "index.php";
        if (!empty($_SESSION['redirect'])) {
            $valid_redirects = array(
                "tracker.php",
                "lookup.php",
                "memberlist.php",
                "edit_account.php",
                "ra_tracker.php",
                "rse_tracker.php",
                "snoo_tracker.php",
                "rising_tracker.php",
                "mirror_tracker.php",
                "rank_matching.php"
                );
            if (in_array($_SESSION['redirect'], $valid_redirects)) {
                $redirect = $_SESSION['redirect'];
            }
        }
        header("Location: " . $redirect);
        die("Redirecting to " . $redirect);
    } else {
        print("Login failed.");
        $submitted_username = htmlspecialchars($_POST['username'], ENT_QUOTES);
    }
}
?>
<html>
<head>
    <title>RJCommand: Login</title>
    <?= $head ?>
</head>
<body>
<?php
echo "<input type=\"hidden\" name=\"location\" value=\"";
if (isset($_GET['location'])) {
    echo htmlspecialchars($_GET['location'], ENT_QUOTES);
}
echo "\" />";
?>

<div class="center">
    <div class="login">
    <h1>Login</h1>
    <form action="login.php" method="post">
        <p class="form-label">Username:</p>
        <input type="text" name="username" value="<?php echo $submitted_username; ?>" />
        <p class="form-label">Password:</p>
        <input type="password" name="password" value="" />
        <br /><br />
        <input type="submit" value="Login" />
        <br /><br />
        <a href="register.php">Register</a><br />
        <!--<a href="forgot.php">Forgot Password</a>-->
    </form>
    </div>
</div>
</body>
</html>
