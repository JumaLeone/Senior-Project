<?php

class DatabaseHandler
{
    private $serverName = "TITANIUM-VORTEX\SQLEXPRESS";
    private $username = "";
    private $password = "";
    private $database = "php_project";
    private $conn;

    public function __construct()
    {
        $connectionOptions = array(
            "Database" => $this->database,
            "Uid" => $this->username,
            "PWD" => $this->password
        );

        $this->conn = sqlsrv_connect($this->serverName, $connectionOptions);

        if ($this->conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    }
    public function getProperties($search = "", $filter = "")
    {
        $query = "SELECT * FROM properties WHERE id NOT IN (SELECT property_id FROM buyers WHERE status = 'approved')";
        $conditions = [];
        $params = [];

        // Adding search condition if search term is provided
        if (!empty($search)) {
            $conditions[] = "(property_type LIKE ? OR location LIKE ? OR description LIKE ?)";
            $params[] = "%" . $search . "%";
            $params[] = "%" . $search . "%";
            $params[] = "%" . $search . "%";
        }

        // Adding filter condition if a specific filter is provided
        if (!empty($filter)) {
            $conditions[] = "property_type = ?";
            $params[] = $filter;
        }

        // If any conditions are added, append them to the query
        if (!empty($conditions)) {
            $query .= " AND " . implode(" AND ", $conditions);
        }

        // Prepare and execute the query
        $stmt = sqlsrv_query($this->conn, $query, $params);

        // Check for any errors
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        // Fetch the results
        $properties = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $properties[] = $row;  // Add each row to the properties array
        }

        // Free the statement to release resources
        sqlsrv_free_stmt($stmt);

        // Return the properties data
        return $properties;
    }
}


$dbHandler = new DatabaseHandler();

$search = isset($_GET['search']) ? $_GET['search'] : "";
$filter = isset($_GET['filter']) ? $_GET['filter'] : "";
$properties = $dbHandler->getProperties($search, $filter);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="./css/searching-style.css" />
    <title>Housing Offers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>
    <nav class="custom-navbar">
        <h1 class="custom-logo">KeyNest</h1>
        <div class="custom-menu">
            <ul>
                <li><a href="homepage.php">HOME</a></li>
                <li><a href="profile.php">MY PROFILE</a></li>
                <li><a href="searching.php">HOUSING OFFERS</a></li>
                <li><a href="notifications.php">NOTIFICATIONS</a></li>
                <li><a href="#about">ABOUT</a></li>
            </ul>
        </div>

        <div class="menu-toggle" id="menuToggle">
            <span></span>
            <span></span>
            <span></span>
        </div>



        <!-- Mobile Dropdown Menu -->
        <div class="mobile-dropdown" id="mobileDropdown">
            <ul>
                <li><a href="homepage.php">HOME</a></li>
                <li><a href="#" class="active">HOUSING OFFERS</a></li>
                <li><a href="profile.php">MY PROFILE</a></li>
                <li><a href="notifications.php">NOTIFICATIONS</a></li>
                <li><a href="homepage.php#about">ABOUT</a></li>
            </ul>
        </div>
        <a href="logout.php" class="custom-btn" style="text-decoration: none;">LOGOUT</a>
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const mobileDropdown = document.getElementById('mobileDropdown');
            let isMenuOpen = false;

            menuToggle.addEventListener('click', function() {
                isMenuOpen = !isMenuOpen;

                // Toggle hamburger animation
                menuToggle.classList.toggle('active');

                // Toggle dropdown menu
                if (isMenuOpen) {
                    mobileDropdown.style.display = 'block';
                    // Small delay to ensure display:block is applied before animation
                    setTimeout(() => {
                        mobileDropdown.classList.add('show');
                    }, 10);
                } else {
                    mobileDropdown.classList.remove('show');
                    // Hide after animation completes
                    setTimeout(() => {
                        mobileDropdown.style.display = 'none';
                    }, 300);
                }
            });

            // Close menu when clicking outside
            document.addEventListener('click', function(e) {
                if (!menuToggle.contains(e.target) && !mobileDropdown.contains(e.target)) {
                    if (isMenuOpen) {
                        isMenuOpen = false;
                        menuToggle.classList.remove('active');
                        mobileDropdown.classList.remove('show');
                        setTimeout(() => {
                            mobileDropdown.style.display = 'none';
                        }, 300);
                    }
                }
            });

            // Close menu when clicking on a link
            const mobileLinks = mobileDropdown.querySelectorAll('a');
            mobileLinks.forEach(link => {
                link.addEventListener('click', function() {
                    isMenuOpen = false;
                    menuToggle.classList.remove('active');
                    mobileDropdown.classList.remove('show');
                    setTimeout(() => {
                        mobileDropdown.style.display = 'none';
                    }, 300);
                });
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768 && isMenuOpen) {
                    isMenuOpen = false;
                    menuToggle.classList.remove('active');
                    mobileDropdown.classList.remove('show');
                    mobileDropdown.style.display = 'none';
                }
            });
        });
    </script>

    <h2 style="text-align: center; margin: 2rem 0;">Search Housing Options</h2>

    <div class="custom-search-container">
        <form method="GET" action="">
            <input type="text" name="search" placeholder="Search by type, location, or description"
                value="<?= htmlspecialchars($search) ?>" />
            <select name="filter">
                <option value="">Filter by Property Type</option>
                <option value="Apartment" <?= $filter == "Apartment" ? "selected" : "" ?>>Apartment</option>
                <option value="Residential Lot" <?= $filter == "Residential Lot" ? "selected" : "" ?>>Residential Lot
                </option>
                <option value="Condo" <?= $filter == "Condo" ? "selected" : "" ?>>Condo</option>
                <option value="House and Lot" <?= $filter == "House and Lot" ? "selected" : "" ?>>House and Lot</option>
                <option value="Commercial" <?= $filter == "Commercial" ? "selected" : "" ?>>Commercial</option>
            </select>
            <button type="submit" class="custom-btn">Search</button>
        </form>
    </div>

    <div class="custom-properties-grid">
        <?php

        $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
        $filterType = isset($_GET['filter']) ? $_GET['filter'] : '';

        // Create an instance of DatabaseHandler 
        $databaseHandler = new DatabaseHandler();

        // Now that the variables are set, you can call the method
        $properties = $databaseHandler->getProperties($searchTerm, $filterType);

        if (!empty($properties)) {
            foreach ($properties as $row) {
                // Check if the 'photos' field is not empty and assign the correct image path
                $photoPath = !empty($row['photos'])
                    ? 'img/' . htmlspecialchars($row['photos'])
                    : 'img/house' . htmlspecialchars($row['id'] % 10 + 1) . '.jpg';

                echo '<div class="custom-property-card">';
                echo '<div class="custom-property-image-container">';
                echo '<img src="' . $photoPath . '" alt="Property" class="custom-property-image" onerror="this.src=\'img/house.jpg\'">';
                echo '</div>';
                echo '<div class="custom-property-details">';
                echo '<div class="custom-property-type">' . htmlspecialchars($row['property_type']) . '</div>';
                echo '<div class="custom-property-location">' . htmlspecialchars($row['location']) . '</div>';

                // ADD THE DESCRIPTION HERE - with text truncation
                $description = htmlspecialchars($row['description']);
                $truncatedDescription = strlen($description) > 80 ? substr($description, 0, 80) . '...' : $description;
                echo '<div class="custom-property-description">' . $truncatedDescription . '</div>';

                echo '<div class="custom-property-specs">';
                echo '<span>Area: ' . htmlspecialchars($row['area']) . ' sqm</span>';
                echo '<span>Capacity: ' . htmlspecialchars($row['capacity']) . '</span>';
                echo '</div>';
                echo '<div class="custom-property-price">Ksh ' . htmlspecialchars($row['price_range']) . '</div>';
                echo '<div class="custom-property-deposit">*Deposit: Ksh ' . number_format($row['deposit_fee']) . ' <span class="tps-note">per month on TPS</span></div>';


                // Added data-price attribute here
                echo '<button class="custom-buy-now buy-btn" data-id="' . $row['id'] . '" data-price="' . $row['deposit_fee'] . '">Buy Now</button>';

                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<p class="text-center">No properties found</p>';
        }

        ?>
    </div>

    <!-- Modal Section -->
    <div class="modal fade" id="buyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="buyerForm" action="buyer.php" method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Buyer Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="property_id" id="propertyId">
                    <!-- Hidden input for property price -->
                    <input type="hidden" name="property_price" id="propertyPrice" value="0" />

                    <div class="form-group">
                        <label for="fullname">Full Name</label>
                        <input type="text" name="fullname" id="fullname" class="form-control" required />
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" class="form-control" required />
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" name="phone" id="phone" class="form-control" required />
                    </div>

                    <!-- Occupation dropdown -->
                    <div class="form-group">
                        <label for="occupation">Occupation</label>
                        <select name="occupation" id="occupation" class="form-control" required>
                            <option value="" disabled selected>Select your occupation</option>
                            <option value="Engineer">Engineer</option>
                            <option value="Doctor">Doctor</option>
                            <option value="Teacher">Teacher</option>
                            <option value="Lawyer">Lawyer</option>
                            <option value="Business Owner">Business Owner</option>
                            <option value="Civil Servant">Civil Servant</option>
                            <option value="IT Professional">IT Professional</option>
                            <option value="Sales/Marketing">Sales/Marketing</option>
                            <option value="Finance/Banking">Finance/Banking</option>
                            <option value="Healthcare Worker">Healthcare Worker</option>
                            <option value="Student">Student</option>
                            <option value="Retired">Retired</option>
                            <option value="Self Employed">Self Employed</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea name="message" id="message" class="form-control" rows="4"></textarea>
                    </div>
                </div>

                <div class="modal-footer justify-content-between">
                    <div class="payment-options">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="mpesaOption" value="mpesa" checked>
                            <label class="form-check-label" for="mpesaOption">
                                Pay via M-Pesa
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="cashOption" value="cash">
                            <label class="form-check-label" for="cashOption">
                                Pay with Bank
                            </label>
                        </div>
                    </div>
                    <button type="submit" name="submitBuyer" class="btn btn-primary">Proceed to Payment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const buyButtons = document.querySelectorAll('.buy-btn');
            const propertyIdInput = document.getElementById('propertyId');
            const propertyPriceInput = document.getElementById('propertyPrice');
            const buyerForm = document.getElementById('buyerForm');
            const buyModal = new bootstrap.Modal(document.getElementById('buyModal'));

            // Handle Buy Now button clicks
            buyButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const propertyId = this.getAttribute('data-id');
                    const propertyPrice = this.getAttribute('data-price') || 0;

                    propertyIdInput.value = propertyId;
                    propertyPriceInput.value = propertyPrice;

                    buyModal.show();
                });
            });

            // Handle form submission
            // Handle form submission - FIXED to include submit button data
            if (buyerForm) {
                buyerForm.addEventListener('submit', function(e) {
                    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
                    const propertyPrice = document.getElementById('propertyPrice').value;

                    // ADD HIDDEN INPUT FOR SUBMIT BUTTON
                    let submitInput = document.querySelector('input[name="submitBuyer"]');
                    if (!submitInput) {
                        submitInput = document.createElement('input');
                        submitInput.type = 'hidden';
                        submitInput.name = 'submitBuyer';
                        submitInput.value = 'Proceed to Payment';
                        this.appendChild(submitInput);
                    }

                    // Set the correct action based on payment method BEFORE submission
                    if (paymentMethod === 'mpesa' && propertyPrice > 0) {
                        // For M-Pesa payments - redirect to payment processing
                        this.action = 'process_payment.php';
                    } else if (paymentMethod === 'cash' || paymentMethod === 'bank') {
                        // For bank/cash payments - redirect to buyer.php
                        this.action = 'buyer.php';
                    } else {
                        // Default fallback to buyer.php
                        this.action = 'buyer.php';
                    }

                    // Let the form submit naturally
                    // Now the submitBuyer field will be included
                });
            }

            // FIXED: Get menu variables within this scope or remove this section
            const menuToggle = document.getElementById('menuToggle');
            const mobileDropdown = document.getElementById('mobileDropdown');
            let isMenuOpen = false;

            // Close menu when clicking outside (if menu elements exist)
            if (menuToggle && mobileDropdown) {
                document.addEventListener('click', function(e) {
                    if (!menuToggle.contains(e.target) && !mobileDropdown.contains(e.target)) {
                        if (isMenuOpen) {
                            isMenuOpen = false;
                            menuToggle.classList.remove('active');
                            mobileDropdown.classList.remove('show');
                            setTimeout(() => {
                                mobileDropdown.style.display = 'none';
                            }, 300);
                        }
                    }
                });
            }
        });
    </script>


    <footer class="footer">
        <div class="footer-bottom">
            <p>&copy; 2025 KeyNest. All rights reserved.</p>
        </div>
    </footer>
</body>

</html>