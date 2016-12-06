<?php
require("common.php");
if (isset($_POST['email'])) {
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        die("Invalid email!");
    }
    $query = "SELECT 
      id, username, email, salt
    FROM users
    WHERE email = :email
    ";
    $query_params = array(':email' => $_POST['email']);
    try {
        $stmt = $db->prepare($query);
        if ($stmt->execute($query_params)) {
            $row = $stmt->fetch();
            $url = "www.rjcommand.space/reset.php?q=" . $salt . "&i=" . $row['id'];
            mail($row['email'], "RJCommand Password Reset",
                "Hello $username,\n
                You, or someone who knows your email, has requested
                a password reset on rjcommand.space. If this wasn't you,
                please drown your sorrows in the nearest bourbon.\n
                Otherwise, visit $url to reset your password.",
                'From: webmaster@example.com' . "\r\n" .
                'Reply-To: webmaster@example.com' . "\r\n" .
                'X-Mailer: PHP/' . phpversion());
            echo("Password reset mail sent.");
        }
    } catch (PDOException $ex) {
        die("Failed to retrieve user details");
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
    <form action="forgot.php" method="post">
        <p class="form-label">Email Address: </p>
        <input type="text" name="email" /><br />
        <input type="submit" value="Send Reset Email" />
    </form>
    <a href="login.php">Return</a>
</div>
</body>

</html>
