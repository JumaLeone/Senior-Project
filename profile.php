<?php
session_start();
include('connect.php');

$userInfo = null;

if (isset($_SESSION['email'])) {
  $email = $_SESSION['email'];

  // Get user info
  $query = "SELECT username, email FROM users WHERE email = ?";
  $params = array($email);
  $stmtUserInfo = sqlsrv_query($conn, $query, $params);

  if ($stmtUserInfo) {
    $userInfo = sqlsrv_fetch_array($stmtUserInfo, SQLSRV_FETCH_ASSOC);
    if ($userInfo) {
      $userInfo['username'] = htmlspecialchars($userInfo['username']);
      $userInfo['email'] = htmlspecialchars($userInfo['email']);
    }
    sqlsrv_free_stmt($stmtUserInfo);
  }
}

// Fetch notifications
$notifications = [];
if ($userInfo) {
  $query = "SELECT * FROM notifications WHERE user_email = ? ORDER BY date_created DESC";
  $params = array($userInfo['email']);
  $stmtNotifications = sqlsrv_query($conn, $query, $params);

  if ($stmtNotifications) {
    while ($row = sqlsrv_fetch_array($stmtNotifications, SQLSRV_FETCH_ASSOC)) {
      $notifications[] = $row;
    }
    sqlsrv_free_stmt($stmtNotifications);
  }
}

// Fetch purchased properties
$purchasedProperties = [];
if ($userInfo) {
  $query = "SELECT id FROM users WHERE email = ?";
  $params = array($userInfo['email']);
  $stmtUserId = sqlsrv_query($conn, $query, $params);

  if ($stmtUserId) {
    $row = sqlsrv_fetch_array($stmtUserId, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmtUserId);

    if ($row) {
      $userId = $row['id'];

      $query = "
                SELECT p.property_type, p.price_range, p.location, p.area, p.capacity, p.description, b.status
                FROM properties p
                JOIN buyers b ON p.id = b.property_id
                WHERE b.user_id = ?";
      $params = array($userId);
      $stmtProperties = sqlsrv_query($conn, $query, $params);

      if ($stmtProperties) {
        while ($row = sqlsrv_fetch_array($stmtProperties, SQLSRV_FETCH_ASSOC)) {
          $purchasedProperties[] = array_map('htmlspecialchars', $row);
        }
        sqlsrv_free_stmt($stmtProperties);
      }
    }
  }
}

// Fetch average assignment duration (in days)
$avgTimeQuery = "
  SELECT AVG(DATEDIFF(DAY, b.application_date, b.assigned_date)) AS avg_days
  FROM buyers b
  WHERE b.user_id = ? AND b.status = 'approved' AND b.application_date IS NOT NULL AND b.assigned_date IS NOT NULL
";
$avgTimeStmt = sqlsrv_query($conn, $avgTimeQuery, [$userId]);
$avgAssignmentTime = null;

if ($avgTimeStmt && $row = sqlsrv_fetch_array($avgTimeStmt, SQLSRV_FETCH_ASSOC)) {
  $avgAssignmentTime = round($row['avg_days']);
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="./css/profile.css">
  <title>My Profile - KeyNest: Housing Allocation System</title>
</head>

<body>
  <!-- Navbar -->
  <nav class="navbar">
    <a href="homepage.php" class="logo">KeyNest</a>

    <!-- Desktop Menu -->
    <div class="menu">
      <ul>
        <li><a href="homepage.php">HOME</a></li>
        <li><a href="#" class="active">MY PROFILE</a></li>
        <li><a href="searching.php">HOUSING OFFERS</a></li>
        <li><a href="notifications.php">NOTIFICATIONS</a></li>
        <li><a href="homepage.php#about">ABOUT</a></li>
      </ul>
    </div>

    <!-- Mobile Menu Toggle -->
    <div class="menu-toggle" id="menuToggle">
      <span></span>
      <span></span>
      <span></span>
    </div>

    <a href="logout.php" class="btn">LOGOUT</a>

    <!-- Mobile Dropdown Menu -->
    <div class="mobile-dropdown" id="mobileDropdown">
      <ul>
        <li><a href="homepage.php">HOME</a></li>
        <li><a href="#" class="active">MY PROFILE</a></li>
        <li><a href="searching.php">HOUSING OFFERS</a></li>
        <li><a href="notifications.php">NOTIFICATIONS</a></li>
        <li><a href="homepage.php#about">ABOUT</a></li>
      </ul>
    </div>
  </nav>

  <!-- Your page content goes here -->
  <div class="content">
    <!-- Add your profile content here -->
  </div>

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
  <section class="profile">
    <div class="profile-content">
      <h1>My Profile</h1>
      <div class="profile-info">
        <div class="profile-image-wrapper">


          <!-- Pencil icon overlay -->
          <label for="upload" class="edit-icon">✏️</label>

          <!-- Hidden file input -->
          <input type="file" id="upload" name="profile_image" accept="image/*" style="display: none;">
        </div>


        <div class="profile-details">
          <h2>Personal Information</h2>
          <?php if ($userInfo): ?>
            <div class="info-group">
              <label>Username:</label>
              <p><?= $userInfo['username'] ?></p>
            </div>
            <div class="info-group">
              <label>Email:</label>
              <p><?= $userInfo['email'] ?></p>
            </div>
          <?php else: ?>
            <p>User information not found. Please log in.</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="property-section">
        <div style="text-align: right; margin-bottom: 10px;">


          <button class="btn btn-danger" onclick="generatePDF()">
            <i class="fas fa-file-pdf"></i> Download PDF Report
          </button>
        </div>

        <h2>Purchased Properties</h2>
        <?php if (!empty($purchasedProperties)): ?>
          <?php foreach ($purchasedProperties as $property): ?>
            <div class="property">
              <p><strong>Type:</strong> <?= $property['property_type'] ?></p>
              <p><strong>Price Range:</strong> <?= $property['price_range'] ?></p>
              <p><strong>Location:</strong> <?= $property['location'] ?></p>
              <p><strong>Area:</strong> <?= $property['area'] ?> sq ft</p>
              <p><strong>Capacity:</strong> <?= $property['capacity'] ?></p>
              <p><strong>Description:</strong> <?= $property['description'] ?></p>
              <p><strong>Status:</strong>
                <?php if ($property['status'] == 'pending'): ?>
                  <span style="color: orange;">Pending</span>
                <?php elseif ($property['status'] == 'approved'): ?>
                  <span style="color: green;">Approved</span>
                <?php elseif ($property['status'] == 'rejected'): ?>
                  <span style="color: red;">Rejected</span>
                <?php endif; ?>
              </p>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No purchased properties found.</p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Preview Image Script -->
  <script>
    const uploadInput = document.getElementById('upload');
    const profilePic = document.getElementById('profile-pic');

    uploadInput.addEventListener('change', function() {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function() {
          profilePic.src = reader.result;
        };
        reader.readAsDataURL(file);
      }
    });
  </script>

  <style>
    .profile-image-wrapper {
      position: relative;
      width: 150px;
      height: 150px;
      border-radius: 50%;
      overflow: hidden;
      background-color: #f1f1f1;
      /* light gray background */
      display: flex;
      align-items: center;
      justify-content: center;
    }

    #profile-pic {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 50%;
    }

    .edit-icon {
      position: absolute;
      bottom: 10px;
      right: 10px;
      background-color: white;
      border-radius: 50%;
      padding: 5px;
      cursor: pointer;
      font-size: 18px;
      box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
    }

    .edit-icon:hover {
      background-color: #e0e0e0;
    }
  </style>




  <!-- JavaScript to Preview Uploaded Image -->
  <script>
    const uploadInput = document.getElementById('upload');
    const profilePic = document.getElementById('profile-pic');

    uploadInput.addEventListener('change', function() {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function() {
          profilePic.src = reader.result;
        };
        reader.readAsDataURL(file);
      }
    });

    function deleteNotification(id) {
      if (confirm('Delete this notification?')) {
        fetch('deleteNotification.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + id
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              location.reload();
            }
          });
      }
    }
  </script>

  <footer class="footer">
    <div class="footer-bottom">
      <p>&copy; 2025 KeyNest. All rights reserved.</p>
    </div>
  </footer>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
  <script>
    async function generatePDF() {
      const {
        jsPDF
      } = window.jspdf;
      const doc = new jsPDF();

      // Title
      doc.setFontSize(18);
      doc.text("Purchased Properties Report", 14, 20);

      // User Info
      doc.setFontSize(12);
      doc.text("Username: <?= $userInfo['username'] ?>", 14, 30);
      doc.text("Email: <?= $userInfo['email'] ?>", 14, 37);

      // Table Header
      const tableHead = [
        ["Type", "Price Range", "Location", "Area", "Capacity", "Status"]
      ];

      // Table Data
      const tableData = [
        <?php foreach ($purchasedProperties as $property): ?>[
            "<?= $property['property_type'] ?>",
            "<?= $property['price_range'] ?>",
            "<?= $property['location'] ?>",
            "<?= $property['area'] ?> sq ft",
            "<?= $property['capacity'] ?>",
            "<?= ucfirst($property['status']) ?>"
          ],
        <?php endforeach; ?>
      ];

      // Table
      doc.autoTable({
        startY: 45,
        head: tableHead,
        body: tableData,
        styles: {
          fontSize: 10,
          cellPadding: 3,
        },
        headStyles: {
          fillColor: [220, 53, 69] // Bootstrap red
        }
      });

      // Save the PDF
      doc.save("Purchased_Properties_Report.pdf");
    }
  </script>
  <style>
    .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
      font-weight: 500;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.2s ease-in-out;
    }

    .btn-danger {
      background-color: #dc3545;
      color: white;
    }

    .btn-danger:hover {
      background-color: #a71d2a;
    }
  </style>




</body>

</html>

<script>

</script>