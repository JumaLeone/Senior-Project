<?php  
require_once __DIR__ . '/vendor/autoload.php';
include('connect.php');

$invoiceId = $_GET['invoice_id'] ?? 'N/A';
$amount = "0.00";
$phone = "N/A";
$property = "N/A";

// Fetch from DB if invoice ID exists
if ($invoiceId !== 'N/A') {
    $sql = "SELECT p.amount, p.phone, pr.property_type, pr.location 
            FROM payments p
            JOIN properties pr ON p.property_id = pr.id
            WHERE p.invoice_id = ?";
    $stmt = sqlsrv_query($conn, $sql, [$invoiceId]);
    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $amount = number_format($row['amount'], 2);
        $phone = $row['phone'];
        $property = $row['property_type'] . " in " . $row['location'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>     
    <meta charset="UTF-8">     
    <meta name="viewport" content="width=device-width, initial-scale=1.0">     
    <title>Payment Success</title>     
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">     
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css"> 
</head> 
<body>     
    <div class="container mt-5">         
        <div id="fallback-content" class="alert alert-success" style="display: none;">             
            <h4>Payment Initiated Successfully!</h4>             
            <p>Check your phone to complete the M-Pesa payment.</p>             
            <p>Invoice ID: <?= htmlspecialchars($invoiceId) ?></p>
            <p>Amount: KES <?= htmlspecialchars($amount) ?></p>
            <p>Property: <?= htmlspecialchars($property) ?></p>             
            <a href="searching.php" class="btn btn-primary">Back to Properties</a>             
            <a href="#" onclick="downloadReceipt(invoiceId)" class="btn btn-success ms-2">Download Receipt</a>         
        </div>     
    </div>

    <!-- Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>     
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>     
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">     

    <!-- Custom Variables from PHP -->
    <script>
        const invoiceId = <?= json_encode($invoiceId) ?>;
        const amount = <?= json_encode($amount) ?>;
        const phone = <?= json_encode($phone) ?>;
        const property = <?= json_encode($property) ?>;

        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                title: 'Payment Initiated Successfully!',
                html: `
                    <div class="text-center">
                        <i class="fas fa-mobile-alt fa-3x text-success mb-3"></i>
                        <p class="mb-2">Check your phone to complete the M-Pesa payment.</p>
                        <p><strong>Invoice ID:</strong> ${invoiceId}</p>
                        <p><strong>Amount:</strong> KES ${amount}</p>
                        <p><strong>Property:</strong> ${property}</p>
                    </div>
                `,
                icon: 'success',
                confirmButtonText: 'Back to Properties',
                confirmButtonColor: '#007bff',
                denyButtonText: 'Download Receipt',
                denyButtonColor: '#28a745',
                showDenyButton: true,
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'searching.php';
                } else if (result.isDenied) {
                    downloadReceipt(invoiceId, amount, phone, property);
                }
            });
        });

        window.addEventListener('error', function () {
            document.getElementById('fallback-content').style.display = 'block';
        });

        function downloadReceipt(invoiceId, amount, phone, property) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            const currentDate = new Date().toLocaleDateString();
            const currentTime = new Date().toLocaleTimeString();

            doc.setFontSize(20);
            doc.setFont("helvetica", "bold");
            doc.text("PAYMENT RECEIPT", 105, 30, { align: "center" });

            doc.setFontSize(12);
            doc.setFont("helvetica", "normal");
            doc.text("KeyNest Allocation Systems", 105, 45, { align: "center" });
            doc.text("Email: okothleone42@gmail.com", 105, 55, { align: "center" });
            doc.text("Phone: +254 708 941 090", 105, 65, { align: "center" });

            doc.line(20, 75, 190, 75);
            doc.setFontSize(14);
            doc.setFont("helvetica", "bold");
            doc.text("Transaction Details", 20, 90);

            doc.setFontSize(11);
            doc.setFont("helvetica", "normal");
            doc.text(`Invoice ID: ${invoiceId}`, 20, 105);
            doc.text(`Phone: ${phone}`, 20, 115);
            doc.text(`Date: ${currentDate}`, 20, 125);
            doc.text(`Time: ${currentTime}`, 20, 135);
            doc.text("Payment Method: M-Pesa", 20, 145);
            doc.text("Status: Initiated", 20, 155);

            doc.setFontSize(14);
            doc.setFont("helvetica", "bold");
            doc.text("Payment Information", 20, 175);

            doc.setFontSize(11);
            doc.setFont("helvetica", "normal");
            doc.text(`Service: ${property}`, 20, 190);
            doc.text(`Amount: KES ${amount}`, 20, 200);
            doc.text("Transaction Fee: KES 0.00", 20, 210);
            doc.text(`Total: KES ${amount}`, 20, 220);

            doc.setFontSize(12);
            doc.setFont("helvetica", "bold");
            doc.text("Important Instructions:", 20, 240);
            doc.setFontSize(10);
            doc.text("1. Complete M-Pesa payment on your phone", 20, 255);
            doc.text("2. Keep this receipt for your records", 20, 265);
            doc.text("3. Contact support if payment fails in 5 minutes", 20, 275);

            doc.line(20, 285, 190, 285);
            doc.setFontSize(8);
            doc.text("This is a system-generated receipt. No signature required.", 105, 295, { align: "center" });

            doc.save(`Receipt_${invoiceId}_${currentDate.replace(/\//g, '-')}.pdf`);

            Swal.fire({
                title: 'Receipt Downloaded!',
                text: 'Your payment receipt has been downloaded.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        }
    </script>

    <style>
        .payment-success-popup {
            border-radius: 15px !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
        }
        .payment-success-title {
            color: #28a745 !important;
            font-weight: bold !important;
        }
        .payment-success-content {
            font-size: 16px !important;
        }
        .fa-mobile-alt {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
</body>
</html>
