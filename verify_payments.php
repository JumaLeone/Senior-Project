<?php
// Check pending payments every hour
$pendingPayments = sqlsrv_query($conn, 
    "SELECT invoice_id FROM payments WHERE status = 'pending'");
    
while ($row = sqlsrv_fetch_array($pendingPayments, SQLSRV_FETCH_ASSOC)) {
    $invoiceId = $row['invoice_id'];
    
    $client = new \GuzzleHttp\Client();
    $response = $client->request('GET', 
        "https://api.intasend.com/api/v1/payment/status/$invoiceId/", [
            'headers' => [
                'Authorization' => 'Bearer '.$privateKey
            ]
        ]);
    
    $statusData = json_decode($response->getBody(), true);
    
    if ($statusData['state'] == 'COMPLETE') {
        // Update database
        sqlsrv_query($conn,
            "UPDATE payments SET status = 'completed' WHERE invoice_id = ?",
            [$invoiceId]);
            
        // Update property status if needed
        sqlsrv_query($conn,
            "UPDATE properties SET status = 'booked' WHERE id IN
            (SELECT property_id FROM payments WHERE invoice_id = ?)",
            [$invoiceId]);
    }
}