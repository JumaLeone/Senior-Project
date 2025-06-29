<?php  
// Verify the payment was successful by checking with IntaSend API 
// Then display success message to user  
?> 
<!DOCTYPE html> 
<html lang="en"> 
<head>     
    <meta charset="UTF-8">     
    <meta name="viewport" content="width=device-width, initial-scale=1.0">     
    <title>Payment Success</title>     
    <!-- Bootstrap CSS -->     
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">     
    <!-- SweetAlert2 CSS -->     
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css"> 
</head> 
<body>     
    <div class="container mt-5">         
        <!-- Fallback content (hidden by default, shown if JS fails) -->         
        <div id="fallback-content" class="alert alert-success" style="display: none;">             
            <h4>Payment Initiated Successfully!</h4>             
            <p>Please check your phone to complete the M-Pesa payment.</p>             
            <p>Invoice ID: <?= htmlspecialchars($_GET['invoice_id'] ?? 'N/A') ?></p>             
            <a href="searching.php" class="btn btn-primary">Back to Properties</a>             
            <a href="download_receipt.php?invoice_id=<?= htmlspecialchars($_GET['invoice_id'] ?? '') ?>" class="btn btn-success ms-2">Download Receipt</a>         
        </div>     
    </div>           
    
    <!-- Bootstrap JS -->     
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>     
    <!-- SweetAlert2 JS -->     
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>     
    <!-- jsPDF for receipt generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    
    <script>         
        document.addEventListener('DOMContentLoaded', function() {             
            // Get invoice ID from PHP             
            const invoiceId = <?= json_encode($_GET['invoice_id'] ?? 'N/A') ?>;                          
            
            Swal.fire({                 
                title: 'Payment Initiated Successfully!',                 
                html: `                     
                    <div class="text-center">                         
                        <i class="fas fa-mobile-alt fa-3x text-success mb-3"></i>                         
                        <p class="mb-2">Please check your phone to complete the M-Pesa payment.</p>                         
                        <p class="mb-3"><strong>Invoice ID:</strong> ${invoiceId}</p>                     
                    </div>                 
                `,                 
                icon: 'success',                 
                iconColor: '#28a745',                 
                confirmButtonText: 'Back to Properties',                 
                confirmButtonColor: '#007bff',                 
                denyButtonText: 'Download Receipt',
                denyButtonColor: '#28a745',
                showDenyButton: true,
                allowOutsideClick: false,                 
                allowEscapeKey: false,                 
                showCloseButton: true,                 
                customClass: {                     
                    popup: 'payment-success-popup',                     
                    title: 'payment-success-title',                     
                    htmlContainer: 'payment-success-content'                 
                }             
            }).then((result) => {                 
                if (result.isConfirmed) {                     
                    window.location.href = 'searching.php';                 
                } else if (result.isDenied) {
                    downloadReceipt(invoiceId);
                } else if (result.isDismissed) {
                    window.location.href = 'searching.php';
                }
            });         
        });          
        
        // Show fallback content if SweetAlert fails to load         
        window.addEventListener('error', function() {             
            document.getElementById('fallback-content').style.display = 'block';         
        });

        // Function to download receipt as PDF
        function downloadReceipt(invoiceId) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Receipt content
            const currentDate = new Date().toLocaleDateString();
            const currentTime = new Date().toLocaleTimeString();

            // Add header
            doc.setFontSize(20);
            doc.setFont("helvetica", "bold");
            doc.text("PAYMENT RECEIPT", 105, 30, { align: "center" });

            // Add company info (customize as needed)
            doc.setFontSize(12);
            doc.setFont("helvetica", "normal");
            doc.text("KeyNest Allocation Systems", 105, 45, { align: "center" });
            doc.text("Email: okothleone42@gmail.com.com", 105, 55, { align: "center" });
            doc.text("Phone: +254 708 941 090", 105, 65, { align: "center" });

            // Add line separator
            doc.line(20, 75, 190, 75);

            // Add transaction details
            doc.setFontSize(14);
            doc.setFont("helvetica", "bold");
            doc.text("Transaction Details", 20, 90);

            doc.setFontSize(11);
            doc.setFont("helvetica", "normal");
            doc.text(`Invoice ID: ${invoiceId}`, 20, 105);
            doc.text(`Date: ${currentDate}`, 20, 115);
            doc.text(`Time: ${currentTime}`, 20, 125);
            doc.text("Payment Method: M-Pesa", 20, 135);
            doc.text("Status: Initiated", 20, 145);

            // Add payment details (you can customize these based on your data)
            doc.setFontSize(14);
            doc.setFont("helvetica", "bold");
            doc.text("Payment Information", 20, 165);

            doc.setFontSize(11);
            doc.setFont("helvetica", "normal");
            doc.text("Service: Property Booking", 20, 180);
            doc.text("Amount: KES 0.00", 20, 190); // You can pass actual amount
            doc.text("Transaction Fee: KES 0.00", 20, 200);
            doc.text("Total: KES 0.00", 20, 210);

            // Add instructions
            doc.setFontSize(12);
            doc.setFont("helvetica", "bold");
            doc.text("Important Instructions:", 20, 230);

            doc.setFontSize(10);
            doc.setFont("helvetica", "normal");
            doc.text("1. Please complete the M-Pesa payment on your phone", 20, 245);
            doc.text("2. Keep this receipt for your records", 20, 255);
            doc.text("3. Contact support if payment is not processed within 5 minutes", 20, 265);

            // Add footer
            doc.line(20, 280, 190, 280);
            doc.setFontSize(8);
            doc.text("This is a computer-generated receipt. No signature required.", 105, 290, { align: "center" });

            // Download the PDF
            doc.save(`Receipt_${invoiceId}_${currentDate.replace(/\//g, '-')}.pdf`);

            // Show success message
            Swal.fire({
                title: 'Receipt Downloaded!',
                text: 'Your payment receipt has been downloaded successfully.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                // Show the main dialog again
                setTimeout(() => {
                    location.reload();
                }, 500);
            });
        }
    </script>      
    
    <!-- Optional: Font Awesome for mobile icon -->     
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">      
    
    <!-- Custom CSS for additional styling -->     
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
        
        /* Animation for mobile icon */         
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