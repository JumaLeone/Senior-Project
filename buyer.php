<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Verify the request is POST and has the required data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Invalid Request',
                text: 'This page only accepts POST requests',
                confirmButtonColor: '#d33'
            }).then(() => {
                window.history.back();
            });
        </script>
    </body>
    </html>
    <?php
    exit();
}

if (!isset($_POST['submitBuyer'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Form Error',
                text: 'Form not submitted correctly. Missing submit button data.',
                confirmButtonColor: '#d33'
            }).then(() => {
                window.history.back();
            });
        </script>
    </body>
    </html>
    <?php
    exit();
}

// Verify session
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include('connect.php');
if (!$conn) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Database Error',
                text: 'Database connection failed. Please try again later.',
                confirmButtonColor: '#d33'
            }).then(() => {
                window.history.back();
            });
        </script>
    </body>
    </html>
    <?php
    exit();
}

// Verify all required fields exist
$required = ['property_id', 'fullname', 'email', 'phone', 'occupation', 'payment_method'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        </head>
        <body>
            <script>
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Information',
                    text: 'Missing required field: <?php echo ucfirst(str_replace('_', ' ', $field)); ?>',
                    confirmButtonColor: '#f39c12'
                }).then(() => {
                    window.history.back();
                });
            </script>
        </body>
        </html>
        <?php
        exit();
    }
}

// Process the form data
try {
    $query = "INSERT INTO buyers (property_id, fullname, email, phone, message, user_id, occupation, status)
              VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
    
    $params = [
        $_POST['property_id'],
        $_POST['fullname'],
        $_POST['email'],
        $_POST['phone'],
        $_POST['message'] ?? '',
        $_SESSION['user_id'],
        $_POST['occupation']
    ];
    
    $stmt = sqlsrv_query($conn, $query, $params);
    
    if ($stmt === false) {
        throw new Exception("Database error: " . print_r(sqlsrv_errors(), true));
    }
    
    // Handle payment method
    if ($_POST['payment_method'] === 'mpesa') {
        $_SESSION['payment'] = [
            'property_id' => $_POST['property_id'],
            'fullname' => $_POST['fullname'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'amount' => $_POST['property_price'] ?? 0
        ];
        
        // SweetAlert for M-Pesa redirect
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        </head>
        <body>
            <script>
                Swal.fire({
                    icon: 'info',
                    title: 'Redirecting to Payment',
                    text: 'You will be redirected to M-Pesa payment processing...',
                    timer: 2000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                }).then(() => {
                    window.location.href = 'process_payment.php';
                });
            </script>
        </body>
        </html>
        <?php
        exit();
    } else {
        // For bank payment, show success message
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        </head>
        <body>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Request Submitted!',
                    text: 'Your request has been submitted for approval. You will be notified once it\'s processed.',
                    confirmButtonText: 'Continue',
                    confirmButtonColor: '#28a745'
                }).then(() => {
                    window.location.href = 'homepage.php';
                });
            </script>
        </body>
        </html>
        <?php
        exit();
    }

} catch (Exception $e) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Processing Error',
                text: 'There was an error processing your request. Please try again.',
                confirmButtonColor: '#d33'
            }).then(() => {
                window.history.back();
            });
        </script>
    </body>
    </html>
    <?php
    exit();
}
?>