<?php
session_start();
include('connect.php');

class Properties
{
    public $conn;
    public $id;
    public $propertyType;
    public $priceRange;
    public $location;
    public $area;
    public $capacity;
    public $description;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function addProperty()
    {
        $query = "INSERT INTO properties (property_type, price_range, location, area, capacity, description) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $params = [$this->propertyType, $this->priceRange, $this->location, $this->area, $this->capacity, $this->description];
        return sqlsrv_query($this->conn, $query, $params);
    }

    public function editProperty()
    {
        $query = "UPDATE properties SET 
                property_type = ?, 
                price_range = ?, 
                location = ?,
                area = ?, 
                capacity = ?, 
                description = ?
                WHERE id = ?";
        $params = [$this->propertyType, $this->priceRange, $this->location, $this->area, $this->capacity, $this->description, $this->id];
        return sqlsrv_query($this->conn, $query, $params);
    }

    public function deleteProperty($id)
    {
        $query = "DELETE FROM properties WHERE id = ?";
        $params = [$id];
        return sqlsrv_query($this->conn, $query, $params);
    }

    public function getPropertyById($id)
    {
        $query = "SELECT * FROM properties WHERE id = ?";
        $params = [$id];
        $stmt = sqlsrv_query($this->conn, $query, $params);
        return $stmt;
    }
}

$propertyManager = new Properties($conn);

// Handle fetch property by ID for editing (AJAX)
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $property = $propertyManager->getPropertyById($id);
    $row = sqlsrv_fetch_array($property, SQLSRV_FETCH_ASSOC);
    echo json_encode($row);
    exit();
}

// Handle Add Property
if (isset($_POST['add_property'])) {
    $propertyManager->propertyType = $_POST['property_type'];
    $propertyManager->priceRange = $_POST['price_range'];
    $propertyManager->location = $_POST['location'];
    $propertyManager->area = intval($_POST['area']);
    $propertyManager->capacity = $_POST['capacity'];
    $propertyManager->description = $_POST['description'];

    if ($propertyManager->addProperty()) {
        $_SESSION['notification'] = "Property added successfully";
    } else {
        $_SESSION['notification'] = "Error adding property";
    }
    header("Location: adminhome.php");
    exit();
}

// Handle Edit Property
if (isset($_POST['edit_property'])) {
    $propertyManager->id = intval($_POST['property_id']);
    $propertyManager->propertyType = $_POST['property_type'];
    $propertyManager->priceRange = $_POST['price_range'];
    $propertyManager->location = $_POST['location'];
    $propertyManager->area = intval($_POST['area']);
    $propertyManager->capacity = $_POST['capacity'];
    $propertyManager->description = $_POST['description'];

    if ($propertyManager->editProperty()) {
        $_SESSION['notification'] = "Property updated successfully";
    } else {
        $_SESSION['notification'] = "Error updating property";
    }
    header("Location: adminhome.php");
    exit();
}

// Handle Delete Property
if (isset($_POST['delete_property'])) {
    $property_id = intval($_POST['property_id']);
    if ($propertyManager->deleteProperty($property_id)) {
        $_SESSION['notification'] = "Property deleted successfully";
    } else {
        $_SESSION['notification'] = "Error deleting property";
    }
    header("Location: adminhome.php");
    exit();
}

// Handle Approve Buyer
if (isset($_POST['approve'])) {
    $buyer_id = intval($_POST['buyer_id']);
    $query = "UPDATE buyers SET status = 'approved' WHERE id = ?";
    $params = [$buyer_id];
    $stmt = sqlsrv_prepare($conn, $query, $params);
    if (sqlsrv_execute($stmt)) {
        $_SESSION['notification'] = "Request approved successfully";
    } else {
        $_SESSION['notification'] = "Error approving request";
    }
    sqlsrv_free_stmt($stmt);
    header("Location: adminhome.php");
    exit();
}

// Handle Reject Buyer
if (isset($_POST['reject'])) {
    $buyer_id = intval($_POST['buyer_id']);
    $query = "UPDATE buyers SET status = 'rejected' WHERE id = ?";
    $params = [$buyer_id];
    $stmt = sqlsrv_prepare($conn, $query, $params);
    if (sqlsrv_execute($stmt)) {
        $_SESSION['notification'] = "Request rejected successfully";
    } else {
        $_SESSION['notification'] = "Error rejecting request";
    }
    sqlsrv_free_stmt($stmt);
    header("Location: adminhome.php");
    exit();
}

// Fetch Pending Buyers
$result = sqlsrv_query($conn, "SELECT buyers.*, properties.property_type, properties.location 
                               FROM buyers 
                               JOIN properties ON buyers.property_id = properties.id 
                               WHERE buyers.status = 'pending'");

// Simplified and more robust feedback fetching
$feedbackData = [];
$feedbackError = null;

// First, let's check if the feedback table exists
$checkTableQuery = "SELECT name FROM sys.tables WHERE name = 'feedback'";
$tableCheck = sqlsrv_query($conn, $checkTableQuery);

if ($tableCheck && sqlsrv_fetch_array($tableCheck)) {
    // Table exists, now try to fetch data
    $feedbackQuery = "SELECT * FROM feedback ORDER BY id DESC";
    $feedbackResult = sqlsrv_query($conn, $feedbackQuery);

    if ($feedbackResult) {
        while ($row = sqlsrv_fetch_array($feedbackResult, SQLSRV_FETCH_ASSOC)) {
            $feedbackData[] = $row;
        }
    } else {
        $feedbackError = "Error fetching feedback: " . print_r(sqlsrv_errors(), true);
    }
} else {
    $feedbackError = "Feedback table does not exist in the database";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="./css/admin.css" />

    <style>
        /* Custom styles to ensure feedback section is visible */
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
        }

        .feedback-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-top: 40px;
            margin-bottom: 30px;
            overflow: hidden;
        }

        .feedback-header {
            background-color: #B1BAC4FF;
            color: white;
            padding: 15px 20px;
            margin: 0;
        }

        .feedback-body {
            padding: 20px;
        }

        .debug-info {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            display: inline-block;
            text-transform: uppercase;
        }

        .status-unread {
            background-color: #f39c12;
            color: white;
        }

        .status-read {
            background-color: #27ae60;
            color: white;
        }

        .message-cell {
            max-width: 300px;
            word-wrap: break-word;
            white-space: pre-wrap;
            overflow-wrap: break-word;
        }
    </style>
</head>

<body class="container-fluid">

    <h1 class="mb-4">Admin Dashboard</h1>



    <div class="mb-5">
        <h3>Add New Property</h3>
        <form method="post" action="adminhome.php" id="addPropertyForm" class="row g-3 bg-white p-4 rounded shadow-sm">
            <div class="col-md-4">
                <label for="property_type" class="form-label">Property Type</label>
                <select name="property_type" id="property_type" class="form-select" required>
                    <option value="Apartment">Apartment</option>
                    <option value="Residential Lot">Residential Lot</option>
                    <option value="Condo">Condo</option>
                    <option value="House and Lot">House and Lot</option>
                    <option value="Commercial">Commercial</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="price_range" class="form-label">Price Range</label>
                <input type="text" name="price_range" id="price_range" class="form-control" placeholder="Price Range" required />
            </div>
            <div class="col-md-4">
                <label for="location" class="form-label">Location</label>
                <input type="text" name="location" id="location" class="form-control" placeholder="Location" required />
            </div>
            <div class="col-md-3">
                <label for="area" class="form-label">Area (sqm)</label>
                <input type="number" name="area" id="area" class="form-control" placeholder="Area" required />
            </div>
            <div class="col-md-3">
                <label for="capacity" class="form-label">Capacity</label>
                <input type="text" name="capacity" id="capacity" class="form-control" placeholder="Capacity" required />
            </div>
            <div class="col-md-6">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control" placeholder="Description" rows="2" required></textarea>
            </div>
            <div class="col-12">
                <button type="submit" name="add_property" class="btn btn-primary">Add Property</button>
            </div>
        </form>
    </div>

    <!-- Property List -->
    <div class="mb-5 bg-white p-4 rounded shadow-sm">
        <h3>Manage Properties</h3>
        <table id="propertyTable" class="display table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Property Type</th>
                    <th>Price Range</th>
                    <th>Location</th>
                    <th>Area (sqm)</th>
                    <th>Capacity</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th style="min-width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $properties = sqlsrv_query($conn, "
                    SELECT p.*, 
                           CASE 
                               WHEN b.status = 'approved' THEN 'Sold' 
                               WHEN b.status = 'pending' THEN 'Pending Sale'
                               ELSE 'Available' 
                           END as property_status
                    FROM properties p 
                    LEFT JOIN buyers b ON p.id = b.property_id AND b.status IN ('approved', 'pending')
                ");

                if ($properties) {
                    while ($property = sqlsrv_fetch_array($properties, SQLSRV_FETCH_ASSOC)) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($property['property_type']) . "</td>";
                        echo "<td>" . htmlspecialchars($property['price_range']) . "</td>";
                        echo "<td>" . htmlspecialchars($property['location']) . "</td>";
                        echo "<td>" . htmlspecialchars($property['area']) . "</td>";
                        echo "<td>" . htmlspecialchars($property['capacity']) . "</td>";
                        echo "<td>" . htmlspecialchars($property['description']) . "</td>";

                        $badgeClass = 'success';
                        if ($property['property_status'] == 'Pending Sale') {
                            $badgeClass = 'warning';
                        } elseif ($property['property_status'] == 'Sold') {
                            $badgeClass = 'secondary';
                        }
                        echo "<td><span class='badge bg-{$badgeClass}'>" . htmlspecialchars($property['property_status']) . "</span></td>";

                        echo "<td>
                                <button class='btn btn-sm btn-warning me-2' onclick='editProperty({$property['id']})'>Edit</button>
                                <button class='btn btn-sm btn-danger' onclick='deleteProperty({$property['id']})'>Delete</button>
                              </td>";
                        echo "</tr>";
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Pending Buyer Requests -->
    <div class="mb-5 bg-white p-4 rounded shadow-sm">
        <h3>Pending Purchase Requests</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Buyer Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Property Type</th>
                    <th>Location</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result): ?>
                    <?php while ($buyer = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) : ?>
                        <tr>
                            <td><?= htmlspecialchars($buyer['fullname']) ?></td>
                            <td><?= htmlspecialchars($buyer['email']) ?></td>
                            <td><?= htmlspecialchars($buyer['phone']) ?></td>
                            <td><?= htmlspecialchars($buyer['property_type']) ?></td>
                            <td><?= htmlspecialchars($buyer['location']) ?></td>
                            <td>
                                <form method="post" action="adminhome.php" style="display:inline;">
                                    <input type="hidden" name="buyer_id" value="<?= $buyer['id'] ?>">
                                    <button type="submit" name="approve" class="btn btn-success btn-sm">Approve</button>
                                </form>
                                <form method="post" action="adminhome.php" style="display:inline;">
                                    <input type="hidden" name="buyer_id" value="<?= $buyer['id'] ?>">
                                    <button type="submit" name="reject" class="btn btn-danger btn-sm">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Customer Feedback Section - ALWAYS VISIBLE -->
    <div class="feedback-section">
        <div class="feedback-header">
            <h3 class="mb-0">Customer Feedback</h3>
        </div>
        <div class="feedback-body">
            <?php if (!empty($feedbackData)): ?>
                <div class="table-responsive">
                    <table id="feedbackTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User Email</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Date Submitted</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feedbackData as $feedback): ?>
                                <tr>
                                    <td><?= htmlspecialchars($feedback['id'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($feedback['user_email'] ?? $feedback['email'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($feedback['subject'] ?? 'N/A') ?></td>
                                    <td class="message-cell"><?= nl2br(htmlspecialchars($feedback['message'] ?? 'N/A')) ?></td>
                                    <td>
                                        <?php
                                        $dateField = $feedback['date_submitted'] ?? $feedback['created_at'] ?? $feedback['timestamp'] ?? null;
                                        if (is_object($dateField)) {
                                            echo $dateField->format('Y-m-d H:i:s');
                                        } elseif ($dateField) {
                                            echo htmlspecialchars($dateField);
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= htmlspecialchars($feedback['status'] ?? 'unread') ?>">
                                            <?= ucfirst(htmlspecialchars($feedback['status'] ?? 'unread')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary me-1" onclick="markAsRead(<?= $feedback['id'] ?? 0 ?>)">
                                            <i class="fas fa-check"></i> Read
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteFeedback(<?= $feedback['id'] ?? 0 ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <?php if ($feedbackError): ?>
                        <strong>Database Issue:</strong> <?= htmlspecialchars($feedbackError) ?>
                        <br><small>Please check your database configuration or create the feedback table.</small>
                    <?php else: ?>
                        No feedback submissions found.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Initialize DataTables
            $('#propertyTable').DataTable({
                responsive: true,
                pageLength: 10
            });

            // Initialize feedback table if it has data
            if ($('#feedbackTable').length && $('#feedbackTable tbody tr').length > 0) {
                $('#feedbackTable').DataTable({
                    responsive: true,
                    order: [
                        [0, 'desc']
                    ], // Sort by ID column (newest first)
                    pageLength: 10,
                    columnDefs: [{
                            orderable: false,
                            targets: 6
                        } // Actions column
                    ]
                });
            }

            // Show notification if exists
            <?php if (isset($_SESSION['notification'])): ?>
                Swal.fire({
                    title: 'Notification',
                    text: '<?= $_SESSION['notification'] ?>',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
                <?php unset($_SESSION['notification']); ?>
            <?php endif; ?>
        });

        function editProperty(id) {
            $.get('adminhome.php', {
                id: id
            }, function(data) {
                try {
                    let property = JSON.parse(data);
                    Swal.fire({
                        title: 'Edit Property',
                        html: `
                        <form id="editPropertyForm" method="post" action="adminhome.php" class="text-start">
                            <input type="hidden" name="property_id" value="${property.id}" />
                            <div class="mb-2">
                                <label>Property Type</label>
                                <select name="property_type" class="form-select" required>
                                    <option ${property.property_type === 'Apartment' ? 'selected' : ''}>Apartment</option>
                                    <option ${property.property_type === 'Residential Lot' ? 'selected' : ''}>Residential Lot</option>
                                    <option ${property.property_type === 'Condo' ? 'selected' : ''}>Condo</option>
                                    <option ${property.property_type === 'House and Lot' ? 'selected' : ''}>House and Lot</option>
                                    <option ${property.property_type === 'Commercial' ? 'selected' : ''}>Commercial</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label>Price Range</label>
                                <input type="text" name="price_range" value="${property.price_range}" class="form-control" required />
                            </div>
                            <div class="mb-2">
                                <label>Location</label>
                                <input type="text" name="location" value="${property.location}" class="form-control" required />
                            </div>
                            <div class="mb-2">
                                <label>Area (sqm)</label>
                                <input type="number" name="area" value="${property.area}" class="form-control" required />
                            </div>
                            <div class="mb-2">
                                <label>Capacity</label>
                                <input type="text" name="capacity" value="${property.capacity}" class="form-control" required />
                            </div>
                            <div class="mb-2">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="3" required>${property.description}</textarea>
                            </div>
                            <button type="submit" name="edit_property" class="btn btn-primary w-100">Save Changes</button>
                        </form>
                        `,
                        showConfirmButton: false,
                        width: 600
                    });
                } catch (e) {
                    Swal.fire('Error', 'Could not load property data.', 'error');
                }
            }).fail(function() {
                Swal.fire('Error', 'Failed to fetch property data.', 'error');
            });
        }

        function deleteProperty(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This property will be deleted permanently.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    let form = $('<form method="post" action="adminhome.php"></form>');
                    form.append(`<input type="hidden" name="property_id" value="${id}">`);
                    form.append('<input type="hidden" name="delete_property" value="1">');
                    $('body').append(form);
                    form.submit();
                }
            });
        }

        function markAsRead(feedbackId) {
            // You'll need to create an update_feedback.php file to handle this
            Swal.fire('Info', 'Mark as read functionality needs to be implemented in update_feedback.php', 'info');
        }

        function deleteFeedback(feedbackId) {
            Swal.fire({
                title: 'Delete Feedback?',
                text: "This feedback will be deleted permanently.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // You'll need to create delete functionality
                    Swal.fire('Info', 'Delete feedback functionality needs to be implemented', 'info');
                }
            });
        }

        function markAsRead(id) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'update_feedback.php';

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'feedback_id';
            idInput.value = id;

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'mark_read';
            actionInput.value = '1';

            form.appendChild(idInput);
            form.appendChild(actionInput);
            document.body.appendChild(form);
            form.submit();
        }

        function deleteFeedback(id) {
            Swal.fire({
                title: 'Delete Feedback?',
                text: "This action cannot be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'update_feedback.php';

                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'feedback_id';
                    idInput.value = id;

                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'delete_feedback';
                    actionInput.value = '1';

                    form.appendChild(idInput);
                    form.appendChild(actionInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>

</html>