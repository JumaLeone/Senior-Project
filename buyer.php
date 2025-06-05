<?php
session_start();
include('connect.php');

abstract class Base
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
                window.location.href = 'homepage.php';
            });
        </script>
        </body>
        </html>";
    }

    abstract public function execute($data);
}

class SubmitBuyer extends Base
{
    public function execute($data)
    {
        $data['user_id'] = $_SESSION['user_id'];

        $query = "INSERT INTO buyers (property_id, fullname, email, phone, message, user_id, occupation) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";

        $params = array(
            $data['property_id'],
            $data['fullname'],
            $data['email'],
            $data['phone'],
            $data['message'],
            $data['user_id'],
            $data['occupation']
        );

        $stmt = sqlsrv_query($this->conn, $query, $params);

        if ($stmt === false) {
            $this->showAlert('Error!', 'There was an error submitting your request.', 'error');
        } else {
            $this->showAlert('Success!', 'Your purchase request has been submitted.', 'success');
        }

        sqlsrv_free_stmt($stmt);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submitBuyer'])) {
        $data = [
            'property_id' => $_POST['property_id'],
            'fullname'    => $_POST['fullname'],
            'email'       => $_POST['email'],
            'phone'       => $_POST['phone'],
            'message'     => $_POST['message'],
            'occupation'  => $_POST['occupation'] // Make sure this matches your HTML field
        ];

        $submitBuyer = new SubmitBuyer($conn);
        $submitBuyer->execute($data);
    }
}
?>
