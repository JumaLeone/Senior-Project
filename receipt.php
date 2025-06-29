<?php
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Database Connection ---
$serverName = "TITANIUM-VORTEX\\SQLEXPRESS";
$connectionOptions = [
    "Database" => "php_project",
    "Uid" => "",
    "PWD" => ""
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
    die("<h2>Database Connection Failed</h2><pre>" . print_r(sqlsrv_errors(), true) . "</pre>");
}

// --- Get invoice ID ---
$invoiceId = $_GET['invoice_id'] ?? null;
if (!$invoiceId) {
    die('<h2>Invoice ID Required</h2>
        <p>Please provide an invoice ID in the URL like:</p>
        <code>receipt.php?invoice_id=YOUR_INVOICE_ID</code>');
}

try {
    // --- Fetch payment info ---
    $sql = "SELECT TOP 1 p.*, pr.property_type, pr.location 
            FROM payments p
            JOIN properties pr ON p.property_id = pr.id
            WHERE p.invoice_id = ?";

    $stmt = sqlsrv_query($conn, $sql, [$invoiceId]);
    if (!$stmt) {
        throw new Exception("Database query failed: " . print_r(sqlsrv_errors(), true));
    }

    $payment = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$payment) {
        throw new Exception("No payment found for Invoice ID: $invoiceId");
    }

    // --- Generate receipt HTML ---
    $receiptHtml = generateReceiptHtml($payment, $invoiceId);

    // --- Generate PDF from HTML ---
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A5',
        'margin_top' => 10,
        'margin_bottom' => 10,
        'margin_left' => 10,
        'margin_right' => 10
    ]);

    $mpdf->SetTitle("Receipt $invoiceId");
    $mpdf->WriteHTML($receiptHtml);

    // --- Capture PDF output as string (for email) ---
    $pdfContent = $mpdf->Output('', 'S');

    // --- Send receipt via email ---
    $buyerEmail = $payment['sent_to'] ?? 'fallback@example.com'; // Update if your column is named differently

    $mail = new PHPMailer(true);
    try {
        $mail->SMTPDebug = 2; // Log debug info to error_log
        $mail->Debugoutput = 'error_log';

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'jumaleone42@gmail.com';
        $mail->Password = 'owatlklxodvhvxze'; // App password with NO SPACES
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('jumaleone42@gmail.com', 'KeyNest');
        $mail->addAddress($buyerEmail);
        $mail->addBCC('jumaleone42@gmail.com'); // You get a copy

        $mail->isHTML(true);
        $mail->Subject = 'Your Payment Receipt - KeyNest Properties';
        $mail->Body = "
            Dear customer,<br><br>
            Attached is your payment receipt for invoice ID: <strong>$invoiceId</strong>.<br><br>
            Thank you for choosing KeyNest Properties.<br><br>
            Regards,<br>
            KeyNest Team
        ";

        $mail->addStringAttachment($pdfContent, "Receipt_$invoiceId.pdf");
        $mail->send();
        error_log("Receipt email sent to $buyerEmail");
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
    }

    // --- Output PDF to browser ---
    $mpdf->Output("receipt_$invoiceId.pdf", 'D');
    exit;

} catch (Exception $e) {
    die("<h2>Error Generating Receipt</h2>
        <p>{$e->getMessage()}</p>
        <p>Please contact support with this error message.</p>");
}

// --- HTML Receipt Generator Function ---
function generateReceiptHtml(array $payment, string $invoiceId): string {
    $date = isset($payment['created_at']) ? 
        date_format($payment['created_at'], 'M j, Y H:i') : 
        date('M j, Y H:i');

    $amount = isset($payment['amount']) ? number_format($payment['amount'], 2) : '0.00';
    $propertyType = htmlspecialchars($payment['property_type'] ?? 'Unknown');
    $location = htmlspecialchars($payment['location'] ?? 'Unknown');
    $phone = htmlspecialchars($payment['phone'] ?? 'N/A');
    $status = htmlspecialchars($payment['status'] ?? 'Unknown');

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 15px; }
        .logo { font-size: 18px; font-weight: bold; color: #2c3e50; }
        .title { font-size: 16px; margin: 5px 0; text-transform: uppercase; }
        .divider { border-top: 1px dashed #000; margin: 10px 0; }
        .details { margin: 15px 0; }
        .row { display: flex; margin: 5px 0; }
        .label { font-weight: bold; width: 120px; }
        .footer { margin-top: 20px; font-size: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">KeyNest Properties</div>
        <div class="title">Payment Receipt</div>
    </div>
    
    <div class="divider"></div>
    
    <div class="details">
        <div class="row">
            <span class="label">Invoice ID:</span>
            <span>$invoiceId</span>
        </div>
        <div class="row">
            <span class="label">Date:</span>
            <span>$date</span>
        </div>
        <div class="row">
            <span class="label">Amount:</span>
            <span>KES $amount</span>
        </div>
        <div class="row">
            <span class="label">Property:</span>
            <span>$propertyType in $location</span>
        </div>
        <div class="row">
            <span class="label">Phone:</span>
            <span>$phone</span>
        </div>
        <div class="row">
            <span class="label">Status:</span>
            <span>$status</span>
        </div>
    </div>
    
    <div class="divider"></div>
    
    <div class="footer">
        <p>Thank you for your business!</p>
        <p>This is a system generated receipt. Please contact support for any issues.</p>
    </div>
</body>
</html>
HTML;
}
?>
