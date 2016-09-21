<?php
    // Connect to DB and start session
    require("common.php");
    // Redisplay username if they enter the wrong password with value stored here
    $submitted_username = '';
    // Check to see if login form has been submitted
    if (!empty($_POST)) {
        // Retrieve user info with username
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
            // Execute query
            $stmt = $db->prepare($query);
            $result = $stmt->execute($query_params);
        } catch (PDOException $ex) {
            die("Failed to retrieve user information.");
        }
        // Sentinel for if the user managed to log in.
        $login_ok = false;
        // Retrieve user data from DB. If $row is false, username is not registered.
        $row = $stmt->fetch();
        if ($row) {
            // Hash up the password the user submitted to compare
            $check_password = hash('sha256', $_POST['password'] . $row['salt']);
            for ($round = 0; $round < 65536; $round++) {
                $check_password = hash('sha256', $check_password . $row['salt']);
            }
            if ($check_password === $row['password'] && $row['active']) {
                // They logged in real good!
                $login_ok = true;
            }
        }
        // If the user logged in, proceed - otherwise, hit them with the login again
        if ($login_ok) {
            // Prepare the $row to store in $_SESSION by removing sensitive info
            // for best practices.
            unset($row['salt']);
            unset($row['password']);

            // Store the user data in the session for future checking of login, as well as
            // retrieval of said data
            $_SESSION['user'] = $row;


            $redirect = null;
            if (!empty($_SESSION['redirect'])) {
                $redirect = $_SESSION['redirect'];
                switch ($redirect) {
                    case "tracker.php":
                    case "lookup.php":
                    case "memberlist.php":
                    case "edit_account.php":
                    case "ra_tracker.php":
                    case "rse_tracker.php":
                    case "snoo_tracker.php":
                    case "rising_tracker.php":
                    case "rank_matching.php":
                        break;
                    default:
                        $redirect = "index.php";
                        break;
                }
                header("Location: " . $redirect);
                die("Redirecting to: " . $redirect);
            } else {
                // Redirect user to private page
                header("Location: index.php");
                die("Redirecting to: index.php");
            }
        } else {
            print("Login Failed.");
            // Show the same username they entered so they only have to redo the password.
            // htmlentities to prevent XSS injection. Always use this to sanitize user input.
            $submitted_username = htmlentities($_POST['username'], ENT_QUOTES, 'UTF-8');
        }
    }
?>
<head>
    <title>RJCommand: Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<?php echo "<input type=\"hidden\" name=\"location\" value=\"";
if (isset($_GET['location'])) { echo htmlentities($_GET['location'], ENT_QUOTES, 'UTF-8'); }
echo "\" />";
?>
<div class="center">
    <div class="inner">
        <h1>Login</h1>
        <form action="login.php" method="post">
        <p class="form-label">Username:</p>
        <input type="text" name="username" size="40" value="<?php echo $submitted_username; ?>" />
        <p class="form-label">Password:</p>
        <input type="password" name="password" size="40" value="" />
        <br />
        <input type="submit" value="Login" />
        <br /><br />
        <a href="register.php">Register</a>
        </form>
    </div>
</div>