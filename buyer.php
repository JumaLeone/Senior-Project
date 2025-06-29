<?php
session_start();
include('connect.php');

abstract class Base {
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

class SubmitBuyer extends Base {
    public function execute($data)
    {
        $data['user_id'] = $_SESSION['user_id'];
        
        // ✅ START DATABASE TRANSACTION
        if (sqlsrv_begin_transaction($this->conn) === false) {
            error_log("Failed to start transaction: " . print_r(sqlsrv_errors(), true));
            $this->showAlert('Error!', 'Database connection error. Please try again.', 'error');
            return;
        }
        
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
            // ✅ ROLLBACK TRANSACTION ON ERROR
            sqlsrv_rollback($this->conn);
            error_log("Database error in SubmitBuyer: " . print_r(sqlsrv_errors(), true));
            $this->showAlert('Error!', 'There was an error submitting your request. Please try again.', 'error');
            return; // Exit early on error
        }
        
        // ✅ COMMIT TRANSACTION TO ENSURE DATA IS SAVED
        if (sqlsrv_commit($this->conn) === false) {
            sqlsrv_rollback($this->conn);
            error_log("Failed to commit transaction: " . print_r(sqlsrv_errors(), true));
            $this->showAlert('Error!', 'Failed to save your request. Please try again.', 'error');
            sqlsrv_free_stmt($stmt);
            return;
        }
        
        // ✅ FREE THE STATEMENT AFTER SUCCESSFUL COMMIT
        sqlsrv_free_stmt($stmt);
        
        // ✅ Now handle payment logic AFTER database transaction is complete
        if ($data['payment_method'] === 'mpesa') {
            // Get the buyer ID that was just inserted for payment tracking
            $getBuyerIdQuery = "SELECT TOP 1 id FROM buyers WHERE user_id = ? AND property_id = ? ORDER BY id DESC";
            $getBuyerIdParams = [$data['user_id'], $data['property_id']];
            $buyerIdStmt = sqlsrv_query($this->conn, $getBuyerIdQuery, $getBuyerIdParams);
            
            $buyerId = null;
            if ($buyerIdStmt && $row = sqlsrv_fetch_array($buyerIdStmt, SQLSRV_FETCH_ASSOC)) {
                $buyerId = $row['id'];
                sqlsrv_free_stmt($buyerIdStmt);
            }
            
            // Store buyer info in session for payment processing
            $_SESSION['payment'] = [
                'buyer_id'    => $buyerId, // ✅ Include buyer ID for reference
                'property_id' => $data['property_id'],
                'fullname'    => $data['fullname'],
                'email'       => $data['email'],
                'phone'       => $data['phone'],
                'amount'      => $data['amount']
            ];
            
            // Redirect to process_payment.php for M-Pesa
            header("Location: process_payment.php");
            exit();
        } else {
            // For cash payments, show success message
            $this->showAlert('Success!', 'Your purchase request has been submitted and is pending admin approval.', 'success');
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submitBuyer'])) {
        // Validate required session data
        if (!isset($_SESSION['user_id'])) {
            header("Location: login.php");
            exit();
        }
        
        $data = [
            'property_id'    => $_POST['property_id'],
            'fullname'       => $_POST['fullname'],
            'email'          => $_POST['email'],
            'phone'          => $_POST['phone'],
            'message'        => $_POST['message'],
            'occupation'     => $_POST['occupation'],
            'payment_method' => $_POST['payment_method'],
            'amount'         => $_POST['property_price'] // sent from the form
        ];
        
        $submitBuyer = new SubmitBuyer($conn);
        $submitBuyer->execute($data);
    }
}
?>