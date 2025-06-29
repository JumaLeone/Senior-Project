<?php
require_once __DIR__ . '/vendor/autoload.php';
include('connect.php'); // Ensure this sets up $conn using sqlsrv_connect

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $property_id = $_POST['property_id'];
    $fullname    = $_POST['fullname'];
    $email       = $_POST['email'];
    $phone       = $_POST['phone'];
    $amount      = $_POST['property_price']; // From hidden input

    // Format phone number (Safaricom format)
    $phone_number = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone_number, 0, 1) == '0') {
        $phone_number = '254' . substr($phone_number, 1);
    }

    try {
        // Send STK Push via IntaSend
        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', 'https://api.intasend.com/api/v1/payment/mpesa-stk-push/', [
            'body' => json_encode([
                'amount' => $amount,
                'phone_number' => $phone_number
            ]),
            'headers' => [
                'accept' => 'application/json',
                'authorization' => 'Bearer ISSecretKey_live_9d79df69-1af4-4a7d-9ba9-ffe1caff70f7',
                'content-type' => 'application/json',
            ],
        ]);

        $responseData = json_decode($response->getBody(), true);

        if ($responseData && isset($responseData['invoice']['state']) && $responseData['invoice']['state'] === 'PENDING') {
            // Save to database
            $sql = "INSERT INTO payments (
                invoice_id, 
                property_id, 
                amount, 
                phone, 
                status, 
                payment_method,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, GETDATE())";

            $params = [
                $responseData['invoice']['invoice_id'],
                $property_id,
                $amount,
                $phone_number,
                'pending',
                'mpesa'
            ];

            $stmt = sqlsrv_prepare($conn, $sql, $params);
            if (!sqlsrv_execute($stmt)) {
                error_log("DB insert failed: " . print_r(sqlsrv_errors(), true));
            }

            // Generate a simple receipt (HTML only)
            generateReceipt(
                $responseData['invoice']['invoice_id'],
                $amount,
                $phone_number,
                $email
            );

            // Redirect to success
            header("Location: payment_success.php?invoice_id=" . $responseData['invoice']['invoice_id']);
            exit();
        } else {
            // Payment failed at API level
            header("Location: payment_error.php?error=Payment STK Push Failed");
            exit();
        }
    } catch (Exception $e) {
        header("Location: payment_error.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}

// --- Receipt Generator Function ---
function generateReceipt($invoiceId, $amount, $phone, $email)
{
    global $conn;

    $receiptContent = "
        <html>
        <body>
            <h2>Payment Receipt</h2>
            <p><strong>Invoice ID:</strong> $invoiceId</p>
            <p><strong>Amount:</strong> KES " . number_format($amount, 2) . "</p>
            <p><strong>Phone:</strong> $phone</p>
            <p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>
            <p><strong>Status:</strong> Pending Confirmation</p>
        </body>
        </html>
    ";

    // Save receipt in DB (optional table: receipts)
    $sql = "INSERT INTO receipts (invoice_id, content, sent_to) VALUES (?, ?, ?)";
    $params = [$invoiceId, $receiptContent, $email];
    sqlsrv_query($conn, $sql, $params);

    // Optionally send via email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'your-smtp-server.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'noreply@yourdomain.com';
        $mail->Password = 'yourpassword';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('noreply@yourdomain.com', 'KeyNest');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Payment Receipt - Invoice ' . $invoiceId;
        $mail->Body    = $receiptContent;
        $mail->send();
    } catch (Exception $e) {
        error_log("Receipt email failed: " . $mail->ErrorInfo);
    }

    // Optionally send SMS here too (via Africa's Talking, Twilio, etc.)
}
?>
