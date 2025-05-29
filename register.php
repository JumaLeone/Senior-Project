<?php

include 'connect.php';

class Users {
    private $email;
    private $username;
    private $password;
    private $conn;

    // Constructor with database connection
    public function __construct($email, $username, $password) {
        $this->email = $email;
        $this->username = $username;
        $this->password = $password;
        $this->conn = $this->getDbConnection(); // Establishing connection
    }

    // Database connection function using PDO for MSSQL
    private function getDbConnection() {
        try {
            // Replace with your actual MSSQL connection details
            $dsn = "sqlsrv:server=;
            Database=your_database";
            $username = "your_username";
            $password = "your_password";
            $conn = new PDO($dsn, $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
    }

    // Getter for email
    public function getEmail() {
        return $this->email;
    }

    // Getter for username
    public function getUsername() {
        return $this->username;
    }

    // Getter for password
    public function getPassword() {
        return $this->password;
    }

    // Method to insert user into the database
    public function insertUser() {
        $query = "INSERT INTO Users (email, username, password) VALUES (:email, :username, :password)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':password', $this->password);
        try {
            $stmt->execute();
            echo "User inserted successfully.";
        } catch (PDOException $e) {
            echo "Error inserting user: " . $e->getMessage();
        }
    }
}

abstract class BaseMethod {
    protected $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    protected function showAlert($title, $message, $type) {
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

class SignUp extends BaseMethod {
    public function execute($user) {
        $checkEmailQuery = "SELECT * FROM users WHERE email = ?";
        $params = array($user->getEmail());

        // Use sqlsrv_query with parameters
        $stmt = sqlsrv_query($this->conn, $checkEmailQuery, $params);

        if ($stmt === false) {
            $this->showAlert('Error!', 'Database error!', 'error');
            die(print_r(sqlsrv_errors(), true));
        }

        // Count rows
        $rows = 0;
        while (sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows++;
        }

        if ($rows > 0) {
            $this->showAlert('Error!', 'Email already exists!', 'error');
        } else {
            $hashedPassword = password_hash($user->getPassword(), PASSWORD_DEFAULT);
            $insertQuery = "INSERT INTO users (email, username, password) VALUES (?, ?, ?)";
            $insertParams = array(
                $user->getEmail(),
                $user->getUsername(),
                $hashedPassword
            );

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


class Login extends BaseMethod {
    public function execute($user) {
        // Using parameterized query with SQL Server (avoid SQL Injection)
        $query = "SELECT id, username, password FROM users WHERE email = ?";
        $params = array($user->getEmail());

        // Execute the query
        $stmt = sqlsrv_query($this->conn, $query, $params);

        if ($stmt === false) {
            $this->showAlert('Error!', 'Database error during login.', 'error');
            die(print_r(sqlsrv_errors(), true)); // Print errors if query fails
        }

        // Fetch the result
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if ($row) {
            // Verify password
            if (password_verify($user->getPassword(), $row['password'])) {
                session_start();
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['email'] = $user->getEmail();
                $_SESSION['username'] = $row['username'];

                // Show success message using SweetAlert
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


class ConfirmPassword extends BaseMethod {
    public function execute($data) {
        $email = $data['email'];
        $newPassword = $data['newPassword'];
        $confirmPassword = $data['confirmPassword'];

        if ($newPassword !== $confirmPassword) {
            $this->showAlert('Error!', 'Passwords do not match.', 'error');
            return;
        }

        $checkEmailQuery = "SELECT * FROM users WHERE email = '" . $email . "'";
        $result = $this->conn->query($checkEmailQuery);

        if ($result->num_rows > 0) {
            $hashedPassword = password_hash($confirmPassword, PASSWORD_DEFAULT);
            $updatePasswordQuery = "UPDATE users SET password = '" . $hashedPassword . "' WHERE email = '" . $email . "'";
            if ($this->conn->query($updatePasswordQuery)) {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['signUp'])) {
        $user = new Users($_POST['email'], $_POST['username'], $_POST['password']);
        $signUp = new SignUp($conn);
        $signUp->execute($user);
    }

    if (isset($_POST['login'])) {
        $user = new Users($_POST['email'], null, $_POST['password']);
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
?>