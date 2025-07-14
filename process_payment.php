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
    $amount      = $_POST['property_price'];

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
            'timeout' => 30,
            'connect_timeout' => 10
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

            // Send Receipt Email and SMS
            sendAutoReceipt($responseData['invoice']['invoice_id'], $email, $phone_number);

            // Redirect to success
            header("Location: payment_success.php?invoice_id=" . $responseData['invoice']['invoice_id']);
            exit();
        } else {
            header("Location: payment_error.php?error=" . urlencode("Payment STK Push Failed"));
            exit();
        }

    } catch (\GuzzleHttp\Exception\RequestException $e) {
        $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'No response';
        $errorLog = "STK Push Failed:\n" .
                    "Message: " . $e->getMessage() . "\n" .
                    "Response: " . $errorBody . "\n" .
                    "Phone: $phone_number, Amount: $amount\n" .
                    "Date: " . date('Y-m-d H:i:s') . "\n\n";

        // Ensure logs folder exists
        if (!file_exists(__DIR__ . '/logs')) {
            mkdir(__DIR__ . '/logs', 0777, true);
        }

        file_put_contents(__DIR__ . '/logs/payment_errors.log', $errorLog, FILE_APPEND);

        header("Location: payment_error.php?error=" . urlencode("Payment failed or timed out. Please try again."));
        exit();
    }
}

// --- Send receipt email and SMS using receipt.php logic ---
function sendAutoReceipt($invoiceId, $buyerEmail, $buyerPhone) {
    global $conn;

    $sql = "SELECT TOP 1 p.*, pr.property_type, pr.location 
            FROM payments p
            JOIN properties pr ON p.property_id = pr.id
            WHERE p.invoice_id = ?";
    $stmt = sqlsrv_query($conn, $sql, [$invoiceId]);
    $payment = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$payment) return;

    $date = isset($payment['created_at']) ? date_format($payment['created_at'], 'M j, Y H:i') : date('M j, Y H:i');
    $amount = isset($payment['amount']) ? number_format($payment['amount'], 2) : '0.00';
    $propertyType = htmlspecialchars($payment['property_type'] ?? 'Unknown');
    $location = htmlspecialchars($payment['location'] ?? 'Unknown');
    $status = htmlspecialchars($payment['status'] ?? 'Unknown');

    $receiptHtml = <<<HTML
    <html><body>
        <h2>KeyNest Properties</h2>
        <h3>Payment Receipt</h3>
        <p><strong>Invoice ID:</strong> $invoiceId</p>
        <p><strong>Date:</strong> $date</p>
        <p><strong>Amount:</strong> KES $amount</p>
        <p><strong>Property:</strong> $propertyType in $location</p>
        <p><strong>Status:</strong> $status</p>
    </body></html>
    HTML;

    // PDF Generation with custom temp directory
    try {
        // Create custom temp directory for mPDF
        $tempDir = __DIR__ . '/temp/mpdf';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        // Initialize mPDF with custom temp directory
        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A5',
            'tempDir' => $tempDir,
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
        ]);
        
        $mpdf->WriteHTML($receiptHtml);
        $pdfContent = $mpdf->Output('', 'S');

        // Email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'jumaleone42@gmail.com';
            $mail->Password = 'owatlklxodvhvxze';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('jumaleone42@gmail.com', 'KeyNest');
            $mail->addAddress($buyerEmail);
            $mail->Subject = "Payment Receipt - Invoice #$invoiceId";
            $mail->Body = "Attached is your payment receipt.";
            $mail->addStringAttachment($pdfContent, "Receipt_$invoiceId.pdf");
            $mail->send();
        } catch (Exception $e) {
            error_log("Email send error: " . $mail->ErrorInfo);
        }

        // SMS
        $smsMessage = "Hello, your KeyNest receipt (Invoice ID: $invoiceId) has been sent to your email.";
        $payload = json_encode([ "to" => $buyerPhone, "message" => $smsMessage ]);
        $headers = [
            "Authorization: Bearer ISSecretKey_live_9d79df69-1af4-4a7d-9ba9-ffe1caff70f7",
            "Content-Type: application/json"
        ];
        $ch = curl_init("https://api.intasend.com/v1/sms/send");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        if (curl_errno($ch)) error_log("SMS cURL Error: " . curl_error($ch));
        curl_close($ch);

    } catch (\Mpdf\MpdfException $e) {
        error_log("PDF generation error: " . $e->getMessage());
        
        // Send email without PDF attachment as fallback
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'jumaleone42@gmail.com';
            $mail->Password = 'owatlklxodvhvxze';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('jumaleone42@gmail.com', 'KeyNest');
            $mail->addAddress($buyerEmail);
            $mail->Subject = "Payment Receipt - Invoice #$invoiceId";
            $mail->isHTML(true);
            $mail->Body = $receiptHtml;
            $mail->send();
        } catch (Exception $e) {
            error_log("Fallback email send error: " . $mail->ErrorInfo);
        }
    }
}
?>