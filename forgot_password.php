<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/forgot_pass.css">
</head>

<body>
    <div class="container">
        <div class="form-container">
            <h1>Forgot Password</h1>
            <form action="register.php" method="post">
                <label for="email">Email:</label>
                <input type="email" name="email" placeholder="Enter email" required>
                <label for="password">New Password:</label>
                <input type="password" name="password" placeholder="Enter new password" required>
                <label for="newPassword">Confirm Password:</label>
                <input type="password" name="newPassword" placeholder="Enter Confirm password" required>
                <a href="index.php">Back to Login</a>
                <button type="submit" name="Confirm">Reset Password</button>
            </form>
        </div>
    </div>
</body>

</html>