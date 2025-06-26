<?php
require_once 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

include('connect.php');

// === Fetch Manage Properties with Status ===
$properties = [];
$pQuery = "
    SELECT TOP 100 p.*, 
           CASE 
               WHEN b.status = 'approved' THEN 'Sold' 
               WHEN b.status = 'pending' THEN 'Pending Sale'
               ELSE 'Available' 
           END as property_status
    FROM properties p
    LEFT JOIN buyers b ON p.id = b.property_id AND b.status IN ('approved', 'pending')
    ORDER BY p.id DESC";
$pResult = sqlsrv_query($conn, $pQuery);
while ($row = sqlsrv_fetch_array($pResult, SQLSRV_FETCH_ASSOC)) {
    $properties[] = $row;
}
sqlsrv_free_stmt($pResult);

// === Fetch Pending Buyers ===
$buyers = [];
$bQuery = "SELECT buyers.*, properties.property_type, properties.location 
           FROM buyers 
           JOIN properties ON buyers.property_id = properties.id 
           WHERE buyers.status = 'pending' 
           ORDER BY buyers.id DESC";
$bResult = sqlsrv_query($conn, $bQuery);
while ($row = sqlsrv_fetch_array($bResult, SQLSRV_FETCH_ASSOC)) {
    $buyers[] = $row;
}
sqlsrv_free_stmt($bResult);

// === Fetch Feedback ===
$feedbacks = [];
$fQuery = "SELECT TOP 50 * FROM feedback ORDER BY id DESC";
$fResult = sqlsrv_query($conn, $fQuery);
while ($row = sqlsrv_fetch_array($fResult, SQLSRV_FETCH_ASSOC)) {
    $feedbacks[] = $row;
}
sqlsrv_free_stmt($fResult);

// === HTML Content for PDF ===
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h2 { background: #f0f0f0; padding: 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background-color: #eaeaea; }
        .status-available { background: #d4edda; }
        .status-pending { background: #fff3cd; }
        .status-sold { background: #e2e3e5; }
    </style>
</head>
<body>

<h2>Manage Properties</h2>
<table>
    <thead>
        <tr>
            <th>Type</th>
            <th>Price</th>
            <th>Location</th>
            <th>Area</th>
            <th>Capacity</th>
            <th>Status</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($properties as $p): ?>
        <?php
            $status = $p['property_status'];
            $statusClass = 'status-available';
            if ($status === 'Pending Sale') $statusClass = 'status-pending';
            if ($status === 'Sold') $statusClass = 'status-sold';
        ?>
        <tr class="<?= $statusClass ?>">
            <td><?= htmlspecialchars($p['property_type']) ?></td>
            <td><?= htmlspecialchars($p['price_range']) ?></td>
            <td><?= htmlspecialchars($p['location']) ?></td>
            <td><?= htmlspecialchars($p['area']) ?></td>
            <td><?= htmlspecialchars($p['capacity']) ?></td>
            <td><?= $status ?></td>
            <td><?= htmlspecialchars(substr($p['description'], 0, 40)) ?>...</td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h2>Pending Purchase Requests</h2>
<table>
    <thead>
        <tr>
            <th>Name</th><th>Email</th><th>Phone</th><th>Property Type</th><th>Location</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($buyers as $b): ?>
        <tr>
            <td><?= htmlspecialchars($b['fullname']) ?></td>
            <td><?= htmlspecialchars($b['email']) ?></td>
            <td><?= htmlspecialchars($b['phone']) ?></td>
            <td><?= htmlspecialchars($b['property_type']) ?></td>
            <td><?= htmlspecialchars($b['location']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h2>Customer Feedback</h2>
<table>
    <thead>
        <tr>
            <th>Email</th><th>Subject</th><th>Message</th><th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($feedbacks as $f): ?>
        <tr>
            <td><?= htmlspecialchars($f['user_email'] ?? $f['email'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($f['subject']) ?></td>
            <td><?= htmlspecialchars(substr($f['message'], 0, 60)) ?>...</td>
            <td><?= htmlspecialchars($f['status'] ?? 'unread') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
<?php
$html = ob_get_clean();

// === Generate and Stream PDF ===
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("admin_dashboard_report_" . date("Ymd_His") . ".pdf", ["Attachment" => true]);
exit;
?>
