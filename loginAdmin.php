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

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Dashboard</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css" />

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />

    <!-- Custom CSS -->
    <link rel="stylesheet" href="./css/admin.css" />

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="container py-4">

    <h1 class="mb-4">Admin Dashboard</h1>

    <!-- Add Property Form -->
    <div class="mb-5">
        <h3>Add New Property</h3>
        <form method="post" action="adminhome.php" id="addPropertyForm" class="row g-3">
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
    <div class="mb-5">
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
                    <th style="min-width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $properties = sqlsrv_query($conn, "SELECT * FROM properties WHERE id NOT IN (SELECT property_id FROM buyers WHERE status = 'approved')");
                while ($property = sqlsrv_fetch_array($properties, SQLSRV_FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($property['property_type']) . "</td>";
                    echo "<td>" . htmlspecialchars($property['price_range']) . "</td>";
                    echo "<td>" . htmlspecialchars($property['location']) . "</td>";
                    echo "<td>" . htmlspecialchars($property['area']) . "</td>";
                    echo "<td>" . htmlspecialchars($property['capacity']) . "</td>";
                    echo "<td>" . htmlspecialchars($property['description']) . "</td>";
                    echo "<td>
                            <button class='btn btn-sm btn-warning me-2' onclick='editProperty({$property['id']})'>Edit</button>
                            <button class='btn btn-sm btn-danger' onclick='deleteProperty({$property['id']})'>Delete</button>
                          </td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Pending Buyer Requests -->
    <div>
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
            </tbody>
        </table>
    </div>

    <script>
        $(document).ready(function() {
            $('#propertyTable').DataTable();

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
            console.log('Edit clicked for property id:', id);
            $.get('adminhome.php', {
                id: id
            }, function(data) {
                try {
                    let property = JSON.parse(data);
                    console.log('Property data received:', property);
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
                    console.error('JSON parse error:', e);
                    Swal.fire('Error', 'Could not load property data.', 'error');
                }
            }).fail(function() {
                Swal.fire('Error', 'Failed to fetch property data.', 'error');
            });
        }

        function deleteProperty(id) {
            console.log('Delete clicked for property id:', id);
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
    </script>
</body>

</html>