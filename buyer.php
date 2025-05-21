<?php
session_start();
include('connect.php');

abstract class Base {
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
            window.location.href = 'homepage.php';
            });
        </script>
        </body>
        </html>";
    }

    abstract public function execute($data);
}

class SubmitBuyer extends Base {
    public function execute($data) {
        // Ensure user_id is taken from session
        $data['user_id'] = $_SESSION['user_id'];

        // Use sqlsrv_query to execute the SQL query
        $query = "INSERT INTO buyers (property_id, fullname, email, phone, message, user_id) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        // Prepare the statement
        $params = array(
            $data['property_id'], 
            $data['fullname'], 
            $data['email'], 
            $data['phone'], 
            $data['message'], 
            $data['user_id']
        );
        
        // Execute the query with sqlsrv
        $stmt = sqlsrv_query($this->conn, $query, $params);

        // Check if the query was successful
        if ($stmt === false) {
            // Show error if query fails
            $this->showAlert('Error!', 'There was an error submitting your request.', 'error');
        } else {
            // Show success message if query succeeds
            $this->showAlert('Success!', 'Your purchase request has been submitted.', 'success');
        }

        // Free the statement after use
        sqlsrv_free_stmt($stmt);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submitBuyer'])) {
        // Collect form data
        $data = [
            'property_id' => $_POST['property_id'],
            'fullname' => $_POST['fullname'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'message' => $_POST['message']
        ];

        // Create an instance of SubmitBuyer and execute
        $submitBuyer = new SubmitBuyer($conn);
        $submitBuyer->execute($data);
    }
}

?>