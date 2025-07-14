<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login and Register</title>
    <link rel="stylesheet" href="./css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container">
        <div class="form-container">
            <div class="form-toggle">
                <button id="registerBtn" class="toggle-btn active">Register</button>
                <button id="loginBtn" class="toggle-btn">Login</button>
            </div>

            <form id="registerForm" action="adminhome.php" method="post" class="form">
                <h1>Register</h1>
                <label for="email">Email:</label>
                <input type="email" name="email" placeholder="Enter email" required>
                <label for="username">Username:</label>
                <input type="text" name="username" placeholder="Enter username" required>
                <label for="password">Password:</label>
                <input type="password" name="password" placeholder="Enter password" required>
                <button type="submit" name="signUp">Sign Up</button>
            </form>

            <form id="loginForm" action="adminhome.php" method="post" class="form hidden">
                <h1>Login</h1>
                <label for="username">Email:</label>
                <input type="text" name="email" placeholder="Enter email" required>
                <label for="password">Password:</label>
                <input type="password" name="password" placeholder="Enter password" required>
                <p><a href="forgot_password.php">Forgot Password?</a></p>
                <button type="submit" name="login">Login</button>
            </form>
        </div>
    </div>
    <script src="login.js"></script>
</body>

</html>