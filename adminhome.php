<?php
session_start();

include('connect.php');

// Handle Mark as Read via AJAX
if (isset($_POST['mark_read']) && isset($_POST['feedback_id'])) {
    $feedback_id = intval($_POST['feedback_id']);
    $update = sqlsrv_prepare($conn, "UPDATE feedback SET status = 'read' WHERE id = ?", [$feedback_id]);

    if ($update && sqlsrv_execute($update)) {
        echo json_encode(['success' => true]);
    } else {
        $error = sqlsrv_errors();
        echo json_encode(['success' => false, 'error' => $error[0]['message'] ?? 'Unknown error']);
    }
    exit;
}
// Handle Delete Feedback via AJAX
if (isset($_POST['delete_feedback']) && isset($_POST['feedback_id'])) {
    $feedback_id = intval($_POST['feedback_id']);
    $stmt = sqlsrv_prepare($conn, "DELETE FROM feedback WHERE id = ?", [$feedback_id]);

    if ($stmt && sqlsrv_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        $error = sqlsrv_errors();
        echo json_encode(['success' => false, 'error' => $error[0]['message'] ?? 'Unknown error']);
    }
    exit;
}


// Add connection timeout check
if (!$conn) {
    die("Database connection failed: " . print_r(sqlsrv_errors(), true));
}

// Fetch admin info
$adminInfo = ['username' => 'Admin', 'email' => ''];
if (isset($_SESSION['email'])) {
    $adminEmail = $_SESSION['email'];

    // Fetch only if the email exists in the admin_users table
    $stmt = sqlsrv_prepare($conn, "SELECT username, email FROM admin_users WHERE email = ?", [$adminEmail]);
    if ($stmt && sqlsrv_execute($stmt)) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($row) {
            $adminInfo['username'] = $row['username'];
            $adminInfo['email'] = $row['email'];
        }
        sqlsrv_free_stmt($stmt);
    }
}

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
        $stmt = sqlsrv_prepare($this->conn, $query, $params);
        if ($stmt) {
            $result = sqlsrv_execute($stmt);
            sqlsrv_free_stmt($stmt);
            return $result;
        }
        return false;
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
        $stmt = sqlsrv_prepare($this->conn, $query, $params);
        if ($stmt) {
            $result = sqlsrv_execute($stmt);
            sqlsrv_free_stmt($stmt);
            return $result;
        }
        return false;
    }

    public function deleteProperty($id)
    {
        $query = "DELETE FROM properties WHERE id = ?";
        $params = [$id];
        $stmt = sqlsrv_prepare($this->conn, $query, $params);
        if ($stmt) {
            $result = sqlsrv_execute($stmt);
            sqlsrv_free_stmt($stmt);
            return $result;
        }
        return false;
    }

    public function getPropertyById($id)
    {
        $query = "SELECT * FROM properties WHERE id = ?";
        $params = [$id];
        $stmt = sqlsrv_prepare($this->conn, $query, $params);
        if ($stmt && sqlsrv_execute($stmt)) {
            return $stmt;
        }
        return false;
    }
}

$propertyManager = new Properties($conn);

// Handle fetch property by ID for editing (AJAX)
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $property = $propertyManager->getPropertyById($id);
    if ($property) {
        $row = sqlsrv_fetch_array($property, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($property);
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'Property not found']);
    }
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
    if ($stmt && sqlsrv_execute($stmt)) {
        $_SESSION['notification'] = "Request approved successfully";
    } else {
        $_SESSION['notification'] = "Error approving request";
    }
    if ($stmt) sqlsrv_free_stmt($stmt);
    header("Location: adminhome.php");
    exit();
}

// Handle Reject Buyer
if (isset($_POST['reject'])) {
    $buyer_id = intval($_POST['buyer_id']);
    $query = "UPDATE buyers SET status = 'rejected' WHERE id = ?";
    $params = [$buyer_id];
    $stmt = sqlsrv_prepare($conn, $query, $params);
    if ($stmt && sqlsrv_execute($stmt)) {
        $_SESSION['notification'] = "Request rejected successfully";
    } else {
        $_SESSION['notification'] = "Error rejecting request";
    }
    if ($stmt) sqlsrv_free_stmt($stmt);
    header("Location: adminhome.php");
    exit();
}

// OPTIMIZED: Use a single query with LIMIT for better performance
$result = sqlsrv_query($conn, "SELECT TOP 100 buyers.*, properties.property_type, properties.location 
                               FROM buyers 
                               JOIN properties ON buyers.property_id = properties.id 
                               WHERE buyers.status = 'pending'
                               ORDER BY buyers.id DESC");

// OPTIMIZED: Simplified feedback fetching with timeout and limits
$feedbackData = [];
$feedbackError = null;

try {
    // Set query timeout
    $feedbackQuery = "SELECT TOP 50 * FROM feedback ORDER BY id DESC";
    $feedbackResult = sqlsrv_query($conn, $feedbackQuery, array(), array("QueryTimeout" => 10));

    if ($feedbackResult) {
        while ($row = sqlsrv_fetch_array($feedbackResult, SQLSRV_FETCH_ASSOC)) {
            $feedbackData[] = $row;
        }
        sqlsrv_free_stmt($feedbackResult);
    } else {
        $errors = sqlsrv_errors();
        if ($errors) {
            $feedbackError = "Database error: " . $errors[0]['message'];
        } else {
            $feedbackError = "Feedback table may not exist";
        }
    }
} catch (Exception $e) {
    $feedbackError = "Error accessing feedback: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Dashboard</title>

    <!-- OPTIMIZED: Load critical CSS first -->
    <style>
        .admin-box {
            background-color: #ffffff;
            padding: 15px 20px;
            border-radius: 10px;
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            max-width: 300px;
            margin-bottom: 20px;
        }


        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>

    <!-- OPTIMIZED: Load external resources asynchronously -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="./css/admin.css" />

    <!-- Load DataTables CSS only if needed -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
</head>

<body class="container-fluid">
    <div class="admin-box">
        <h5 class="text-primary">👤 Logged in as</h5>
        <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($adminInfo['username']) ?></p>

    </div>
    <div class="loading" id="loadingIndicator">
        <div class="spinner"></div>
        <p>Loading dashboard...</p>
    </div>

    <div id="mainContent" style="display: none;">
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
            <div class="table-responsive">
                <table id="propertyTable" class="table table-bordered table-striped">
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
                        // OPTIMIZED: Use a more efficient query with proper indexing
                        $propertiesQuery = "SELECT TOP 100 p.*, 
                                           CASE 
                                               WHEN b.status = 'approved' THEN 'Sold' 
                                               WHEN b.status = 'pending' THEN 'Pending Sale'
                                               ELSE 'Available' 
                                           END as property_status
                                    FROM properties p 
                                    LEFT JOIN buyers b ON p.id = b.property_id AND b.status IN ('approved', 'pending')
                                    ORDER BY p.id DESC";

                        $properties = sqlsrv_query($conn, $propertiesQuery, array(), array("QueryTimeout" => 10));

                        if ($properties) {
                            while ($property = sqlsrv_fetch_array($properties, SQLSRV_FETCH_ASSOC)) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($property['property_type']) . "</td>";
                                echo "<td>" . htmlspecialchars($property['price_range']) . "</td>";
                                echo "<td>" . htmlspecialchars($property['location']) . "</td>";
                                echo "<td>" . htmlspecialchars($property['area']) . "</td>";
                                echo "<td>" . htmlspecialchars($property['capacity']) . "</td>";
                                echo "<td>" . htmlspecialchars(substr($property['description'], 0, 100)) . "...</td>";

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
                            sqlsrv_free_stmt($properties);
                        } else {
                            echo "<tr><td colspan='8'>Error loading properties</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pending Buyer Requests -->
        <div class="mb-5 bg-white p-4 rounded shadow-sm">
            <h3>Pending Purchase Requests</h3>
            <div class="table-responsive">
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
                            <?php sqlsrv_free_stmt($result); ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Customer Feedback Section -->
        <div class="mb-5 bg-white p-4 rounded shadow-sm">
            <h3>Customer Feedback</h3>
            <?php if (!empty($feedbackData)): ?>
                <div class="table-responsive">
                    <table id="feedbackTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User Email</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feedbackData as $feedback): ?>
                                <tr>
                                    <td><?= $feedback['id'] ?? 'N/A' ?></td>
                                    <td><?= htmlspecialchars($feedback['user_email'] ?? $feedback['email'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($feedback['subject'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars(substr($feedback['message'] ?? 'N/A', 0, 100)) ?>...</td>
                                    <td>
                                        <?php
                                        $dateField = $feedback['date_submitted'] ?? $feedback['created_at'] ?? 'N/A';
                                        if (is_object($dateField)) {
                                            echo $dateField->format('Y-m-d');
                                        } else {
                                            echo htmlspecialchars($dateField);
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= ($feedback['status'] ?? 'unread') === 'read' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($feedback['status'] ?? 'unread') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="markAsRead(<?= $feedback['id'] ?? 0 ?>)">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteFeedback(<?= $feedback['id'] ?? 0 ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <?= $feedbackError ? "Error: " . htmlspecialchars($feedbackError) : "No feedback found." ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <a href="generate_report.php" target="_blank" class="btn btn-outline-danger mb-4">
        <i class="fas fa-file-pdf"></i> Download Admin PDF Report
    </a>


    <!-- OPTIMIZED: Load JavaScript at the end and conditionally -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Show loading indicator
        document.getElementById('loadingIndicator').style.display = 'block';

        // Hide loading and show content when ready
        $(document).ready(function() {
            document.getElementById('loadingIndicator').style.display = 'none';
            document.getElementById('mainContent').style.display = 'block';

            // Show notification if exists
            <?php if (isset($_SESSION['notification'])): ?>
                alert('<?= addslashes($_SESSION['notification']) ?>');
                <?php unset($_SESSION['notification']); ?>
            <?php endif; ?>
        });

        // Load heavy JavaScript libraries asynchronously
        function loadDataTables() {
            const script = document.createElement('script');
            script.src = 'https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js';
            script.onload = function() {
                // Initialize DataTables only when needed
                if ($('#propertyTable tbody tr').length > 0) {
                    $('#propertyTable').DataTable({
                        responsive: true,
                        pageLength: 25,
                        order: [
                            [0, 'desc']
                        ]
                    });
                }

                if ($('#feedbackTable tbody tr').length > 0) {
                    $('#feedbackTable').DataTable({
                        responsive: true,
                        order: [
                            [0, 'desc']
                        ],
                        pageLength: 25
                    });
                }
            };
            document.head.appendChild(script);
        }

        function loadSweetAlert() {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
            document.head.appendChild(script);
        }

        // Load libraries after page is ready
        setTimeout(() => {
            loadDataTables();
            loadSweetAlert();
        }, 100);

        function editProperty(id) {
            $.get('adminhome.php', {
                id: id
            }, function(data) {
                try {
                    let property = JSON.parse(data);
                    if (property.error) {
                        alert('Error: ' + property.error);
                        return;
                    }

                    // Create a simple modal instead of SweetAlert for faster loading
                    const modal = `
                        <div class="modal fade" id="editModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Property</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="post" action="adminhome.php">
                                            <input type="hidden" name="property_id" value="${property.id}" />
                                            <div class="mb-3">
                                                <label>Property Type</label>
                                                <select name="property_type" class="form-select" required>
                                                    <option ${property.property_type === 'Apartment' ? 'selected' : ''}>Apartment</option>
                                                    <option ${property.property_type === 'Residential Lot' ? 'selected' : ''}>Residential Lot</option>
                                                    <option ${property.property_type === 'Condo' ? 'selected' : ''}>Condo</option>
                                                    <option ${property.property_type === 'House and Lot' ? 'selected' : ''}>House and Lot</option>
                                                    <option ${property.property_type === 'Commercial' ? 'selected' : ''}>Commercial</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label>Price Range</label>
                                                <input type="text" name="price_range" value="${property.price_range}" class="form-control" required />
                                            </div>
                                            <div class="mb-3">
                                                <label>Location</label>
                                                <input type="text" name="location" value="${property.location}" class="form-control" required />
                                            </div>
                                            <div class="mb-3">
                                                <label>Area (sqm)</label>
                                                <input type="number" name="area" value="${property.area}" class="form-control" required />
                                            </div>
                                            <div class="mb-3">
                                                <label>Capacity</label>
                                                <input type="text" name="capacity" value="${property.capacity}" class="form-control" required />
                                            </div>
                                            <div class="mb-3">
                                                <label>Description</label>
                                                <textarea name="description" class="form-control" rows="3" required>${property.description}</textarea>
                                            </div>
                                            <button type="submit" name="edit_property" class="btn btn-primary">Save Changes</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    $('body').append(modal);
                    $('#editModal').modal('show');
                    $('#editModal').on('hidden.bs.modal', function() {
                        $(this).remove();
                    });

                } catch (e) {
                    alert('Error loading property data');
                }
            }).fail(function() {
                alert('Failed to fetch property data');
            });
        }

        function deleteProperty(id) {
            if (confirm('Are you sure you want to delete this property?')) {
                let form = $('<form method="post" action="adminhome.php"></form>');
                form.append(`<input type="hidden" name="property_id" value="${id}">`);
                form.append('<input type="hidden" name="delete_property" value="1">');
                $('body').append(form);
                form.submit();
            }
        }

        function markAsRead(id) {
            $.ajax({
                url: 'adminhome.php',
                method: 'POST',
                data: {
                    mark_read: 1,
                    feedback_id: id
                },
                success: function(response) {
                    let result = {};
                    try {
                        result = JSON.parse(response);
                    } catch (e) {
                        alert('Invalid server response');
                        return;
                    }

                    if (result.success) {
                        // Optionally: update badge color without refresh
                        $(`#feedbackTable tr`).each(function() {
                            if ($(this).find('td:first').text() == id) {
                                $(this).find('span.badge')
                                    .removeClass('bg-warning')
                                    .addClass('bg-success')
                                    .text('Read');
                            }
                        });
                    } else {
                        alert('Failed to mark as read: ' + (result.error || 'Unknown error'));
                    }
                },
                error: function() {
                    alert('AJAX request failed.');
                }
            });
        }

        function deleteFeedback(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to undo this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('adminhome.php', {
                        delete_feedback: 1,
                        feedback_id: id
                    }, function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                $('#feedbackTable tbody tr').each(function() {
                                    if ($(this).find('td:first').text() == id) {
                                        $(this).remove();
                                    }
                                });
                                Swal.fire('Deleted!', 'Feedback has been deleted.', 'success');
                            } else {
                                Swal.fire('Error', result.error || 'Unknown error occurred.', 'error');
                            }
                        } catch (e) {
                            Swal.fire('Error', 'Invalid server response.', 'error');
                        }
                    }).fail(() => {
                        Swal.fire('Error', 'Failed to send delete request.', 'error');
                    });
                }
            });
        }

        function confirmLogout() {
            Swal.fire({
                title: 'Are you sure you want to logout?',
                text: "You will be redirected to the login page.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, logout',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logoutAdmin.php';
                }
            });
        }
    </script>

    <!-- Load Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <a href="#" class="btn btn-outline-danger float-end" onclick="confirmLogout()">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>

</body>

</html>