<?php

include 'connect.php';

class Users
{
    private $email;
    private $username;
    private $password;
    public $conn;

    public function __construct($email, $username, $password, $conn)
    {
        $this->email = $email;
        $this->username = $username;
        $this->password = $password;
        $this->conn = $conn;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function insertUser()
    {
        $query = "INSERT INTO users (email, username, password) VALUES (?, ?, ?)";
        $stmt = sqlsrv_query($this->conn, $query, [$this->email, $this->username, $this->password]);

        if ($stmt) {
            echo "User inserted successfully.";
        } else {
            echo "Error inserting user: ";
            die(print_r(sqlsrv_errors(), true));
        }
    }
}

// --- BASE METHOD ---
abstract class BaseMethod
{
    protected $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    protected function showAlert($title, $message, $type)
    {
        echo "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>SweetAlert</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
        <script>
            Swal.fire({
                title: '{$title}',
                text: '{$message}',
                icon: '{$type}'
            }).then(() => {
                window.history.back();
            });
        </script>
        </body>
        </html>";
    }

    abstract public function execute($data);
}

// --- SIGN UP ---
class SignUp extends BaseMethod
{
    public function execute($user)
    {
        $checkEmailQuery = "SELECT * FROM users WHERE email = ?";
        $params = [$user->getEmail()];
        $stmt = sqlsrv_query($this->conn, $checkEmailQuery, $params);

        if ($stmt === false) {
            $this->showAlert('Error!', 'Database error!', 'error');
            die(print_r(sqlsrv_errors(), true));
        }

        $exists = false;
        while (sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $exists = true;
            break;
        }

        if ($exists) {
            $this->showAlert('Error!', 'Email already exists!', 'error');
        } else {
            $hashedPassword = password_hash($user->getPassword(), PASSWORD_DEFAULT);
            $insertQuery = "INSERT INTO users (email, username, password) VALUES (?, ?, ?)";
            $insertParams = [
                $user->getEmail(),
                $user->getUsername(),
                $hashedPassword
            ];

            $insertStmt = sqlsrv_query($this->conn, $insertQuery, $insertParams);

            if ($insertStmt) {
                $this->showAlert('Success', 'Account created successfully!', 'success');
            } else {
                $this->showAlert('Error!', 'Account registration failed!', 'error');
                die(print_r(sqlsrv_errors(), true));
            }
        }
    }
}

// --- LOGIN ---
class Login extends BaseMethod
{
    public function execute($user)
    {
        $query = "SELECT id, username, password FROM users WHERE email = ?";
        $params = [$user->getEmail()];
        $stmt = sqlsrv_query($this->conn, $query, $params);

        if ($stmt === false) {
            $this->showAlert('Error!', 'Database error during login.', 'error');
            die(print_r(sqlsrv_errors(), true));
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if ($row) {
            if (password_verify($user->getPassword(), $row['password'])) {
                session_start();
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['email'] = $user->getEmail();
                $_SESSION['username'] = $row['username'];

                echo "
                <!DOCTYPE html>
                <html lang='en'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>SweetAlert</title>
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                </head>
                <body>
                <script>
                    Swal.fire({
                        title: 'Hello, {$row['username']}!',
                        text: 'Welcome back!',
                        icon: 'success'
                    }).then(() => {
                        window.location.href = 'homepage.php';
                    });
                </script>
                </body>
                </html>";
                exit();
            } else {
                $this->showAlert('Error!', 'Incorrect email or password.', 'error');
            }
        } else {
            $this->showAlert('Error!', 'User not found.', 'error');
        }
    }
}

// --- CONFIRM PASSWORD RESET ---
class ConfirmPassword extends BaseMethod
{
    public function execute($data)
    {
        $email = $data['email'];
        $newPassword = $data['newPassword'];
        $confirmPassword = $data['confirmPassword'];

        if ($newPassword !== $confirmPassword) {
            $this->showAlert('Error!', 'Passwords do not match.', 'error');
            return;
        }

        $checkEmailQuery = "SELECT * FROM users WHERE email = ?";
        $checkStmt = sqlsrv_query($this->conn, $checkEmailQuery, [$email]);

        if ($checkStmt === false) {
            $this->showAlert('Error!', 'Error checking email.', 'error');
            die(print_r(sqlsrv_errors(), true));
        }

        $userExists = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);

        if ($userExists) {
            $hashedPassword = password_hash($confirmPassword, PASSWORD_DEFAULT);
            $updateQuery = "UPDATE users SET password = ? WHERE email = ?";
            $updateStmt = sqlsrv_query($this->conn, $updateQuery, [$hashedPassword, $email]);

            if ($updateStmt) {
                echo "
                <!DOCTYPE html>
                <html lang='en'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Success</title>
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                </head>
                <body>
                <script>
                    Swal.fire({
                        title: 'Success',
                        text: 'Password updated successfully!',
                        icon: 'success'
                    }).then(() => {
                        window.location.href = 'index.php';
                    });
                </script>
                </body>
                </html>";
                exit();
            } else {
                $this->showAlert('Error!', 'Failed to update password.', 'error');
            }
        } else {
            $this->showAlert('Error!', 'Email not found.', 'error');
        }
    }
}

// --- ROUTING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['signUp'])) {
        $user = new Users($_POST['email'], $_POST['username'], $_POST['password'], $conn);
        $signUp = new SignUp($conn);
        $signUp->execute($user);
    }

    if (isset($_POST['login'])) {
        $user = new Users($_POST['email'], null, $_POST['password'], $conn);
        $login = new Login($conn);
        $login->execute($user);
    }

    if (isset($_POST['Confirm'])) {
        $data = [
            'email' => $_POST['email'],
            'newPassword' => $_POST['password'],
            'confirmPassword' => $_POST['newPassword']
        ];
        $confirmPassword = new ConfirmPassword($conn);
        $confirmPassword->execute($data);
    }
}
