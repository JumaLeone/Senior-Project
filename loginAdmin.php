<?php
include 'connect.php';

class Users {
    private $email;
    private $username;
    private $password;

    public function __construct($email, $username, $password) {
        $this->email = $email;
        $this->username = $username;
        $this->password = $password;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getPassword() {
        return $this->password;
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
        $checkEmailQuery = "SELECT * FROM admin_users WHERE email = ?";
        $params = array($user->getEmail());
        $result = sqlsrv_query($this->conn, $checkEmailQuery, $params);

        if ($result === false) {
            $this->showAlert('Error!', 'Query failed!', 'error');
            return;
        }

        if (sqlsrv_has_rows($result)) {
            $this->showAlert('Error!', 'Email already exists!', 'error');
        } else {
            $hashedPassword = password_hash($user->getPassword(), PASSWORD_DEFAULT);
            $insertQuery = "INSERT INTO admin_users (email, username, password) VALUES (?, ?, ?)";
            $insertParams = array($user->getEmail(), $user->getUsername(), $hashedPassword);

            $insertResult = sqlsrv_query($this->conn, $insertQuery, $insertParams);

            if ($insertResult) {
                $this->showAlert('Success', 'Account created successfully!', 'success');
            } else {
                $this->showAlert('Error!', 'Account registration failed!', 'error');
            }
        }
    }
}


class Login extends BaseMethod {
    public function execute($user) {
        $query = "SELECT id, username, password FROM admin_users WHERE email = ?";
        $params = array($user->getEmail());
        $result = sqlsrv_query($this->conn, $query, $params);

        if ($result === false) {
            $this->showAlert('Error!', 'Query failed.', 'error');
            return;
        }

        if (sqlsrv_has_rows($result)) {
            $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
            if ($row && password_verify($user->getPassword(), $row['password'])) {
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
                        window.location.href = 'adminhome.php';
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

        $checkEmailQuery = "SELECT * FROM admin_users WHERE email = '" . $email . "'";
        $result = $this->conn->query($checkEmailQuery);

        if ($result->num_rows > 0) {
            $hashedPassword = password_hash($confirmPassword, PASSWORD_DEFAULT);
            $updatePasswordQuery = "UPDATE admin_users SET password = '" . $hashedPassword . "' WHERE email = '" . $email . "'";
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
                        window.location.href = 'indexAdmin.php';
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